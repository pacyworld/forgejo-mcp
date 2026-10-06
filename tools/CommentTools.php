<?php
/**
 * Forgejo MCP Server — Issue Comment Tools
 *
 * Consolidated: one `issue_comment` tool whose `action` selects the operation.
 * The per-operation methods remain as internal handlers.
 * Attachments live in the `attachment` tool (target=comment).
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class CommentTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'issue_comment',
		description: 'Manage comments on an issue or PR. Actions and their required parameters: list(owner, repo, index), get(owner, repo, id), create(owner, repo, index, body), edit(owner, repo, id, body), delete(owner, repo, id). Attachments: use attachment target=comment.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'get', 'create', 'edit', 'delete']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer', 'description' => 'Issue or PR number'],
				'id' => ['type' => 'integer', 'description' => 'Comment ID'],
				'body' => ['type' => 'string', 'description' => 'Markdown'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_issue_comments' => 'issue_comment action=list',
			'get_issue_comment' => 'issue_comment action=get',
			'create_issue_comment' => 'issue_comment action=create',
			'edit_issue_comment' => 'issue_comment action=edit',
			'delete_issue_comment' => 'issue_comment action=delete',
		]
	)]
	public function issue_comment(string $action, ?string $owner = null, ?string $repo = null, ?int $index = null, ?int $id = null, ?string $body = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('issue_comment', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_issue_comments'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'page', 'limit', 'instance', 'user']],
			'get' => ['handler' => [$this, 'get_issue_comment'], 'required' => ['owner', 'repo', 'id'], 'args' => ['owner', 'repo', 'id', 'instance', 'user']],
			'create' => ['handler' => [$this, 'create_issue_comment'], 'required' => ['owner', 'repo', 'index', 'body'], 'args' => ['owner', 'repo', 'index', 'body', 'instance', 'user']],
			'edit' => ['handler' => [$this, 'edit_issue_comment'], 'required' => ['owner', 'repo', 'id', 'body'], 'args' => ['owner', 'repo', 'id', 'body', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_issue_comment'], 'required' => ['owner', 'repo', 'id'], 'args' => ['owner', 'repo', 'id', 'instance', 'user']],
		]);
	}

	public function list_issue_comments(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/comments", ['page' => $page, 'limit' => $limit]);
	}

	public function get_issue_comment(string $owner, string $repo, int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$id}");
	}

	public function create_issue_comment(string $owner, string $repo, int $index, string $body, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/comments", ['body' => $body]);
	}

	public function edit_issue_comment(string $owner, string $repo, int $id, string $body, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/comments/{$id}", ['body' => $body]);
	}

	public function delete_issue_comment(string $owner, string $repo, int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/comments/{$id}");
	}
}
