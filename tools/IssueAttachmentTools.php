<?php
/**
 * Forgejo MCP Server — Issue Attachment Tools
 *
 * Internal handlers for the consolidated `attachment` tool (AttachmentTools,
 * target=issue). Not registered as tools themselves.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use Forgejo\InstanceManager;

class IssueAttachmentTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	public function list_issue_attachments(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets");
	}

	public function get_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
	}

	public function download_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$meta = $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
		$meta['browser_download_url'] = $meta['browser_download_url'] ?? $client->getBaseUrl() . "/attachments/{$meta['uuid']}";
		return $meta;
	}

	public function create_issue_attachment(string $owner, string $repo, int $index, string $filename, string $content, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$decoded = base64_decode($content, true);
		if ($decoded === false) {
			throw new \InvalidArgumentException("Invalid base64 content");
		}
		return $client->uploadFile("repos/{$owner}/{$repo}/issues/{$index}/assets", 'attachment', $filename, $decoded);
	}

	public function edit_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}", ['name' => $name]);
	}

	public function delete_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
	}
}
