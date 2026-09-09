#!/usr/bin/env php
<?php
/**
 * Liveness fixture server
 *
 * A minimal MCP stdio server exposing slow tools, used by
 * tests/transport-liveness.php to verify that a long-running call does
 * not starve the protocol channel. It boots through the real
 * application bootstrap, so it exercises the same vendored
 * EnchiladaMCP + Comal stack as bin/forgejo-mcp.
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

use EnchiladaMCP\Liveness;
use EnchiladaMCP\McpServer;
use EnchiladaMCP\McpTool;
use EnchiladaMCP\StdioTransport;

class LivenessProbeTools
{
	/**
	 * Sleep without yielding: the pathological case. Models a tool
	 * blocked inside a C-level call (curl_exec, blocking IMAP read).
	 */
	#[McpTool(description: 'Block for N seconds without yielding to the event loop.', readOnlyHint: true)]
	public function slow_blocking(int $seconds = 5): string
	{
		sleep($seconds);
		return "blocked {$seconds}s";
	}

	/**
	 * Sleep via the liveness-aware sleep, which suspends the dispatch
	 * fiber so the reactor keeps serving the protocol channel.
	 */
	#[McpTool(description: 'Wait N seconds, yielding to the event loop.', readOnlyHint: true)]
	public function slow_async(int $seconds = 5): string
	{
		Liveness::sleep((float)$seconds);
		return "awaited {$seconds}s";
	}

	/**
	 * Drive a state machine through Liveness::await(), the pattern real
	 * tools use for asynchronous I/O (e.g. EnchiladaMultiHTTP).
	 */
	#[McpTool(description: 'Await a simulated async state machine for N seconds.', readOnlyHint: true)]
	public function slow_await(int $seconds = 5): string
	{
		$deadline = microtime(true) + $seconds;
		$steps = 0;
		Liveness::await(
			function () use (&$steps) {
				// One step of the "state machine": a short, bounded wait
				// exactly like curl_multi_select() performs.
				usleep(50000);
				$steps++;
			},
			fn() => microtime(true) < $deadline
		);
		return "awaited {$seconds}s in {$steps} steps";
	}

	#[McpTool(description: 'Echo text back immediately.', readOnlyHint: true)]
	public function fast_echo(string $text): string
	{
		return $text;
	}
}

$server = new McpServer('liveness-fixture', '1.0.0');
$server->register(new LivenessProbeTools());

$transport = new StdioTransport($server);
if (in_array('--blocking', $argv ?? [], true)) {
	$transport->setIoMode('blocking');
}
$transport->setLogger(function (string $m) {
	fwrite(STDERR, '[fixture] ' . $m . "\n");
});
$transport->run();
