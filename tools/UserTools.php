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
		description: 'Get profile information of the currently authenticated user.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => [],
		]
	)]
	public function get_my_user_info(?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user');
	}

	/**
	 * Search for users on the Forgejo instance.
	 */
	#[McpTool(
		name: 'search_users',
		description: 'Search for users by username or email.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'q' => ['type' => 'string', 'description' => 'Search query (username or email)'],
				'limit' => ['type' => 'integer', 'description' => 'Maximum results to return (default 10)'],
				'page' => ['type' => 'integer', 'description' => 'Page number (default 1)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['q'],
		]
	)]
	public function search_users(string $q, int $limit = 10, int $page = 1, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('users/search', ['q' => $q, 'limit' => $limit, 'page' => $page]);
	}
}
