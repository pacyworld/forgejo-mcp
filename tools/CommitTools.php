<?php
/**
 * Forgejo MCP Server — Commit Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class CommitTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_repo_commits', description: 'List commits in a repository.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'repo' => ['type' => 'string'], 'sha' => ['type' => 'string', 'description' => 'Branch or commit SHA to start from'], 'path' => ['type' => 'string', 'description' => 'Only commits touching this path'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer', 'description' => 'default 20'], 'instance' => ['type' => 'string'], 'user' => ['type' => 'string']], 'required' => ['owner', 'repo', 'instance', 'user']])]
	public function list_repo_commits(string $owner, string $repo, ?string $sha = null, ?string $path = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($sha !== null) $query['sha'] = $sha;
		if ($path !== null) $query['path'] = $path;
		return $client->get("repos/{$owner}/{$repo}/commits", $query);
	}
}
