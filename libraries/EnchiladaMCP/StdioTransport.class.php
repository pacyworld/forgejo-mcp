<?php

namespace EnchiladaMCP;

use Enchilada\Comal\ReactorFactory;
use Enchilada\Comal\ReactorInterface;

/* Enchilada Framework 3.0
 * MCP Stdio Transport
 *
 * Event-driven transport for MCP servers speaking JSON-RPC over
 * stdin/stdout. Built on the Enchilada Comal reactor, so multiplexing
 * uses kqueue (via libev/libevent) rather than a hand-rolled
 * stream_select() loop.
 *
 * Requires Comal vendored alongside this library:
 *   libraries/Enchilada/Comal/
 *
 * Two I/O modes:
 *
 *   reactor  - stdin is a reactor read watcher; every request is
 *              dispatched inside a Fiber. When a tool suspends at an
 *              await point (Liveness::await/sleep) the loop keeps
 *              running, so `ping` is answered and progress notifications
 *              keep firing while the call is still in flight. Default on
 *              POSIX, where pipes are pollable.
 *
 *   blocking - plain blocking line reads on stdin. Default on Windows,
 *              where no PHP-visible mechanism can poll an anonymous
 *              pipe. Measured on Windows 11 / PHP 8.4.25:
 *                - stream_select() on a stdin pipe returns in 0ms and
 *                  always reports it readable, whether or not data is
 *                  pending (php-src #64770 / GH-16889),
 *                - stream_set_blocking($pipe, false) is a no-op, so the
 *                  read that follows blocks for an unbounded time,
 *                - and ext-ev / ext-event do not exist for Windows
 *                  (they only accept sockets in any case).
 *              Requests are handled synchronously; liveness during a
 *              call comes from progress notifications emitted via
 *              Liveness::tick()/await(), not from pings.
 *
 * Mode 'auto' (the default) picks by platform.
 *
 * Tool calls are serialised: while one is in flight, further requests
 * are queued and only liveness traffic (ping, notifications/cancelled)
 * is answered out of band. Tools therefore never run concurrently with
 * each other, matching the previous single-threaded contract.
 *
 * Usage:
 *   $server = new McpServer('my-server', '1.0.0');
 *   $transport = new StdioTransport($server);
 *   $transport->run();
 *
 * Software License Agreement (BSD License)
 * 
 * Copyright (c) 2026, The Daniel Morante Company, Inc.
 * All rights reserved.
 */

class StdioTransport implements LivenessSink
{
	/** @var McpServer */
	private McpServer $server;

	/** @var ReactorInterface|null Event loop (created at run() unless injected) */
	private ?ReactorInterface $reactor = null;

	/** @var array<string,array{stream:resource,callback:callable,watcher:?string}> */
	private array $additionalStreams = [];

	/** @var bool */
	private bool $running = false;

	/** @var callable|null */
	private $logger = null;

	/** @var string Requested I/O mode: 'auto', 'reactor' or 'blocking' */
	private string $ioMode = 'auto';

	/** @var string|null I/O mode actually in use once run() started */
	private ?string $resolvedMode = null;

	/** @var string Partial-line carry buffer for stdin */
	private string $buffer = '';

	/** @var array<int,array<string,mixed>> Requests queued behind the in-flight call */
	private array $pending = [];

	/** @var bool Whether a request is currently being dispatched */
	private bool $inFlight = false;

	/** @var string|null Reactor timer emitting progress for the in-flight call */
	private ?string $progressTimer = null;

	/** @var string|null Reactor watcher for stdin */
	private ?string $stdinWatcher = null;

	/** @var int Seconds of wire silence after which a keepalive notification
	 *          is sent (0 = disabled) */
	private int $keepAliveInterval = 0;

	/** @var string|null Reactor timer for keepalives */
	private ?string $keepAliveTimer = null;

	/** @var float Last time any wire traffic occurred */
	private float $lastWireAt = 0.0;

	/** @var bool Whether the blocking-mode multiplexing warning was logged */
	private bool $blockingModeWarned = false;

	/**
	 * Methods answered immediately even while a tool call is in flight.
	 * Everything else is queued so tools stay serialised.
	 */
	private const LIVENESS_METHODS = ['ping', 'notifications/cancelled'];

	/**
	 * Create a new stdio transport.
	 *
	 * @param McpServer $server Protocol handler
	 */
	public function __construct(McpServer $server)
	{
		$this->server = $server;
		$server->setLivenessSink($this);
	}

	/**
	 * Set a logging callback.
	 *
	 * @param callable $logger Function accepting a string message
	 */
	public function setLogger(callable $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * Supply a preconfigured reactor (e.g. one shared with an embedded
	 * HTTP listener, or a specific Comal backend). When omitted, run()
	 * creates one via ReactorFactory auto-detection.
	 */
	public function setReactor(ReactorInterface $reactor): void
	{
		$this->reactor = $reactor;
	}

	/**
	 * The reactor in use, once run() has started (or one injected via
	 * setReactor()).
	 */
	public function reactor(): ?ReactorInterface
	{
		return $this->reactor;
	}

	/**
	 * Force an I/O mode: 'auto' (platform default), 'reactor' or
	 * 'blocking'. Useful to opt a Windows host into reactor mode if its
	 * stdio is known to be socket-backed, or to force blocking mode for
	 * debugging.
	 */
	public function setIoMode(string $mode): void
	{
		if (!in_array($mode, ['auto', 'reactor', 'blocking'], true)) {
			throw new \InvalidArgumentException("I/O mode must be 'auto', 'reactor' or 'blocking'");
		}
		$this->ioMode = $mode;
	}

	/**
	 * The I/O mode in effect ('reactor' or 'blocking'), null before run().
	 */
	public function resolvedIoMode(): ?string
	{
		return $this->resolvedMode;
	}

	/**
	 * Send a keepalive notification after $seconds of complete wire
	 * silence. 0 disables (default). Reactor mode only.
	 */
	public function setKeepAliveInterval(int $seconds): void
	{
		$this->keepAliveInterval = max(0, $seconds);
	}

	/**
	 * Register an additional stream to monitor (e.g. an OAuth callback
	 * listener). When the stream becomes readable, $callback is invoked.
	 *
	 * In blocking I/O mode stdin cannot be multiplexed, so registered
	 * streams are only serviced between incoming stdin lines.
	 *
	 * @param resource $stream   Stream resource (e.g., TCP socket)
	 * @param callable $callback Called when stream is readable: function($stream): void
	 */
	public function addStream($stream, callable $callback): void
	{
		if (!is_resource($stream)) {
			throw new \InvalidArgumentException('addStream() requires an open stream resource');
		}

		$key = (string)(int)$stream;
		$this->additionalStreams[$key] = [
			'stream' => $stream,
			'callback' => $callback,
			'watcher' => null,
		];

		if ($this->resolvedMode === 'reactor' && $this->reactor !== null) {
			$this->watchAdditionalStream($key);
		} elseif ($this->resolvedMode === 'blocking' && !$this->blockingModeWarned) {
			$this->blockingModeWarned = true;
			$this->log('WARNING: additional stream registered in blocking I/O mode; it is only serviced between incoming stdin lines (stdio cannot be multiplexed on this platform)');
		}
	}

	/**
	 * Remove a previously registered stream.
	 *
	 * @param resource $stream Stream resource to remove
	 */
	public function removeStream($stream): void
	{
		$key = (string)(int)$stream;
		$this->unwatchAdditionalStream($key);
		unset($this->additionalStreams[$key]);
	}

	/**
	 * Enter the main event loop.
	 *
	 * Reads JSON-RPC messages from stdin, dispatches them to the server,
	 * writes responses to stdout. Exits when stdin reaches EOF (client
	 * disconnected) or stop() is called.
	 */
	public function run(): void
	{
		// stdout is a framed JSON-RPC channel. PHP's CLI default of
		// printing errors to STDOUT (as shipped by several distro
		// php.ini files) would corrupt the protocol stream with stray
		// text whenever a warning or fatal escapes, so pin error display
		// to stderr for the process.
		@ini_set('display_errors', 'stderr');

		$this->running = true;
		$this->lastWireAt = microtime(true);

		$mode = $this->ioMode;
		if ($mode === 'auto') {
			// On Windows an anonymous pipe (which is what an MCP host
			// hands us as stdin) cannot be polled from PHP at all:
			// stream_select() misreports pipes and libev/libevent only
			// accept sockets. Blocking reads are the only correct choice.
			$mode = (\PHP_OS_FAMILY === 'Windows') ? 'blocking' : 'reactor';
		}
		$this->resolvedMode = $mode;

		if ($mode === 'reactor') {
			// Reactor mode needs Comal. Blocking mode deliberately does
			// not, so a host that cannot poll its stdio anyway (Windows)
			// stays servable even where Comal is not vendored.
			if ($this->reactor === null) {
				if (!class_exists(ReactorFactory::class)) {
					throw new \RuntimeException(
						'EnchiladaMCP\\StdioTransport reactor mode requires the Enchilada Comal reactor. '
						. 'Vendor it into libraries/Enchilada/Comal/ (git.morante.net/Enchilada/Comal), '
						. "or call setIoMode('blocking')."
					);
				}
				$this->reactor = ReactorFactory::create();
			}
			$backend = class_exists(ReactorFactory::class) ? ReactorFactory::detectBackend() : 'unknown';
			$this->log("Transport started (reactor I/O, Comal backend: {$backend})");
			if (\PHP_OS_FAMILY === 'Windows') {
				// Measured on Windows 11 / PHP 8.4.25: stream_select() on a
				// stdin pipe returns in 0ms always claiming it is readable,
				// and stream_set_blocking(false) is a no-op for pipes, so
				// the following read blocks for an unbounded time (9s in
				// testing, i.e. until the peer wrote or closed). Timers
				// cannot fire meanwhile, so progress notifications stop.
				// Reactor mode here is for diagnostics only.
				$this->log('NOTE reactor I/O on Windows is unsafe (stdin pipes are not pollable there); blocking mode is the supported choice');
			}
			$this->runReactor();
		} else {
			$this->log('Transport started (blocking I/O)');
			$this->runBlocking();
		}

		$this->log('Transport stopped');
	}

	/**
	 * Send a JSON-RPC notification to the client (no id, no response expected).
	 *
	 * @param string              $method Notification method name
	 * @param array<string,mixed> $params Notification parameters
	 */
	public function sendNotification(string $method, array $params = []): void
	{
		$msg = ['jsonrpc' => '2.0', 'method' => $method];
		if (!empty($params)) {
			$msg['params'] = $params;
		}
		$output = json_encode($msg, JSON_UNESCAPED_SLASHES);
		if ($output === false) {
			return;
		}
		$this->log('Notification (' . Logger::digest($output) . '): ' . Logger::truncate($output));
		$this->writeLine($output);
	}

	/**
	 * Send a log message notification to the client.
	 *
	 * @param string $level   Log level: debug, info, notice, warning, error, critical, alert, emergency
	 * @param string $message Log message text
	 * @param string $logger  Optional logger name
	 */
	public function sendLogMessage(string $level, string $message, string $logger = ''): void
	{
		$params = ['level' => $level, 'message' => $message];
		if (!empty($logger)) {
			$params['logger'] = $logger;
		}
		$this->sendNotification('notifications/message', $params);
	}

	/**
	 * Stop the event loop.
	 */
	public function stop(): void
	{
		$this->running = false;
		if ($this->reactor !== null && $this->resolvedMode === 'reactor') {
			$this->reactor->stop();
		}
	}

	/**
	 * Check if the transport is currently running.
	 *
	 * @return bool
	 */
	public function isRunning(): bool
	{
		return $this->running;
	}

	/**
	 * Reactor-driven loop: stdin is a read watcher and requests run in
	 * Fibers, so a suspended tool call never blocks protocol traffic.
	 */
	private function runReactor(): void
	{
		stream_set_blocking(STDIN, false);
		Liveness::setReactor($this->reactor);

		$this->stdinWatcher = $this->reactor->onReadable(STDIN, function ($stream) {
			$this->onStdinReadable($stream);
		});

		foreach (array_keys($this->additionalStreams) as $key) {
			$this->watchAdditionalStream($key);
		}

		if ($this->keepAliveInterval > 0) {
			$this->keepAliveTimer = $this->reactor->repeat(1.0, function () {
				$this->maybeKeepAlive();
			});
		}

		try {
			$this->reactor->run();
		} finally {
			Liveness::setReactor(null);
		}
	}

	/**
	 * Blocking line-read loop (Windows default). Requests are handled
	 * synchronously; Liveness::tick()/await() still emit progress from
	 * inside long tool calls.
	 */
	private function runBlocking(): void
	{
		stream_set_blocking(STDIN, true);

		// No loop is running while we block in fgets(), so awaits must
		// use their synchronous fallback rather than suspending.
		Liveness::setReactor(null);

		while ($this->running) {
			if (function_exists('pcntl_signal_dispatch')) {
				pcntl_signal_dispatch();
			}

			$this->serviceAdditionalStreams();

			$line = fgets(STDIN);
			if ($line === false) {
				if (feof(STDIN)) {
					$this->log('stdin EOF, stopping');
					break;
				}
				continue;
			}

			$this->noteWire();
			$line = trim($line);
			if ($line === '') {
				continue;
			}

			$request = $this->decode($line);
			if ($request === null) {
				continue;
			}

			$this->startProgressTracking();
			try {
				$this->dispatch($request);
			} finally {
				$this->stopProgressTracking();
			}

			// Anything that arrived mid-call was not queued in this mode
			// (nothing could read it), so there is no backlog to drain.
		}
	}

	/**
	 * Reactor callback: stdin has data (or reached EOF).
	 *
	 * @param resource $stream
	 */
	private function onStdinReadable($stream): void
	{
		$chunk = fread($stream, 65536);
		if ($chunk === false || ($chunk === '' && feof($stream))) {
			$this->log('stdin EOF, stopping');
			$this->stop();
			return;
		}
		if ($chunk === '') {
			return;
		}

		$this->noteWire();
		$this->buffer .= $chunk;

		while (($pos = strpos($this->buffer, "\n")) !== false) {
			$line = trim(substr($this->buffer, 0, $pos));
			$this->buffer = substr($this->buffer, $pos + 1);
			if ($line === '') {
				continue;
			}
			$request = $this->decode($line);
			if ($request !== null) {
				$this->intake($request);
			}
		}
	}

	/**
	 * Route a decoded request: liveness traffic is answered immediately,
	 * everything else waits for the in-flight call to finish.
	 *
	 * @param array<string,mixed> $request
	 */
	private function intake(array $request): void
	{
		$method = $request['method'] ?? '';

		if ($this->inFlight && !in_array($method, self::LIVENESS_METHODS, true)) {
			$this->pending[] = $request;
			$this->log("Queued {$method} behind in-flight call (" . count($this->pending) . ' pending)');
			return;
		}

		if ($this->inFlight) {
			// Liveness traffic during a suspended call: answer inline.
			// McpServer keeps the in-flight call's progress state intact
			// for these methods.
			$this->log("Answering {$method} during in-flight call");
			$this->dispatch($request);
			return;
		}

		$this->dispatchAsync($request);
	}

	/**
	 * Dispatch a request inside a Fiber so that awaits inside tool code
	 * suspend instead of blocking the loop.
	 *
	 * @param array<string,mixed> $request
	 */
	private function dispatchAsync(array $request): void
	{
		$this->inFlight = true;
		$this->startProgressTracking();

		$fiber = new \Fiber(function () use ($request) {
			try {
				$this->dispatch($request);
			} finally {
				$this->stopProgressTracking();
				$this->inFlight = false;
				// Drain on a fresh loop turn: draining here would nest
				// dispatches inside this fiber's stack.
				if ($this->running && !empty($this->pending) && $this->reactor !== null) {
					$this->reactor->delay(0.0, function () {
						$this->drainPending();
					});
				}
			}
		});

		try {
			$fiber->start();
		} catch (\Throwable $e) {
			// A throw that escaped dispatch()'s own guard; the finally
			// block above has already cleared the in-flight state.
			$this->log('Unhandled exception in dispatch fiber: ' . $e->getMessage());
		}
	}

	/**
	 * Start the next queued request, if any.
	 */
	private function drainPending(): void
	{
		if ($this->inFlight || empty($this->pending) || !$this->running) {
			return;
		}
		$request = array_shift($this->pending);
		$this->dispatchAsync($request);
	}

	/**
	 * Hand a request to the protocol layer and write its response.
	 *
	 * @param array<string,mixed> $request
	 */
	private function dispatch(array $request): void
	{
		// Defense-in-depth: McpServer::handleRequest() already catches
		// \Throwable internally and converts failures to JSON-RPC/tool
		// error responses. This outer guard exists so that a future
		// change to McpServer, or any error occurring outside that
		// guarded region (e.g. response serialization), can never take
		// down the whole transport.
		try {
			$response = $this->server->handleRequest($request);
		} catch (\Throwable $e) {
			$this->log('Unhandled exception in handleRequest: ' . $e->getMessage());
			$response = [
				'jsonrpc' => '2.0',
				'id' => $request['id'] ?? null,
				'error' => [
					'code' => -32603,
					'message' => 'Internal error: ' . $e->getMessage(),
				],
			];
		}

		if (empty($response)) {
			return;
		}

		$output = json_encode($response, JSON_UNESCAPED_SLASHES);
		if ($output === false) {
			$this->log('Failed to encode response: ' . json_last_error_msg());
			return;
		}
		$this->log('Sending (' . Logger::digest($output) . '): ' . Logger::truncate($output));
		$this->writeLine($output);
	}

	/**
	 * Decode one JSON-RPC line.
	 *
	 * @return array<string,mixed>|null null when the line is not usable
	 */
	private function decode(string $line): ?array
	{
		$this->log('Received (' . Logger::digest($line) . '): ' . Logger::truncate($line));

		$request = json_decode($line, true);
		if (!is_array($request)) {
			$this->log('Invalid JSON received (' . json_last_error_msg() . ', ' . Logger::digest($line) . ')');
			return null;
		}
		return $request;
	}

	/**
	 * Begin emitting progress notifications for the request now starting.
	 *
	 * In reactor mode a timer drives them, so progress flows even when
	 * the tool never yields explicitly but does suspend at an await.
	 */
	private function startProgressTracking(): void
	{
		if ($this->resolvedMode !== 'reactor' || $this->reactor === null) {
			return;
		}
		$this->progressTimer = $this->reactor->repeat(1.0, function () {
			$this->server->tick();
		});
	}

	/**
	 * Stop the progress timer for the finished request.
	 */
	private function stopProgressTracking(): void
	{
		if ($this->progressTimer !== null && $this->reactor !== null) {
			$this->reactor->cancel($this->progressTimer);
		}
		$this->progressTimer = null;
	}

	/**
	 * Register a reactor read watcher for an additional stream.
	 */
	private function watchAdditionalStream(string $key): void
	{
		if (!isset($this->additionalStreams[$key]) || $this->reactor === null) {
			return;
		}
		$entry = $this->additionalStreams[$key];
		if ($entry['watcher'] !== null) {
			return;
		}
		if (!is_resource($entry['stream'])) {
			$this->log("Additional stream #{$key} was closed before registration; dropping it");
			unset($this->additionalStreams[$key]);
			return;
		}
		$this->additionalStreams[$key]['watcher'] = $this->reactor->onReadable(
			$entry['stream'],
			function ($stream) use ($key) {
				$entry = $this->additionalStreams[$key] ?? null;
				if ($entry === null) {
					return;
				}
				try {
					($entry['callback'])($stream);
				} catch (\Throwable $e) {
					$this->log("Additional stream #{$key} callback threw: " . $e->getMessage());
				}
			}
		);
	}

	/**
	 * Cancel the reactor watcher for an additional stream.
	 */
	private function unwatchAdditionalStream(string $key): void
	{
		$watcher = $this->additionalStreams[$key]['watcher'] ?? null;
		if ($watcher !== null && $this->reactor !== null) {
			$this->reactor->cancel($watcher);
			$this->additionalStreams[$key]['watcher'] = null;
		}
	}

	/**
	 * Blocking-mode helper: service any additional stream that already
	 * has data, without waiting.
	 *
	 * Closed streams are dropped rather than passed to select: PHP 8
	 * throws a TypeError on invalid stream resources, and '@' does not
	 * suppress it, so a stale OAuth socket would otherwise take the
	 * transport down.
	 */
	private function serviceAdditionalStreams(): void
	{
		if (empty($this->additionalStreams)) {
			return;
		}

		$read = [];
		foreach ($this->additionalStreams as $key => $entry) {
			if (!is_resource($entry['stream'])) {
				$this->log("Additional stream #{$key} was closed or invalidated; unregistering it");
				unset($this->additionalStreams[$key]);
				continue;
			}
			$read[] = $entry['stream'];
		}
		if (empty($read)) {
			return;
		}

		$write = $except = null;
		try {
			$changed = @stream_select($read, $write, $except, 0);
		} catch (\Throwable $e) {
			$this->log('stream_select threw while polling additional streams: ' . $e->getMessage());
			return;
		}
		if ($changed === false || $changed === 0) {
			return;
		}

		foreach ($this->additionalStreams as $key => $entry) {
			if (in_array($entry['stream'], $read, true)) {
				try {
					($entry['callback'])($entry['stream']);
				} catch (\Throwable $e) {
					$this->log("Additional stream #{$key} callback threw: " . $e->getMessage());
				}
			}
		}
	}

	/**
	 * Emit a keepalive notification after the configured silence window.
	 */
	private function maybeKeepAlive(): void
	{
		if ($this->keepAliveInterval <= 0) {
			return;
		}
		if ((microtime(true) - $this->lastWireAt) >= $this->keepAliveInterval) {
			$this->sendLogMessage('debug', 'keepalive');
		}
	}

	/**
	 * Write one protocol line to stdout, handling partial writes.
	 *
	 * @param  string $output Line payload (newline is appended)
	 * @return bool           false when stdout is dead (loop is stopped)
	 */
	private function writeLine(string $output): bool
	{
		$data = $output . "\n";
		$len = strlen($data);
		$written = 0;
		while ($written < $len) {
			$n = @fwrite(STDOUT, substr($data, $written));
			if ($n === false || $n === 0) {
				$this->log('stdout write failed (pipe broken?), stopping');
				$this->stop();
				return false;
			}
			$written += $n;
		}
		@fflush(STDOUT);
		$this->noteWire();
		return true;
	}

	/**
	 * Record wire activity for keepalive accounting.
	 */
	private function noteWire(): void
	{
		$this->lastWireAt = microtime(true);
	}

	/**
	 * Log a message via the configured logger.
	 *
	 * @param string $message
	 */
	private function log(string $message): void
	{
		if ($this->logger) {
			try {
				($this->logger)($message);
			} catch (\Throwable $e) {
				// Logging must never break the transport
			}
		}
	}
}
