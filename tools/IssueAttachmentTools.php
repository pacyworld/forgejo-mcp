<?php
/**
 * Forgejo MCP Server — Issue Attachment Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class IssueAttachmentTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_issue_attachments', description: 'List attachments on an issue or pull request.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer', 'description' => 'Issue/PR index'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index']])]
	public function list_issue_attachments(string $owner, string $repo, int $index, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets");
	}

	#[McpTool(name: 'get_issue_attachment', description: 'Get metadata for a single issue/PR attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer', 'description' => 'Attachment ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'attachment_id']])]
	public function get_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
	}

	#[McpTool(name: 'download_issue_attachment', description: 'Download an issue/PR attachment. Returns metadata with browser_download_url for large files.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'attachment_id']])]
	public function download_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$meta = $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
		$meta['browser_download_url'] = $meta['browser_download_url'] ?? $client->getBaseUrl() . "/attachments/{$meta['uuid']}";
		return $meta;
	}

	#[McpTool(name: 'create_issue_attachment', description: 'Upload a new attachment to an issue or pull request. Provide base64-encoded file content.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'filename' => ['type' => 'string', 'description' => 'Filename for the attachment'], 'content' => ['type' => 'string', 'description' => 'Base64-encoded file content'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'filename', 'content']])]
	public function create_issue_attachment(string $owner, string $repo, int $index, string $filename, string $content, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$decoded = base64_decode($content, true);
		if ($decoded === false) {
			throw new \InvalidArgumentException("Invalid base64 content");
		}
		return $client->uploadFile("repos/{$owner}/{$repo}/issues/{$index}/assets", 'attachment', $filename, $decoded);
	}

	#[McpTool(name: 'edit_issue_attachment', description: 'Rename an issue/PR attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'name' => ['type' => 'string', 'description' => 'New filename'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'attachment_id', 'name']])]
	public function edit_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $name, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}", ['name' => $name]);
	}

	#[McpTool(name: 'delete_issue_attachment', description: 'Delete an issue/PR attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'attachment_id']])]
	public function delete_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
	}
}
