<?php
/**
 * Forgejo MCP Server — Comment Attachment Tools
 *
 * Internal handlers for the consolidated `attachment` tool (AttachmentTools,
 * target=comment). Not registered as tools themselves.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use Forgejo\InstanceManager;

class CommentAttachmentTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	public function list_comment_attachments(string $owner, string $repo, int $comment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets");
	}

	public function get_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
	}

	public function download_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$meta = $client->get("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
		$meta['browser_download_url'] = $meta['browser_download_url'] ?? $client->getBaseUrl() . "/attachments/{$meta['uuid']}";
		return $meta;
	}

	public function create_comment_attachment(string $owner, string $repo, int $comment_id, string $filename, string $content, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$decoded = base64_decode($content, true);
		if ($decoded === false) {
			throw new \InvalidArgumentException("Invalid base64 content");
		}
		return $client->uploadFile("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets", 'attachment', $filename, $decoded);
	}

	public function edit_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}", ['name' => $name]);
	}

	public function delete_comment_attachment(string $owner, string $repo, int $comment_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/comments/{$comment_id}/assets/{$attachment_id}");
	}
}
