#!/usr/bin/env php
<?php
/**
 * Liveness fixture server
 *
 * A minimal MCP stdio server exposing slow tools, used by
 * tests/transport-liveness.php to verify that a long-running call does
 * not starve the protocol channel. It boots through the real
 * application bootstrap, so it exercises the same vendored
 * EnchiladaMCP + HTTP/ + Enchilada\Tortilla + Comal stack as
 * bin/forgejo-mcp, and wires the composition root the same way.
 *
 * Flags:
 *   --blocking   Force blocking I/O mode (the Windows default path)
 *
 * @package    ForgejoMCP
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

require_once dirname(__DIR__, 2) . '/system/bootstrap.inc.php';

use EnchiladaMCP\McpServer;
use EnchiladaMCP\McpTool;
use Enchilada\Tortilla\ComalEventLoop;
use Enchilada\Tortilla\EventLoop;
use Enchilada\Tortilla\HttpClient;
use Enchilada\Tortilla\StdioTransport;

/**
 * A deterministic stand-in for curl_multi: each queued request
 * completes after $delay seconds of tick()s. Exercises HttpClient's
 * real reactor and blocking wait regimes without any network — the
 * suite asserts liveness behavior, not HTTP correctness.
 */
class FakeMultiHTTP extends \EnchiladaMultiHTTP
{
	/** @var array<int,float> request id => queued-at */
	private array $fake = [];
	private float $delay;
	private int $nextId = 1;
	private int $ticks = 0;

	public function __construct(float $delay)
	{
		// Deliberately NOT calling parent: no curl handle, no base URL.
		$this->delay = $delay;
	}

	public function queue($method, $data = null, $http_verb = 'GET', array $extra_headers = array(), $timeout = null, $format = 'json', $writeCallback = null)
	{
		$id = $this->nextId++;
		$this->fake[$id] = microtime(true);
		return $id;
	}

	public function tick()
	{
		// One bounded step, exactly like curl_multi_select()'s wait.
		usleep(50000);
		$this->ticks++;
	}

	public function hasPendingRequests()
	{
		return !empty($this->fake);
	}

	public function getResult($requestId)
	{
		if (!isset($this->fake[$requestId])) {
			return null;
		}
		if (microtime(true) - $this->fake[$requestId] < $this->delay) {
			return null;
		}
		unset($this->fake[$requestId]);
		return [
			'result' => ['ticks' => $this->ticks],
			'error' => null,
			'raw' => '{"ticks":' . $this->ticks . '}',
			'http_code' => 200,
			'curl_errno' => 0,
			'curl_error' => '',
		];
	}

	public function ticks(): int
	{
		return $this->ticks;
	}
}

class LivenessProbeTools
{
	public function __construct(
		private ?EventLoop $loop,
		private ?\Closure $progress,
	) {}

	/**
	 * Sleep without yielding: the pathological case. Models a tool
	 * blocked inside a C-level call (curl_exec, blocking IMAP read).
	 * Starves the protocol channel in every I/O mode — the suite
	 * asserts this so the limitation stays documented.
	 */
	#[McpTool(description: 'Block for N seconds without yielding to the event loop.', readOnlyHint: true)]
	public function slow_blocking(int $seconds = 5): string
	{
		sleep($seconds);
		return "blocked {$seconds}s";
	}

	/**
	 * Wait N seconds on an HTTP call through Enchilada\Tortilla\HttpClient,
	 * the pattern real tools use for asynchronous I/O. Which wait
	 * regime runs (fiber suspend vs. blocking poll) is decided inside
	 * the client from the injected loop.
	 */
	#[McpTool(description: 'Await an HTTP call for N seconds.', readOnlyHint: true)]
	public function slow_await(int $seconds = 5): string
	{
		$multi = new FakeMultiHTTP((float)$seconds);
		$client = new HttpClient($multi, $this->loop, $this->progress);
		$result = $client->call('fake/slow');
		$code = $client->getHttpCode();
		$steps = is_array($result) ? ($result['ticks'] ?? 0) : 0;
		return "awaited {$seconds}s in {$steps} ticks (http {$code})";
	}

	#[McpTool(description: 'Echo text back immediately.', readOnlyHint: true)]
	public function fast_echo(string $text): string
	{
		return $text;
	}
}

$server = new McpServer('liveness-fixture', '1.0.0');

// Composition root mirrors bin/forgejo-mcp: primitives into the
// transport, notifier back to the server, application-owned loop
// shared with the tools' HttpClient.
$transport = new StdioTransport($server->handleRequest(...), $server->tick(...));
$loop = ComalEventLoop::create();
if (in_array('--blocking', $argv ?? [], true)) {
	$transport->setIoMode('blocking');
	$loop = null;
} elseif ($loop !== null) {
	$transport->setLoop($loop);
}
$server->setNotifier($transport->sendNotification(...));
$server->register(new LivenessProbeTools($loop, $server->tick(...)));
$transport->setLogger(function (string $m) {
	fwrite(STDERR, '[fixture] ' . $m . "\n");
});
$transport->run();
