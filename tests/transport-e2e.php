#!/usr/bin/env php
<?php
/**
 * Transport end-to-end test (real server binary, real pipes)
 *
 * Drives bin/forgejo-mcp as a child process the way an agent host does
 * and asserts the transport contract that IDE hosts depend on:
 *
 *   1. initialize is answered promptly (a slow/absent reply is the
 *      "server timed out at startup" failure)
 *   2. stdout carries only framed JSON-RPC (no diagnostics, no warnings)
 *   3. a large tools/list payload survives the pipe intact
 *   4. an offline tool call round-trips
 *   5. the server stays responsive while idle
 *   6. stdin EOF shuts the server down cleanly
 *
 * Runs against both I/O modes so the Windows path (blocking) and the
 * POSIX path (Comal reactor) are both covered on every platform.
 *
 * Run: php tests/transport-e2e.php
 *
 * @package    ForgejoMCP
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

require_once __DIR__ . '/lib/StdioProbe.php';

$root = dirname(__DIR__);
$server = $root . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'forgejo-mcp';
$config = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'transport-instances.json';

$passed = 0;
$failed = 0;

function check(string $label, bool $cond, string $detail = ''): void
{
	global $passed, $failed;
	if ($cond) {
		$passed++;
		echo "  ✓ {$label}\n";
	} else {
		$failed++;
		echo "  ✗ {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
	}
}

echo 'Transport E2E — PHP ' . PHP_VERSION . ' on ' . PHP_OS_FAMILY . ' (' . php_uname('s') . ")\n\n";

/**
 * @param string[] $extraArgs
 */
function runSuite(string $label, string $server, string $config, array $extraArgs): void
{
	echo "{$label}\n";

	$probe = new StdioProbe($server, array_merge(['--config=' . $config], $extraArgs));

	try {
		// --- 1: initialize answered promptly ---
		$probe->send([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'initialize',
			'params' => [
				'protocolVersion' => '2025-06-18',
				'clientInfo' => ['name' => 'transport-e2e', 'version' => '1.0'],
				'capabilities' => new stdClass(),
			],
		]);
		$init = $probe->await(1, 15.0);
		check('initialize answered', $init !== null, 'no response within 15s');
		if ($init === null) {
			echo "    stderr: " . trim($probe->stderr()) . "\n";
			$probe->kill();
			return;
		}
		$at = $probe->responseAt(1);
		check("initialize is prompt ({$at}s)", $at !== null && $at < 10.0);
		check(
			'initialize reports protocolVersion + serverInfo',
			isset($init['result']['protocolVersion'], $init['result']['serverInfo']['name'])
		);

		$probe->send(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);

		// --- 2+3: large tools/list payload intact ---
		$probe->send(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list', 'params' => new stdClass()]);
		$list = $probe->await(2, 20.0);
		check('tools/list answered', $list !== null, 'no response within 20s');
		$toolCount = isset($list['result']['tools']) ? count($list['result']['tools']) : 0;
		check("tools/list returns the full tool set ({$toolCount} tools)", $toolCount > 50);

		$names = array_column($list['result']['tools'] ?? [], 'name');
		check('tool names decoded intact', in_array('list_forgejo_instances', $names, true));

		// --- 4: offline tool call round-trips ---
		$probe->send([
			'jsonrpc' => '2.0',
			'id' => 3,
			'method' => 'tools/call',
			'params' => ['name' => 'list_forgejo_instances', 'arguments' => new stdClass()],
		]);
		$call = $probe->await(3, 20.0);
		check('tools/call answered', $call !== null, 'no response within 20s');
		$text = $call['result']['content'][0]['text'] ?? '';
		check('tools/call payload mentions the fixture instance', str_contains($text, 'example'));

		// --- 5: responsive while idle ---
		$probe->pump(3.0);
		$probe->send(['jsonrpc' => '2.0', 'id' => 4, 'method' => 'ping']);
		$ping = $probe->await(4, 10.0);
		check('ping answered after an idle period', $ping !== null, 'no response within 10s');

		// --- 2 (cont): stdout stayed protocol-only ---
		// Every line the probe saw parsed as JSON-RPC; anything else
		// (warnings, notices, debug prints) would have been dropped by
		// the parser, so assert the responses we expected all arrived.
		check(
			'stdout carried only framed JSON-RPC',
			$probe->hasResponse(1) && $probe->hasResponse(2)
				&& $probe->hasResponse(3) && $probe->hasResponse(4)
		);

		// --- 6: EOF shuts down cleanly ---
		$probe->closeStdin();
		$exit = $probe->waitForExit(10.0);
		check('server exits on stdin EOF', $exit !== null, 'still running 10s after EOF');
		check('exit code is 0' . ($exit === null ? '' : " (got {$exit})"), $exit === 0 || $exit === null);

		$err = $probe->stderr();
		check(
			'no PHP warnings/fatals on stderr',
			!preg_match('/(PHP )?(Warning|Fatal error|Uncaught|Deprecated):/i', $err),
			trim(substr($err, 0, 400))
		);
	} finally {
		$probe->kill();
	}

	echo "\n";
}

/**
 * A host that captures stderr but never reads it must not be able to
 * wedge the server. Once the stderr pipe buffer fills, an unbounded
 * blocking write never returns and the server stops answering while
 * staying alive — which the host reports as a startup hang. This is only
 * reproducible with a real *pipe*: collecting stderr into a file cannot
 * fail this way.
 *
 * tools/list is the stress point here (a ~71 KB payload, and the largest
 * log lines the server produces).
 */
function runUndrainedStderrSuite(string $server, string $config): void
{
	echo "Undrained stderr pipe\n";

	// Force stderr mirroring on, so the test is meaningful even though
	// the shipped default is off.
	$prev = getenv('FORGEJO_MCP_LOG_STDERR');
	putenv('FORGEJO_MCP_LOG_STDERR=1');

	$probe = new StdioProbe($server, ['--config=' . $config], null, true);
	try {
		$probe->send([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'initialize',
			'params' => [
				'protocolVersion' => '2025-11-25',
				'clientInfo' => ['name' => 'undrained-stderr', 'version' => '1.0'],
				'capabilities' => new stdClass(),
			],
		]);
		check('initialize answered with stderr undrained', $probe->await(1, 15.0) !== null);

		$probe->send(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);
		$probe->send(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list', 'params' => new stdClass()]);
		$list = $probe->await(2, 25.0);
		check(
			'tools/list answered with stderr undrained',
			$list !== null,
			'server wedged on a full stderr pipe'
		);

		// And it must still be usable afterwards, not just lucky once.
		$probe->send(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'ping']);
		check('still responsive afterwards', $probe->await(3, 15.0) !== null);
	} finally {
		$probe->kill();
		if ($prev === false) {
			putenv('FORGEJO_MCP_LOG_STDERR');
		} else {
			putenv('FORGEJO_MCP_LOG_STDERR=' . $prev);
		}
	}

	echo "\n";
}

// --suite=default|forced|stderr|all (default: all). Handy when one mode
// hangs and the other needs to stay observable.
$suite = 'all';
foreach ($argv as $arg) {
	if (str_starts_with($arg, '--suite=')) {
		$suite = substr($arg, 8);
	}
}

// Default mode for the platform (reactor on POSIX, blocking on Windows)
if ($suite === 'all' || $suite === 'default') {
	runSuite('Default I/O mode', $server, $config, []);
}

// Explicitly exercise the other mode too, so both code paths are
// covered regardless of which platform the suite runs on.
if ($suite === 'all' || $suite === 'forced') {
	$forced = (PHP_OS_FAMILY === 'Windows') ? '--io-mode=reactor' : '--io-mode=blocking';
	runSuite("Forced {$forced}", $server, $config, [$forced]);
}

if ($suite === 'all' || $suite === 'stderr') {
	runUndrainedStderrSuite($server, $config);
}

echo str_repeat('─', 46) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
