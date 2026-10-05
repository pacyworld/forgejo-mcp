<?php
/**
 * Forgejo MCP Server — Tag Tools
 *
 * Consolidated: one `tag` tool whose `action` selects the operation.
 * The per-operation methods remain as internal handlers.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class TagTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'tag',
		description: 'Manage repository tags. Actions and their required parameters: list(owner, repo), get(owner, repo, tag), create(owner, repo, tag_name; optional target, message), delete(owner, repo, tag).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'get', 'create', 'delete']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'tag' => ['type' => 'string'],
				'tag_name' => ['type' => 'string'],
				'target' => ['type' => 'string', 'description' => 'Branch or SHA; default branch if omitted'],
				'message' => ['type' => 'string', 'description' => 'Makes an annotated tag'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_tags' => 'tag action=list',
			'get_tag' => 'tag action=get',
			'create_tag' => 'tag action=create',
			'delete_tag' => 'tag action=delete',
		]
	)]
	public function tag(string $action, ?string $owner = null, ?string $repo = null, ?string $tag = null, ?string $tag_name = null, ?string $target = null, ?string $message = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('tag', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_tags'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'page', 'limit', 'instance', 'user']],
			'get' => ['handler' => [$this, 'get_tag'], 'required' => ['owner', 'repo', 'tag'], 'args' => ['owner', 'repo', 'tag', 'instance', 'user']],
			'create' => ['handler' => [$this, 'create_tag'], 'required' => ['owner', 'repo', 'tag_name'], 'args' => ['owner', 'repo', 'tag_name', 'target', 'message', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_tag'], 'required' => ['owner', 'repo', 'tag'], 'args' => ['owner', 'repo', 'tag', 'instance', 'user']],
		]);
	}

	public function list_tags(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/tags", ['page' => $page, 'limit' => $limit]);
	}

	public function get_tag(string $owner, string $repo, string $tag, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/tags/{$tag}");
	}

	public function create_tag(string $owner, string $repo, string $tag_name, ?string $target = null, ?string $message = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['tag_name' => $tag_name];
		if ($target !== null) $data['target'] = $target;
		if ($message !== null) $data['message'] = $message;
		return $client->post("repos/{$owner}/{$repo}/tags", $data);
	}

	public function delete_tag(string $owner, string $repo, string $tag, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/tags/{$tag}");
	}
}
