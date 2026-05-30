<?php
/**
 * Forgejo MCP Server — Issue Comment Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class CommentTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'list_issue_comments',
		description: 'List comments on an issue.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'Issue index number'],
				'page' => ['type' => 'integer', 'description' => 'Page number'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index'],
		]
	)]
	public function list_issue_comments(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/comments", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'get_issue_comment',
		description: 'Get a specific comment by ID.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'id' => ['type' => 'integer', 'description' => 'Comment ID'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'id'],
		]
	)]
	public function get_issue_comment(string $owner, string $repo, int $id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$id}");
	}

	#[McpTool(
		name: 'create_issue_comment',
		description: 'Add a comment to an issue.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'Issue index number'],
				'body' => ['type' => 'string', 'description' => 'Comment body (Markdown)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index', 'body'],
		]
	)]
	public function create_issue_comment(string $owner, string $repo, int $index, string $body, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/comments", ['body' => $body]);
	}

	#[McpTool(
		name: 'edit_issue_comment',
		description: 'Edit an existing comment.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'id' => ['type' => 'integer', 'description' => 'Comment ID'],
				'body' => ['type' => 'string', 'description' => 'New comment body'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'id', 'body'],
		]
	)]
	public function edit_issue_comment(string $owner, string $repo, int $id, string $body, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/comments/{$id}", ['body' => $body]);
	}

	#[McpTool(
		name: 'delete_issue_comment',
		description: 'Delete a comment.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'id' => ['type' => 'integer', 'description' => 'Comment ID'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'id'],
		]
	)]
	public function delete_issue_comment(string $owner, string $repo, int $id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/comments/{$id}");
	}
}
