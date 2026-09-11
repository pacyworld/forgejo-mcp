<?php

use PHPUnit\Framework\TestCase;
use Forgejo\InstanceManager;

require_once APPLICATION_ROOT . 'tools/MirrorTools.php';

class MirrorToolsTest extends TestCase
{
	/**
	 * Build a MirrorTools backed by an injected HTTP handler that answers
	 * every request with $code/$responseBody.
	 *
	 * @param array|null $requests Captured requests: [{method, path, query, body}]
	 */
	private function makeTools(?array &$requests, int $code = 200, string $responseBody = '[]'): MirrorTools
	{
		$requests = [];
		$httpClient = function ($method, $url, $headers, $body) use (&$requests, $code, $responseBody) {
			$requests[] = [
				'method' => strtoupper($method),
				'path' => parse_url($url, PHP_URL_PATH),
				'query' => (string)parse_url($url, PHP_URL_QUERY),
				'body' => $body,
			];
			return ['code' => $code, 'body' => $responseBody];
		};
		$manager = new InstanceManager([
			'test' => [
				'url' => 'https://forgejo.example.com',
				'users' => ['me' => ['token' => 'abc']],
			],
		], 'test', 'me', $httpClient);
		return new MirrorTools($manager);
	}

	public function testSyncPushMirrorPostsToHyphenatedEndpoint(): void
	{
		$tools = $this->makeTools($requests, 200, '');

		$tools->sync_push_mirror('o', 'r', 'test', 'me');

		$this->assertCount(1, $requests);
		$this->assertSame('POST', $requests[0]['method']);
		$this->assertSame('/api/v1/repos/o/r/push_mirrors-sync', $requests[0]['path']);
	}

	public function testListPushMirrors(): void
	{
		$tools = $this->makeTools($requests, 200, '[{"remote_name":"remote_mirror_a"}]');

		$result = $tools->list_push_mirrors('o', 'r', 2, 5, 'test', 'me');

		$this->assertEquals('remote_mirror_a', $result[0]['remote_name']);
		$this->assertSame('GET', $requests[0]['method']);
		$this->assertSame('/api/v1/repos/o/r/push_mirrors', $requests[0]['path']);
		parse_str($requests[0]['query'], $query);
		$this->assertEquals(['page' => '2', 'limit' => '5'], $query);
	}

	public function testAddPushMirror(): void
	{
		$tools = $this->makeTools($requests, 201, '{"remote_name":"remote_mirror_a"}');

		$tools->add_push_mirror('o', 'r', 'https://mirror.example.com/o/r.git', null, null, '8h0m0s', true, 'test', 'me');

		$this->assertSame('POST', $requests[0]['method']);
		$this->assertSame('/api/v1/repos/o/r/push_mirrors', $requests[0]['path']);
		$payload = json_decode($requests[0]['body'], true);
		$this->assertSame('https://mirror.example.com/o/r.git', $payload['remote_address']);
		$this->assertArrayNotHasKey('remote_username', $payload);
		$this->assertArrayNotHasKey('remote_password', $payload);
	}

	public function testGetPushMirror(): void
	{
		$tools = $this->makeTools($requests, 200, '{"remote_name":"remote_mirror_a"}');

		$tools->get_push_mirror('o', 'r', 'remote_mirror_a', 'test', 'me');

		$this->assertSame('GET', $requests[0]['method']);
		$this->assertSame('/api/v1/repos/o/r/push_mirrors/remote_mirror_a', $requests[0]['path']);
	}

	public function testDeletePushMirror(): void
	{
		$tools = $this->makeTools($requests, 204, '');

		$tools->delete_push_mirror('o', 'r', 'remote_mirror_a', 'test', 'me');

		$this->assertSame('DELETE', $requests[0]['method']);
		$this->assertSame('/api/v1/repos/o/r/push_mirrors/remote_mirror_a', $requests[0]['path']);
	}
}
