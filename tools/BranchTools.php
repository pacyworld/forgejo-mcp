<?php
/**
 * Forgejo MCP Server — Branch Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class BranchTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'list_branches',
		description: 'List branches of a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'page' => ['type' => 'integer', 'description' => 'Page number (default 1)'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page (default 20)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo'],
		]
	)]
	public function list_branches(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/branches", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'create_branch',
		description: 'Create a new branch in a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'new_branch_name' => ['type' => 'string', 'description' => 'Name for the new branch'],
				'old_branch_name' => ['type' => 'string', 'description' => 'Branch to create from (optional, defaults to default branch)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'new_branch_name'],
		]
	)]
	public function create_branch(string $owner, string $repo, string $new_branch_name, ?string $old_branch_name = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['new_branch_name' => $new_branch_name];
		if ($old_branch_name !== null) $data['old_branch_name'] = $old_branch_name;
		return $client->post("repos/{$owner}/{$repo}/branches", $data);
	}

	#[McpTool(
		name: 'delete_branch',
		description: 'Delete a branch from a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'branch' => ['type' => 'string', 'description' => 'Branch name to delete'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'branch'],
		]
	)]
	public function delete_branch(string $owner, string $repo, string $branch, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/branches/{$branch}");
	}
}
