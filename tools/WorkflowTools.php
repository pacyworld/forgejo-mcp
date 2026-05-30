<?php
/**
 * Forgejo MCP Server — Workflow / Actions Tools
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\InstanceManager;

class WorkflowTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(name: 'dispatch_workflow', description: 'Trigger a workflow dispatch event.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'workflow_id' => ['type' => 'string', 'description' => 'Workflow filename (e.g., ci.yml)'], 'ref' => ['type' => 'string', 'description' => 'Branch or tag to run on'], 'inputs' => ['type' => 'object', 'description' => 'Workflow input parameters'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'workflow_id', 'ref']])]
	public function dispatch_workflow(string $owner, string $repo, string $workflow_id, string $ref, ?array $inputs = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['ref' => $ref];
		if ($inputs !== null) $data['inputs'] = $inputs;
		return $client->post("repos/{$owner}/{$repo}/actions/workflows/{$workflow_id}/dispatches", $data);
	}

	#[McpTool(name: 'list_workflow_runs', description: 'List workflow runs for a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'page' => ['type' => 'integer', 'description' => 'Page number'], 'limit' => ['type' => 'integer', 'description' => 'Results per page'], 'status' => ['type' => 'string', 'description' => 'Filter by status: success, failure, waiting, running'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function list_workflow_runs(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $status = null, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		$query = ['page' => $page, 'limit' => $limit];
		if ($status !== null) $query['status'] = $status;
		return $client->get("repos/{$owner}/{$repo}/actions/runs", $query);
	}

	#[McpTool(name: 'get_workflow_run', description: 'Get details of a specific workflow run.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'run_id' => ['type' => 'integer', 'description' => 'Workflow run ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'run_id']])]
	public function get_workflow_run(string $owner, string $repo, int $run_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/runs/{$run_id}");
	}
}
