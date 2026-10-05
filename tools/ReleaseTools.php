<?php
/**
 * Forgejo MCP Server — Release Tools
 *
 * Consolidated: one `release` tool whose `action` selects the operation.
 * Attachments live in the `attachment` tool (target=release).
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class ReleaseTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'release',
		description: 'Manage releases. Actions and their required parameters: list(owner, repo), get_by_id(owner, repo, id), get_by_tag(owner, repo, tag), latest(owner, repo), create(owner, repo, tag_name; optional name, body, draft, prerelease, target_commitish), edit(owner, repo, id; only given fields change), delete(owner, repo, id), delete_by_tag(owner, repo, tag). Attachments: use attachment target=release.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'get_by_id', 'get_by_tag', 'latest', 'create', 'edit', 'delete', 'delete_by_tag']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'id' => ['type' => 'integer'],
				'tag' => ['type' => 'string'],
				'tag_name' => ['type' => 'string'],
				'name' => ['type' => 'string', 'description' => 'Release title'],
				'body' => ['type' => 'string', 'description' => 'Release notes (Markdown)'],
				'draft' => ['type' => 'boolean'],
				'prerelease' => ['type' => 'boolean'],
				'target_commitish' => ['type' => 'string', 'description' => 'Branch or SHA to tag'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_releases' => 'release action=list',
			'get_release_by_id' => 'release action=get_by_id',
			'get_release_by_tag' => 'release action=get_by_tag',
			'get_latest_release' => 'release action=latest',
			'create_release' => 'release action=create',
			'edit_release' => 'release action=edit',
			'delete_release' => 'release action=delete',
			'delete_release_by_tag' => 'release action=delete_by_tag',
		]
	)]
	public function release(string $action, ?string $owner = null, ?string $repo = null, ?int $id = null, ?string $tag = null, ?string $tag_name = null, ?string $name = null, ?string $body = null, ?bool $draft = null, ?bool $prerelease = null, ?string $target_commitish = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('release', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_releases'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'page', 'limit', 'instance', 'user']],
			'get_by_id' => ['handler' => [$this, 'get_release_by_id'], 'required' => ['owner', 'repo', 'id'], 'args' => ['owner', 'repo', 'id', 'instance', 'user']],
			'get_by_tag' => ['handler' => [$this, 'get_release_by_tag'], 'required' => ['owner', 'repo', 'tag'], 'args' => ['owner', 'repo', 'tag', 'instance', 'user']],
			'latest' => ['handler' => [$this, 'get_latest_release'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'instance', 'user']],
			'create' => ['handler' => [$this, 'create_release'], 'required' => ['owner', 'repo', 'tag_name'], 'args' => ['owner', 'repo', 'tag_name', 'name', 'body', 'draft', 'prerelease', 'target_commitish', 'instance', 'user']],
			'edit' => ['handler' => [$this, 'edit_release'], 'required' => ['owner', 'repo', 'id'], 'args' => ['owner', 'repo', 'id', 'tag_name', 'name', 'body', 'draft', 'prerelease', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_release'], 'required' => ['owner', 'repo', 'id'], 'args' => ['owner', 'repo', 'id', 'instance', 'user']],
			'delete_by_tag' => ['handler' => [$this, 'delete_release_by_tag'], 'required' => ['owner', 'repo', 'tag'], 'args' => ['owner', 'repo', 'tag', 'instance', 'user']],
		]);
	}

	public function list_releases(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases", ['page' => $page, 'limit' => $limit]);
	}

	public function get_release_by_id(string $owner, string $repo, int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/{$id}");
	}

	public function get_release_by_tag(string $owner, string $repo, string $tag, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/tags/{$tag}");
	}

	public function get_latest_release(string $owner, string $repo, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/latest");
	}

	public function create_release(string $owner, string $repo, string $tag_name, string $name = '', string $body = '', bool $draft = false, bool $prerelease = false, ?string $target_commitish = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['tag_name' => $tag_name, 'draft' => $draft, 'prerelease' => $prerelease];
		if (!empty($name)) $data['name'] = $name;
		if (!empty($body)) $data['body'] = $body;
		if ($target_commitish !== null) $data['target_commitish'] = $target_commitish;
		return $client->post("repos/{$owner}/{$repo}/releases", $data);
	}

	public function edit_release(string $owner, string $repo, int $id, ?string $tag_name = null, ?string $name = null, ?string $body = null, ?bool $draft = null, ?bool $prerelease = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = [];
		if ($tag_name !== null) $data['tag_name'] = $tag_name;
		if ($name !== null) $data['name'] = $name;
		if ($body !== null) $data['body'] = $body;
		if ($draft !== null) $data['draft'] = $draft;
		if ($prerelease !== null) $data['prerelease'] = $prerelease;
		return $client->patch("repos/{$owner}/{$repo}/releases/{$id}", $data);
	}

	public function delete_release(string $owner, string $repo, int $id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/releases/{$id}");
	}

	public function delete_release_by_tag(string $owner, string $repo, string $tag, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/releases/tags/{$tag}");
	}
}
