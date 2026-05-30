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
}
