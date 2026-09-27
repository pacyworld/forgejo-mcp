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

	#[McpTool(name: 'list_issue_attachments', description: 'List attachments on an issue or PR.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'instance', 'user']])]
	public function list_issue_attachments(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets");
	}

	#[McpTool(name: 'get_issue_attachment', description: 'Get issue/PR attachment metadata.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'attachment_id', 'instance', 'user']])]
	public function get_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
	}

	#[McpTool(name: 'download_issue_attachment', description: 'Get an issue/PR attachment\'s metadata including browser_download_url.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'attachment_id', 'instance', 'user']])]
	public function download_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$meta = $client->get("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
		$meta['browser_download_url'] = $meta['browser_download_url'] ?? $client->getBaseUrl() . "/attachments/{$meta['uuid']}";
		return $meta;
	}

	#[McpTool(name: 'create_issue_attachment', description: 'Upload an attachment to an issue or PR.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'filename' => ['type' => 'string'], 'content' => ['type' => 'string', 'description' => 'Base64'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'filename', 'content', 'instance', 'user']])]
	public function create_issue_attachment(string $owner, string $repo, int $index, string $filename, string $content, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$decoded = base64_decode($content, true);
		if ($decoded === false) {
			throw new \InvalidArgumentException("Invalid base64 content");
		}
		return $client->uploadFile("repos/{$owner}/{$repo}/issues/{$index}/assets", 'attachment', $filename, $decoded);
	}

	#[McpTool(name: 'edit_issue_attachment', description: 'Rename an issue/PR attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'name' => ['type' => 'string', 'description' => 'New filename'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'attachment_id', 'name', 'instance', 'user']])]
	public function edit_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}", ['name' => $name]);
	}

	#[McpTool(name: 'delete_issue_attachment', description: 'Delete an issue/PR attachment.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'attachment_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'attachment_id', 'instance', 'user']])]
	public function delete_issue_attachment(string $owner, string $repo, int $index, int $attachment_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/assets/{$attachment_id}");
	}
}
