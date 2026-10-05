<?php
/**
 * Forgejo MCP Server — Workflow / Actions Tools
 *
 * Consolidated: `workflow` covers runs/jobs/logs/dispatch, `action_secret`
 * covers repository and organization secrets. The per-operation methods
 * remain as internal handlers.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

class WorkflowTools extends ConsolidatedToolBase
{
	#[McpTool(
		name: 'workflow',
		description: 'Work with Actions workflows. Actions and their required parameters: dispatch(owner, repo, workflow_id, ref; optional inputs), list_runs(owner, repo; optional status), get_run(owner, repo, run_id), list_jobs(owner, repo, run_id), job_logs(owner, repo, run_id; optional job_index 0-based, attempt; before Forgejo 16 works for public repositories only), job_logs_by_id(owner, repo, job_id; optional attempt 1-based, latest if omitted; Forgejo 16+ only), download_run_logs(owner, repo, run_id; every job, or raw ZIP base64 when the host cannot extract; Forgejo 16+ only). All take owner, repo.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['dispatch', 'list_runs', 'get_run', 'list_jobs', 'job_logs', 'job_logs_by_id', 'download_run_logs']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'workflow_id' => ['type' => 'string', 'description' => 'Workflow filename, e.g. ci.yml'],
				'ref' => ['type' => 'string', 'description' => 'Branch or tag to run on'],
				'inputs' => ['type' => 'object', 'description' => 'Workflow inputs'],
				'status' => ['type' => 'string', 'description' => 'success|failure|waiting|running'],
				'run_id' => ['type' => 'integer'],
				'job_index' => ['type' => 'integer', 'description' => '0-based position of the job within the run (default 0)'],
				'job_id' => ['type' => 'integer', 'description' => 'From list_jobs'],
				'attempt' => ['type' => 'integer'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer', 'description' => 'default 20'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'dispatch_workflow' => 'workflow action=dispatch',
			'list_workflow_runs' => 'workflow action=list_runs',
			'get_workflow_run' => 'workflow action=get_run',
			'list_workflow_run_jobs' => 'workflow action=list_jobs',
			'get_workflow_job_logs' => 'workflow action=job_logs',
			'get_action_job_logs' => 'workflow action=job_logs_by_id',
			'download_action_run_logs' => 'workflow action=download_run_logs',
		]
	)]
	public function workflow(string $action, ?string $owner = null, ?string $repo = null, ?string $workflow_id = null, ?string $ref = null, ?array $inputs = null, ?string $status = null, ?int $run_id = null, ?int $job_index = null, ?int $job_id = null, ?int $attempt = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		$repoScoped = ['owner', 'repo'];
		return $this->dispatch('workflow', $action, get_defined_vars(), [
			'dispatch' => ['handler' => [$this, 'dispatch_workflow'], 'required' => array_merge($repoScoped, ['workflow_id', 'ref']), 'args' => ['owner', 'repo', 'workflow_id', 'ref', 'inputs', 'instance', 'user']],
			'list_runs' => ['handler' => [$this, 'list_workflow_runs'], 'required' => $repoScoped, 'args' => ['owner', 'repo', 'page', 'limit', 'status', 'instance', 'user']],
			'get_run' => ['handler' => [$this, 'get_workflow_run'], 'required' => array_merge($repoScoped, ['run_id']), 'args' => ['owner', 'repo', 'run_id', 'instance', 'user']],
			'list_jobs' => ['handler' => [$this, 'list_workflow_run_jobs'], 'required' => array_merge($repoScoped, ['run_id']), 'args' => ['owner', 'repo', 'run_id', 'instance', 'user']],
			'job_logs' => ['handler' => [$this, 'get_workflow_job_logs'], 'required' => array_merge($repoScoped, ['run_id']), 'args' => ['owner', 'repo', 'run_id', 'job_index', 'attempt', 'instance', 'user']],
			'job_logs_by_id' => ['handler' => [$this, 'get_action_job_logs'], 'required' => array_merge($repoScoped, ['job_id']), 'args' => ['owner', 'repo', 'job_id', 'attempt', 'instance', 'user']],
			'download_run_logs' => ['handler' => [$this, 'download_action_run_logs'], 'required' => array_merge($repoScoped, ['run_id']), 'args' => ['owner', 'repo', 'run_id', 'instance', 'user']],
		]);
	}

	#[McpTool(
		name: 'action_secret',
		description: 'Manage Actions secrets (values are write-only). Actions and their required parameters: list_repo(owner, repo), set_repo(owner, repo, secret_name, data), delete_repo(owner, repo, secret_name), list_org(org), set_org(org, secret_name, data), delete_org(org, secret_name). Listing accepts optional page/limit.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list_repo', 'set_repo', 'delete_repo', 'list_org', 'set_org', 'delete_org']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'org' => ['type' => 'string'],
				'secret_name' => ['type' => 'string', 'description' => 'e.g. FORGE_TOKEN'],
				'data' => ['type' => 'string', 'description' => 'Secret value'],
				'page' => ['type' => 'integer'],
				'limit' => ['type' => 'integer'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'instance', 'user'],
		],
		renamedFrom: [
			'list_repo_action_secrets' => 'action_secret action=list_repo',
			'create_or_update_repo_action_secret' => 'action_secret action=set_repo',
			'delete_repo_action_secret' => 'action_secret action=delete_repo',
			'list_org_action_secrets' => 'action_secret action=list_org',
			'create_or_update_org_action_secret' => 'action_secret action=set_org',
			'delete_org_action_secret' => 'action_secret action=delete_org',
		]
	)]
	public function action_secret(string $action, ?string $owner = null, ?string $repo = null, ?string $org = null, ?string $secret_name = null, ?string $data = null, ?int $page = null, ?int $limit = null, string $instance = '', string $user = ''): mixed
	{
		return $this->dispatch('action_secret', $action, get_defined_vars(), [
			'list_repo' => ['handler' => [$this, 'list_repo_action_secrets'], 'required' => ['owner', 'repo'], 'args' => ['owner', 'repo', 'page', 'limit', 'instance', 'user']],
			'set_repo' => ['handler' => [$this, 'create_or_update_repo_action_secret'], 'required' => ['owner', 'repo', 'secret_name', 'data'], 'args' => ['owner', 'repo', 'secret_name', 'data', 'instance', 'user']],
			'delete_repo' => ['handler' => [$this, 'delete_repo_action_secret'], 'required' => ['owner', 'repo', 'secret_name'], 'args' => ['owner', 'repo', 'secret_name', 'instance', 'user']],
			'list_org' => ['handler' => [$this, 'list_org_action_secrets'], 'required' => ['org'], 'args' => ['org', 'page', 'limit', 'instance', 'user']],
			'set_org' => ['handler' => [$this, 'create_or_update_org_action_secret'], 'required' => ['org', 'secret_name', 'data'], 'args' => ['org', 'secret_name', 'data', 'instance', 'user']],
			'delete_org' => ['handler' => [$this, 'delete_org_action_secret'], 'required' => ['org', 'secret_name'], 'args' => ['org', 'secret_name', 'instance', 'user']],
		]);
	}

	public function dispatch_workflow(string $owner, string $repo, string $workflow_id, string $ref, ?array $inputs = null, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		$data = ['ref' => $ref];
		if ($inputs !== null) $data['inputs'] = $inputs;
		return $client->post("repos/{$owner}/{$repo}/actions/workflows/{$workflow_id}/dispatches", $data);
	}

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

	public function get_workflow_run(string $owner, string $repo, int $run_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/runs/{$run_id}");
	}

	public function list_workflow_run_jobs(string $owner, string $repo, int $run_id, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/runs/{$run_id}/jobs");
	}

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
					'workaround' => "View the logs in your browser: {$url} — or upgrade the server to Forgejo 16+ to enable API log download (workflow action=job_logs_by_id).",
				];
			}
			throw $e;
		}

		return ['logs' => $logs];
	}

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

	public function list_repo_action_secrets(string $owner, string $repo, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("repos/{$owner}/{$repo}/actions/secrets", ['page' => $page, 'limit' => $limit]);
	}

	public function create_or_update_repo_action_secret(string $owner, string $repo, string $secret_name, string $data, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("repos/{$owner}/{$repo}/actions/secrets/{$secret_name}", ['data' => $data]);
	}

	public function delete_repo_action_secret(string $owner, string $repo, string $secret_name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("repos/{$owner}/{$repo}/actions/secrets/{$secret_name}");
	}

	public function list_org_action_secrets(string $org, int $page = 1, int $limit = 20, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->get("orgs/{$org}/actions/secrets", ['page' => $page, 'limit' => $limit]);
	}

	public function create_or_update_org_action_secret(string $org, string $secret_name, string $data, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->put("orgs/{$org}/actions/secrets/{$secret_name}", ['data' => $data]);
	}

	public function delete_org_action_secret(string $org, string $secret_name, string $instance = '', string $user = ''): array
	{
		$client = $this->manager->getClient($instance, $user);
		return $client->delete("orgs/{$org}/actions/secrets/{$secret_name}");
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
				'hint' => 'Use workflow action=list_jobs to see the jobs of this run and their indices.',
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
			'workaround' => 'View logs in the browser, or use workflow action=job_logs which falls back to the legacy web route (public repositories only).',
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
					$entry['content_omitted'] = 'Entry exceeds ' . self::MAX_LOG_ENTRY_BYTES . ' bytes; fetch it individually with workflow action=job_logs_by_id.';
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
}
