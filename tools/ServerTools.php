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

	#[McpTool(name: 'get_forgejo_mcp_server_version', description: 'Get this MCP server\'s version.', readOnlyHint: true)]
	public function get_forgejo_mcp_server_version(): array
	{
		return [
			'name' => APPLICATION_NAME,
			'version' => APPLICATION_VERSION,
			'website' => APPLICATION_WEBSITE,
		];
	}

	#[McpTool(name: 'get_forgejo_version', description: 'Get an instance\'s Forgejo version and supported version-gated features (action_logs_api needs Forgejo 16+).', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['instance', 'user']])]
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
