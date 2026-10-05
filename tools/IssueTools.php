<?php
/**
 * Forgejo MCP Server — Issue Tools
 *
 * High-traffic tools stay standalone (list_repo_issues, get_issue_by_index,
 * create_issue); update_issue and issue_state_change are consolidated into
 * the `issue` tool.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class IssueTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'issue',
		description: 'Modify an issue. Actions and their required parameters: update(owner, repo, index; only given fields change; optional title, body, state, milestone, assignees), state_change(owner, repo, index, state).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['update', 'state_change']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer', 'description' => 'Issue number'],
				'title' => ['type' => 'string'],
				'body' => ['type' => 'string'],
				'state' => ['type' => 'string', 'description' => 'open|closed'],
				'milestone' => ['type' => 'integer', 'description' => 'Milestone ID; 0 clears'],
				'assignees' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'update_issue' => 'issue action=update',
			'issue_state_change' => 'issue action=state_change',
		]
	)]
	public function issue(string $action, ?string $owner = null, ?string $repo = null, ?int $index = null, ?string $title = null, ?string $body = null, ?string $state = null, ?int $milestone = null, ?array $assignees = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('issue', $action, get_defined_vars(), [
			'update' => ['handler' => [$this, 'update_issue'], 'required' => ['owner', 'repo', 'index'], 'args' => ['owner', 'repo', 'index', 'title', 'body', 'state', 'milestone', 'assignees', 'instance', 'user']],
			'state_change' => ['handler' => [$this, 'issue_state_change'], 'required' => ['owner', 'repo', 'index', 'state'], 'args' => ['owner', 'repo', 'index', 'state', 'instance', 'user']],
		]);
	}

	#[McpTool(
		name: 'list_repo_issues',
		description: 'List issues (or PRs) in a repository.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'state' => ['type' => 'string', 'description' => 'open|closed|all (default open)'],
				'labels' => ['type' => 'string', 'description' => 'Comma-separated label names'],
				'milestone' => ['type' => 'string', 'description' => 'Milestone name or ID'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'type' => ['type' => 'string', 'description' => 'issues|pulls (default issues)'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'instance', 'user'],
		]
	)]
	public function list_repo_issues(string $owner, string $repo, string $state = 'open', ?string $labels = null, ?string $milestone = null, int $page = 1, int $limit = 20, string $type = 'issues', string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['state' => $state, 'page' => $page, 'limit' => $limit, 'type' => $type];
		if ($labels !== null) $query['labels'] = $labels;
		if ($milestone !== null) $query['milestone'] = $milestone;
		return $client->get("repos/{$owner}/{$repo}/issues", $query);
	}

	#[McpTool(
		name: 'get_issue_by_index',
		description: 'Get an issue by index.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'index', 'instance', 'user'],
		]
	)]
	public function get_issue_by_index(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}");
	}

	#[McpTool(
		name: 'create_issue',
		description: 'Create an issue.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'title' => ['type' => 'string'],
				'body' => ['type' => 'string', 'description' => 'Markdown'],
				'labels' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Label IDs'],
				'milestone' => ['type' => 'integer', 'description' => 'Milestone ID'],
				'assignees' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Usernames'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'title', 'instance', 'user'],
		]
	)]
	public function create_issue(string $owner, string $repo, string $title, string $body = '', ?array $labels = null, ?int $milestone = null, ?array $assignees = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['title' => $title];
		if (!empty($body)) $data['body'] = $body;
		if ($labels !== null) $data['labels'] = $labels;
		if ($milestone !== null) $data['milestone'] = $milestone;
		if ($assignees !== null) $data['assignees'] = $assignees;
		return $client->post("repos/{$owner}/{$repo}/issues", $data);
	}

	public function update_issue(string $owner, string $repo, int $index, ?string $title = null, ?string $body = null, ?string $state = null, ?int $milestone = null, ?array $assignees = null, string $instance = '', string $user = ''): array
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

	public function issue_state_change(string $owner, string $repo, int $index, string $state, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("repos/{$owner}/{$repo}/issues/{$index}", ['state' => $state]);
	}
}
