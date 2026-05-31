<?php
/**
 * Forgejo MCP Server — Tag Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class TagTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_tags', description: 'List tags of a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function list_tags(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/tags", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'get_tag', description: 'Get a specific tag by name.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag' => ['type' => 'string', 'description' => 'Tag name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'tag']])]
	public function get_tag(string $owner, string $repo, string $tag, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/tags/{$tag}");
	}

	#[McpTool(name: 'create_tag', description: 'Create a tag in a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag_name' => ['type' => 'string', 'description' => 'Tag name'], 'target' => ['type' => 'string', 'description' => 'Branch or SHA to tag (optional, defaults to default branch)'], 'message' => ['type' => 'string', 'description' => 'Tag message for annotated tag (optional)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'tag_name']])]
	public function create_tag(string $owner, string $repo, string $tag_name, ?string $target = null, ?string $message = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['tag_name' => $tag_name];
		if ($target !== null) $data['target'] = $target;
		if ($message !== null) $data['message'] = $message;
		return $client->post("repos/{$owner}/{$repo}/tags", $data);
	}

	#[McpTool(name: 'delete_tag', description: 'Delete a tag from a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag' => ['type' => 'string', 'description' => 'Tag name to delete'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'tag']])]
	public function delete_tag(string $owner, string $repo, string $tag, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/tags/{$tag}");
	}
}
