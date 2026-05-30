<?php
/**
 * Forgejo MCP Server — Release Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class ReleaseTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_releases', description: 'List releases for a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'page' => ['type' => 'integer', 'description' => 'Page number'], 'limit' => ['type' => 'integer', 'description' => 'Results per page'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function list_releases(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'get_release_by_id', description: 'Get a release by its ID.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'id' => ['type' => 'integer', 'description' => 'Release ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'id']])]
	public function get_release_by_id(string $owner, string $repo, int $id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/{$id}");
	}

	#[McpTool(name: 'get_release_by_tag', description: 'Get a release by its tag name.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag' => ['type' => 'string', 'description' => 'Tag name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'tag']])]
	public function get_release_by_tag(string $owner, string $repo, string $tag, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/tags/{$tag}");
	}

	#[McpTool(name: 'get_latest_release', description: 'Get the latest release of a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function get_latest_release(string $owner, string $repo, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/releases/latest");
	}

	#[McpTool(name: 'create_release', description: 'Create a new release.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag_name' => ['type' => 'string', 'description' => 'Tag name for the release'], 'name' => ['type' => 'string', 'description' => 'Release title'], 'body' => ['type' => 'string', 'description' => 'Release notes (Markdown)'], 'draft' => ['type' => 'boolean', 'description' => 'Mark as draft'], 'prerelease' => ['type' => 'boolean', 'description' => 'Mark as pre-release'], 'target_commitish' => ['type' => 'string', 'description' => 'Branch or commit to tag'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'tag_name']])]
	public function create_release(string $owner, string $repo, string $tag_name, string $name = '', string $body = '', bool $draft = false, bool $prerelease = false, ?string $target_commitish = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['tag_name' => $tag_name, 'draft' => $draft, 'prerelease' => $prerelease];
		if (!empty($name)) $data['name'] = $name;
		if (!empty($body)) $data['body'] = $body;
		if ($target_commitish !== null) $data['target_commitish'] = $target_commitish;
		return $client->post("repos/{$owner}/{$repo}/releases", $data);
	}

	#[McpTool(name: 'edit_release', description: 'Edit an existing release.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'id' => ['type' => 'integer', 'description' => 'Release ID'], 'tag_name' => ['type' => 'string'], 'name' => ['type' => 'string'], 'body' => ['type' => 'string'], 'draft' => ['type' => 'boolean'], 'prerelease' => ['type' => 'boolean'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'id']])]
	public function edit_release(string $owner, string $repo, int $id, ?string $tag_name = null, ?string $name = null, ?string $body = null, ?bool $draft = null, ?bool $prerelease = null, ?string $instance = null, ?string $user = null): array
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

	#[McpTool(name: 'delete_release', description: 'Delete a release by ID.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'id' => ['type' => 'integer', 'description' => 'Release ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'id']])]
	public function delete_release(string $owner, string $repo, int $id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/releases/{$id}");
	}

	#[McpTool(name: 'delete_release_by_tag', description: 'Delete a release by tag name.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag' => ['type' => 'string', 'description' => 'Tag name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'tag']])]
	public function delete_release_by_tag(string $owner, string $repo, string $tag, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/releases/tags/{$tag}");
	}
}
