<?php
/**
 * Forgejo MCP Server — Pull Request Review Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class ReviewTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'list_pull_reviews',
		description: 'List reviews on a pull request.',
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
	public function list_pull_reviews(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'get_pull_review',
		description: 'Get a specific review by ID.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'review_id' => ['type' => 'integer', 'description' => 'Review ID'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index', 'review_id'],
		]
	)]
	public function get_pull_review(string $owner, string $repo, int $index, int $review_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}");
	}

	#[McpTool(
		name: 'list_pull_review_comments',
		description: 'List comments within a pull request review.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'review_id' => ['type' => 'integer', 'description' => 'Review ID'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index', 'review_id'],
		]
	)]
	public function list_pull_review_comments(string $owner, string $repo, int $index, int $review_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}/comments");
	}

	#[McpTool(
		name: 'create_pull_review',
		description: 'Submit a review on a pull request.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'PR index number'],
				'event' => ['type' => 'string', 'description' => 'Review action: APPROVED, REQUEST_CHANGES, COMMENT'],
				'body' => ['type' => 'string', 'description' => 'Review body text'],
				'comments' => ['type' => 'array', 'description' => 'Inline comments array [{path, body, new_position}]'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index', 'event'],
		]
	)]
	public function create_pull_review(string $owner, string $repo, int $index, string $event, string $body = '', ?array $comments = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['event' => $event];
		if (!empty($body)) $data['body'] = $body;
		if ($comments !== null) $data['comments'] = $comments;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews", $data);
	}

	#[McpTool(name: 'submit_pull_review', description: 'Submit a pending pull request review.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'review_id' => ['type' => 'integer'], 'event' => ['type' => 'string', 'description' => 'APPROVED, REQUEST_CHANGES, or COMMENT'], 'body' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'review_id', 'event']])]
	public function submit_pull_review(string $owner, string $repo, int $index, int $review_id, string $event, string $body = '', ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['event' => $event];
		if (!empty($body)) $data['body'] = $body;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}", $data);
	}

	#[McpTool(name: 'delete_pull_review', description: 'Delete a pending pull request review.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'review_id' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'review_id']])]
	public function delete_pull_review(string $owner, string $repo, int $index, int $review_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}");
	}

	#[McpTool(name: 'dismiss_pull_review', description: 'Dismiss a pull request review.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'review_id' => ['type' => 'integer'], 'message' => ['type' => 'string', 'description' => 'Reason for dismissal'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'review_id', 'message']])]
	public function dismiss_pull_review(string $owner, string $repo, int $index, int $review_id, string $message, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}/dismissals", ['message' => $message]);
	}

	#[McpTool(name: 'create_review_requests', description: 'Request reviews from specific users or teams.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames to request review from'], 'team_reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Team names to request review from'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index']])]
	public function create_review_requests(string $owner, string $repo, int $index, ?array $reviewers = null, ?array $team_reviewers = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($reviewers !== null) $data['reviewers'] = $reviewers;
		if ($team_reviewers !== null) $data['team_reviewers'] = $team_reviewers;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/requested_reviewers", $data);
	}

	#[McpTool(name: 'delete_review_requests', description: 'Cancel pending review requests.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames to cancel'], 'team_reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Team names to cancel'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index']])]
	public function delete_review_requests(string $owner, string $repo, int $index, ?array $reviewers = null, ?array $team_reviewers = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($reviewers !== null) $data['reviewers'] = $reviewers;
		if ($team_reviewers !== null) $data['team_reviewers'] = $team_reviewers;
		return $client->delete("repos/{$owner}/{$repo}/pulls/{$index}/requested_reviewers?" . http_build_query($data));
	}
}
