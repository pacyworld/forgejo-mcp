<?php
/**
 * Forgejo MCP Server — Issue Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class IssueTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'list_repo_issues',
		description: 'List issues in a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'state' => ['type' => 'string', 'description' => 'Filter by state: open, closed, all (default open)'],
				'labels' => ['type' => 'string', 'description' => 'Comma-separated label names'],
				'milestone' => ['type' => 'string', 'description' => 'Milestone name or ID'],
				'page' => ['type' => 'integer', 'description' => 'Page number (default 1)'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page (default 20)'],
				'type' => ['type' => 'string', 'description' => 'Filter by type: issues, pulls (default issues)'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo'],
		]
	)]
	public function list_repo_issues(string $owner, string $repo, string $state = 'open', ?string $labels = null, ?string $milestone = null, int $page = 1, int $limit = 20, string $type = 'issues', ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['state' => $state, 'page' => $page, 'limit' => $limit, 'type' => $type];
		if ($labels !== null) $query['labels'] = $labels;
		if ($milestone !== null) $query['milestone'] = $milestone;
		return $client->get("repos/{$owner}/{$repo}/issues", $query);
	}

	#[McpTool(
		name: 'get_issue_by_index',
		description: 'Get a specific issue by its index number.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'Issue index number'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index'],
		]
	)]
	public function get_issue_by_index(string $owner, string $repo, int $index, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}");
	}

	#[McpTool(
		name: 'create_issue',
		description: 'Create a new issue in a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'title' => ['type' => 'string', 'description' => 'Issue title'],
				'body' => ['type' => 'string', 'description' => 'Issue body (Markdown)'],
				'labels' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Label IDs to assign'],
				'milestone' => ['type' => 'integer', 'description' => 'Milestone ID'],
				'assignees' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames to assign'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'title'],
		]
	)]
	public function create_issue(string $owner, string $repo, string $title, string $body = '', ?array $labels = null, ?int $milestone = null, ?array $assignees = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['title' => $title];
		if (!empty($body)) $data['body'] = $body;
		if ($labels !== null) $data['labels'] = $labels;
		if ($milestone !== null) $data['milestone'] = $milestone;
		if ($assignees !== null) $data['assignees'] = $assignees;
		return $client->post("repos/{$owner}/{$repo}/issues", $data);
	}

	#[McpTool(
		name: 'update_issue',
		description: 'Update an existing issue (title, body, assignees, milestone, state).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'Issue index number'],
				'title' => ['type' => 'string', 'description' => 'New title'],
				'body' => ['type' => 'string', 'description' => 'New body'],
				'state' => ['type' => 'string', 'description' => 'New state: open or closed'],
				'milestone' => ['type' => 'integer', 'description' => 'Milestone ID (0 to clear)'],
				'assignees' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Assignee usernames'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index'],
		]
	)]
	public function update_issue(string $owner, string $repo, int $index, ?string $title = null, ?string $body = null, ?string $state = null, ?int $milestone = null, ?array $assignees = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($title !== null) $data['title'] = $title;
		if ($body !== null) $data['body'] = $body;
		if ($state !== null) $data['state'] = $state;
		if ($milestone !== null) $data['milestone'] = $milestone;
		if ($assignees !== null) $data['assignees'] = $assignees;
		return $client->patch("repos/{$owner}/{$repo}/issues/{$index}", $data);
	}

	#[McpTool(
		name: 'issue_state_change',
		description: 'Open or close an issue.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'Issue index number'],
				'state' => ['type' => 'string', 'description' => 'New state: open or closed'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index', 'state'],
		]
	)]
	public function issue_state_change(string $owner, string $repo, int $index, string $state, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/{$index}", ['state' => $state]);
	}
}
