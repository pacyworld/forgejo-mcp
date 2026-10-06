<?php
/**
 * Forgejo MCP Server — Organization & Team Tools
 *
 * Consolidated: `org` covers organization and membership operations, `team`
 * covers team operations. The per-operation methods remain as internal
 * handlers.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class OrgTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'org',
		description: 'Manage organizations and membership. Actions and their required parameters: get(org), create(username; optional full_name, description, visibility), edit(org; only given fields change), delete(org; irreversible), list_mine, list_user(username), list_members(org), check_membership(org, username), remove_member(org, username).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['get', 'create', 'edit', 'delete', 'list_mine', 'list_user', 'list_members', 'check_membership', 'remove_member']],
				'org' => ['type' => 'string'],
				'username' => ['type' => 'string', 'description' => 'Organization name on create; the member everywhere else'],
				'full_name' => ['type' => 'string', 'description' => 'Display name'],
				'description' => ['type' => 'string'],
				'visibility' => ['type' => 'string', 'description' => 'public|limited|private (default public)'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'get_org' => 'org action=get',
			'create_org' => 'org action=create',
			'edit_org' => 'org action=edit',
			'delete_org' => 'org action=delete',
			'list_my_orgs' => 'org action=list_mine',
			'list_user_orgs' => 'org action=list_user',
			'list_org_members' => 'org action=list_members',
			'check_org_membership' => 'org action=check_membership',
			'remove_org_member' => 'org action=remove_member',
		]
	)]
	public function org(string $action, ?string $org = null, ?string $username = null, ?string $full_name = null, ?string $description = null, ?string $visibility = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('org', $action, get_defined_vars(), [
			'get' => ['handler' => [$this, 'get_org'], 'required' => ['org'], 'args' => ['org', 'instance', 'user']],
			'create' => ['handler' => [$this, 'create_org'], 'required' => ['username'], 'args' => ['username', 'full_name', 'description', 'visibility', 'instance', 'user']],
			'edit' => ['handler' => [$this, 'edit_org'], 'required' => ['org'], 'args' => ['org', 'full_name', 'description', 'visibility', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_org'], 'required' => ['org'], 'args' => ['org', 'instance', 'user']],
			'list_mine' => ['handler' => [$this, 'list_my_orgs'], 'required' => [], 'args' => ['page', 'limit', 'instance', 'user']],
			'list_user' => ['handler' => [$this, 'list_user_orgs'], 'required' => ['username'], 'args' => ['username', 'page', 'limit', 'instance', 'user']],
			'list_members' => ['handler' => [$this, 'list_org_members'], 'required' => ['org'], 'args' => ['org', 'page', 'limit', 'instance', 'user']],
			'check_membership' => ['handler' => [$this, 'check_org_membership'], 'required' => ['org', 'username'], 'args' => ['org', 'username', 'instance', 'user']],
			'remove_member' => ['handler' => [$this, 'remove_org_member'], 'required' => ['org', 'username'], 'args' => ['org', 'username', 'instance', 'user']],
		]);
	}

	#[McpTool(
		name: 'team',
		description: 'Manage organization teams. Actions and their required parameters: list(org), search(org; optional q), create(org, name; optional description, permission, units), add_member(team_id, username), remove_member(team_id, username), add_repo(team_id, org, repo), remove_repo(team_id, org, repo).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'search', 'create', 'add_member', 'remove_member', 'add_repo', 'remove_repo']],
				'org' => ['type' => 'string'],
				'team_id' => ['type' => 'integer'],
				'name' => ['type' => 'string'],
				'description' => ['type' => 'string'],
				'permission' => ['type' => 'string', 'description' => 'read|write|admin|owner (default read)'],
				'units' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'e.g. repo.code, repo.issues, repo.pulls'],
				'username' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'q' => ['type' => 'string'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_org_teams' => 'team action=list',
			'search_org_teams' => 'team action=search',
			'create_org_team' => 'team action=create',
			'add_team_member' => 'team action=add_member',
			'remove_team_member' => 'team action=remove_member',
			'add_team_repo' => 'team action=add_repo',
			'remove_team_repo' => 'team action=remove_repo',
		]
	)]
	public function team(string $action, ?string $org = null, ?int $team_id = null, ?string $name = null, ?string $description = null, ?string $permission = null, ?array $units = null, ?string $username = null, ?string $repo = null, ?string $q = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('team', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_org_teams'], 'required' => ['org'], 'args' => ['org', 'page', 'limit', 'instance', 'user']],
			'search' => ['handler' => [$this, 'search_org_teams'], 'required' => ['org'], 'args' => ['org', 'q', 'page', 'limit', 'instance', 'user']],
			'create' => ['handler' => [$this, 'create_org_team'], 'required' => ['org', 'name'], 'args' => ['org', 'name', 'description', 'permission', 'units', 'instance', 'user']],
			'add_member' => ['handler' => [$this, 'add_team_member'], 'required' => ['team_id', 'username'], 'args' => ['team_id', 'username', 'instance', 'user']],
			'remove_member' => ['handler' => [$this, 'remove_team_member'], 'required' => ['team_id', 'username'], 'args' => ['team_id', 'username', 'instance', 'user']],
			'add_repo' => ['handler' => [$this, 'add_team_repo'], 'required' => ['team_id', 'org', 'repo'], 'args' => ['team_id', 'org', 'repo', 'instance', 'user']],
			'remove_repo' => ['handler' => [$this, 'remove_team_repo'], 'required' => ['team_id', 'org', 'repo'], 'args' => ['team_id', 'org', 'repo', 'instance', 'user']],
		]);
	}

	public function get_org(string $org, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}");
	}

	public function create_org(string $username, string $full_name = '', string $description = '', string $visibility = 'public', string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['username' => $username, 'visibility' => $visibility];
		if (!empty($full_name)) $data['full_name'] = $full_name;
		if (!empty($description)) $data['description'] = $description;
		return $client->post('orgs', $data);
	}

	public function edit_org(string $org, ?string $full_name = null, ?string $description = null, ?string $visibility = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($full_name !== null) $data['full_name'] = $full_name;
		if ($description !== null) $data['description'] = $description;
		if ($visibility !== null) $data['visibility'] = $visibility;
		return $client->patch("orgs/{$org}", $data);
	}

	public function delete_org(string $org, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("orgs/{$org}");
	}

	public function list_my_orgs(int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/orgs', ['page' => $page, 'limit' => $limit]);
	}

	public function list_user_orgs(string $username, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("users/{$username}/orgs", ['page' => $page, 'limit' => $limit]);
	}

	public function list_org_members(string $org, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/members", ['page' => $page, 'limit' => $limit]);
	}

	public function check_org_membership(string $org, string $username, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/members/{$username}");
	}

	public function remove_org_member(string $org, string $username, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("orgs/{$org}/members/{$username}");
	}

	public function list_org_teams(string $org, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/teams", ['page' => $page, 'limit' => $limit]);
	}

	public function search_org_teams(string $org, ?string $q = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($q !== null) $query['q'] = $q;
		return $client->get("orgs/{$org}/teams/search", $query);
	}

	public function create_org_team(string $org, string $name, string $description = '', string $permission = 'read', ?array $units = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['name' => $name, 'permission' => $permission];
		if (!empty($description)) $data['description'] = $description;
		if ($units !== null) $data['units'] = $units;
		return $client->post("orgs/{$org}/teams", $data);
	}

	public function add_team_member(int $team_id, string $username, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("teams/{$team_id}/members/{$username}");
	}

	public function remove_team_member(int $team_id, string $username, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("teams/{$team_id}/members/{$username}");
	}

	public function add_team_repo(int $team_id, string $org, string $repo, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("teams/{$team_id}/repos/{$org}/{$repo}");
	}

	public function remove_team_repo(int $team_id, string $org, string $repo, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("teams/{$team_id}/repos/{$org}/{$repo}");
	}
}
