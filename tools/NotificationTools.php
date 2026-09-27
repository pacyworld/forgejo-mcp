<?php
/**
 * Forgejo MCP Server — Notification Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class NotificationTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'check_notifications',
		description: 'List the user\'s notifications.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'status_types' => ['type' => 'string', 'description' => 'Comma-separated: unread,read,pinned'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['instance', 'user'],
		]
	)]
	public function check_notifications(?string $status_types = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($status_types !== null) $query['status-types'] = $status_types;
		return $client->get('notifications', $query);
	}

	#[McpTool(
		name: 'get_notification_thread',
		description: 'Get a notification thread.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'id' => ['type' => 'integer', 'description' => 'Notification thread ID'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['id', 'instance', 'user'],
		]
	)]
	public function get_notification_thread(int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("notifications/threads/{$id}");
	}

	#[McpTool(
		name: 'mark_notification_read',
		description: 'Mark a notification thread read.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'id' => ['type' => 'integer', 'description' => 'Notification thread ID'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['id', 'instance', 'user'],
		]
	)]
	public function mark_notification_read(int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("notifications/threads/{$id}", ['status' => 'read']);
	}

	#[McpTool(
		name: 'mark_all_notifications_read',
		description: 'Mark all notifications read.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['instance', 'user'],
		]
	)]
	public function mark_all_notifications_read(string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put('notifications', ['status' => 'read']);
	}

	#[McpTool(
		name: 'list_repo_notifications',
		description: 'List the user\'s notifications for one repository.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'status_types' => ['type' => 'string', 'description' => 'Comma-separated: unread,read,pinned'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'instance', 'user'],
		]
	)]
	public function list_repo_notifications(string $owner, string $repo, ?string $status_types = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($status_types !== null) $query['status-types'] = $status_types;
		return $client->get("repos/{$owner}/{$repo}/notifications", $query);
	}

	#[McpTool(
		name: 'mark_repo_notifications_read',
		description: 'Mark all of a repository\'s notifications read.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'],
				'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)'],
			],
			'required' => ['owner', 'repo', 'instance', 'user'],
		]
	)]
	public function mark_repo_notifications_read(string $owner, string $repo, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("repos/{$owner}/{$repo}/notifications", ['status' => 'read']);
	}
}
