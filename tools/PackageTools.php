<?php
/**
 * Forgejo MCP Server — Package Registry Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class PackageTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'list_packages', description: 'List packages for an owner (user or org).', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Package owner (user or org)'], 'type' => ['type' => 'string', 'description' => 'Package type filter: generic, container, npm, pypi, etc.'], 'q' => ['type' => 'string', 'description' => 'Search query (optional)'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner']])]
	public function list_packages(string $owner, ?string $type = null, ?string $q = null, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($type !== null) $query['type'] = $type;
		if ($q !== null) $query['q'] = $q;
		return $client->get("packages/{$owner}", $query);
	}

	#[McpTool(name: 'get_package', description: 'Get details of a specific package.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'type' => ['type' => 'string', 'description' => 'Package type (generic, container, npm, etc.)'], 'name' => ['type' => 'string', 'description' => 'Package name'], 'version' => ['type' => 'string', 'description' => 'Package version'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'type', 'name', 'version']])]
	public function get_package(string $owner, string $type, string $name, string $version, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("packages/{$owner}/{$type}/{$name}/{$version}");
	}

	#[McpTool(name: 'delete_package', description: 'Delete a package version.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'type' => ['type' => 'string', 'description' => 'Package type'], 'name' => ['type' => 'string', 'description' => 'Package name'], 'version' => ['type' => 'string', 'description' => 'Package version'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'type', 'name', 'version']])]
	public function delete_package(string $owner, string $type, string $name, string $version, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("packages/{$owner}/{$type}/{$name}/{$version}");
	}

	#[McpTool(name: 'list_package_files', description: 'List files in a package version.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string'], 'type' => ['type' => 'string'], 'name' => ['type' => 'string'], 'version' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'type', 'name', 'version']])]
	public function list_package_files(string $owner, string $type, string $name, string $version, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("packages/{$owner}/{$type}/{$name}/{$version}/files");
	}
}
