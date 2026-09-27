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
		description: 'List a repository\'s branches.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'instance', 'user'],
		]
	)]
	public function list_branches(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/branches", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'create_branch',
		description: 'Create a branch.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'new_branch_name' => ['type' => 'string'],
				'old_branch_name' => ['type' => 'string', 'description' => 'Source branch; default branch if omitted'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'new_branch_name', 'instance', 'user'],
		]
	)]
	public function create_branch(string $owner, string $repo, string $new_branch_name, ?string $old_branch_name = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['new_branch_name' => $new_branch_name];
		if ($old_branch_name !== null) $data['old_branch_name'] = $old_branch_name;
		return $client->post("repos/{$owner}/{$repo}/branches", $data);
	}

	#[McpTool(
		name: 'delete_branch',
		description: 'Delete a branch.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'branch' => ['type' => 'string'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'branch', 'instance', 'user'],
		]
	)]
	public function delete_branch(string $owner, string $repo, string $branch, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/branches/{$branch}");
	}
}
