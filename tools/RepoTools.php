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
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['instance', 'user'],
		]
	)]
	public function list_my_repos(int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/repos', ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'search_repos',
		description: 'Search repositories on the instance.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'q' => ['type' => 'string'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['q', 'instance', 'user'],
		]
	)]
	public function search_repos(string $q, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('repos/search', ['q' => $q, 'page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'create_repo',
		description: 'Create a repository under the given organization, or the authenticated user if omitted.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'name' => ['type' => 'string'],
				'description' => ['type' => 'string'],
				'organization' => ['type' => 'string'],
				'private' => ['type' => 'boolean', 'description' => 'default false'],
				'auto_init' => ['type' => 'boolean', 'description' => 'Initialize with README (default false)'],
				'default_branch' => ['type' => 'string', 'description' => 'default "master"'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['name', 'instance', 'user'],
		]
	)]
	public function create_repo(string $name, string $description = '', ?string $organization = null, bool $private = false, bool $auto_init = false, string $default_branch = 'master', string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [
			'name' => $name,
			'description' => $description,
			'private' => $private,
			'auto_init' => $auto_init,
			'default_branch' => $default_branch,
		];

		if ($organization !== null) {
			return $client->post("orgs/{$organization}/repos", $data);
		}

		return $client->post('user/repos', $data);
	}

	#[McpTool(
		name: 'fork_repo',
		description: 'Fork a repository to the authenticated user or an organization.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'organization' => ['type' => 'string', 'description' => 'Fork into this org'],
				'name' => ['type' => 'string', 'description' => 'Name for the fork'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'instance', 'user'],
		]
	)]
	public function fork_repo(string $owner, string $repo, ?string $organization = null, ?string $name = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($organization !== null) $data['organization'] = $organization;
		if ($name !== null) $data['name'] = $name;
		return $client->post("repos/{$owner}/{$repo}/forks", $data ?: null);
	}

	#[McpTool(name: 'list_repo_contents', description: 'List files and directories at a repository path.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'path' => ['type' => 'string', 'description' => 'Empty for root'], 'ref' => ['type' => 'string', 'description' => 'Branch, tag or SHA'], 'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'], 'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)']], 'required' => ['owner', 'repo', 'instance', 'user']])]
	public function list_repo_contents(string $owner, string $repo, string $path = '', ?string $ref = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$endpoint = "repos/{$owner}/{$repo}/contents";
		if (!empty($path)) $endpoint .= "/{$path}";
		$query = [];
		if ($ref !== null) $query['ref'] = $ref;
		return $client->get($endpoint, $query);
	}

	#[McpTool(name: 'get_repo_tree', description: 'Get a Git tree; recursive=true returns the full file tree.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'sha' => ['type' => 'string', 'description' => 'Tree SHA or branch name'], 'recursive' => ['type' => 'boolean', 'description' => 'default false'], 'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'], 'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)']], 'required' => ['owner', 'repo', 'sha', 'instance', 'user']])]
	public function get_repo_tree(string $owner, string $repo, string $sha, bool $recursive = false, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = [];
		if ($recursive) $query['recursive'] = 'true';
		return $client->get("repos/{$owner}/{$repo}/git/trees/{$sha}", $query);
	}
}
