<?php
/**
 * Forgejo MCP Server — Pull Request Tools
 *
 * High-traffic tools stay standalone (create_pull_request,
 * merge_pull_request); the rest are consolidated into `pull_request`.
 * Reviews live in the `pull_request_review` tool (ReviewTools).
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;
use Forgejo\TimeoutException;

class PullRequestTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'pull_request',
		description: 'Read and update pull requests. Actions and their required parameters: list(owner, repo; optional state, sort, labels), get(owner, repo, index), update(owner, repo, index; only given fields change; optional title, body, state, base), files(owner, repo, index), diff(owner, repo, index). Create with create_pull_request, merge with merge_pull_request, reviews via pull_review.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'get', 'update', 'files', 'diff']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer', 'description' => 'Pull request number'],
				'state' => ['type' => 'string', 'description' => 'open|closed|all (list, default open); open|closed (update)'],
				'sort' => ['type' => 'string', 'description' => 'oldest|recentupdate|leastupdate|mostcomment|leastcomment|priority'],
				'labels' => ['type' => 'string', 'description' => 'Comma-separated label IDs'],
				'title' => ['type' => 'string'],
				'body' => ['type' => 'string'],
				'base' => ['type' => 'string', 'description' => 'New target branch'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20 (50 for files)'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_repo_pull_requests' => 'pull_request action=list',
			'get_pull_request_by_index' => 'pull_request action=get',
			'update_pull_request' => 'pull_request action=update',
			'list_pull_request_files' => 'pull_request action=files',
			'get_pull_request_diff' => 'pull_request action=diff',
		]
	)]
	public function pull_request(string $action, ?string $owner = null, ?string $repo = null, ?int $index = null, ?string $state = null, ?string $sort = null, ?string $labels = null, ?string $title = null, ?string $body = null, ?string $base = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('pull_request', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_repo_pull_requests'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'state', 'sort', 'labels', 'page', 'limit', 'instance', 'user']],
			'get' => ['handler' => [$this, 'get_pull_request_by_index'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'instance', 'user']],
			'update' => ['handler' => [$this, 'update_pull_request'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'title', 'body', 'state', 'base', 'instance', 'user']],
			'files' => ['handler' => [$this, 'list_pull_request_files'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'page', 'limit', 'instance', 'user']],
			'diff' => ['handler' => [$this, 'get_pull_request_diff'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'instance', 'user']],
		]);
	}

	#[McpTool(
		name: 'create_pull_request',
		description: 'Create a pull request.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'title' => ['type' => 'string'],
				'body' => ['type' => 'string', 'description' => 'Markdown'],
				'head' => ['type' => 'string', 'description' => 'Source branch, or fork_owner:branch'],
				'base' => ['type' => 'string', 'description' => 'Target branch'],
				'labels' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Label IDs'],
				'milestone' => ['type' => 'integer', 'description' => 'Milestone ID'],
				'assignees' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
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
		name: 'merge_pull_request',
		description: 'Merge a pull request. On timeout the PR state is checked and reported as merged or in_progress; that is not a failure — never retry, check the PR state later with pull_request action=get.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'Do' => ['type' => 'string', 'description' => 'merge|rebase|rebase-merge|squash|manually-merged'],
				'merge_message_field' => ['type' => 'string', 'description' => 'Merge commit message'],
				'delete_branch_after_merge' => ['type' => 'boolean', 'description' => 'Delete head branch (default false)'],
				'timeout' => ['type' => 'integer', 'description' => 'Seconds (default 90)'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
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

	public function list_repo_pull_requests(string $owner, string $repo, string $state = 'open', ?string $sort = null, ?string $labels = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['state' => $state, 'page' => $page, 'limit' => $limit];
		if ($sort !== null) $query['sort'] = $sort;
		if ($labels !== null) $query['labels'] = $labels;
		return $client->get("repos/{$owner}/{$repo}/pulls", $query);
	}

	public function get_pull_request_by_index(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}");
	}

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

	public function list_pull_request_files(string $owner, string $repo, int $index, int $page = 1, int $limit = 50, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/files", ['page' => $page, 'limit' => $limit]);
	}

	public function get_pull_request_diff(string $owner, string $repo, int $index, string $instance = '', string $user = ''): string
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->getRaw("repos/{$owner}/{$repo}/pulls/{$index}.diff");
	}

	/**
	 * Ground a timed-out merge in the PR's actual state.
	 *
	 * Forgejo >= 14 continues a merge server-side after the client
	 * disconnects (context.WithoutCancel, bounded by Git.Timeout.Default),
	 * so a client-side timeout does NOT mean the merge failed. A cheap
	 * follow-up GET tells the caller whether the PR is already merged or
	 * still being processed — and in both cases that no retry is needed.
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
					. 'Do not retry unless an error was returned. Check PR state with pull_request action=get.',
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
				. 'Re-check PR state in a few minutes with pull_request action=get.',
		];
	}
}
