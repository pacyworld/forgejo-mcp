<?php
/**
 * Forgejo MCP Server — Organization Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class OrgTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'search_org_teams', description: 'Search teams within an organization.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'q' => ['type' => 'string', 'description' => 'Search query (optional)'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org']])]
	public function search_org_teams(string $org, ?string $q = null, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($q !== null) $query['q'] = $q;
		return $client->get("orgs/{$org}/teams", $query);
	}
}
