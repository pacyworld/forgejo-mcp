<?php
/**
 * Forgejo MCP Server — Branch Tools
 *
 * Consolidated: one `branch` tool whose `action` selects the operation.
 * The per-operation methods remain as internal handlers.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class BranchTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'branch',
		description: 'Manage repository branches. Actions and their required parameters: list(owner, repo), create(owner, repo, new_branch_name; optional old_branch_name), delete(owner, repo, branch).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'create', 'delete']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'branch' => ['type' => 'string'],
				'new_branch_name' => ['type' => 'string'],
				'old_branch_name' => ['type' => 'string', 'description' => 'Source branch; default branch if omitted'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_branches' => 'branch action=list',
			'create_branch' => 'branch action=create',
			'delete_branch' => 'branch action=delete',
		]
	)]
	public function branch(string $action, ?string $owner = null, ?string $repo = null, ?string $branch = null, ?string $new_branch_name = null, ?string $old_branch_name = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('branch', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_branches'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'page', 'limit', 'instance', 'user']],
			'create' => ['handler' => [$this, 'create_branch'], 'required' => ['owner', 'repo', 'new_branch_name'], 'args' => ['owner', 'repo', 'new_branch_name', 'old_branch_name', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_branch'], 'required' => ['owner', 'repo', 'branch'], 'args' => ['owner', 'repo', 'branch', 'instance', 'user']],
		]);
	}

	public function list_branches(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/branches", ['page' => $page, 'limit' => $limit]);
	}

	public function create_branch(string $owner, string $repo, string $new_branch_name, ?string $old_branch_name = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['new_branch_name' => $new_branch_name];
		if ($old_branch_name !== null) $data['old_branch_name'] = $old_branch_name;
		return $client->post("repos/{$owner}/{$repo}/branches", $data);
	}

	public function delete_branch(string $owner, string $repo, string $branch, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/branches/{$branch}");
	}
}
