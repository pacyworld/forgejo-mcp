<?php
/**
 * Forgejo MCP Server — Release Attachment Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class ReleaseAttachmentTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_release_attachments', description: 'List attachments of a release.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'release_id' => ['type' => 'integer', 'description' => 'Release ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'release_id']])]
	public function list_release_attachments(string $owner, string $repo, int $release_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/{$release_id}/assets");
	}

	#[McpTool(name: 'get_release_attachment', description: 'Get metadata of a release attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'release_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer', 'description' => 'Attachment ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'release_id', 'attachment_id']])]
	public function get_release_attachment(string $owner, string $repo, int $release_id, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/{$release_id}/assets/{$attachment_id}");
	}

	#[McpTool(name: 'delete_release_attachment', description: 'Delete a release attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'release_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'release_id', 'attachment_id']])]
	public function delete_release_attachment(string $owner, string $repo, int $release_id, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/releases/{$release_id}/assets/{$attachment_id}");
	}

	#[McpTool(name: 'edit_release_attachment', description: 'Edit a release attachment name.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'release_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'name' => ['type' => 'string', 'description' => 'New attachment name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'release_id', 'attachment_id', 'name']])]
	public function edit_release_attachment(string $owner, string $repo, int $release_id, int $attachment_id, string $name, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/releases/{$release_id}/assets/{$attachment_id}", ['name' => $name]);
	}

	#[McpTool(name: 'create_release_attachment', description: 'Upload an attachment to a release. Provide base64-encoded file content.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'release_id' => ['type' => 'integer'], 'filename' => ['type' => 'string', 'description' => 'Filename for the attachment'], 'content' => ['type' => 'string', 'description' => 'Base64-encoded file content'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'release_id', 'filename', 'content']])]
	public function create_release_attachment(string $owner, string $repo, int $release_id, string $filename, string $content, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$decoded = base64_decode($content, true);
		if ($decoded === false) {
			throw new \InvalidArgumentException("Invalid base64 content");
		}
		return $client->uploadFile("repos/{$owner}/{$repo}/releases/{$release_id}/assets?name={$filename}", 'attachment', $filename, $decoded);
	}

	#[McpTool(name: 'download_release_attachment', description: 'Download a release attachment. Returns metadata with browser_download_url.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'release_id' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'release_id', 'attachment_id']])]
	public function download_release_attachment(string $owner, string $repo, int $release_id, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$meta = $client->get("repos/{$owner}/{$repo}/releases/{$release_id}/assets/{$attachment_id}");
		$meta['browser_download_url'] = $meta['browser_download_url'] ?? $client->getBaseUrl() . "/" . ($meta['download_url'] ?? '');
		return $meta;
	}
}
