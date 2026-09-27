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
		description: 'Get a repository file: metadata (incl. sha) plus decoded_content.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'filepath' => ['type' => 'string'],
				'ref' => ['type' => 'string', 'description' => 'Branch, tag or SHA; default branch if omitted'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'filepath', 'instance', 'user'],
		]
	)]
	public function get_file_content(string $owner, string $repo, string $filepath, ?string $ref = null, string $instance = '', string $user = ''): array
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
		description: 'Create a file and commit it.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'filepath' => ['type' => 'string'],
				'content' => ['type' => 'string', 'description' => 'Plain text (encoded by the server)'],
				'message' => ['type' => 'string', 'description' => 'Commit message'],
				'branch' => ['type' => 'string', 'description' => 'Branch to commit to'],
				'new_branch' => ['type' => 'string', 'description' => 'Commit to a new branch with this name'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'filepath', 'content', 'message', 'instance', 'user'],
		]
	)]
	public function create_file(string $owner, string $repo, string $filepath, string $content, string $message, ?string $branch = null, ?string $new_branch = null, string $instance = '', string $user = ''): array
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
		description: 'Replace a file\'s content and commit it.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'filepath' => ['type' => 'string'],
				'content' => ['type' => 'string', 'description' => 'Plain text (encoded by the server)'],
				'message' => ['type' => 'string', 'description' => 'Commit message'],
				'sha' => ['type' => 'string', 'description' => 'Current file SHA (from get_file_content)'],
				'branch' => ['type' => 'string', 'description' => 'Branch to commit to'],
				'new_branch' => ['type' => 'string', 'description' => 'Commit to a new branch with this name'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'filepath', 'content', 'message', 'sha', 'instance', 'user'],
		]
	)]
	public function update_file(string $owner, string $repo, string $filepath, string $content, string $message, string $sha, ?string $branch = null, ?string $new_branch = null, string $instance = '', string $user = ''): array
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
		description: 'Delete a file and commit it.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'filepath' => ['type' => 'string'],
				'message' => ['type' => 'string', 'description' => 'Commit message'],
				'sha' => ['type' => 'string', 'description' => 'Current file SHA (from get_file_content)'],
				'branch' => ['type' => 'string'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'filepath', 'message', 'sha', 'instance', 'user'],
		]
	)]
	public function delete_file(string $owner, string $repo, string $filepath, string $message, string $sha, ?string $branch = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [
			'message' => $message,
			'sha' => $sha,
		];
		if ($branch !== null) $data['branch'] = $branch;
		return $client->delete("repos/{$owner}/{$repo}/contents/{$filepath}", $data);
	}
}
