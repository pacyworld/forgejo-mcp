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
		description: 'List all configured Forgejo instances with their users. Shows which instance and user is currently active.'
	)]
	public function forgejo_list_instances(): array
	{
		return [
			'default_instance' => $this->manager->getDefaultInstance(),
			'default_user' => $this->manager->getDefaultUser(),
			'instances' => $this->manager->listInstances(),
		];
	}

	/**
	 * Switch the active Forgejo instance.
	 */
	#[McpTool(
		name: 'forgejo_switch_instance',
		description: 'Switch the active default Forgejo instance. All subsequent tool calls without an explicit instance parameter will use this instance.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Name of the instance to set as default'],
			],
			'required' => ['instance'],
		]
	)]
	public function forgejo_switch_instance(string $instance): array
	{
		$this->manager->setDefaultInstance($instance);
		return [
			'success' => true,
			'default_instance' => $instance,
			'default_user' => $this->manager->getDefaultUser(),
		];
	}

	/**
	 * Switch the active user within the current instance.
	 */
	#[McpTool(
		name: 'forgejo_switch_user',
		description: 'Switch the active user within the current Forgejo instance. All subsequent tool calls without an explicit user parameter will use this user.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'user' => ['type' => 'string', 'description' => 'Name of the user to set as default'],
			],
			'required' => ['user'],
		]
	)]
	public function forgejo_switch_user(string $user): array
	{
		$this->manager->setDefaultUser($user);
		return [
			'success' => true,
			'default_instance' => $this->manager->getDefaultInstance(),
			'default_user' => $user,
		];
	}
}
