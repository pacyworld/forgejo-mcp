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
		description: 'Create a new repository. If organization is specified, creates under that org; otherwise creates under the authenticated user.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'name' => ['type' => 'string', 'description' => 'Repository name'],
				'description' => ['type' => 'string', 'description' => 'Repository description'],
				'organization' => ['type' => 'string', 'description' => 'Organization to create the repo under (omit for personal repo)'],
				'private' => ['type' => 'boolean', 'description' => 'Whether the repo is private (default false)'],
				'auto_init' => ['type' => 'boolean', 'description' => 'Initialize with README (default false)'],
				'default_branch' => ['type' => 'string', 'description' => 'Default branch name (default "master")'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['name'],
		]
	)]
	public function create_repo(string $name, string $description = '', ?string $organization = null, bool $private = false, bool $auto_init = false, string $default_branch = 'master', ?string $instance = null, ?string $user = null): array
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

	#[McpTool(name: 'list_repo_contents', description: 'List files and directories at a given path in a repository. Use path="" for the root.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'path' => ['type' => 'string', 'description' => 'Directory path (empty string for root)'], 'ref' => ['type' => 'string', 'description' => 'Branch, tag, or SHA (optional)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function list_repo_contents(string $owner, string $repo, string $path = '', ?string $ref = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$endpoint = "repos/{$owner}/{$repo}/contents";
		if (!empty($path)) $endpoint .= "/{$path}";
		$query = [];
		if ($ref !== null) $query['ref'] = $ref;
		return $client->get($endpoint, $query);
	}

	#[McpTool(name: 'get_repo_tree', description: 'Get the Git tree of a repository. With recursive=true, returns the complete file tree.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'sha' => ['type' => 'string', 'description' => 'Tree SHA or branch name'], 'recursive' => ['type' => 'boolean', 'description' => 'Recurse into subtrees (default false)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'sha']])]
	public function get_repo_tree(string $owner, string $repo, string $sha, bool $recursive = false, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = [];
		if ($recursive) $query['recursive'] = 'true';
		return $client->get("repos/{$owner}/{$repo}/git/trees/{$sha}", $query);
	}
}
