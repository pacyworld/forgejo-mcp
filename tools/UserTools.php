<?php
/**
 * Forgejo MCP Server — User Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class UserTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Get the authenticated user's profile information.
	 */
	#[McpTool(
		name: 'get_my_user_info',
		description: 'Get the authenticated user\'s profile.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['instance', 'user'],
		]
	)]
	public function get_my_user_info(string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user');
	}

	/**
	 * Search for users on the Forgejo instance.
	 */
	#[McpTool(
		name: 'search_users',
		description: 'Search users by username or email.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'q' => ['type' => 'string'],
				'limit' => ['type' => 'integer', 'description' => 'default 10'],
				'page' => ['type' => 'integer'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['q', 'instance', 'user'],
		]
	)]
	public function search_users(string $q, int $limit = 10, int $page = 1, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('users/search', ['q' => $q, 'limit' => $limit, 'page' => $page]);
	}
}
