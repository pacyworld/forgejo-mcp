<?php
/**
 * Forgejo MCP Server — Label Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class LabelTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'list_repo_labels',
		description: 'List a repository\'s labels.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'instance', 'user'],
		]
	)]
	public function list_repo_labels(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/labels", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'list_org_labels',
		description: 'List an organization\'s labels.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'org' => ['type' => 'string'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['org', 'instance', 'user'],
		]
	)]
	public function list_org_labels(string $org, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/labels", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'add_issue_labels',
		description: 'Add labels to an issue or PR.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'labels' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Label IDs'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'index', 'labels', 'instance', 'user'],
		]
	)]
	public function add_issue_labels(string $owner, string $repo, int $index, array $labels, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/labels", ['labels' => $labels]);
	}

	#[McpTool(
		name: 'remove_issue_labels',
		description: 'Remove one label from an issue or PR.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer'],
				'label_id' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['owner', 'repo', 'index', 'label_id', 'instance', 'user'],
		]
	)]
	public function remove_issue_labels(string $owner, string $repo, int $index, int $label_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/labels/{$label_id}");
	}
}
