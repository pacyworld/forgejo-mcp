<?php
/**
 * Forgejo MCP Server — Server Info Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class ServerTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'get_forgejo_mcp_server_version', description: 'Get the version of this Forgejo MCP server.', readOnlyHint: true)]
	public function get_forgejo_mcp_server_version(): array
	{
		return [
			'name' => APPLICATION_NAME,
			'version' => APPLICATION_VERSION,
			'website' => APPLICATION_WEBSITE,
		];
	}

	#[McpTool(name: 'get_forgejo_version', description: 'Get the Forgejo server version of a connected instance and which version-gated API features it supports (e.g. action_logs_api requires Forgejo 16+).', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['instance', 'user']])]
	public function get_forgejo_version(string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$version = $client->getServerVersion();
		return [
			'version' => $version !== '' ? $version : 'unknown',
			'features' => [
				'action_logs_api' => $client->supportsActionLogsApi(),
			],
		];
	}
}
