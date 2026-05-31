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
		$result = $client->get("repos/{$owner}/{$repo}/actions/runs", $query);
		if (isset($result['workflow_runs']) && is_array($result['workflow_runs'])) {
			$result['workflow_runs'] = array_reverse($result['workflow_runs']);
		}
		return $result;
	}

	#[McpTool(name: 'get_workflow_run', description: 'Get details of a specific workflow run.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'run_id' => ['type' => 'integer', 'description' => 'Workflow run ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'run_id']])]
	public function get_workflow_run(string $owner, string $repo, int $run_id, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/runs/{$run_id}");
	}

	#[McpTool(name: 'get_workflow_job_logs', description: 'Download logs for a workflow run job. Works for public repositories. Private repository logs require browser session auth (Forgejo limitation — the Actions log endpoint does not accept API tokens). Specify job_index (0-based, default 0) and attempt (default 1).', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'run_id' => ['type' => 'integer', 'description' => 'Workflow run ID (from list_workflow_runs)'], 'job_index' => ['type' => 'integer', 'description' => 'Job index within the run (0-based, default 0)'], 'attempt' => ['type' => 'integer', 'description' => 'Attempt number (default 1)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'run_id']])]
	public function get_workflow_job_logs(string $owner, string $repo, int $run_id, int $job_index = 0, int $attempt = 1, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);

		try {
			$logs = $client->getRaw("{$owner}/{$repo}/actions/runs/{$run_id}/jobs/{$job_index}/attempt/{$attempt}/logs", [], true);
		} catch (\Forgejo\ClientException $e) {
			if ($e->getCode() === 404) {
				$url = $client->getBaseUrl() . "/{$owner}/{$repo}/actions/runs/{$run_id}/jobs/{$job_index}/attempt/{$attempt}/logs";
				return [
					'error' => 'Log download failed (404). This is likely a private repository.',
					'reason' => 'Forgejo does not expose workflow logs via its API. Logs are served from a web route that requires browser session authentication. API tokens are not accepted for this endpoint.',
					'limitation' => 'This is a known Forgejo platform limitation, not a bug in this MCP server.',
					'workaround' => "View the logs in your browser: {$url}",
				];
			}
			throw $e;
		}

		return ['logs' => $logs];
	}

	#[McpTool(name: 'list_repo_action_secrets', description: 'List action secrets for a repository (names only, values are never exposed).', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo']])]
	public function list_repo_action_secrets(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/secrets", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'create_or_update_repo_action_secret', description: 'Create or update an action secret for a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'secret_name' => ['type' => 'string', 'description' => 'Secret name (e.g., FORGE_TOKEN)'], 'data' => ['type' => 'string', 'description' => 'Secret value'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'secret_name', 'data']])]
	public function create_or_update_repo_action_secret(string $owner, string $repo, string $secret_name, string $data, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("repos/{$owner}/{$repo}/actions/secrets/{$secret_name}", ['data' => $data]);
	}

	#[McpTool(name: 'delete_repo_action_secret', description: 'Delete an action secret from a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'secret_name' => ['type' => 'string', 'description' => 'Secret name to delete'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['owner', 'repo', 'secret_name']])]
	public function delete_repo_action_secret(string $owner, string $repo, string $secret_name, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/actions/secrets/{$secret_name}");
	}

	#[McpTool(name: 'list_org_action_secrets', description: 'List action secrets for an organization.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org']])]
	public function list_org_action_secrets(string $org, int $page = 1, int $limit = 20, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/actions/secrets", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'create_or_update_org_action_secret', description: 'Create or update an action secret for an organization.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'secret_name' => ['type' => 'string', 'description' => 'Secret name'], 'data' => ['type' => 'string', 'description' => 'Secret value'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org', 'secret_name', 'data']])]
	public function create_or_update_org_action_secret(string $org, string $secret_name, string $data, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("orgs/{$org}/actions/secrets/{$secret_name}", ['data' => $data]);
	}

	#[McpTool(name: 'delete_org_action_secret', description: 'Delete an action secret from an organization.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'secret_name' => ['type' => 'string', 'description' => 'Secret name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance (optional)'], 'user' => ['type' => 'string', 'description' => 'User identity (optional)']], 'required' => ['org', 'secret_name']])]
	public function delete_org_action_secret(string $org, string $secret_name, ?string $instance = null, ?string $user = null): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("orgs/{$org}/actions/secrets/{$secret_name}");
	}
}
