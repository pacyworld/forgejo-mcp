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

	#[McpTool(name: 'list_push_mirrors', description: 'List a repository\'s push mirrors.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'instance', 'user']])]
	public function list_push_mirrors(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/push_mirrors", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'add_push_mirror', description: 'Add a push mirror to a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'remote_address' => ['type' => 'string', 'description' => 'Remote repository URL'], 'remote_username' => ['type' => 'string'], 'remote_password' => ['type' => 'string', 'description' => 'Password or token'], 'interval' => ['type' => 'string', 'description' => 'Go duration (default "8h0m0s")'], 'sync_on_commit' => ['type' => 'boolean', 'description' => 'Sync on every push (default true)'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'remote_address', 'instance', 'user']])]
	public function add_push_mirror(string $owner, string $repo, string $remote_address, ?string $remote_username = null, ?string $remote_password = null, string $interval = '8h0m0s', bool $sync_on_commit = true, string $instance = '', string $user = ''): array
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

	#[McpTool(name: 'get_push_mirror', description: 'Get a push mirror by remote name.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'mirror_name' => ['type' => 'string', 'description' => 'Remote name'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'mirror_name', 'instance', 'user']])]
	public function get_push_mirror(string $owner, string $repo, string $mirror_name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/push_mirrors/{$mirror_name}");
	}

	#[McpTool(name: 'delete_push_mirror', description: 'Delete a push mirror.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'mirror_name' => ['type' => 'string', 'description' => 'Remote name'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'mirror_name', 'instance', 'user']])]
	public function delete_push_mirror(string $owner, string $repo, string $mirror_name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/push_mirrors/{$mirror_name}");
	}

	#[McpTool(name: 'sync_push_mirror', description: 'Sync all of a repository\'s push mirrors now.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'instance', 'user']])]
	public function sync_push_mirror(string $owner, string $repo, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/push_mirrors-sync");
	}
}
