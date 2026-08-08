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

	#[McpTool(name: 'dispatch_workflow', description: 'Trigger a workflow dispatch event.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'workflow_id' => ['type' => 'string', 'description' => 'Workflow filename (e.g., ci.yml)'], 'ref' => ['type' => 'string', 'description' => 'Branch or tag to run on'], 'inputs' => ['type' => 'object', 'description' => 'Workflow input parameters'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'workflow_id', 'ref', 'instance', 'user']])]
	public function dispatch_workflow(string $owner, string $repo, string $workflow_id, string $ref, ?array $inputs = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['ref' => $ref];
		if ($inputs !== null) $data['inputs'] = $inputs;
		return $client->post("repos/{$owner}/{$repo}/actions/workflows/{$workflow_id}/dispatches", $data);
	}

	#[McpTool(name: 'list_workflow_runs', description: 'List workflow runs for a repository.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'page' => ['type' => 'integer', 'description' => 'Page number'], 'limit' => ['type' => 'integer', 'description' => 'Results per page'], 'status' => ['type' => 'string', 'description' => 'Filter by status: success, failure, waiting, running'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'instance', 'user']])]
	public function list_workflow_runs(string $owner, string $repo, int $page = 1, int $limit = 20, ?string $status = null, string $instance = '', string $user = ''): array
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

	#[McpTool(name: 'get_workflow_run', description: 'Get details of a specific workflow run.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'run_id' => ['type' => 'integer', 'description' => 'Workflow run ID'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'run_id', 'instance', 'user']])]
	public function get_workflow_run(string $owner, string $repo, int $run_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/runs/{$run_id}");
	}

	#[McpTool(name: 'list_workflow_run_jobs', description: 'List jobs of a workflow run (id, name, status, attempt). Use a job id with get_action_job_logs, or a job index with get_workflow_job_logs.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'run_id' => ['type' => 'integer', 'description' => 'Workflow run ID (from list_workflow_runs)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'run_id', 'instance', 'user']])]
	public function list_workflow_run_jobs(string $owner, string $repo, int $run_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/runs/{$run_id}/jobs");
	}

	#[McpTool(name: 'get_workflow_job_logs', description: 'Download logs for a workflow run job identified by run ID and job index. On Forgejo 16+ servers the official REST API is used (API tokens work for public and private repos); on older servers it falls back to the legacy web route which only works for public repositories. Specify job_index (0-based, default 0) and attempt (default 1).', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'run_id' => ['type' => 'integer', 'description' => 'Workflow run ID (from list_workflow_runs)'], 'job_index' => ['type' => 'integer', 'description' => 'Job index within the run (0-based, default 0)'], 'attempt' => ['type' => 'integer', 'description' => 'Attempt number (default 1)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'run_id', 'instance', 'user']])]
	public function get_workflow_job_logs(string $owner, string $repo, int $run_id, int $job_index = 0, int $attempt = 1, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);

		// Forgejo 16+ exposes logs via the REST API with token auth — prefer it.
		if ($client->supportsActionLogsApi()) {
			return $this->getJobLogsViaApi($client, $owner, $repo, $run_id, $job_index, $attempt);
		}

		try {
			$logs = $client->getRaw("{$owner}/{$repo}/actions/runs/{$run_id}/jobs/{$job_index}/attempt/{$attempt}/logs", [], true);
		} catch (\Forgejo\ClientException $e) {
			if ($e->getCode() === 404) {
				$url = $client->getBaseUrl() . "/{$owner}/{$repo}/actions/runs/{$run_id}/jobs/{$job_index}/attempt/{$attempt}/logs";
				return [
					'error' => 'Log download failed (404). This is likely a private repository.',
					'reason' => 'This server predates the Forgejo 16 action logs REST API. Logs are served from a web route that requires browser session authentication. API tokens are not accepted for this endpoint.',
					'limitation' => 'This is a Forgejo platform limitation on servers older than 16.0, not a bug in this MCP server.',
					'workaround' => "View the logs in your browser: {$url} — or upgrade the server to Forgejo 16+ to enable API log download (get_action_job_logs).",
				];
			}
			throw $e;
		}

		return ['logs' => $logs];
	}

	#[McpTool(name: 'get_action_job_logs', description: 'Download the plaintext logs of a single workflow job by job ID (Forgejo 16+ only). Works for public and private repositories via API token. Omit attempt to fetch the latest attempt; attempt is 1-based and matches the attempt field from list_workflow_run_jobs. Returns a structured error when the connected server is older than Forgejo 16.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'job_id' => ['type' => 'integer', 'description' => 'Workflow job ID (from list_workflow_run_jobs)'], 'attempt' => ['type' => 'integer', 'description' => 'Attempt number (1-based, omit for latest)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'job_id', 'instance', 'user']])]
	public function get_action_job_logs(string $owner, string $repo, int $job_id, ?int $attempt = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$unsupported = $this->requireActionLogsApi($client);
		if ($unsupported !== null) {
			return $unsupported;
		}

		$query = [];
		if ($attempt !== null) {
			$query['attempt'] = $attempt;
		}

		try {
			$logs = $client->getRaw("repos/{$owner}/{$repo}/actions/jobs/{$job_id}/logs", $query);
		} catch (\Forgejo\ClientException $e) {
			if ($e->getCode() === 404) {
				return [
					'error' => "No logs available for job {$job_id}" . ($attempt !== null ? " attempt {$attempt}" : '') . ' (404).',
					'reason' => 'The job does not exist, belongs to a different repository, has not executed yet, the attempt number is unknown, or its logs have expired on the server.',
				];
			}
			throw $e;
		}

		return [
			'job_id' => $job_id,
			'attempt' => $attempt ?? 'latest',
			'logs' => $logs,
		];
	}

	#[McpTool(name: 'download_action_run_logs', description: 'Download logs for every job in a workflow run (Forgejo 16+ only). The server streams a ZIP with one {job-name}-{job-id}-attempt-{N}.log entry per job; entries flagged missing are jobs that have not started or whose logs expired. Log text is extracted inline when the PHP zip extension is available on this MCP host, otherwise the archive is returned base64-encoded. Returns a structured error when the connected server is older than Forgejo 16.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'run_id' => ['type' => 'integer', 'description' => 'Workflow run ID (from list_workflow_runs)'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'run_id', 'instance', 'user']])]
	public function download_action_run_logs(string $owner, string $repo, int $run_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$unsupported = $this->requireActionLogsApi($client);
		if ($unsupported !== null) {
			return $unsupported;
		}

		try {
			$zip = $client->getRaw("repos/{$owner}/{$repo}/actions/runs/{$run_id}/logs");
		} catch (\Forgejo\ClientException $e) {
			if ($e->getCode() === 404) {
				return [
					'error' => "Run {$run_id} not found (404).",
					'reason' => 'The run does not exist or belongs to a different repository.',
				];
			}
			throw $e;
		}

		$result = [
			'run_id' => $run_id,
			'archive_bytes' => strlen($zip),
		];

		$files = $this->extractLogZip($zip);
		if ($files === null) {
			$result['format'] = 'zip-base64';
			$result['archive_base64'] = base64_encode($zip);
			$result['note'] = 'Log archive could not be extracted on this MCP host (PHP zip extension not installed). The raw ZIP is returned base64-encoded.';
			return $result;
		}

		$result['format'] = 'files';
		$result['files'] = $files;
		return $result;
	}

	/**
	 * Fetch job logs via the Forgejo 16+ REST API for get_workflow_job_logs.
	 *
	 * Resolves the job index within the run to a job ID, then downloads the
	 * plaintext log for the requested attempt.
	 */
	private function getJobLogsViaApi(\Forgejo\Client $client, string $owner, string $repo, int $run_id, int $job_index, int $attempt): array
	{
		$jobs = $client->get("repos/{$owner}/{$repo}/actions/runs/{$run_id}/jobs");
		$jobList = (isset($jobs['jobs']) && is_array($jobs['jobs'])) ? $jobs['jobs'] : $jobs;

		if (!isset($jobList[$job_index]) || !is_array($jobList[$job_index])) {
			return [
				'error' => "Job index {$job_index} is out of range for run {$run_id}.",
				'job_count' => count($jobList),
				'hint' => 'Use list_workflow_run_jobs to see the jobs of this run and their indices.',
			];
		}

		$job = $jobList[$job_index];
		$jobId = (int)($job['id'] ?? 0);

		try {
			$logs = $client->getRaw("repos/{$owner}/{$repo}/actions/jobs/{$jobId}/logs", ['attempt' => $attempt]);
		} catch (\Forgejo\ClientException $e) {
			if ($e->getCode() === 404) {
				return [
					'error' => "No logs available for job {$jobId} attempt {$attempt} (404).",
					'reason' => 'The job has not executed yet, the attempt number is unknown, or its logs have expired on the server.',
				];
			}
			throw $e;
		}

		return [
			'logs' => $logs,
			'job_id' => $jobId,
			'job_name' => $job['name'] ?? '',
			'attempt' => $attempt,
		];
	}

	/**
	 * Return a structured "feature not supported" response when the connected
	 * server predates the Forgejo 16 action logs API, or null when supported.
	 *
	 * @return array|null Structured error response, or null when supported
	 */
	private function requireActionLogsApi(\Forgejo\Client $client): ?array
	{
		if ($client->supportsActionLogsApi()) {
			return null;
		}

		$version = $client->getServerVersion();
		return [
			'error' => 'The action log download API is not available on this server.',
			'required_version' => 'Forgejo ' . \Forgejo\Client::ACTION_LOGS_API_MIN_VERSION . ' or newer',
			'detected_version' => $version !== '' ? $version : 'unknown',
			'details' => 'Downloading action logs over the REST API (actions/jobs/{job_id}/logs and actions/runs/{run_id}/logs) was added in Forgejo 16. The connected server reports an older version, so these endpoints do not exist there.',
			'workaround' => 'View logs in the browser, or use get_workflow_job_logs which falls back to the legacy web route (public repositories only).',
		];
	}

	/** Maximum uncompressed size of a single extracted log entry */
	private const MAX_LOG_ENTRY_BYTES = 4194304;

	/**
	 * Extract a Forgejo run-logs ZIP into per-job log entries.
	 *
	 * Entries whose name ends in .MISSING are placeholders for jobs that have
	 * not started or whose logs expired; they are flagged, not extracted.
	 *
	 * @param  string     $zip Raw ZIP archive data
	 * @return array|null      List of entries, or null when the archive cannot be processed on this host
	 */
	private function extractLogZip(string $zip): ?array
	{
		if (!class_exists(\ZipArchive::class)) {
			return null;
		}

		$tmpFile = tempnam(sys_get_temp_dir(), 'forgejo-run-logs-');
		if ($tmpFile === false) {
			return null;
		}
		file_put_contents($tmpFile, $zip);

		$archive = new \ZipArchive();
		if ($archive->open($tmpFile) !== true) {
			@unlink($tmpFile);
			return null;
		}

		$files = [];
		for ($i = 0; $i < $archive->numFiles; $i++) {
			$stat = $archive->statIndex($i);
			if ($stat === false) {
				continue;
			}

			$entry = [
				'name' => $stat['name'],
				'size' => $stat['size'],
				'missing' => str_ends_with($stat['name'], '.MISSING'),
			];

			if (!$entry['missing']) {
				if ($stat['size'] > self::MAX_LOG_ENTRY_BYTES) {
					$entry['content_omitted'] = 'Entry exceeds ' . self::MAX_LOG_ENTRY_BYTES . ' bytes; fetch it individually with get_action_job_logs.';
				} else {
					$content = $archive->getFromIndex($i);
					$entry['content'] = is_string($content) ? $content : '';
				}
			}

			$files[] = $entry;
		}

		$archive->close();
		@unlink($tmpFile);
		return $files;
	}

	#[McpTool(name: 'list_repo_action_secrets', description: 'List action secrets for a repository (names only, values are never exposed).', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'instance', 'user']])]
	public function list_repo_action_secrets(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/secrets", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'create_or_update_repo_action_secret', description: 'Create or update an action secret for a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'secret_name' => ['type' => 'string', 'description' => 'Secret name (e.g., FORGE_TOKEN)'], 'data' => ['type' => 'string', 'description' => 'Secret value'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'secret_name', 'data', 'instance', 'user']])]
	public function create_or_update_repo_action_secret(string $owner, string $repo, string $secret_name, string $data, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("repos/{$owner}/{$repo}/actions/secrets/{$secret_name}", ['data' => $data]);
	}

	#[McpTool(name: 'delete_repo_action_secret', description: 'Delete an action secret from a repository.', inputSchema: ['type' => 'object', 'properties' => ['owner' => ['type' => 'string', 'description' => 'Repository owner'], 'repo' => ['type' => 'string', 'description' => 'Repository name'], 'secret_name' => ['type' => 'string', 'description' => 'Secret name to delete'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['owner', 'repo', 'secret_name', 'instance', 'user']])]
	public function delete_repo_action_secret(string $owner, string $repo, string $secret_name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/actions/secrets/{$secret_name}");
	}

	#[McpTool(name: 'list_org_action_secrets', description: 'List action secrets for an organization.', readOnlyHint: true, inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['org', 'instance', 'user']])]
	public function list_org_action_secrets(string $org, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/actions/secrets", ['page' => $page, 'limit' => $limit]);
	}

	#[McpTool(name: 'create_or_update_org_action_secret', description: 'Create or update an action secret for an organization.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'secret_name' => ['type' => 'string', 'description' => 'Secret name'], 'data' => ['type' => 'string', 'description' => 'Secret value'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['org', 'secret_name', 'data', 'instance', 'user']])]
	public function create_or_update_org_action_secret(string $org, string $secret_name, string $data, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("orgs/{$org}/actions/secrets/{$secret_name}", ['data' => $data]);
	}

	#[McpTool(name: 'delete_org_action_secret', description: 'Delete an action secret from an organization.', inputSchema: ['type' => 'object', 'properties' => ['org' => ['type' => 'string', 'description' => 'Organization name'], 'secret_name' => ['type' => 'string', 'description' => 'Secret name'], 'instance' => ['type' => 'string', 'description' => 'Forgejo instance'], 'user' => ['type' => 'string', 'description' => 'User identity']], 'required' => ['org', 'secret_name', 'instance', 'user']])]
	public function delete_org_action_secret(string $org, string $secret_name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("orgs/{$org}/actions/secrets/{$secret_name}");
	}
}
