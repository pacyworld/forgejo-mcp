<?php

use PHPUnit\Framework\TestCase;
use EnchiladaMCP\ToolRegistry;
use Forgejo\InstanceManager;

require_once APPLICATION_ROOT . 'tools/AttachmentTools.php';
require_once APPLICATION_ROOT . 'tools/MirrorTools.php';
require_once APPLICATION_ROOT . 'tools/TimeTrackingTools.php';

/**
 * Consolidated action-dispatched tools: routing, per-action validation and
 * unknown-tool migration hints (#11, phase 2/3).
 */
class ConsolidatedToolsTest extends TestCase
{
	/**
	 * Build an InstanceManager whose HTTP transport captures requests.
	 *
	 * @param array|null $requests Captured requests: [{method, path, query, body}]
	 */
	private function makeManager(?array &$requests, string $responseBody = '[]'): InstanceManager
	{
		$requests = [];
		$httpClient = function ($method, $url, $headers, $body) use (&$requests, $responseBody) {
			$requests[] = [
				'method' => strtoupper($method),
				'path' => parse_url($url, PHP_URL_PATH),
				'query' => (string)parse_url($url, PHP_URL_QUERY),
				'body' => $body,
			];
			return ['code' => 200, 'body' => $responseBody];
		};
		return new InstanceManager([
			'test' => [
				'url' => 'https://forgejo.example.com',
				'users' => ['me' => ['token' => 'abc']],
			],
		], 'test', 'me', $httpClient);
	}

	public function testAttachmentRoutesByTargetAndAction(): void
	{
		$tools = new AttachmentTools($this->makeManager($requests));

		$tools->attachment('list', 'issue', 'o', 'r', 5, null, null, null, null, null, null, 'test', 'me');
		$this->assertSame('/api/v1/repos/o/r/issues/5/assets', $requests[0]['path']);

		$tools->attachment('list', 'comment', 'o', 'r', null, 42, null, null, null, null, null, 'test', 'me');
		$this->assertSame('/api/v1/repos/o/r/issues/comments/42/assets', $requests[1]['path']);

		$tools->attachment('delete', 'release', 'o', 'r', null, null, 7, 99, null, null, null, 'test', 'me');
		$this->assertSame('DELETE', $requests[2]['method']);
		$this->assertSame('/api/v1/repos/o/r/releases/7/assets/99', $requests[2]['path']);
	}

	public function testAttachmentRejectsUnknownTarget(): void
	{
		$tools = new AttachmentTools($this->makeManager($requests));
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('unknown target');
		$tools->attachment('list', 'gist');
	}

	public function testMissingRequiredParameterNamesIt(): void
	{
		$tools = new MirrorTools($this->makeManager($requests));
		try {
			$tools->push_mirror('add', 'o', 'r', null, null, null, null, null, null, null, null, 'test', 'me');
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString("action 'add'", $e->getMessage());
			$this->assertStringContainsString('remote_address', $e->getMessage());
		}
	}

	public function testUnknownActionListsValidActions(): void
	{
		$tools = new MirrorTools($this->makeManager($requests));
		try {
			$tools->push_mirror('rename', 'o', 'r');
			$this->fail('expected InvalidArgumentException');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString("unknown action 'rename'", $e->getMessage());
			$this->assertStringContainsString('list, add, get, delete, sync', $e->getMessage());
		}
	}

	public function testDispatchSkipsNullOptionalsSoDefaultsApply(): void
	{
		$tools = new TimeTrackingTools($this->makeManager($requests));
		$tools->time_tracking('add', 'o', 'r', 3, 600, null, null, null, 'test', 'me');
		$this->assertSame('POST', $requests[0]['method']);
		$this->assertSame('/api/v1/repos/o/r/issues/3/times', $requests[0]['path']);
		$payload = json_decode($requests[0]['body'], true);
		$this->assertSame(600, $payload['time']);
	}

	public function testOldToolNamesSuggestConsolidatedToolAndAction(): void
	{
		$manager = $this->makeManager($requests);
		$registry = new ToolRegistry();
		$registry->register(new MirrorTools($manager));

		$suggestions = $registry->suggestTools('list_push_mirrors');
		$this->assertSame('push_mirror action=list', $suggestions[0]);
	}
}
