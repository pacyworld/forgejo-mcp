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
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'index', 'instance', 'user'],
		]
	)]
	public function list_pull_reviews(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'get_pull_review',
		description: 'Get a pull request review.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'review_id' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'index', 'review_id', 'instance', 'user'],
		]
	)]
	public function get_pull_review(string $owner, string $repo, int $index, int $review_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}");
	}

	#[McpTool(
		name: 'list_pull_review_comments',
		description: 'List a review\'s inline comments.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'review_id' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'index', 'review_id', 'instance', 'user'],
		]
	)]
	public function list_pull_review_comments(string $owner, string $repo, int $index, int $review_id, string $instance = '', string $user = ''): array
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
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'event' => ['type' => 'string', 'description' => 'APPROVED|REQUEST_CHANGES|COMMENT'],
				'body' => ['type' => 'string'],
				'comments' => ['type' => 'array', 'description' => 'Inline comments: [{path, body, new_position}]'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'index', 'event', 'instance', 'user'],
		]
	)]
	public function create_pull_review(string $owner, string $repo, int $index, string $event, string $body = '', ?array $comments = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['event' => $event];
		if (!empty($body)) $data['body'] = $body;
		if ($comments !== null) $data['comments'] = $comments;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews", $data);
	}

	#[McpTool(name: 'submit_pull_review', description: 'Submit a pending pull request review.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'review_id' => ['type' => 'integer'], 'event' => ['type' => 'string', 'description' => 'APPROVED|REQUEST_CHANGES|COMMENT'], 'body' => ['type' => 'string'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'review_id', 'event', 'instance', 'user']])]
	public function submit_pull_review(string $owner, string $repo, int $index, int $review_id, string $event, string $body = '', string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['event' => $event];
		if (!empty($body)) $data['body'] = $body;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}", $data);
	}

	#[McpTool(name: 'delete_pull_review', description: 'Delete a pending pull request review.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'review_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'review_id', 'instance', 'user']])]
	public function delete_pull_review(string $owner, string $repo, int $index, int $review_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}");
	}

	#[McpTool(name: 'dismiss_pull_review', description: 'Dismiss a pull request review.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'review_id' => ['type' => 'integer'], 'message' => ['type' => 'string', 'description' => 'Reason'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'review_id', 'message', 'instance', 'user']])]
	public function dismiss_pull_review(string $owner, string $repo, int $index, int $review_id, string $message, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/reviews/{$review_id}/dismissals", ['message' => $message]);
	}

	#[McpTool(name: 'create_review_requests', description: 'Request PR reviews from users and/or teams.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames'], 'team_reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Team names'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'instance', 'user']])]
	public function create_review_requests(string $owner, string $repo, int $index, ?array $reviewers = null, ?array $team_reviewers = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($reviewers !== null) $data['reviewers'] = $reviewers;
		if ($team_reviewers !== null) $data['team_reviewers'] = $team_reviewers;
		return $client->post("repos/{$owner}/{$repo}/pulls/{$index}/requested_reviewers", $data);
	}

	#[McpTool(name: 'delete_review_requests', description: 'Cancel pending PR review requests.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames'], 'team_reviewers' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Team names'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'instance', 'user']])]
	public function delete_review_requests(string $owner, string $repo, int $index, ?array $reviewers = null, ?array $team_reviewers = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($reviewers !== null) $data['reviewers'] = $reviewers;
		if ($team_reviewers !== null) $data['team_reviewers'] = $team_reviewers;
		return $client->delete("repos/{$owner}/{$repo}/pulls/{$index}/requested_reviewers?" . http_build_query($data));
	}
}
