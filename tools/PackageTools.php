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

	#[McpTool(name: 'list_packages', description: 'List packages owned by a user or org.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'User or org'], 'type' => ['type' => 'string', 'description' => 'e.g. generic, container, npm, pypi'], 'q' => ['type' => 'string'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'], 'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)']], 'required' => ['owner', 'instance', 'user']])]
	public function list_packages(string $owner, ?string $type = null, ?string $q = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($type !== null) $query['type'] = $type;
		if ($q !== null) $query['q'] = $q;
		return $client->get("packages/{$owner}", $query);
	}

	#[McpTool(name: 'get_package', description: 'Get a package version.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'User or org'], 'type' => ['type' => 'string', 'description' => 'e.g. generic, container, npm'], 'name' => ['type' => 'string'], 'version' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'], 'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)']], 'required' => ['owner', 'type', 'name', 'version', 'instance', 'user']])]
	public function get_package(string $owner, string $type, string $name, string $version, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("packages/{$owner}/{$type}/{$name}/{$version}");
	}

	#[McpTool(name: 'delete_package', description: 'Delete a package version.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'User or org'], 'type' => ['type' => 'string', 'description' => 'e.g. generic, container, npm'], 'name' => ['type' => 'string'], 'version' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'], 'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)']], 'required' => ['owner', 'type', 'name', 'version', 'instance', 'user']])]
	public function delete_package(string $owner, string $type, string $name, string $version, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("packages/{$owner}/{$type}/{$name}/{$version}");
	}

	#[McpTool(name: 'list_package_files', description: 'List files in a package version.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'User or org'], 'type' => ['type' => 'string', 'description' => 'e.g. generic, container, npm'], 'name' => ['type' => 'string'], 'version' => ['type' => 'string'], 'instance' => ['type' => 'string', 'description' => 'Instance name (see list_forgejo_instances)'], 'user' => ['type' => 'string', 'description' => 'User identity for the instance (see list_forgejo_instances)']], 'required' => ['owner', 'type', 'name', 'version', 'instance', 'user']])]
	public function list_package_files(string $owner, string $type, string $name, string $version, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("packages/{$owner}/{$type}/{$name}/{$version}/files");
	}
}
