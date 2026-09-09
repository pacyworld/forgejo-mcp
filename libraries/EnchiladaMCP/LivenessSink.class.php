<?php

namespace EnchiladaMCP;

/* Enchilada Framework 3.0
 * MCP Liveness Sink
 *
 * Narrow transport contract used by McpServer to emit out-of-band
 * traffic (progress notifications, keepalives) while a request is still
 * being handled. Implemented by transports that can write to the client
 * mid-request.
 *
 * Servicing the *inbound* channel during a long call is the reactor's
 * job, not this interface's: StdioTransport keeps its stdin watcher
 * registered while a tool call is suspended, so pings are answered by
 * the event loop rather than by a hand-rolled pump.
 *
 * Software License Agreement (BSD License)
 *
 * Copyright (c) 2026, The Daniel Morante Company, Inc.
 * All rights reserved.
 */

interface LivenessSink
{
	/**
	 * Send a JSON-RPC notification to the client.
	 *
	 * @param string              $method Notification method name
	 * @param array<string,mixed> $params Notification parameters
	 */
	public function sendNotification(string $method, array $params = []): void;
}
