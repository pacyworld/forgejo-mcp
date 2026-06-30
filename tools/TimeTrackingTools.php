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

	#[McpTool(name: 'list_issue_tracked_times', description: 'List tracked times on an issue.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer', 'description' => 'Issue index'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index']])]
	public function list_issue_tracked_times(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/times", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'list_repo_tracked_times', description: 'List all tracked times for a repository.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function list_repo_tracked_times(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/times", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'list_my_tracked_times', description: 'List tracked times for the authenticated user.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => []])]
	public function list_my_tracked_times(int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/times', ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'add_issue_time', description: 'Add tracked time to an issue.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer', 'description' => 'Issue index'], 'time' => ['type' => 'integer', 'description' => 'Time in seconds to add'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'time']])]
	public function add_issue_time(string $owner, string $repo, int $index, int $time, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/times", ['time' => $time]);
	}

	#[McpTool(name: 'reset_issue_time', description: 'Reset all tracked time on an issue.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer', 'description' => 'Issue index'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index']])]
	public function reset_issue_time(string $owner, string $repo, int $index, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/times");
	}

	#[McpTool(name: 'delete_issue_time_entry', description: 'Delete a specific tracked time entry.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer', 'description' => 'Issue index'], 'time_id' => ['type' => 'integer', 'description' => 'Time entry ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index', 'time_id']])]
	public function delete_issue_time_entry(string $owner, string $repo, int $index, int $time_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/times/{$time_id}");
	}

	#[McpTool(name: 'start_issue_stopwatch', description: 'Start a stopwatch on an issue.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index']])]
	public function start_issue_stopwatch(string $owner, string $repo, int $index, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/start");
	}

	#[McpTool(name: 'stop_issue_stopwatch', description: 'Stop a running stopwatch on an issue.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index']])]
	public function stop_issue_stopwatch(string $owner, string $repo, int $index, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/stop");
	}

	#[McpTool(name: 'cancel_issue_stopwatch', description: 'Cancel a running stopwatch on an issue without adding time.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'index' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'index']])]
	public function cancel_issue_stopwatch(string $owner, string $repo, int $index, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/delete");
	}

	#[McpTool(name: 'list_my_stopwatches', description: 'List all running stopwatches for the authenticated user.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => []])]
	public function list_my_stopwatches(?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/stopwatches');
	}
}
