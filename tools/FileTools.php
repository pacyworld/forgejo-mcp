<?php
/**
 * Forgejo MCP Server — File Content Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class FileTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'get_file_content',
		description: 'Get the content of a file from a repository. Returns decoded content and metadata.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'filepath' => ['type' => 'string', 'description' => 'Path to the file'],
				'ref' => ['type' => 'string', 'description' => 'Branch, tag, or commit SHA (optional, defaults to default branch)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'filepath'],
		]
	)]
	public function get_file_content(string $owner, string $repo, string $filepath, ?string $ref = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = [];
		if ($ref !== null) $query['ref'] = $ref;
		$result = $client->get("repos/{$owner}/{$repo}/contents/{$filepath}", $query);

		// Decode base64 content for convenience
		if (isset($result['content']) && isset($result['encoding']) && $result['encoding'] === 'base64') {
			$result['decoded_content'] = base64_decode($result['content']);
		}

		return $result;
	}

	#[McpTool(
		name: 'create_file',
		description: 'Create a new file in a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'filepath' => ['type' => 'string', 'description' => 'Path for the new file'],
				'content' => ['type' => 'string', 'description' => 'File content (plain text, will be base64-encoded)'],
				'message' => ['type' => 'string', 'description' => 'Commit message'],
				'branch' => ['type' => 'string', 'description' => 'Branch to commit to (optional)'],
				'new_branch' => ['type' => 'string', 'description' => 'Create a new branch with this name (optional)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'filepath', 'content', 'message'],
		]
	)]
	public function create_file(string $owner, string $repo, string $filepath, string $content, string $message, ?string $branch = null, ?string $new_branch = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [
			'content' => base64_encode($content),
			'message' => $message,
		];
		if ($branch !== null) $data['branch'] = $branch;
		if ($new_branch !== null) $data['new_branch'] = $new_branch;
		return $client->post("repos/{$owner}/{$repo}/contents/{$filepath}", $data);
	}

	#[McpTool(
		name: 'update_file',
		description: 'Update an existing file in a repository. Requires the current file SHA.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'filepath' => ['type' => 'string', 'description' => 'Path to the file'],
				'content' => ['type' => 'string', 'description' => 'New file content (plain text)'],
				'message' => ['type' => 'string', 'description' => 'Commit message'],
				'sha' => ['type' => 'string', 'description' => 'SHA of the file being replaced (from get_file_content)'],
				'branch' => ['type' => 'string', 'description' => 'Branch to commit to (optional)'],
				'new_branch' => ['type' => 'string', 'description' => 'Create a new branch (optional)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'filepath', 'content', 'message', 'sha'],
		]
	)]
	public function update_file(string $owner, string $repo, string $filepath, string $content, string $message, string $sha, ?string $branch = null, ?string $new_branch = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [
			'content' => base64_encode($content),
			'message' => $message,
			'sha' => $sha,
		];
		if ($branch !== null) $data['branch'] = $branch;
		if ($new_branch !== null) $data['new_branch'] = $new_branch;
		return $client->put("repos/{$owner}/{$repo}/contents/{$filepath}", $data);
	}

	#[McpTool(
		name: 'delete_file',
		description: 'Delete a file from a repository. Requires the current file SHA.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'filepath' => ['type' => 'string', 'description' => 'Path to the file'],
				'message' => ['type' => 'string', 'description' => 'Commit message'],
				'sha' => ['type' => 'string', 'description' => 'SHA of the file to delete'],
				'branch' => ['type' => 'string', 'description' => 'Branch (optional)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'filepath', 'message', 'sha'],
		]
	)]
	public function delete_file(string $owner, string $repo, string $filepath, string $message, string $sha, ?string $branch = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [
			'message' => $message,
			'sha' => $sha,
		];
		if ($branch !== null) $data['branch'] = $branch;
		// Forgejo DELETE for file contents uses a request body
		// We need to send this as a DELETE with body - use post with _method override or custom approach
		// Actually the Forgejo API accepts DELETE with JSON body for this endpoint
		return $client->delete("repos/{$owner}/{$repo}/contents/{$filepath}?" . http_build_query($data));
	}
}
