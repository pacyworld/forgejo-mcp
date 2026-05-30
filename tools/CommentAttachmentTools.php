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

	#[McpTool(name: 'list_comment_attachments', description: 'List attachments on an issue/PR comment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer', 'description' => 'Comment ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'comment_id']])]
	public function list_comment_attachments(string $owner, string $repo, int $comment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets");
	}

	#[McpTool(name: 'get_comment_attachment', description: 'Get metadata for a single comment attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'comment_id', 'attachment_id']])]
	public function get_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
	}

	#[McpTool(name: 'download_comment_attachment', description: 'Download a comment attachment. Returns metadata with browser_download_url.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'comment_id', 'attachment_id']])]
	public function download_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$meta = $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
		$meta['browser_download_url'] = $meta['browser_download_url'] ?? $client->getBaseUrl() . "/attachments/{$meta['uuid']}";
		return $meta;
	}

	#[McpTool(name: 'create_comment_attachment', description: 'Upload a new attachment to an issue/PR comment. Provide base64-encoded content.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'filename' => ['type' => 'string', 'description' => 'Filename'], 'content' => ['type' => 'string', 'description' => 'Base64-encoded file content'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'comment_id', 'filename', 'content']])]
	public function create_comment_attachment(string $owner, string $repo, int $comment_id, string $filename, string $content, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets", [
			'name' => $filename,
			'content' => $content,
		]);
	}

	#[McpTool(name: 'edit_comment_attachment', description: 'Rename a comment attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'name' => ['type' => 'string', 'description' => 'New filename'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'comment_id', 'attachment_id', 'name']])]
	public function edit_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $name, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}", ['name' => $name]);
	}

	#[McpTool(name: 'delete_comment_attachment', description: 'Delete a comment attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'comment_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'comment_id', 'attachment_id']])]
	public function delete_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
	}
}
