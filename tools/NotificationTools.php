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
		description: 'List notifications for the authenticated user.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'status_types' => ['type' => 'string', 'description' => 'Filter: unread, read, pinned (comma-separated)'],
				'page' => ['type' => 'integer', 'description' => 'Page number'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => [],
		]
	)]
	public function check_notifications(?string $status_types = null, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($status_types !== null) $query['status-types'] = $status_types;
		return $client->get('notifications', $query);
	}

	#[McpTool(
		name: 'get_notification_thread',
		description: 'Get a specific notification thread.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'id' => ['type' => 'integer', 'description' => 'Notification thread ID'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['id'],
		]
	)]
	public function get_notification_thread(int $id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("notifications/threads/{$id}");
	}

	#[McpTool(
		name: 'mark_notification_read',
		description: 'Mark a notification thread as read.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'id' => ['type' => 'integer', 'description' => 'Notification thread ID'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['id'],
		]
	)]
	public function mark_notification_read(int $id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->patch("notifications/threads/{$id}", ['status' => 'read']);
	}

	#[McpTool(
		name: 'mark_all_notifications_read',
		description: 'Mark all notifications as read.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => [],
		]
	)]
	public function mark_all_notifications_read(?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put('notifications', ['status' => 'read']);
	}

	#[McpTool(
		name: 'list_repo_notifications',
		description: 'List notifications for a specific repository.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'status_types' => ['type' => 'string', 'description' => 'Filter: unread, read, pinned'],
				'page' => ['type' => 'integer', 'description' => 'Page number'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo'],
		]
	)]
	public function list_repo_notifications(string $owner, string $repo, ?string $status_types = null, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($status_types !== null) $query['status-types'] = $status_types;
		return $client->get("repos/{$owner}/{$repo}/notifications", $query);
	}

	#[McpTool(
		name: 'mark_repo_notifications_read',
		description: 'Mark all notifications in a repository as read.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo'],
		]
	)]
	public function mark_repo_notifications_read(string $owner, string $repo, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("repos/{$owner}/{$repo}/notifications", ['status' => 'read']);
	}
}
