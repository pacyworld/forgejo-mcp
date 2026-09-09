<?php
/**
 * StdioProbe — minimal MCP client for transport tests
 *
 * Spawns an MCP stdio server as a child process and drives JSON-RPC over
 * its pipes, mimicking how an agent host behaves: it applies a per-request
 * deadline and records exactly when each response and notification arrives.
 * That timing is the whole point — the failures these tests guard against
 * are "the reply eventually came, but far too late for the host".
 *
 * Portability note — how the child's stdout is captured:
 *
 *   POSIX    A real pipe, read non-blocking. This is exactly what an MCP
 *            host gives a server, so the production path is under test.
 *
 *   Windows  A temporary file. stream_set_blocking(..., false) is a no-op
 *            for proc_open() pipes on Windows, so a pipe read blocks
 *            until data arrives — the probe itself would hang the moment
 *            the server went idle, which is indistinguishable from the
 *            server hanging. Reading a growing file never blocks and
 *            still preserves arrival timing (the server fflush()es every
 *            message). The child's *stdin* stays a real pipe in both
 *            cases, which is the side whose pollability is in question.
 *
 * @package    ForgejoMCP
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

class StdioProbe
{
	/** @var resource|null */
	private $proc = null;

	/** @var array<int,resource> */
	private array $pipes = [];

	private string $buffer = '';

	/** @var array<string,array{msg:array<string,mixed>,at:float}> Responses by id */
	private array $responses = [];

	/** @var array<int,array{method:string,params:array<string,mixed>,at:float}> */
	private array $notifications = [];

	private float $start;

	private string $stderrPath;

	/** @var string|null Windows: file backing the child's stdout */
	private ?string $stdoutPath = null;

	/** @var resource|null Windows: read handle on that file */
	private $stdoutReader = null;

	/** @var bool Whether stderr is an undrained pipe rather than a file */
	private bool $stderrIsPipe = false;

	/**
	 * @param string[]    $args        Arguments appended to the PHP invocation
	 * @param string|null $stderrPath  Where to collect the child's stderr
	 * @param bool        $stderrPipe  Give the child a stderr PIPE and never
	 *                                 read it, reproducing a host that
	 *                                 captures stderr but does not drain it.
	 *                                 A server that writes unbounded
	 *                                 diagnostics to stderr will wedge once
	 *                                 the buffer fills, which presents as a
	 *                                 startup hang. Collecting stderr into a
	 *                                 file (the default) can never reproduce
	 *                                 that, because files do not block.
	 */
	public function __construct(string $script, array $args = [], ?string $stderrPath = null, bool $stderrPipe = false)
	{
		$this->stderrIsPipe = $stderrPipe;
		$this->start = microtime(true);
		$this->stderrPath = $stderrPath ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stdio-probe-' . getmypid() . '.err';

		$cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
		foreach ($args as $arg) {
			$cmd .= ' ' . escapeshellarg($arg);
		}

		$onWindows = (PHP_OS_FAMILY === 'Windows');
		if ($onWindows) {
			$this->stdoutPath = $this->stderrPath . '.out';
			@unlink($this->stdoutPath);
			touch($this->stdoutPath);
		}

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => $onWindows ? ['file', $this->stdoutPath, 'a'] : ['pipe', 'w'],
			2 => $stderrPipe ? ['pipe', 'w'] : ['file', $this->stderrPath, 'a'],
		];

		// bypass_shell keeps Windows from wrapping the child in cmd.exe,
		// which would otherwise sit between us and the server's pipes.
		$options = ['bypass_shell' => true];

		$this->proc = proc_open($cmd, $descriptors, $this->pipes, null, null, $options);
		if (!is_resource($this->proc)) {
			throw new RuntimeException("Failed to spawn: {$cmd}");
		}

		if ($this->stdoutPath !== null) {
			$this->stdoutReader = fopen($this->stdoutPath, 'rb');
		} else {
			stream_set_blocking($this->pipes[1], false);
		}
	}

	/** Seconds since the probe started, rounded for readable logs. */
	public function elapsed(): float
	{
		return round(microtime(true) - $this->start, 2);
	}

	/**
	 * Write a JSON-RPC message to the server.
	 *
	 * @param array<string,mixed> $msg
	 */
	public function send(array $msg): void
	{
		fwrite($this->pipes[0], json_encode($msg) . "\n");
		fflush($this->pipes[0]);
	}

	/**
	 * Read from the server for $seconds, collecting everything that arrives.
	 */
	public function pump(float $seconds): void
	{
		$until = microtime(true) + $seconds;
		do {
			$this->drain();
			if (microtime(true) >= $until) {
				break;
			}
			usleep(5000);
		} while (true);
	}

	/**
	 * Wait until a response with $id arrives, or $timeout elapses.
	 *
	 * @return array<string,mixed>|null The response, or null on timeout
	 */
	public function await(string|int $id, float $timeout): ?array
	{
		$key = (string)$id;
		$until = microtime(true) + $timeout;
		while (microtime(true) < $until) {
			$this->drain();
			if (isset($this->responses[$key])) {
				return $this->responses[$key]['msg'];
			}
			usleep(5000);
		}
		return null;
	}

	/** When the response for $id arrived, relative to probe start. */
	public function responseAt(string|int $id): ?float
	{
		$key = (string)$id;
		return isset($this->responses[$key])
			? round($this->responses[$key]['at'] - $this->start, 2)
			: null;
	}

	public function hasResponse(string|int $id): bool
	{
		return isset($this->responses[(string)$id]);
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function response(string|int $id): ?array
	{
		return $this->responses[(string)$id]['msg'] ?? null;
	}

	/**
	 * Notifications received so far, optionally filtered by method.
	 *
	 * @return array<int,array{method:string,params:array<string,mixed>,at:float}>
	 */
	public function notifications(?string $method = null): array
	{
		if ($method === null) {
			return $this->notifications;
		}
		return array_values(array_filter(
			$this->notifications,
			fn($n) => $n['method'] === $method
		));
	}

	/** Read whatever is buffered without waiting. */
	public function drain(): void
	{
		if ($this->stdoutReader !== null) {
			// File-backed (Windows): reads past EOF return '' immediately
			// and the handle picks up appended bytes on the next attempt.
			while (($chunk = fread($this->stdoutReader, 65536)) !== false && $chunk !== '') {
				$this->buffer .= $chunk;
			}
		} else {
			if (!isset($this->pipes[1]) || !is_resource($this->pipes[1])) {
				return;
			}
			$chunk = fread($this->pipes[1], 65536);
			if (is_string($chunk) && $chunk !== '') {
				$this->buffer .= $chunk;
			}
		}

		while (($pos = strpos($this->buffer, "\n")) !== false) {
			$line = trim(substr($this->buffer, 0, $pos));
			$this->buffer = substr($this->buffer, $pos + 1);
			if ($line === '') {
				continue;
			}
			$msg = json_decode($line, true);
			if (!is_array($msg)) {
				continue;
			}
			if (array_key_exists('id', $msg) && (isset($msg['result']) || isset($msg['error']))) {
				$this->responses[(string)$msg['id']] = ['msg' => $msg, 'at' => microtime(true)];
			} elseif (isset($msg['method'])) {
				$this->notifications[] = [
					'method' => $msg['method'],
					'params' => $msg['params'] ?? [],
					'at' => microtime(true),
				];
			}
		}
	}

	/** Close stdin, signalling EOF so the server should exit on its own. */
	public function closeStdin(): void
	{
		if (isset($this->pipes[0]) && is_resource($this->pipes[0])) {
			fclose($this->pipes[0]);
			unset($this->pipes[0]);
		}
	}

	/**
	 * Wait for the child to exit after EOF.
	 *
	 * @return int|null Exit code, or null if it was still running at timeout
	 */
	public function waitForExit(float $timeout): ?int
	{
		$until = microtime(true) + $timeout;
		while (microtime(true) < $until) {
			$this->drain();
			$status = proc_get_status($this->proc);
			if ($status !== false && $status['running'] === false) {
				return $status['exitcode'];
			}
			usleep(20000);
		}
		return null;
	}

	/** Contents of the server's stderr (diagnostics live here, not stdout). */
	public function stderr(): string
	{
		if ($this->stderrIsPipe) {
			// Deliberately undrained during the test; read it only now,
			// at teardown, so the run itself reproduces a host that
			// never touches the stream.
			if (!isset($this->pipes[2]) || !is_resource($this->pipes[2])) {
				return '';
			}
			stream_set_blocking($this->pipes[2], false);
			$out = '';
			while (($c = fread($this->pipes[2], 65536)) !== false && $c !== '') {
				$out .= $c;
			}
			return $out;
		}
		clearstatcache(true, $this->stderrPath);
		return is_file($this->stderrPath) ? (string)file_get_contents($this->stderrPath) : '';
	}

	public function kill(): void
	{
		foreach ($this->pipes as $pipe) {
			if (is_resource($pipe)) {
				fclose($pipe);
			}
		}
		$this->pipes = [];
		if (is_resource($this->stdoutReader)) {
			fclose($this->stdoutReader);
		}
		$this->stdoutReader = null;
		if (is_resource($this->proc)) {
			proc_terminate($this->proc);
			proc_close($this->proc);
			$this->proc = null;
		}
		if (is_file($this->stderrPath)) {
			@unlink($this->stderrPath);
		}
		if ($this->stdoutPath !== null && is_file($this->stdoutPath)) {
			@unlink($this->stdoutPath);
		}
	}

	public function __destruct()
	{
		$this->kill();
	}
}
