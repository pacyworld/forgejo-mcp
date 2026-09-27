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
		description: 'List comments on an issue or PR.',
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
	public function list_issue_comments(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/comments", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'get_issue_comment',
		description: 'Get a comment by ID.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'id' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'id', 'instance', 'user'],
		]
	)]
	public function get_issue_comment(string $owner, string $repo, int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$id}");
	}

	#[McpTool(
		name: 'create_issue_comment',
		description: 'Comment on an issue or PR.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'body' => ['type' => 'string', 'description' => 'Markdown'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'index', 'body', 'instance', 'user'],
		]
	)]
	public function create_issue_comment(string $owner, string $repo, int $index, string $body, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/comments", ['body' => $body]);
	}

	#[McpTool(
		name: 'edit_issue_comment',
		description: 'Replace a comment\'s body.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'id' => ['type' => 'integer'],
				'body' => ['type' => 'string', 'description' => 'Markdown'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'id', 'body', 'instance', 'user'],
		]
	)]
	public function edit_issue_comment(string $owner, string $repo, int $id, string $body, string $instance = '', string $user = ''): array
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
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'id' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'id', 'instance', 'user'],
		]
	)]
	public function delete_issue_comment(string $owner, string $repo, int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/comments/{$id}");
	}
}
