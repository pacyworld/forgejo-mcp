<?php
/**
 * Forgejo MCP Server — Package Registry Tools
 *
 * Consolidated: one `package` tool whose `action` selects the operation.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class PackageTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'package',
		description: 'Work with the package registry. Actions and their required parameters: list(owner; optional type, q), get(owner, type, name, version), list_files(owner, type, name, version), delete(owner, type, name, version).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'get', 'list_files', 'delete']],
				'owner' => ['type' => 'string', 'description' => 'User or org'],
				'type' => ['type' => 'string', 'description' => 'e.g. generic, container, npm, pypi'],
				'name' => ['type' => 'string'],
				'version' => ['type' => 'string'],
				'q' => ['type' => 'string'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_packages' => 'package action=list',
			'get_package' => 'package action=get',
			'list_package_files' => 'package action=list_files',
			'delete_package' => 'package action=delete',
		]
	)]
	public function package(string $action, ?string $owner = null, ?string $type = null, ?string $name = null, ?string $version = null, ?string $q = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('package', $action, get_defined_vars(), [
			'list' => ['handler' => [$this, 'list_packages'], 'required' => ['owner'], 'args' => ['owner', 'type', 'q', 'page', 'limit', 'instance', 'user']],
			'get' => ['handler' => [$this, 'get_package'], 'required' => ['owner', 'type', 'name', 'version'], 'args' => ['owner', 'type', 'name', 'version', 'instance', 'user']],
			'list_files' => ['handler' => [$this, 'list_package_files'], 'required' => ['owner', 'type', 'name', 'version'], 'args' => ['owner', 'type', 'name', 'version', 'instance', 'user']],
			'delete' => ['handler' => [$this, 'delete_package'], 'required' => ['owner', 'type', 'name', 'version'], 'args' => ['owner', 'type', 'name', 'version', 'instance', 'user']],
		]);
	}

	public function list_packages(string $owner, ?string $type = null, ?string $q = null, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($type !== null) $query['type'] = $type;
		if ($q !== null) $query['q'] = $q;
		return $client->get("packages/{$owner}", $query);
	}

	public function get_package(string $owner, string $type, string $name, string $version, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("packages/{$owner}/{$type}/{$name}/{$version}");
	}

	public function delete_package(string $owner, string $type, string $name, string $version, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("packages/{$owner}/{$type}/{$name}/{$version}");
	}

	public function list_package_files(string $owner, string $type, string $name, string $version, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("packages/{$owner}/{$type}/{$name}/{$version}/files");
	}
}
