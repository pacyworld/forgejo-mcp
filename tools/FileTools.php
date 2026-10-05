<?php
/**
 * Forgejo MCP Server — File Content Tools
 *
 * get_file_content stays standalone (high traffic). Writes and tree/contents
 * browsing are consolidated into the `file` tool.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class FileTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'file',
		description: 'Write and browse repository files. Actions and their required parameters: create(owner, repo, filepath, content, message), update(owner, repo, filepath, content, message, sha), delete(owner, repo, filepath, message, sha), list_contents(owner, repo; optional path, ref), tree(owner, repo, sha; optional recursive). All take owner, repo; optional on write actions: branch, new_branch.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['create', 'update', 'delete', 'list_contents', 'tree']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'filepath' => ['type' => 'string'],
				'content' => ['type' => 'string', 'description' => 'Plain text (encoded by the server)'],
				'message' => ['type' => 'string', 'description' => 'Commit message'],
				'sha' => ['type' => 'string', 'description' => 'Current file SHA (get it via get_file_content); for tree: tree SHA or branch name'],
				'branch' => ['type' => 'string', 'description' => 'Branch to commit to'],
				'new_branch' => ['type' => 'string', 'description' => 'Commit to a new branch with this name'],
				'path' => ['type' => 'string', 'description' => 'Empty for root'],
				'ref' => ['type' => 'string', 'description' => 'Branch, tag or SHA'],
				'recursive' => ['type' => 'boolean', 'description' => 'default false'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'create_file' => 'file action=create',
			'update_file' => 'file action=update',
			'delete_file' => 'file action=delete',
			'list_repo_contents' => 'file action=list_contents',
			'get_repo_tree' => 'file action=tree',
		]
	)]
	public function file(string $action, ?string $owner = null, ?string $repo = null, ?string $filepath = null, ?string $content = null, ?string $message = null, ?string $sha = null, ?string $branch = null, ?string $new_branch = null, ?string $path = null, ?string $ref = null, ?bool $recursive = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('file', $action, get_defined_vars(), [
			'create' => ['handler' => [$this, 'create_file'], 'required' => ['owner', 'repo', 'filepath', 'content', 'message'], 'args' => ['owner', 'repo', 'filepath', 'content', 'message', 'branch', 'new_branch', 'instance', 'user']],
			'update' => ['handler' => [$this, 'update_file'], 'required' => ['owner', 'repo', 'filepath', 'content', 'message', 'sha'], 'args' => ['owner', 'repo', 'filepath', 'content', 'message', 'sha', 'branch', 'new_branch', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_file'], 'required' => ['owner', 'repo', 'filepath', 'message', 'sha'], 'args' => ['owner', 'repo', 'filepath', 'message', 'sha', 'branch', 'instance', 'user']],
			'list_contents' => ['handler' => [$this, 'list_repo_contents'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'path', 'ref', 'instance', 'user']],
			'tree' => ['handler' => [$this, 'get_repo_tree'], 'required' => ['owner', 'repo', 'sha'], 'args' => ['owner', 'repo', 'sha', 'recursive', 'instance', 'user']],
		]);
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
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
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

	public function list_repo_contents(string $owner, string $repo, string $path = '', ?string $ref = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$endpoint = "repos/{$owner}/{$repo}/contents";
		if (!empty($path)) $endpoint .= "/{$path}";
		$query = [];
		if ($ref !== null) $query['ref'] = $ref;
		return $client->get($endpoint, $query);
	}

	public function get_repo_tree(string $owner, string $repo, string $sha, bool $recursive = false, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = [];
		if ($recursive) $query['recursive'] = 'true';
		return $client->get("repos/{$owner}/{$repo}/git/trees/{$sha}", $query);
	}
}
