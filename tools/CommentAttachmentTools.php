<?php
/**
 * Forgejo MCP Server — Comment Attachment Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class CommentAttachmentTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_comment_attachments', description: 'List attachments on an issue/PR comment.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'comment_id', 'instance', 'user']])]
	public function list_comment_attachments(string $owner, string $repo, int $comment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets");
	}

	#[McpTool(name: 'get_comment_attachment', description: 'Get comment attachment metadata.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'comment_id', 'attachment_id', 'instance', 'user']])]
	public function get_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
	}

	#[McpTool(name: 'download_comment_attachment', description: 'Get a comment attachment\'s metadata including browser_download_url.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'comment_id', 'attachment_id', 'instance', 'user']])]
	public function download_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$meta = $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
		$meta['browser_download_url'] = $meta['browser_download_url'] ?? $client->getBaseUrl() . "/attachments/{$meta['uuid']}";
		return $meta;
	}

	#[McpTool(name: 'create_comment_attachment', description: 'Upload an attachment to an issue/PR comment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'filename' => ['type' => 'string'], 'content' => ['type' => 'string', 'description' => 'Base64'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'comment_id', 'filename', 'content', 'instance', 'user']])]
	public function create_comment_attachment(string $owner, string $repo, int $comment_id, string $filename, string $content, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$decoded = base64_decode($content, true);
		if ($decoded === false) {
			throw new \InvalidArgumentException("Invalid base64 content");
		}
		return $client->uploadFile("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets", 'attachment', $filename, $decoded);
	}

	#[McpTool(name: 'edit_comment_attachment', description: 'Rename a comment attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'name' => ['type' => 'string', 'description' => 'New filename'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'comment_id', 'attachment_id', 'name', 'instance', 'user']])]
	public function edit_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}", ['name' => $name]);
	}

	#[McpTool(name: 'delete_comment_attachment', description: 'Delete a comment attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'comment_id', 'attachment_id', 'instance', 'user']])]
	public function delete_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
	}
}
