<?php
/**
 * Forgejo MCP Server — Pull Request Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class PullRequestTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'list_repo_pull_requests',
		description: 'List pull requests in a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'state' => ['type' => 'string', 'description' => 'State filter: open, closed, all (default open)'],
				'sort' => ['type' => 'string', 'description' => 'Sort: oldest, recentupdate, leastupdate, mostcomment, leastcomment, priority'],
				'labels' => ['type' => 'string', 'description' => 'Comma-separated label IDs'],
				'page' => ['type' => 'integer', 'description' => 'Page number'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo'],
		]
	)]
	public function list_repo_pull_requests(string $owner, string $repo, string $state = 'open', ?string $sort = null, ?string $labels = null, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['state' => $state, 'page' => $page, 'limit' => $limit];
		if ($sort !== null) $query['sort'] = $sort;
		if ($labels !== null) $query['labels'] = $labels;
		return $client->get("repos/{$owner}/{$repo}/pulls", $query);
	}

	#[McpTool(
		name: 'get_pull_request_by_index',
		description: 'Get a specific pull request by index.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index'],
		]
	)]
	public function get_pull_request_by_index(string $owner, string $repo, int $index, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}");
	}

	#[McpTool(
		name: 'create_pull_request',
		description: 'Create a new pull request.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'title' => ['type' => 'string', 'description' => 'PR title'],
				'body' => ['type' => 'string', 'description' => 'PR body (Markdown)'],
				'head' => ['type' => 'string', 'description' => 'Source branch (or fork_owner:branch)'],
				'base' => ['type' => 'string', 'description' => 'Target branch'],
				'labels' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Label IDs'],
				'milestone' => ['type' => 'integer', 'description' => 'Milestone ID'],
				'assignees' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Assignee usernames'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'title', 'head', 'base'],
		]
	)]
	public function create_pull_request(string $owner, string $repo, string $title, string $head, string $base, string $body = '', ?array $labels = null, ?int $milestone = null, ?array $assignees = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['title' => $title, 'head' => $head, 'base' => $base];
		if (!empty($body)) $data['body'] = $body;
		if ($labels !== null) $data['labels'] = $labels;
		if ($milestone !== null) $data['milestone'] = $milestone;
		if ($assignees !== null) $data['assignees'] = $assignees;
		return $client->post("repos/{$owner}/{$repo}/pulls", $data);
	}

	#[McpTool(
		name: 'update_pull_request',
		description: 'Update a pull request.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'title' => ['type' => 'string', 'description' => 'New title'],
				'body' => ['type' => 'string', 'description' => 'New body'],
				'state' => ['type' => 'string', 'description' => 'New state: open or closed'],
				'base' => ['type' => 'string', 'description' => 'New base branch'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index'],
		]
	)]
	public function update_pull_request(string $owner, string $repo, int $index, ?string $title = null, ?string $body = null, ?string $state = null, ?string $base = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($title !== null) $data['title'] = $title;
		if ($body !== null) $data['body'] = $body;
		if ($state !== null) $data['state'] = $state;
		if ($base !== null) $data['base'] = $base;
		return $client->patch("repos/{$owner}/{$repo}/pulls/{$index}", $data);
	}

	#[McpTool(
		name: 'merge_pull_request',
		description: 'Merge a pull request.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'Do' => ['type' => 'string', 'description' => 'Merge method: merge, rebase, rebase-merge, squash, manually-merged'],
				'merge_message_field' => ['type' => 'string', 'description' => 'Merge commit message'],
				'delete_branch_after_merge' => ['type' => 'boolean', 'description' => 'Delete head branch after merge'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index', 'Do'],
		]
	)]
	public function merge_pull_request(string $owner, string $repo, int $index, string $Do, ?string $merge_message_field = null, bool $delete_branch_after_merge = false, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['Do' => $Do, 'delete_branch_after_merge' => $delete_branch_after_merge];
		if ($merge_message_field !== null) $data['merge_message_field'] = $merge_message_field;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/merge", $data);
	}

	#[McpTool(
		name: 'list_pull_request_files',
		description: 'List files changed in a pull request.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'page' => ['type' => 'integer', 'description' => 'Page number'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index'],
		]
	)]
	public function list_pull_request_files(string $owner, string $repo, int $index, int $page = 1, int $limit = 50, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/files", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'get_pull_request_diff',
		description: 'Get the diff of a pull request.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index'],
		]
	)]
	public function get_pull_request_diff(string $owner, string $repo, int $index, ?string $instance = null, ?string $user = null): string
	{
		$client = $this->manager->getClient($instance, $user);
		$result = $client->get("repos/{$owner}/{$repo}/pulls/{$index}.diff");
		// The diff endpoint returns raw text, but our client decodes JSON
		// If it fails JSON decode, we get an exception - need to handle raw response
		return is_array($result) ? json_encode($result) : (string)$result;
	}
}
