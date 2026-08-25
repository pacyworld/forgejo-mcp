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
use Forgejo\TimeoutException;

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
		readOnlyHint: true,
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
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name'],
				'user' => ['type' => 'string', 'description' => 'User identity'],
			],
			'required' => ['owner', 'repo', 'instance', 'user'],
		]
	)]
	public function list_repo_pull_requests(string $owner, string $repo, string $state = 'open', ?string $sort = null, ?string $labels = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
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
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name'],
				'user' => ['type' => 'string', 'description' => 'User identity'],
			],
			'required' => ['owner', 'repo', 'index', 'instance', 'user'],
		]
	)]
	public function get_pull_request_by_index(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
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
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name'],
				'user' => ['type' => 'string', 'description' => 'User identity'],
			],
			'required' => ['owner', 'repo', 'title', 'head', 'base', 'instance', 'user'],
		]
	)]
	public function create_pull_request(string $owner, string $repo, string $title, string $head, string $base, string $body = '', ?array $labels = null, ?int $milestone = null, ?array $assignees = null, string $instance = '', string $user = ''): array
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
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name'],
				'user' => ['type' => 'string', 'description' => 'User identity'],
			],
			'required' => ['owner', 'repo', 'index', 'instance', 'user'],
		]
	)]
	public function update_pull_request(string $owner, string $repo, int $index, ?string $title = null, ?string $body = null, ?string $state = null, ?string $base = null, string $instance = '', string $user = ''): array
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
			'timeout' => ['type' => 'integer', 'description' => 'Request timeout in seconds (default 90; merges on large repositories can exceed the instance default)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name'],
				'user' => ['type' => 'string', 'description' => 'User identity'],
			],
			'required' => ['owner', 'repo', 'index', 'Do', 'instance', 'user'],
		]
	)]
	public function merge_pull_request(string $owner, string $repo, int $index, string $Do, ?string $merge_message_field = null, bool $delete_branch_after_merge = false, int $timeout = 90, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['Do' => $Do, 'delete_branch_after_merge' => $delete_branch_after_merge];
		if ($merge_message_field !== null) $data['merge_message_field'] = $merge_message_field;

		try {
			return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/merge", $data, $timeout);
		} catch (TimeoutException $e) {
			return $this->verifyAfterMergeTimeout($client, $owner, $repo, $index);
		}
	}

	/**
	 * Ground a timed-out merge in the PR's actual state.
	 *
	 * Forgejo >= 14 continues a merge server-side after the client
	 * disconnects (context.WithoutCancel, bounded by Git.Timeout.Default),
	 * so a client-side timeout does NOT mean the merge failed. A cheap
	 * follow-up GET tells the caller whether the PR is already merged or
	 * still being processed — and in both cases that no retry is needed.
	 *
	 * @param  \Forgejo\Client $client API client for the instance
	 * @param  string          $owner  Repository owner
	 * @param  string          $repo   Repository name
	 * @param  int             $index  PR index number
	 * @return array                   Grounded status result
	 */
	private function verifyAfterMergeTimeout(\Forgejo\Client $client, string $owner, string $repo, int $index): array
	{
		try {
			$pr = $client->get("repos/{$owner}/{$repo}/pulls/{$index}", [], 15);
		} catch (\Throwable $e) {
			return [
				'status' => 'unknown',
				'message' => "The merge request timed out and the follow-up status check failed ({$e->getMessage()}). "
					. 'The merge may still be processing or may have already completed on the server. '
					. 'Do not retry unless an error was returned. Check with get_pull_request_by_index.',
			];
		}

		if (!empty($pr['merged'])) {
			$sha = $pr['merge_commit_sha'] ?? null;
			return [
				'status' => 'merged',
				'merged' => true,
				'merge_commit_sha' => $sha,
				'message' => "The merge request timed out client-side, but PR #{$index} is MERGED"
					. ($sha ? " (commit {$sha})" : '') . '. No retry needed.',
			];
		}

		$state = $pr['state'] ?? 'unknown';
		$mergeable = $pr['mergeable'] ?? null;
		return [
			'status' => 'in_progress',
			'merged' => false,
			'pr_state' => $state,
			'mergeable' => $mergeable,
			'message' => "The merge request timed out, but this is not a failure: the server continues processing "
				. "merges after the client disconnects. PR #{$index} is still {$state}"
				. ($mergeable ? ' and mergeable' : '')
				. ', so the merge is most likely still running on the server. Do NOT retry the merge. '
				. 'Re-check with get_pull_request_by_index in a few minutes.',
		];
	}

	#[McpTool(
		name: 'list_pull_request_files',
		description: 'List files changed in a pull request.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'page' => ['type' => 'integer', 'description' => 'Page number'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name'],
				'user' => ['type' => 'string', 'description' => 'User identity'],
			],
			'required' => ['owner', 'repo', 'index', 'instance', 'user'],
		]
	)]
	public function list_pull_request_files(string $owner, string $repo, int $index, int $page = 1, int $limit = 50, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/files", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'get_pull_request_diff',
		description: 'Get the diff of a pull request.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name'],
				'user' => ['type' => 'string', 'description' => 'User identity'],
			],
			'required' => ['owner', 'repo', 'index', 'instance', 'user'],
		]
	)]
	public function get_pull_request_diff(string $owner, string $repo, int $index, string $instance = '', string $user = ''): string
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->getRaw("repos/{$owner}/{$repo}/pulls/{$index}.diff");
	}
}
