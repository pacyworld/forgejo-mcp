<?php
/**
 * Forgejo MCP Server — Time Tracking Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class TimeTrackingTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_issue_tracked_times', description: 'List tracked time entries on an issue.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'instance', 'user']])]
	public function list_issue_tracked_times(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/times", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'list_repo_tracked_times', description: 'List all tracked time entries in a repository.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'instance', 'user']])]
	public function list_repo_tracked_times(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/times", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'list_my_tracked_times', description: 'List the authenticated user\'s tracked time entries.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['instance', 'user']])]
	public function list_my_tracked_times(int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/times', ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'add_issue_time', description: 'Add tracked time to an issue.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'time' => ['type' => 'integer', 'description' => 'Seconds'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'time', 'instance', 'user']])]
	public function add_issue_time(string $owner, string $repo, int $index, int $time, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/times", ['time' => $time]);
	}

	#[McpTool(name: 'reset_issue_time', description: 'Delete all tracked time on an issue.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'instance', 'user']])]
	public function reset_issue_time(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/times");
	}

	#[McpTool(name: 'delete_issue_time_entry', description: 'Delete one tracked time entry.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'time_id' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'time_id', 'instance', 'user']])]
	public function delete_issue_time_entry(string $owner, string $repo, int $index, int $time_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/times/{$time_id}");
	}

	#[McpTool(name: 'start_issue_stopwatch', description: 'Start a stopwatch on an issue.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'instance', 'user']])]
	public function start_issue_stopwatch(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/start");
	}

	#[McpTool(name: 'stop_issue_stopwatch', description: 'Stop an issue\'s stopwatch and record the time.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'instance', 'user']])]
	public function stop_issue_stopwatch(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/stop");
	}

	#[McpTool(name: 'cancel_issue_stopwatch', description: 'Cancel an issue\'s stopwatch without recording time.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'index', 'instance', 'user']])]
	public function cancel_issue_stopwatch(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/delete");
	}

	#[McpTool(name: 'list_my_stopwatches', description: 'List the authenticated user\'s running stopwatches.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['instance', 'user']])]
	public function list_my_stopwatches(string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/stopwatches');
	}
}
