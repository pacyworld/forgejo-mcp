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

	#[McpTool(name: 'list_tags', description: 'List a repository\'s tags.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'instance', 'user']])]
	public function list_tags(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/tags", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'get_tag', description: 'Get a tag by name.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag' => ['type' => 'string'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'tag', 'instance', 'user']])]
	public function get_tag(string $owner, string $repo, string $tag, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/tags/{$tag}");
	}

	#[McpTool(name: 'create_tag', description: 'Create a tag.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag_name' => ['type' => 'string'], 'target' => ['type' => 'string', 'description' => 'Branch or SHA; default branch if omitted'], 'message' => ['type' => 'string', 'description' => 'Makes an annotated tag'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'tag_name', 'instance', 'user']])]
	public function create_tag(string $owner, string $repo, string $tag_name, ?string $target = null, ?string $message = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['tag_name' => $tag_name];
		if ($target !== null) $data['target'] = $target;
		if ($message !== null) $data['message'] = $message;
		return $client->post("repos/{$owner}/{$repo}/tags", $data);
	}

	#[McpTool(name: 'delete_tag', description: 'Delete a tag.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'tag' => ['type' => 'string'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'tag', 'instance', 'user']])]
	public function delete_tag(string $owner, string $repo, string $tag, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/tags/{$tag}");
	}
}
