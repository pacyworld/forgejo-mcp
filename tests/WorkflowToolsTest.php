<?php

use PHPUnit\Framework\TestCase;
use Forgejo\InstanceManager;

require_once APPLICATION_ROOT . 'tools/WorkflowTools.php';

class WorkflowToolsTest extends TestCase
{
	/**
	 * ZIP fixture: build/test job logs plus a .MISSING placeholder entry.
	 * Entries: build-11-attempt-1.log, test-12-attempt-1.log,
	 *          deploy-13-attempt-1.log.MISSING
	 */
	private const RUN_LOGS_ZIP_B64 = 'UEsDBBQAAAAIAKq9B13gwYsRGAAAACIAAAAWAAAAYnVpbGQtMTEtYXR0ZW1wdC0xLmxvZ0sqzcxJUcjJT1fIycxLVTDkSkIVMOICAFBLAwQUAAAACACqvQddscc5aBAAAAAOAAAAFQAAAHRlc3QtMTItYXR0ZW1wdC0xLmxvZytJLS5RyMlPVyhJrSjhAgBQSwMEFAAAAAgAqr0HXUwgYcwSAAAAEAAAAB8AAABkZXBsb3ktMTMtYXR0ZW1wdC0xLmxvZy5NSVNTSU5Hy8lPVyjNSyxLzMxJTMpJ5QIAUEsBAhQDFAAAAAgAqr0HXeDBixEYAAAAIgAAABYAAAAAAAAAAAAAAIABAAAAAGJ1aWxkLTExLWF0dGVtcHQtMS5sb2dQSwECFAMUAAAACACqvQddscc5aBAAAAAOAAAAFQAAAAAAAAAAAAAAgAFMAAAAdGVzdC0xMi1hdHRlbXB0LTEubG9nUEsBAhQDFAAAAAgAqr0HXUwgYcwSAAAAEAAAAB8AAAAAAAAAAAAAAIABjwAAAGRlcGxveS0xMy1hdHRlbXB0LTEubG9nLk1JU1NJTkdQSwUGAAAAAAMAAwDUAAAA3gAAAAAA';

	/**
	 * Build a WorkflowTools backed by an injected HTTP handler.
	 *
	 * @param callable    $handler  fn(string $method, string $url): array{code:int,body:string}
	 * @param array|null  $requests Captured request URLs
	 */
	private function makeTools(callable $handler, ?array &$requests): WorkflowTools
	{
		$requests = [];
		$httpClient = function ($method, $url, $headers, $body) use ($handler, &$requests) {
			$requests[] = $url;
			return $handler($method, $url);
		};
		$manager = new InstanceManager([
			'test' => [
				'url' => 'https://forgejo.example.com',
				'users' => ['me' => ['token' => 'abc']],
			],
		], 'test', 'me', $httpClient);
		return new WorkflowTools($manager);
	}

	/**
	 * HTTP handler that answers /api/v1/version with $version, matches any
	 * extra "needle => response" routes first, and 404s everything else.
	 */
	private function versionHandler(string $version, array $extra = []): callable
	{
		return function ($method, $url) use ($version, $extra) {
			foreach ($extra as $needle => $response) {
				if (str_contains($url, $needle)) {
					return $response;
				}
			}
			if (str_contains($url, 'api/v1/version')) {
				return ['code' => 200, 'body' => json_encode(['version' => $version])];
			}
			return ['code' => 404, 'body' => 'Not Found'];
		};
	}

	public function testListWorkflowRunJobs(): void
	{
		$tools = $this->makeTools(
			fn() => ['code' => 200, 'body' => '[{"id":11,"name":"build"},{"id":12,"name":"test"}]'],
			$requests
		);

		$result = $tools->list_workflow_run_jobs('o', 'r', 5, 'test', 'me');

		$this->assertCount(2, $result);
		$this->assertEquals(11, $result[0]['id']);
		$this->assertStringContainsString('api/v1/repos/o/r/actions/runs/5/jobs', $requests[0]);
	}

	public function testGetActionJobLogsOnForgejo16(): void
	{
		$tools = $this->makeTools($this->versionHandler('16.0.0', [
			'actions/jobs/11/logs' => ['code' => 200, 'body' => "line1\nline2\n"],
		]), $requests);

		$result = $tools->get_action_job_logs('o', 'r', 11, 2, 'test', 'me');

		$this->assertEquals("line1\nline2\n", $result['logs']);
		$this->assertEquals(11, $result['job_id']);
		$this->assertEquals(2, $result['attempt']);
		$this->assertStringContainsString('api/v1/repos/o/r/actions/jobs/11/logs', end($requests));
		$this->assertStringContainsString('attempt=2', end($requests));
	}

	public function testGetActionJobLogsDefaultsToLatestAttempt(): void
	{
		$tools = $this->makeTools($this->versionHandler('16.0.0', [
			'actions/jobs/11/logs' => ['code' => 200, 'body' => 'log text'],
		]), $requests);

		$result = $tools->get_action_job_logs('o', 'r', 11, null, 'test', 'me');

		$this->assertEquals('latest', $result['attempt']);
		$this->assertStringNotContainsString('attempt=', end($requests));
	}

	public function testGetActionJobLogsUnsupportedVersion(): void
	{
		$tools = $this->makeTools($this->versionHandler('11.0.3', [
			'actions/jobs' => ['code' => 500, 'body' => 'must not be requested'],
		]), $requests);

		$result = $tools->get_action_job_logs('o', 'r', 11, null, 'test', 'me');

		$this->assertArrayHasKey('error', $result);
		$this->assertEquals('11.0.3', $result['detected_version']);
		$this->assertStringContainsString('16.0.0', $result['required_version']);
		$this->assertArrayHasKey('workaround', $result);
		// Only the version lookup — the logs endpoint must never be called
		$this->assertCount(1, $requests);
	}

	public function testGetActionJobLogsUnknownServerVersion(): void
	{
		$tools = $this->makeTools(fn() => ['code' => 500, 'body' => 'broken'], $requests);

		$result = $tools->get_action_job_logs('o', 'r', 11, null, 'test', 'me');

		$this->assertArrayHasKey('error', $result);
		$this->assertEquals('unknown', $result['detected_version']);
	}

	public function testGetActionJobLogsNotFound(): void
	{
		$tools = $this->makeTools($this->versionHandler('16.0.0'), $requests);

		$result = $tools->get_action_job_logs('o', 'r', 99, 3, 'test', 'me');

		$this->assertArrayHasKey('error', $result);
		$this->assertStringContainsString('404', $result['error']);
		$this->assertStringContainsString('attempt 3', $result['error']);
	}

	public function testDownloadActionRunLogsUnsupportedVersion(): void
	{
		$tools = $this->makeTools($this->versionHandler('15.9.9'), $requests);

		$result = $tools->download_action_run_logs('o', 'r', 9, 'test', 'me');

		$this->assertArrayHasKey('error', $result);
		$this->assertEquals('15.9.9', $result['detected_version']);
		$this->assertCount(1, $requests);
	}

	public function testDownloadActionRunLogsRunNotFound(): void
	{
		$tools = $this->makeTools($this->versionHandler('16.0.0'), $requests);

		$result = $tools->download_action_run_logs('o', 'r', 42, 'test', 'me');

		$this->assertArrayHasKey('error', $result);
		$this->assertStringContainsString('42', $result['error']);
	}

	public function testDownloadActionRunLogsBase64Fallback(): void
	{
		if (class_exists(\ZipArchive::class)) {
			$this->markTestSkipped('ZipArchive is available; the base64 fallback is not used.');
		}

		$zip = base64_decode(self::RUN_LOGS_ZIP_B64);
		$tools = $this->makeTools($this->versionHandler('16.0.0', [
			'actions/runs/9/logs' => ['code' => 200, 'body' => $zip],
		]), $requests);

		$result = $tools->download_action_run_logs('o', 'r', 9, 'test', 'me');

		$this->assertEquals('zip-base64', $result['format']);
		$this->assertEquals(strlen($zip), $result['archive_bytes']);
		$this->assertEquals($zip, base64_decode($result['archive_base64']));
		$this->assertArrayHasKey('note', $result);
	}

	public function testDownloadActionRunLogsExtractsEntries(): void
	{
		if (!class_exists(\ZipArchive::class)) {
			$this->markTestSkipped('ZipArchive is not available on this host.');
		}

		$zip = base64_decode(self::RUN_LOGS_ZIP_B64);
		$tools = $this->makeTools($this->versionHandler('16.0.0', [
			'actions/runs/9/logs' => ['code' => 200, 'body' => $zip],
		]), $requests);

		$result = $tools->download_action_run_logs('o', 'r', 9, 'test', 'me');

		$this->assertEquals('files', $result['format']);
		$this->assertCount(3, $result['files']);

		$this->assertEquals('build-11-attempt-1.log', $result['files'][0]['name']);
		$this->assertFalse($result['files'][0]['missing']);
		$this->assertEquals("build log line 1\nbuild log line 2\n", $result['files'][0]['content']);

		$this->assertEquals('test-12-attempt-1.log', $result['files'][1]['name']);
		$this->assertEquals("test log text\n", $result['files'][1]['content']);

		$this->assertEquals('deploy-13-attempt-1.log.MISSING', $result['files'][2]['name']);
		$this->assertTrue($result['files'][2]['missing']);
		$this->assertArrayNotHasKey('content', $result['files'][2]);
	}

	public function testGetWorkflowJobLogsUsesApiOnForgejo16(): void
	{
		$tools = $this->makeTools($this->versionHandler('16.0.1', [
			'actions/runs/9/jobs' => ['code' => 200, 'body' => '[{"id":11,"name":"build"},{"id":12,"name":"test"}]'],
			'actions/jobs/11/logs' => ['code' => 200, 'body' => 'build log'],
		]), $requests);

		$result = $tools->get_workflow_job_logs('o', 'r', 9, 0, 1, 'test', 'me');

		$this->assertEquals('build log', $result['logs']);
		$this->assertEquals(11, $result['job_id']);
		$this->assertEquals('build', $result['job_name']);
		$this->assertEquals(1, $result['attempt']);

		// version lookup, jobs listing, then the log download
		$this->assertCount(3, $requests);
		$this->assertStringContainsString('api/v1/repos/o/r/actions/runs/9/jobs', $requests[1]);
		$this->assertStringContainsString('api/v1/repos/o/r/actions/jobs/11/logs', $requests[2]);
		$this->assertStringContainsString('attempt=1', $requests[2]);
	}

	public function testGetWorkflowJobLogsJobIndexOutOfRange(): void
	{
		$tools = $this->makeTools($this->versionHandler('16.0.1', [
			'actions/runs/9/jobs' => ['code' => 200, 'body' => '[{"id":11,"name":"build"}]'],
			'actions/jobs' => ['code' => 500, 'body' => 'must not be requested'],
		]), $requests);

		$result = $tools->get_workflow_job_logs('o', 'r', 9, 5, 1, 'test', 'me');

		$this->assertArrayHasKey('error', $result);
		$this->assertEquals(1, $result['job_count']);
		// version lookup + jobs listing only — no log download attempted
		$this->assertCount(2, $requests);
	}

	public function testGetWorkflowJobLogsLegacyFallbackOnOldServer(): void
	{
		$tools = $this->makeTools($this->versionHandler('11.0.3', [
			'actions/runs/9/jobs/0/attempt/1/logs' => ['code' => 200, 'body' => 'legacy log'],
		]), $requests);

		$result = $tools->get_workflow_job_logs('o', 'r', 9, 0, 1, 'test', 'me');

		$this->assertEquals(['logs' => 'legacy log'], $result);
		$last = end($requests);
		$this->assertStringNotContainsString('api/v1', $last);
		$this->assertStringContainsString('actions/runs/9/jobs/0/attempt/1/logs', $last);
	}

	public function testGetWorkflowJobLogsLegacyPrivateRepo404(): void
	{
		$tools = $this->makeTools($this->versionHandler('11.0.3'), $requests);

		$result = $tools->get_workflow_job_logs('o', 'r', 9, 0, 1, 'test', 'me');

		$this->assertArrayHasKey('error', $result);
		$this->assertArrayHasKey('limitation', $result);
		$this->assertStringContainsString('browser', $result['workaround']);
		$this->assertStringContainsString('Forgejo 16', $result['workaround']);
	}
}
