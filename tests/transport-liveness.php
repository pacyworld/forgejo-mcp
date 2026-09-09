#!/usr/bin/env php
<?php
/**
 * Transport liveness test (long calls must not starve the host)
 *
 * An agent host closes an MCP connection it believes has died. Two
 * signals keep it alive during a slow tool call: `ping` replies and
 * `notifications/progress` (the only one available in protocol revision
 * 2026-07-28, which removed ping). This test drives a fixture server
 * with deliberately slow tools and asserts both.
 *
 * Expectations differ by platform, on purpose:
 *
 *   reactor mode (POSIX)  A tool that yields keeps the protocol channel
 *                         live: pings are answered *during* the call and
 *                         progress notifications keep arriving.
 *
 *   blocking mode (Windows) An anonymous stdin pipe cannot be polled, so
 *                         mid-call pings cannot be answered. Progress
 *                         notifications must still flow from the tool's
 *                         own yield points.
 *
 *   non-yielding tool     Starves the channel in either mode. Asserted
 *                         explicitly so the limitation stays documented
 *                         and any future fix is noticed here first.
 *
 * Run: php tests/transport-liveness.php
 *
 * @package    ForgejoMCP
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

require_once __DIR__ . '/lib/StdioProbe.php';

$fixture = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'liveness-server.php';

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

/**
 * Boot a fixture server and complete the MCP handshake.
 *
 * @param string[] $args
 */
function handshake(string $fixture, array $args): ?StdioProbe
{
	$probe = new StdioProbe($fixture, $args);
	$probe->send([
		'jsonrpc' => '2.0',
		'id' => 'init',
		'method' => 'initialize',
		'params' => [
			'protocolVersion' => '2025-06-18',
			'clientInfo' => ['name' => 'liveness-probe', 'version' => '1.0'],
			'capabilities' => new stdClass(),
		],
	]);
	if ($probe->await('init', 15.0) === null) {
		echo '    stderr: ' . trim($probe->stderr()) . "\n";
		$probe->kill();
		return null;
	}
	$probe->send(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);
	return $probe;
}

/**
 * Start a slow tool call, then send a ping 1s in and report what came
 * back while the call was still running.
 *
 * @param  string[] $args Server arguments
 * @return array{pingDuringCall:bool,pingLatency:?float,progress:int,callOk:bool,callDuration:?float}
 */
function measure(string $fixture, array $args, string $tool, int $seconds): array
{
	$probe = handshake($fixture, $args);
	if ($probe === null) {
		return ['pingDuringCall' => false, 'pingLatency' => null, 'progress' => 0, 'callOk' => false, 'callDuration' => null];
	}

	try {
		$callStart = microtime(true);
		$probe->send([
			'jsonrpc' => '2.0',
			'id' => 'call',
			'method' => 'tools/call',
			'params' => [
				// A progressToken is what licenses the server to send
				// notifications/progress for this request.
				'_meta' => ['progressToken' => 'tok-1'],
				'name' => $tool,
				'arguments' => ['seconds' => $seconds],
			],
		]);

		// Let the call get underway, then probe liveness mid-flight.
		$probe->pump(1.0);
		$pingSentAt = microtime(true);
		$probe->send(['jsonrpc' => '2.0', 'id' => 'ping', 'method' => 'ping']);

		// Watch until the tool call completes (plus headroom).
		$deadline = microtime(true) + $seconds + 15.0;
		$pingAt = null;
		while (microtime(true) < $deadline) {
			$probe->drain();
			if ($pingAt === null && $probe->hasResponse('ping')) {
				$pingAt = microtime(true);
			}
			if ($probe->hasResponse('call')) {
				break;
			}
			usleep(10000);
		}
		$callEnd = microtime(true);
		$probe->drain();

		$callOk = $probe->hasResponse('call');
		$progress = count($probe->notifications('notifications/progress'));

		// "During the call" means the reply beat the tool's own result.
		$pingDuringCall = $pingAt !== null && $callOk && $pingAt < ($callEnd - 0.5);

		return [
			'pingDuringCall' => $pingDuringCall,
			'pingLatency' => $pingAt !== null ? round($pingAt - $pingSentAt, 2) : null,
			'progress' => $progress,
			'callOk' => $callOk,
			'callDuration' => $callOk ? round($callEnd - $callStart, 1) : null,
		];
	} finally {
		$probe->kill();
	}
}

$isWindows = (PHP_OS_FAMILY === 'Windows');
echo 'Transport liveness — PHP ' . PHP_VERSION . ' on ' . PHP_OS_FAMILY . ' (' . php_uname('s') . ")\n\n";

$slow = 8;

// ---------------------------------------------------------------
// Reactor mode: only meaningful where stdio can be polled at all.
// ---------------------------------------------------------------
if (!$isWindows) {
	foreach (['slow_async', 'slow_await'] as $tool) {
		echo "Reactor mode, {$tool}({$slow}s)\n";
		$m = measure($fixture, [], $tool, $slow);
		check('tool call completed', $m['callOk']);
		check("call really took ~{$slow}s (got {$m['callDuration']}s)", $m['callDuration'] !== null && $m['callDuration'] >= $slow - 1);
		check(
			'ping answered DURING the call' . ($m['pingLatency'] !== null ? " (latency {$m['pingLatency']}s)" : ''),
			$m['pingDuringCall'],
			'ping did not come back until the call finished'
		);
		check("progress notifications flowed ({$m['progress']})", $m['progress'] >= 2);
		echo "\n";
	}

	echo "Reactor mode, slow_blocking({$slow}s) — known limitation\n";
	$m = measure($fixture, [], 'slow_blocking', $slow);
	check('tool call completed', $m['callOk']);
	check(
		'a non-yielding tool starves the channel (documented)',
		!$m['pingDuringCall'],
		'unexpectedly stayed responsive — the limitation may be gone; update the docs'
	);
	echo "\n";
}

// ---------------------------------------------------------------
// Blocking mode: the Windows default. Exercised on every platform so
// the path cannot rot, but only Windows asserts it as the default.
// ---------------------------------------------------------------
echo "Blocking mode, slow_await({$slow}s)\n";
$m = measure($fixture, ['--blocking'], 'slow_await', $slow);
check('tool call completed', $m['callOk']);
check("call really took ~{$slow}s (got {$m['callDuration']}s)", $m['callDuration'] !== null && $m['callDuration'] >= $slow - 1);
check(
	"progress notifications flowed while blocked ({$m['progress']})",
	$m['progress'] >= 2,
	'no progress emitted; a host with a short deadline would close the connection'
);
echo "  · mid-call ping answered: " . ($m['pingDuringCall'] ? 'yes' : 'no (expected: stdin pipe is not pollable)') . "\n";
echo "\n";

echo str_repeat('─', 46) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
