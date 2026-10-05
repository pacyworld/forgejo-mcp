<?php
/**
 * Forgejo MCP Server — Label Tools
 *
 * Consolidated: one `label` tool whose `action` selects the operation.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class LabelTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'label',
		description: 'Work with labels. Actions and their required parameters: list_repo(owner, repo), list_org(org), add(owner, repo, index, labels), remove(owner, repo, index, label_id).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list_repo', 'list_org', 'add', 'remove']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'org' => ['type' => 'string'],
				'index' => ['type' => 'integer', 'description' => 'Issue or PR number'],
				'labels' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Label IDs'],
				'label_id' => ['type' => 'integer'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_repo_labels' => 'label action=list_repo',
			'list_org_labels' => 'label action=list_org',
			'add_issue_labels' => 'label action=add',
			'remove_issue_labels' => 'label action=remove',
		]
	)]
	public function label(string $action, ?string $owner = null, ?string $repo = null, ?string $org = null, ?int $index = null, ?array $labels = null, ?int $label_id = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('label', $action, get_defined_vars(), [
			'list_repo' => ['handler' => [$this, 'list_repo_labels'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'page', 'limit', 'instance', 'user']],
			'list_org' => ['handler' => [$this, 'list_org_labels'], 'required' => ['org'], 'args' => ['org', 'page', 'limit', 'instance', 'user']],
			'add' => ['handler' => [$this, 'add_issue_labels'], 'required' => ['owner', 'repo', 'index', 'labels'], 'args' => ['owner', 'repo', 'index', 'labels', 'instance', 'user']],
			'remove' => ['handler' => [$this, 'remove_issue_labels'], 'required' => ['owner', 'repo', 'index', 'label_id'], 'args' => ['owner', 'repo', 'index', 'label_id', 'instance', 'user']],
		]);
	}

	public function list_repo_labels(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/labels", ['page' => $page, 'limit' => $limit]);
	}

	public function list_org_labels(string $org, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/labels", ['page' => $page, 'limit' => $limit]);
	}

	public function add_issue_labels(string $owner, string $repo, int $index, array $labels, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->post("repos/{$owner}/{$repo}/issues/{$index}/labels", ['labels' => $labels]);
	}

	public function remove_issue_labels(string $owner, string $repo, int $index, int $label_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/issues/{$index}/labels/{$label_id}");
	}
}
