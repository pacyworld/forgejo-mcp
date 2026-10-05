<?php
/**
 * Forgejo MCP Server — Time Tracking Tools
 *
 * Consolidated: one `time_tracking` tool whose `action` selects the
 * operation (tracked times and stopwatches share the family).
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class TimeTrackingTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'time_tracking',
		description: 'Track time and stopwatches on issues. Actions and their required parameters: list_issue(owner, repo, index), list_repo(owner, repo), list_mine, add(owner, repo, index, time), reset(owner, repo, index; deletes ALL tracked time on the issue), delete_entry(owner, repo, index, time_id), start_stopwatch(owner, repo, index), stop_stopwatch(owner, repo, index; records the time), cancel_stopwatch(owner, repo, index; discards it), list_stopwatches. Listing actions accept optional page/limit.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list_issue', 'list_repo', 'list_mine', 'add', 'reset', 'delete_entry', 'start_stopwatch', 'stop_stopwatch', 'cancel_stopwatch', 'list_stopwatches']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer', 'description' => 'Issue number'],
				'time' => ['type' => 'integer', 'description' => 'Seconds'],
				'time_id' => ['type' => 'integer'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_issue_tracked_times' => 'time_tracking action=list_issue',
			'list_repo_tracked_times' => 'time_tracking action=list_repo',
			'list_my_tracked_times' => 'time_tracking action=list_mine',
			'add_issue_time' => 'time_tracking action=add',
			'reset_issue_time' => 'time_tracking action=reset',
			'delete_issue_time_entry' => 'time_tracking action=delete_entry',
			'start_issue_stopwatch' => 'time_tracking action=start_stopwatch',
			'stop_issue_stopwatch' => 'time_tracking action=stop_stopwatch',
			'cancel_issue_stopwatch' => 'time_tracking action=cancel_stopwatch',
			'list_my_stopwatches' => 'time_tracking action=list_stopwatches',
		]
	)]
	public function time_tracking(string $action, ?string $owner = null, ?string $repo = null, ?int $index = null, ?int $time = null, ?int $time_id = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		$issue = ['owner', 'repo', 'index'];
		return $this->dispatch('time_tracking', $action, get_defined_vars(), [
			'list_issue' => ['handler' => [$this, 'list_issue_tracked_times'], 'required' => $issue, 'args' => ['owner', 'repo', 'index', 'page', 'limit', 'instance', 'user']],
			'list_repo' => ['handler' => [$this, 'list_repo_tracked_times'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'page', 'limit', 'instance', 'user']],
			'list_mine' => ['handler' => [$this, 'list_my_tracked_times'], 'required' => [], 'args' => ['page', 'limit', 'instance', 'user']],
			'add' => ['handler' => [$this, 'add_issue_time'], 'required' => array_merge($issue, ['time']), 'args' => ['owner', 'repo', 'index', 'time', 'instance', 'user']],
			'reset' => ['handler' => [$this, 'reset_issue_time'], 'required' => $issue, 'args' => ['owner', 'repo', 'index', 'instance', 'user']],
			'delete_entry' => ['handler' => [$this, 'delete_issue_time_entry'], 'required' => array_merge($issue, ['time_id']), 'args' => ['owner', 'repo', 'index', 'time_id', 'instance', 'user']],
			'start_stopwatch' => ['handler' => [$this, 'start_issue_stopwatch'], 'required' => $issue, 'args' => ['owner', 'repo', 'index', 'instance', 'user']],
			'stop_stopwatch' => ['handler' => [$this, 'stop_issue_stopwatch'], 'required' => $issue, 'args' => ['owner', 'repo', 'index', 'instance', 'user']],
			'cancel_stopwatch' => ['handler' => [$this, 'cancel_issue_stopwatch'], 'required' => $issue, 'args' => ['owner', 'repo', 'index', 'instance', 'user']],
			'list_stopwatches' => ['handler' => [$this, 'list_my_stopwatches'], 'required' => [], 'args' => ['instance', 'user']],
		]);
	}

	public function list_issue_tracked_times(string $owner, string $repo, int $index, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/issues/{$index}/times", ['page' => $page, 'limit' => $limit]);
	}

	public function list_repo_tracked_times(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/times", ['page' => $page, 'limit' => $limit]);
	}

	public function list_my_tracked_times(int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/times', ['page' => $page, 'limit' => $limit]);
	}

	public function add_issue_time(string $owner, string $repo, int $index, int $time, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/times", ['time' => $time]);
	}

	public function reset_issue_time(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/times");
	}

	public function delete_issue_time_entry(string $owner, string $repo, int $index, int $time_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/times/{$time_id}");
	}

	public function start_issue_stopwatch(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/start");
	}

	public function stop_issue_stopwatch(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/stop");
	}

	public function cancel_issue_stopwatch(string $owner, string $repo, int $index, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/stopwatch/delete");
	}

	public function list_my_stopwatches(string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get('user/stopwatches');
	}
}
