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
		description: 'List labels for a repository.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'page' => ['type' => 'integer', 'description' => 'Page number'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo'],
		]
	)]
	public function list_repo_labels(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/labels", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'list_org_labels',
		description: 'List labels for an organization.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'org' => ['type' => 'string', 'description' => 'Organization name'],
				'page' => ['type' => 'integer', 'description' => 'Page number'],
				'limit' => ['type' => 'integer', 'description' => 'Results per page'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['org'],
		]
	)]
	public function list_org_labels(string $org, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/labels", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(
		name: 'add_issue_labels',
		description: 'Add labels to an issue.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'Issue index number'],
				'labels' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Label IDs to add'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index', 'labels'],
		]
	)]
	public function add_issue_labels(string $owner, string $repo, int $index, array $labels, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/labels", ['labels' => $labels]);
	}

	#[McpTool(
		name: 'remove_issue_labels',
		description: 'Remove a label from an issue.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'owner' => ['type' => 'string', 'description' => 'Repository owner'],
				'repo' => ['type' => 'string', 'description' => 'Repository name'],
				'index' => ['type' => 'integer', 'description' => 'Issue index number'],
				'label_id' => ['type' => 'integer', 'description' => 'Label ID to remove'],
				'instance' => ['type' => 'string', 'description' => 'Forgejo instance name (optional)'],
				'user' => ['type' => 'string', 'description' => 'User identity (optional)'],
			],
			'required' => ['owner', 'repo', 'index', 'label_id'],
		]
	)]
	public function remove_issue_labels(string $owner, string $repo, int $index, int $label_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/labels/{$label_id}");
	}
}
