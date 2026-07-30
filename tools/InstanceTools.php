<?php
/**
 * Forgejo MCP Server — Instance & User Management Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class InstanceTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * List all configured Forgejo instances and their users.
	 */
	#[McpTool(
		name: 'forgejo_list_instances',
		description: 'List all configured Forgejo instances with their users.',
		readOnlyHint: true
	)]
	public function forgejo_list_instances(): array
	{
		return [
			'instances' => $this->manager->listInstances(),
			'count' => $this->manager->count(),
		];
	}
}
