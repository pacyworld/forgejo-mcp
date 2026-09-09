<?php

namespace EnchiladaMCP;

use Enchilada\Comal\ReactorInterface;

/* Enchilada Framework 3.0
 * MCP Liveness Bridge
 *
 * Lets long-running tool code cooperate with the transport's event loop
 * so the MCP host keeps seeing liveness:
 *
 *   - pending `ping` requests are answered while a call is in flight
 *     (agent hosts tear the connection down when pings starve),
 *   - `notifications/progress` is emitted for the in-flight request when
 *     the client supplied a progressToken. In the modern revision
 *     (2026-07-28) `ping` no longer exists, so progress is the *only*
 *     liveness signal a host has during a slow call.
 *
 * Two integration styles, in order of preference:
 *
 *   await()  Suspends the calling Fiber while an asynchronous state
 *            machine runs (the reactor keeps serving stdin, so pings are
 *            answered and progress timers keep firing). This is the
 *            wanted style — see StdioTransport's reactor mode.
 *
 *   tick()   Manual yield point for code that cannot suspend (blocking
 *            extension calls, Windows blocking-I/O mode). Emits progress
 *            but cannot answer pings.
 *
 * The McpServer registers itself here on construction; the transport
 * registers the reactor. When no server is active every call is a no-op,
 * so libraries may call these unconditionally.
 *
 * Software License Agreement (BSD License)
 *
 * Copyright (c) 2026, The Daniel Morante Company, Inc.
 * All rights reserved.
 */

class Liveness
{
	/** @var McpServer|null The active server (stdio servers are single-server per process) */
	private static ?McpServer $server = null;

	/** @var ReactorInterface|null Event loop driving the active transport, when it has one */
	private static ?ReactorInterface $reactor = null;

	/** @var float Poll interval (seconds) used to advance awaited state machines */
	private static float $awaitInterval = 0.05;

	/**
	 * Register the active server. Called automatically by the McpServer
	 * constructor; explicit calls are only needed by exotic bootstrap paths.
	 */
	public static function register(McpServer $server): void
	{
		self::$server = $server;
	}

	/**
	 * Detach a previously registered server (shutdown, process re-use).
	 */
	public static function unregister(?McpServer $server = null): void
	{
		if ($server === null || self::$server === $server) {
			self::$server = null;
		}
	}

	/**
	 * Publish the reactor driving the current transport, enabling the
	 * Fiber-suspending await() path. Pass null for transports without an
	 * event loop (await() then degrades to a synchronous poll loop).
	 */
	public static function setReactor(?ReactorInterface $reactor): void
	{
		self::$reactor = $reactor;
	}

	/**
	 * The reactor driving the current transport, if any. Tools that want
	 * to register their own watchers or timers can use this.
	 */
	public static function reactor(): ?ReactorInterface
	{
		return self::$reactor;
	}

	/**
	 * Drive an asynchronous state machine to completion without starving
	 * the host.
	 *
	 * $advance performs one step (e.g. EnchiladaMultiHTTP::tick(), which
	 * runs curl_multi_exec and waits briefly on its sockets); $active
	 * reports whether work remains. Inside a Fiber with a reactor present
	 * the calling tool is *suspended* between steps, so the transport
	 * keeps answering pings and emitting progress. Otherwise the steps run
	 * in a plain loop with tick() for progress.
	 *
	 *   $id = $multi->queue('repos/x/pulls', null, 'GET');
	 *   Liveness::await(fn() => $multi->tick(), fn() => $multi->hasPendingRequests());
	 *   $result = $multi->getResult($id);
	 *
	 * @param callable $advance Advances the state machine one step
	 * @param callable $active  Returns true while work is still pending
	 */
	public static function await(callable $advance, callable $active): void
	{
		$fiber = \Fiber::getCurrent();
		$reactor = self::$reactor;

		// No event loop, or not running inside a Fiber: nothing can be
		// serviced while we wait, so just poll and emit progress.
		if ($fiber === null || $reactor === null) {
			while ($active()) {
				$advance();
				self::tick();
			}
			return;
		}

		while ($active()) {
			$timerId = null;
			$resumed = false;
			$timerId = $reactor->repeat(self::$awaitInterval, function () use (&$timerId, &$resumed, $advance, $active, $reactor, $fiber) {
				try {
					$advance();
				} catch (\Throwable $e) {
					// Surface the failure to the awaiting tool, not the loop.
				}
				if ($resumed || $active()) {
					return;
				}
				$resumed = true;
				if ($timerId !== null) {
					$reactor->cancel($timerId);
				}
				// The fiber is parked in Fiber::suspend() below; resuming
				// it runs the rest of the tool call inside this callback.
				if ($fiber->isSuspended()) {
					$fiber->resume();
				}
			});

			\Fiber::suspend();

			// Defensive: if the timer resumed us spuriously the outer
			// while() re-checks $active() and waits again.
			if ($timerId !== null && !$resumed) {
				$reactor->cancel($timerId);
			}
		}
	}

	/**
	 * Suspend the calling Fiber for $seconds without blocking the loop.
	 *
	 * Falls back to a real sleep when there is no reactor/Fiber, so tool
	 * code can call it unconditionally.
	 */
	public static function sleep(float $seconds): void
	{
		$fiber = \Fiber::getCurrent();
		$reactor = self::$reactor;

		if ($fiber === null || $reactor === null) {
			$deadline = microtime(true) + $seconds;
			while (($remaining = $deadline - microtime(true)) > 0) {
				usleep((int)(min($remaining, 0.5) * 1000000));
				self::tick();
			}
			return;
		}

		$reactor->delay($seconds, function () use ($fiber) {
			if ($fiber->isSuspended()) {
				$fiber->resume();
			}
		});
		\Fiber::suspend();
	}

	/**
	 * Manual yield point for code that cannot suspend.
	 *
	 * Emits a throttled progress notification for the in-flight request.
	 * Cheap and side-effect free when idle: safe to call from hot loops.
	 * Never throws.
	 */
	public static function tick(): void
	{
		if (self::$server === null) {
			return;
		}
		try {
			self::$server->tick();
		} catch (\Throwable $e) {
			// Liveness is best-effort; it must never break the operation it serves.
		}
	}
}
