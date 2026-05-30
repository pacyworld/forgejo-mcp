<?php
/**
 * Forgejo MCP Server — Milestone Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class MilestoneTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_repo_milestones', description: 'List milestones in a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'state' => ['type' => 'string', 'description' => 'Filter: open, closed, all'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function list_repo_milestones(string $owner, string $repo, string $state = 'open', int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/milestones", ['state' => $state, 'page' => $page, 'limit' => $limit]);
	}
}
