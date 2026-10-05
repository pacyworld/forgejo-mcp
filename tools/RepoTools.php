<?php
/**
 * Forgejo MCP Server — Repository Tools
 *
 * Consolidated: one `repo` tool for create/fork/list_mine; search_repos
 * stays standalone. Repository file browsing moved to FileTools (`file`
 * tool, actions list_contents/tree).
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class RepoTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'repo',
		description: 'Manage repositories. Actions and their required parameters: list_mine, create(name; optional organization, description, private, auto_init, default_branch), fork(owner, repo; optional organization, name).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list_mine', 'create', 'fork']],
				'name' => ['type' => 'string'],
				'description' => ['type' => 'string'],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'organization' => ['type' => 'string', 'description' => 'Org to create/fork into instead of the authenticated user'],
				'private' => ['type' => 'boolean', 'description' => 'default false'],
				'auto_init' => ['type' => 'boolean', 'description' => 'Initialize with README (default false)'],
				'default_branch' => ['type' => 'string', 'description' => 'default "master"'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_my_repos' => 'repo action=list_mine',
			'create_repo' => 'repo action=create',
			'fork_repo' => 'repo action=fork',
		]
	)]
	public function repo(string $action, ?string $name = null, ?string $description = null, ?string $owner = null, ?string $repo = null, ?string $organization = null, ?bool $private = null, ?bool $auto_init = null, ?string $default_branch = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('repo', $action, get_defined_vars(), [
			'list_mine' => ['handler' => [$this, 'list_my_repos'], 'required' => [], 'args' => ['page', 'limit', 'instance', 'user']],
			'create' => ['handler' => [$this, 'create_repo'], 'required' => ['name'], 'args' => ['name', 'description', 'organization', 'private', 'auto_init', 'default_branch', 'instance', 'user']],
			'fork' => ['handler' => [$this, 'fork_repo'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'organization', 'name', 'instance', 'user']],
		]);
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
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['q', 'instance', 'user'],
		]
	)]
	public function search_repos(string $q, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('repos/search', ['q' => $q, 'page' => $page, 'limit' => $limit]);
	}

	public function list_my_repos(int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/repos', ['page' => $page, 'limit' => $limit]);
	}

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

	public function fork_repo(string $owner, string $repo, ?string $organization = null, ?string $name = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($organization !== null) $data['organization'] = $organization;
		if ($name !== null) $data['name'] = $name;
		return $client->post("repos/{$owner}/{$repo}/forks", $data ?: null);
	}
}
