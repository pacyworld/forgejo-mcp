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

	#[McpTool(name: 'get_forgejo_mcp_server_version', description: 'Get the version of this Forgejo MCP server.')]
	public function get_forgejo_mcp_server_version(): array
	{
		return [
			'name' => APPLICATION_NAME,
			'version' => APPLICATION_VERSION,
			'website' => APPLICATION_WEBSITE,
		];
	}
}
