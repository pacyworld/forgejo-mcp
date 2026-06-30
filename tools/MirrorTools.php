<?php
/**
 * Forgejo MCP Server — Push Mirror Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class MirrorTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_push_mirrors', description: 'List push mirrors for a repository.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function list_push_mirrors(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/push_mirrors", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'add_push_mirror', description: 'Add a push mirror to a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'remote_address' => ['type' => 'string', 'description' => 'Remote repository URL'], 'remote_username' => ['type' => 'string', 'description' => 'Remote username (optional)'], 'remote_password' => ['type' => 'string', 'description' => 'Remote password or token (optional)'], 'interval' => ['type' => 'string', 'description' => 'Sync interval (e.g., "8h0m0s")'], 'sync_on_commit' => ['type' => 'boolean', 'description' => 'Sync on every push (default true)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'remote_address']])]
	public function add_push_mirror(string $owner, string $repo, string $remote_address, ?string $remote_username = null, ?string $remote_password = null, string $interval = '8h0m0s', bool $sync_on_commit = true, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [
			'remote_address' => $remote_address,
			'interval' => $interval,
			'sync_on_commit' => $sync_on_commit,
		];
		if ($remote_username !== null) $data['remote_username'] = $remote_username;
		if ($remote_password !== null) $data['remote_password'] = $remote_password;
		return $client->post("repos/{$owner}/{$repo}/push_mirrors", $data);
	}

	#[McpTool(name: 'get_push_mirror', description: 'Get a specific push mirror by name.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'mirror_name' => ['type' => 'string', 'description' => 'Push mirror remote name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'mirror_name']])]
	public function get_push_mirror(string $owner, string $repo, string $mirror_name, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/push_mirrors/{$mirror_name}");
	}

	#[McpTool(name: 'delete_push_mirror', description: 'Delete a push mirror from a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'mirror_name' => ['type' => 'string', 'description' => 'Push mirror remote name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'mirror_name']])]
	public function delete_push_mirror(string $owner, string $repo, string $mirror_name, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/push_mirrors/{$mirror_name}");
	}

	#[McpTool(name: 'sync_push_mirror', description: 'Trigger a push mirror sync now.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function sync_push_mirror(string $owner, string $repo, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/push_mirrors/sync");
	}
}
