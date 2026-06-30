<?php
/**
 * Forgejo MCP Server — Organization Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class OrgTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'get_org', description: 'Get organization details.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org']])]
	public function get_org(string $org, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}");
	}

	#[McpTool(name: 'create_org', description: 'Create an organization.', inputSchema: ['type' => 'object', 'properties' => ['username' => ['type' => 'string', 'description' => 'Organization username'], 'full_name' => ['type' => 'string', 'description' => 'Display name'], 'description' => ['type' => 'string', 'description' => 'Organization description'], 'visibility' => ['type' => 'string', 'description' => 'Visibility: public, limited, private'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['username']])]
	public function create_org(string $username, string $full_name = '', string $description = '', string $visibility = 'public', ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['username' => $username, 'visibility' => $visibility];
		if (!empty($full_name)) $data['full_name'] = $full_name;
		if (!empty($description)) $data['description'] = $description;
		return $client->post('orgs', $data);
	}

	#[McpTool(name: 'edit_org', description: 'Edit organization settings.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'full_name' => ['type' => 'string'], 'description' => ['type' => 'string'], 'visibility' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org']])]
	public function edit_org(string $org, ?string $full_name = null, ?string $description = null, ?string $visibility = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($full_name !== null) $data['full_name'] = $full_name;
		if ($description !== null) $data['description'] = $description;
		if ($visibility !== null) $data['visibility'] = $visibility;
		return $client->patch("orgs/{$org}", $data);
	}

	#[McpTool(name: 'delete_org', description: 'Delete an organization. WARNING: Destructive and irreversible.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org']])]
	public function delete_org(string $org, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("orgs/{$org}");
	}

	#[McpTool(name: 'list_my_orgs', description: 'List organizations the authenticated user belongs to.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => []])]
	public function list_my_orgs(int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/orgs', ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'list_user_orgs', description: "List a user's organizations.", readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['username' => ['type' => 'string', 'description' => 'Username'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['username']])]
	public function list_user_orgs(string $username, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("users/{$username}/orgs", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'list_org_members', description: 'List members of an organization.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org']])]
	public function list_org_members(string $org, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/members", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'check_org_membership', description: 'Check if a user is a member of an organization.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string'], 'username' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org', 'username']])]
	public function check_org_membership(string $org, string $username, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/members/{$username}");
	}

	#[McpTool(name: 'remove_org_member', description: 'Remove a member from an organization.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string'], 'username' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org', 'username']])]
	public function remove_org_member(string $org, string $username, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("orgs/{$org}/members/{$username}");
	}

	#[McpTool(name: 'list_org_teams', description: 'List teams in an organization.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org']])]
	public function list_org_teams(string $org, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/teams", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'search_org_teams', description: 'Search teams within an organization by name.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'q' => ['type' => 'string', 'description' => 'Search query'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org']])]
	public function search_org_teams(string $org, ?string $q = null, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($q !== null) $query['q'] = $q;
		return $client->get("orgs/{$org}/teams/search", $query);
	}

	#[McpTool(name: 'create_org_team', description: 'Create a team in an organization.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string'], 'name' => ['type' => 'string', 'description' => 'Team name'], 'description' => ['type' => 'string'], 'permission' => ['type' => 'string', 'description' => 'Permission level: read, write, admin, owner'], 'units' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Units: repo.code, repo.issues, repo.pulls, etc.'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org', 'name']])]
	public function create_org_team(string $org, string $name, string $description = '', string $permission = 'read', ?array $units = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['name' => $name, 'permission' => $permission];
		if (!empty($description)) $data['description'] = $description;
		if ($units !== null) $data['units'] = $units;
		return $client->post("orgs/{$org}/teams", $data);
	}

	#[McpTool(name: 'add_team_member', description: 'Add a user to a team.', inputSchema: ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer', 'description' => 'Team ID'], 'username' => ['type' => 'string', 'description' => 'Username to add'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['team_id', 'username']])]
	public function add_team_member(int $team_id, string $username, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("teams/{$team_id}/members/{$username}");
	}

	#[McpTool(name: 'remove_team_member', description: 'Remove a user from a team.', inputSchema: ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer'], 'username' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['team_id', 'username']])]
	public function remove_team_member(int $team_id, string $username, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("teams/{$team_id}/members/{$username}");
	}

	#[McpTool(name: 'add_team_repo', description: 'Add a repository to a team.', inputSchema: ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer'], 'org' => ['type' => 'string', 'description' => 'Organization that owns the repo'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['team_id', 'org', 'repo']])]
	public function add_team_repo(int $team_id, string $org, string $repo, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("teams/{$team_id}/repos/{$org}/{$repo}");
	}

	#[McpTool(name: 'remove_team_repo', description: 'Remove a repository from a team.', inputSchema: ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer'], 'org' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['team_id', 'org', 'repo']])]
	public function remove_team_repo(int $team_id, string $org, string $repo, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("teams/{$team_id}/repos/{$org}/{$repo}");
	}
}
