<?php
/**
 * Forgejo MCP Server — Pull Request Review Tools
 *
 * Consolidated: one `pull_review` tool whose `action` selects the operation.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class ReviewTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'pull_review',
		description: 'Manage pull request reviews. Actions and their required parameters: list(owner, repo, index), get(owner, repo, index, review_id), comments(owner, repo, index, review_id), create(owner, repo, index, event; optional body, comments; submits immediately), submit(owner, repo, index, review_id, event; a pending review), delete(owner, repo, index, review_id), dismiss(owner, repo, index, review_id, message), request_reviewers(owner, repo, index; optional reviewers, team_reviewers), delete_requests(owner, repo, index; optional reviewers, team_reviewers). All take owner, repo.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'get', 'comments', 'create', 'submit', 'delete', 'dismiss', 'request_reviewers', 'delete_requests']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer', 'description' => 'Pull request number'],
				'review_id' => ['type' => 'integer'],
				'event' => ['type' => 'string', 'description' => 'APPROVED|REQUEST_CHANGES|COMMENT'],
				'body' => ['type' => 'string'],
				'comments' => ['type' => 'array', 'description' => 'Inline comments: [{path, body, new_position}]'],
				'message' => ['type' => 'string', 'description' => 'Dismissal reason'],
				'reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames'],
				'team_reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Team names'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_pull_reviews' => 'pull_review action=list',
			'get_pull_review' => 'pull_review action=get',
			'list_pull_review_comments' => 'pull_review action=comments',
			'create_pull_review' => 'pull_review action=create',
			'submit_pull_review' => 'pull_review action=submit',
			'delete_pull_review' => 'pull_review action=delete',
			'dismiss_pull_review' => 'pull_review action=dismiss',
			'create_review_requests' => 'pull_review action=request_reviewers',
			'delete_review_requests' => 'pull_review action=delete_requests',
		]
	)]
	public function pull_review(string $action, ?string $owner = null, ?string $repo = null, ?int $index = null, ?int $review_id = null, ?string $event = null, ?string $body = null, ?array $comments = null, ?string $message = null, ?array $reviewers = null, ?array $team_reviewers = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('pull_review', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_pull_reviews'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'page', 'limit', 'instance', 'user']],
			'get' => ['handler' => [$this, 'get_pull_review'], 'required' => ['owner', 'repo', 'index', 'review_id'], 'args' => ['owner', 'repo', 'index', 'review_id', 'instance', 'user']],
			'comments' => ['handler' => [$this, 'list_pull_review_comments'], 'required' => ['owner', 'repo', 'index', 'review_id'], 'args' => ['owner', 'repo', 'index', 'review_id', 'instance', 'user']],
			'create' => ['handler' => [$this, 'create_pull_review'], 'required' => ['owner', 'repo', 'index', 'event'], 'args' => ['owner', 'repo', 'index', 'event', 'body', 'comments', 'instance', 'user']],
			'submit' => ['handler' => [$this, 'submit_pull_review'], 'required' => ['owner', 'repo', 'index', 'review_id', 'event'], 'args' => ['owner', 'repo', 'index', 'review_id', 'event', 'body', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_pull_review'], 'required' => ['owner', 'repo', 'index', 'review_id'], 'args' => ['owner', 'repo', 'index', 'review_id', 'instance', 'user']],
			'dismiss' => ['handler' => [$this, 'dismiss_pull_review'], 'required' => ['owner', 'repo', 'index', 'review_id', 'message'], 'args' => ['owner', 'repo', 'index', 'review_id', 'message', 'instance', 'user']],
			'request_reviewers' => ['handler' => [$this, 'create_review_requests'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'reviewers', 'team_reviewers', 'instance', 'user']],
			'delete_requests' => ['handler' => [$this, 'delete_review_requests'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'reviewers', 'team_reviewers', 'instance', 'user']],
		]);
	}

	public function list_pull_reviews(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews", ['page' => $page, 'limit' => $limit]);
	}

	public function get_pull_review(string $owner, string $repo, int $index, int $review_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}");
	}

	public function list_pull_review_comments(string $owner, string $repo, int $index, int $review_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}/comments");
	}

	public function create_pull_review(string $owner, string $repo, int $index, string $event, string $body = '', ?array $comments = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['event' => $event];
		if (!empty($body)) $data['body'] = $body;
		if ($comments !== null) $data['comments'] = $comments;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews", $data);
	}

	public function submit_pull_review(string $owner, string $repo, int $index, int $review_id, string $event, string $body = '', string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['event' => $event];
		if (!empty($body)) $data['body'] = $body;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}", $data);
	}

	public function delete_pull_review(string $owner, string $repo, int $index, int $review_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}");
	}

	public function dismiss_pull_review(string $owner, string $repo, int $index, int $review_id, string $message, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}/dismissals", ['message' => $message]);
	}

	public function create_review_requests(string $owner, string $repo, int $index, ?array $reviewers = null, ?array $team_reviewers = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($reviewers !== null) $data['reviewers'] = $reviewers;
		if ($team_reviewers !== null) $data['team_reviewers'] = $team_reviewers;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/requested_reviewers", $data);
	}

	public function delete_review_requests(string $owner, string $repo, int $index, ?array $reviewers = null, ?array $team_reviewers = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($reviewers !== null) $data['reviewers'] = $reviewers;
		if ($team_reviewers !== null) $data['team_reviewers'] = $team_reviewers;
		return $client->delete("repos/{$owner}/{$repo}/pulls/{$index}/requested_reviewers?" . http_build_query($data));
	}
}
