<?php

use PHPUnit\Framework\TestCase;
use Forgejo\Client;
use Forgejo\ClientException;

class ClientTest extends TestCase
{
	private function makeClient(callable $httpClient): Client
	{
		return new Client('https://example.com', 'test-token', true, 30, $httpClient);
	}

	public function testGetRequest(): void
	{
		$client = $this->makeClient(function ($method, $url, $headers, $body) {
			$this->assertEquals('GET', $method);
			$this->assertStringContainsString('api/v1/user', $url);
			$this->assertContains('Authorization: token test-token', $headers);
			return ['code' => 200, 'body' => '{"login":"testuser","id":1}'];
		});

		$result = $client->get('user');
		$this->assertEquals('testuser', $result['login']);
	}

	public function testPostRequest(): void
	{
		$client = $this->makeClient(function ($method, $url, $headers, $body) {
			$this->assertEquals('POST', $method);
			$this->assertStringContainsString('api/v1/user/repos', $url);
			$this->assertContains('Content-Type: application/json', $headers);
			$decoded = json_decode($body, true);
			$this->assertEquals('my-repo', $decoded['name']);
			return ['code' => 201, 'body' => '{"id":42,"name":"my-repo"}'];
		});

		$result = $client->post('user/repos', ['name' => 'my-repo']);
		$this->assertEquals('my-repo', $result['name']);
	}

	public function testPatchRequest(): void
	{
		$client = $this->makeClient(function ($method, $url, $headers, $body) {
			$this->assertEquals('PATCH', $method);
			return ['code' => 200, 'body' => '{"state":"closed"}'];
		});

		$result = $client->patch('repos/o/r/issues/1', ['state' => 'closed']);
		$this->assertEquals('closed', $result['state']);
	}

	public function testDeleteRequest204(): void
	{
		$client = $this->makeClient(function ($method, $url, $headers, $body) {
			$this->assertEquals('DELETE', $method);
			return ['code' => 204, 'body' => ''];
		});

		$result = $client->delete('repos/o/r/branches/test');
		$this->assertEquals([], $result);
	}

	public function testQueryParameters(): void
	{
		$client = $this->makeClient(function ($method, $url, $headers, $body) {
			$this->assertStringContainsString('page=2', $url);
			$this->assertStringContainsString('limit=10', $url);
			return ['code' => 200, 'body' => '[]'];
		});

		$client->get('repos/search', ['page' => 2, 'limit' => 10]);
	}

	public function testUnauthorizedThrows(): void
	{
		$client = $this->makeClient(fn() => ['code' => 401, 'body' => 'Unauthorized']);
		$this->expectException(ClientException::class);
		$this->expectExceptionCode(401);
		$client->get('user');
	}

	public function testForbiddenThrows(): void
	{
		$client = $this->makeClient(fn() => ['code' => 403, 'body' => 'Forbidden']);
		$this->expectException(ClientException::class);
		$this->expectExceptionCode(403);
		$client->get('admin/users');
	}

	public function testNotFoundThrows(): void
	{
		$client = $this->makeClient(fn() => ['code' => 404, 'body' => 'Not Found']);
		$this->expectException(ClientException::class);
		$this->expectExceptionCode(404);
		$client->get('repos/nonexistent/repo');
	}

	public function testServerErrorThrows(): void
	{
		$client = $this->makeClient(fn() => ['code' => 500, 'body' => 'Internal Server Error']);
		$this->expectException(ClientException::class);
		$this->expectExceptionCode(500);
		$client->get('user');
	}

	public function testGetBaseUrl(): void
	{
		$client = $this->makeClient(fn() => ['code' => 200, 'body' => '{}']);
		$this->assertEquals('https://example.com', $client->getBaseUrl());
	}

	public function testGetServerVersion(): void
	{
		$client = $this->makeClient(function ($method, $url) {
			$this->assertStringContainsString('api/v1/version', $url);
			return ['code' => 200, 'body' => '{"version":"16.0.2"}'];
		});
		$this->assertEquals('16.0.2', $client->getServerVersion());
	}

	public function testGetServerVersionIsCached(): void
	{
		$calls = 0;
		$client = $this->makeClient(function () use (&$calls) {
			$calls++;
			return ['code' => 200, 'body' => '{"version":"16.0.2"}'];
		});
		$this->assertEquals('16.0.2', $client->getServerVersion());
		$this->assertEquals('16.0.2', $client->getServerVersion());
		$this->assertEquals(1, $calls);
	}

	public function testGetServerVersionFailureReturnsEmpty(): void
	{
		$client = $this->makeClient(fn() => ['code' => 500, 'body' => 'Internal Server Error']);
		$this->assertEquals('', $client->getServerVersion());
		$this->assertFalse($client->versionAtLeast('16.0.0'));
	}

	public function testVersionAtLeast(): void
	{
		$v16 = $this->makeClient(fn() => ['code' => 200, 'body' => '{"version":"16.0.0"}']);
		$this->assertTrue($v16->versionAtLeast('16.0.0'));
		$this->assertTrue($v16->versionAtLeast('15.0.0'));

		$v11 = $this->makeClient(fn() => ['code' => 200, 'body' => '{"version":"11.0.3"}']);
		$this->assertFalse($v11->versionAtLeast('16.0.0'));
		$this->assertTrue($v11->versionAtLeast('11.0.0'));
	}

	public function testVersionWithBuildMetadata(): void
	{
		$client = $this->makeClient(fn() => ['code' => 200, 'body' => '{"version":"16.0.1+gitea-1.24.6"}']);
		$this->assertTrue($client->versionAtLeast('16.0.0'));
	}

	public function testSupportsActionLogsApi(): void
	{
		$supported = $this->makeClient(fn() => ['code' => 200, 'body' => '{"version":"16.0.0"}']);
		$this->assertTrue($supported->supportsActionLogsApi());

		$unsupported = $this->makeClient(fn() => ['code' => 200, 'body' => '{"version":"15.9.9"}']);
		$this->assertFalse($unsupported->supportsActionLogsApi());

		$unknown = $this->makeClient(fn() => ['code' => 200, 'body' => '{}']);
		$this->assertFalse($unknown->supportsActionLogsApi());
	}

	private function makeFileLogger(?string &$logFile): \EnchiladaMCP\Logger
	{
		$logFile = tempnam(sys_get_temp_dir(), 'forgejo-mcp-client-log-test-');
		return new \EnchiladaMCP\Logger($logFile, \EnchiladaMCP\Logger::LEVEL_DEBUG, false, 'test');
	}

	public function testRequestLogging(): void
	{
		$client = $this->makeClient(fn() => ['code' => 200, 'body' => '{"login":"testuser"}']);
		$client->setLogger($this->makeFileLogger($logFile));

		$client->get('user');

		$log = file_get_contents($logFile);
		unlink($logFile);

		$this->assertStringContainsString('HTTP GET https://example.com/api/v1/user', $log);
		$this->assertStringContainsString('-> 200 in', $log);
		$this->assertStringNotContainsString('test-token', $log);
	}

	public function testRequestLoggingDigestsBodyWithoutValue(): void
	{
		$client = $this->makeClient(fn() => ['code' => 201, 'body' => '{"id":1}']);
		$client->setLogger($this->makeFileLogger($logFile));

		$client->post('user/repos', ['name' => 'super-secret-value']);

		$log = file_get_contents($logFile);
		unlink($logFile);

		$expectedBody = json_encode(['name' => 'super-secret-value']);
		$this->assertStringContainsString('body(len=' . strlen($expectedBody) . ' sha256=' . hash('sha256', $expectedBody) . ')', $log);
		$this->assertStringNotContainsString('super-secret-value', $log);
	}

	public function testErrorLogging(): void
	{
		$client = $this->makeClient(fn() => ['code' => 500, 'body' => '{"message":"boom"}']);
		$client->setLogger($this->makeFileLogger($logFile));

		try {
			$client->get('user');
			$this->fail('Expected ClientException');
		} catch (ClientException $e) {
			// expected
		}

		$log = file_get_contents($logFile);
		unlink($logFile);

		$this->assertStringContainsString('[ERROR]', $log);
		$this->assertStringContainsString('HTTP GET https://example.com/api/v1/user failed after', $log);
		$this->assertStringContainsString('500', $log);
	}

	public function testUrlTokenRedaction(): void
	{
		$client = $this->makeClient(fn() => ['code' => 200, 'body' => '{}']);
		$client->setLogger($this->makeFileLogger($logFile));

		$client->get('user', ['token' => 'abc123secret', 'page' => 2]);

		$log = file_get_contents($logFile);
		unlink($logFile);

		$this->assertStringContainsString('token=[REDACTED]', $log);
		$this->assertStringNotContainsString('abc123secret', $log);
		$this->assertStringContainsString('page=2', $log);
	}

	public function testNoLoggerMeansNoOutput(): void
	{
		$client = $this->makeClient(fn() => ['code' => 200, 'body' => '{"login":"testuser"}']);
		$result = $client->get('user');
		$this->assertEquals('testuser', $result['login']);
	}

	public function testTransportFailureCode0Throws(): void
	{
		// Simulates a curl timeout: no HTTP response, code 0. This must never
		// be silently treated as an empty success (regression: a timed-out PR
		// merge was previously reported as OK with an empty result).
		$client = $this->makeClient(fn() => ['code' => 0, 'body' => '']);

		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('Transport error');
		$client->get('user');
	}

	public function testTimeoutMessageExplainsCause(): void
	{
		$client = $this->makeClient(fn() => ['code' => 200, 'body' => '{}']);

		// Simulate EnchiladaHTTP state after CURLE_OPERATION_TIMEDOUT (28)
		$clientRef = new \ReflectionProperty(Client::class, 'http');
		$http = $clientRef->getValue($client);
		$errnoRef = new \ReflectionProperty(\EnchiladaHTTP::class, 'last_curl_errno');
		$errnoRef->setValue($http, 28);
		$errorRef = new \ReflectionProperty(\EnchiladaHTTP::class, 'last_curl_error');
		$errorRef->setValue($http, 'Operation timed out after 30000 milliseconds');

		$method = new \ReflectionMethod(Client::class, 'transportError');
		$exception = $method->invoke($client, 'https://example.com/api/v1/repos/o/r/pulls/1/merge');

		// Timeout is a tool warning (non-error MCP result), still a ClientException for catch compatibility
		$this->assertInstanceOf(\Forgejo\TimeoutException::class, $exception);
		$this->assertInstanceOf(ClientException::class, $exception);
		$this->assertInstanceOf(\EnchiladaMCP\ToolWarningInterface::class, $exception);
		$this->assertSame(
			'Request timed out. This is a known issue on large repositories; '
			. 'and may still be processing or already completed. '
			. 'Re-trying is not needed.',
			$exception->getMessage()
		);
	}

	public function testTransportFailureIsLoggedAsError(): void
	{
		$client = $this->makeClient(fn() => ['code' => 0, 'body' => '']);
		$client->setLogger($this->makeFileLogger($logFile));

		try {
			$client->get('user');
			$this->fail('Expected ClientException');
		} catch (ClientException $e) {
			// expected
		}

		$log = file_get_contents($logFile);
		unlink($logFile);

		$this->assertStringContainsString('[ERROR]', $log);
		$this->assertStringContainsString('Transport error', $log);
	}
}
