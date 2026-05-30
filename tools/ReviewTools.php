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
}
