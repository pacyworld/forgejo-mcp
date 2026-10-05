<?php
/**
 * Forgejo MCP Server — Push Mirror Tools
 *
 * Consolidated: one `push_mirror` tool whose `action` selects the operation.
 * The per-operation methods remain as internal handlers.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class MirrorTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'push_mirror',
		description: 'Manage a repository\'s push mirrors. Actions and their required parameters: list(owner, repo), add(owner, repo, remote_address; optional remote_username, remote_password, interval, sync_on_commit), get(owner, repo, mirror_name), delete(owner, repo, mirror_name), sync(owner, repo).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'add', 'get', 'delete', 'sync']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'mirror_name' => ['type' => 'string', 'description' => 'Remote name'],
				'remote_address' => ['type' => 'string', 'description' => 'Remote repository URL'],
				'remote_username' => ['type' => 'string'],
				'remote_password' => ['type' => 'string', 'description' => 'Password or token'],
				'interval' => ['type' => 'string', 'description' => 'Go duration (default "8h0m0s")'],
				'sync_on_commit' => ['type' => 'boolean', 'description' => 'Sync on every push (default true)'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_push_mirrors' => 'push_mirror action=list',
			'add_push_mirror' => 'push_mirror action=add',
			'get_push_mirror' => 'push_mirror action=get',
			'delete_push_mirror' => 'push_mirror action=delete',
			'sync_push_mirror' => 'push_mirror action=sync',
		]
	)]
	public function push_mirror(string $action, ?string $owner = null, ?string $repo = null, ?string $mirror_name = null, ?string $remote_address = null, ?string $remote_username = null, ?string $remote_password = null, ?string $interval = null, ?bool $sync_on_commit = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('push_mirror', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_push_mirrors'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'page', 'limit', 'instance', 'user']],
			'add' => ['handler' => [$this, 'add_push_mirror'], 'required' => ['owner', 'repo', 'remote_address'], 'args' => ['owner', 'repo', 'remote_address', 'remote_username', 'remote_password', 'interval', 'sync_on_commit', 'instance', 'user']],
			'get' => ['handler' => [$this, 'get_push_mirror'], 'required' => ['owner', 'repo', 'mirror_name'], 'args' => ['owner', 'repo', 'mirror_name', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_push_mirror'], 'required' => ['owner', 'repo', 'mirror_name'], 'args' => ['owner', 'repo', 'mirror_name', 'instance', 'user']],
			'sync' => ['handler' => [$this, 'sync_push_mirror'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'instance', 'user']],
		]);
	}

	public function list_push_mirrors(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/push_mirrors", ['page' => $page, 'limit' => $limit]);
	}

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

	public function get_push_mirror(string $owner, string $repo, string $mirror_name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/push_mirrors/{$mirror_name}");
	}

	public function delete_push_mirror(string $owner, string $repo, string $mirror_name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/push_mirrors/{$mirror_name}");
	}

	public function sync_push_mirror(string $owner, string $repo, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/push_mirrors-sync");
	}
}
