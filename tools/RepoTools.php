<?php
/**
 * Forgejo MCP Server — Repository Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class RepoTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'list_my_repos',
		description: 'List repositories owned by the authenticated user.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'page' => ['type' => 'integer', 'description' => 'Page number (default 1)'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page (default 20)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => [],
		]
	)]
	public function list_my_repos(int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/repos', ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'search_repos',
		description: 'Search repositories across the Forgejo instance.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'q' => ['type' => 'string', 'description' => 'Search query'],
				'page' => ['type' => 'integer', 'description' => 'Page number (default 1)'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page (default 20)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['q'],
		]
	)]
	public function search_repos(string $q, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('repos/search', ['q' => $q, 'page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'create_repo',
		description: 'Create a new repository for the authenticated user.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'name' => ['type' => 'string', 'description' => 'Repository name'],
				'description' => ['type' => 'string', 'description' => 'Repository description'],
				'private' => ['type' => 'boolean', 'description' => 'Whether the repo is private (default false)'],
				'auto_init' => ['type' => 'boolean', 'description' => 'Initialize with README (default false)'],
				'default_branch' => ['type' => 'string', 'description' => 'Default branch name (default "master")'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['name'],
		]
	)]
	public function create_repo(string $name, string $description = '', bool $private = false, bool $auto_init = false, string $default_branch = 'master', ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [
			'name' => $name,
			'description' => $description,
			'private' => $private,
			'auto_init' => $auto_init,
			'default_branch' => $default_branch,
		];
		return $client->post('user/repos', $data);
	}

	#[McpTool(
		name: 'fork_repo',
		description: 'Fork a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Owner of the repo to fork'],
				'repo' => ['type' => 'string', 'description' => 'Repository name to fork'],
				'organization' => ['type' => 'string', 'description' => 'Fork to this organization (optional)'],
				'name' => ['type' => 'string', 'description' => 'Name for the forked repo (optional)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo'],
		]
	)]
	public function fork_repo(string $owner, string $repo, ?string $organization = null, ?string $name = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($organization !== null) $data['organization'] = $organization;
		if ($name !== null) $data['name'] = $name;
		return $client->post("repos/{$owner}/{$repo}/forks", $data ?: null);
	}
}
