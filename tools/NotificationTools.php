<?php
/**
 * Forgejo MCP Server — Notification Tools
 *
 * Consolidated: one `notification` tool whose `action` selects the operation.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class NotificationTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'notification',
		description: 'Work with notifications. Actions and their required parameters: check (list mine; optional status_types, page, limit), list_repo(owner, repo; optional status_types), get_thread(id), mark_read(id), mark_all_read, mark_repo_read(owner, repo).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['check', 'list_repo', 'get_thread', 'mark_read', 'mark_all_read', 'mark_repo_read']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'id' => ['type' => 'integer', 'description' => 'Thread ID'],
				'status_types' => ['type' => 'string', 'description' => 'Comma-separated: unread,read,pinned'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'check_notifications' => 'notification action=check',
			'list_repo_notifications' => 'notification action=list_repo',
			'get_notification_thread' => 'notification action=get_thread',
			'mark_notification_read' => 'notification action=mark_read',
			'mark_all_notifications_read' => 'notification action=mark_all_read',
			'mark_repo_notifications_read' => 'notification action=mark_repo_read',
		]
	)]
	public function notification(string $action, ?string $owner = null, ?string $repo = null, ?int $id = null, ?string $status_types = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('notification', $action, get_defined_vars(), [
			'check' => ['handler' => [$this, 'check_notifications'], 'required' => [], 'args' => ['status_types', 'page', 'limit', 'instance', 'user']],
			'list_repo' => ['handler' => [$this, 'list_repo_notifications'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'status_types', 'page', 'limit', 'instance', 'user']],
			'get_thread' => ['handler' => [$this, 'get_notification_thread'], 'required' => ['id'], 'args' => ['id', 'instance', 'user']],
			'mark_read' => ['handler' => [$this, 'mark_notification_read'], 'required' => ['id'], 'args' => ['id', 'instance', 'user']],
			'mark_all_read' => ['handler' => [$this, 'mark_all_notifications_read'], 'required' => [], 'args' => ['instance', 'user']],
			'mark_repo_read' => ['handler' => [$this, 'mark_repo_notifications_read'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'instance', 'user']],
		]);
	}

	public function check_notifications(?string $status_types = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($status_types !== null) $query['status-types'] = $status_types;
		return $client->get('notifications', $query);
	}

	public function get_notification_thread(int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("notifications/threads/{$id}");
	}

	public function mark_notification_read(int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("notifications/threads/{$id}", ['status' => 'read']);
	}

	public function mark_all_notifications_read(string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put('notifications', ['status' => 'read']);
	}

	public function list_repo_notifications(string $owner, string $repo, ?string $status_types = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($status_types !== null) $query['status-types'] = $status_types;
		return $client->get("repos/{$owner}/{$repo}/notifications", $query);
	}

	public function mark_repo_notifications_read(string $owner, string $repo, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("repos/{$owner}/{$repo}/notifications", ['status' => 'read']);
	}
}
