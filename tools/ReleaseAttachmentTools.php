<?php
/**
 * Forgejo MCP Server — Release Attachment Tools
 *
 * Internal handlers for the consolidated `attachment` tool (AttachmentTools,
 * target=release). Not registered as tools themselves.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use Forgejo\InstanceManager;

class ReleaseAttachmentTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	public function list_release_attachments(string $owner, string $repo, int $release_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/{$release_id}/assets");
	}

	public function get_release_attachment(string $owner, string $repo, int $release_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/{$release_id}/assets/{$attachment_id}");
	}

	public function delete_release_attachment(string $owner, string $repo, int $release_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/releases/{$release_id}/assets/{$attachment_id}");
	}

	public function edit_release_attachment(string $owner, string $repo, int $release_id, int $attachment_id, string $name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/releases/{$release_id}/assets/{$attachment_id}", ['name' => $name]);
	}

	public function create_release_attachment(string $owner, string $repo, int $release_id, string $filename, string $content, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$decoded = base64_decode($content, true);
		if ($decoded === false) {
			throw new \InvalidArgumentException("Invalid base64 content");
		}
		return $client->uploadFile("repos/{$owner}/{$repo}/releases/{$release_id}/assets?name={$filename}", 'attachment', $filename, $decoded);
	}

	public function download_release_attachment(string $owner, string $repo, int $release_id, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$meta = $client->get("repos/{$owner}/{$repo}/releases/{$release_id}/assets/{$attachment_id}");
		$meta['browser_download_url'] = $meta['browser_download_url'] ?? $client->getBaseUrl() . "/" . ($meta['download_url'] ?? '');
		return $meta;
	}
}
