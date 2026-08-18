<?php

use PHPUnit\Framework\TestCase;
use EnchiladaMCP\McpServer;
use EnchiladaMCP\McpTool;
use EnchiladaMCP\McpResource;

class McpServerTestHandler
{
	#[McpTool(name: 'failing_tool', description: 'Always fails')]
	public function failingTool(): array
	{
		throw new \RuntimeException('the specific failure reason');
	}

	#[McpTool(name: 'timeout_tool', description: 'Always times out')]
	public function timeoutTool(): array
	{
		throw new \Forgejo\TimeoutException('Request timed out after 30s. Verify the state on the server before retrying.', 0);
	}

	#[McpResource(uriTemplate: 'test://static', name: 'Static', description: 'Static resource')]
	public function staticResource(): array
	{
		return ['ok' => true];
	}

	#[McpResource(uriTemplate: 'test://item/{id}', name: 'Item', description: 'Templated resource')]
	public function templatedResource(string $id): array
	{
		return ['id' => $id];
	}
}

class McpServerTest extends TestCase
{
	private function makeServer(): McpServer
	{
		$server = new McpServer('test-server', '0.0.1');
		$server->register(new McpServerTestHandler());
		return $server;
	}

	public function testResourcesListIsHandled(): void
	{
		$response = $this->makeServer()->handleRequest([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'resources/list',
		]);

		$this->assertArrayNotHasKey('error', $response);
		$this->assertArrayHasKey('resources', $response['result']);
		// Only static (non-templated) resources appear in resources/list
		$this->assertCount(1, $response['result']['resources']);
		$this->assertSame('test://static', $response['result']['resources'][0]['uri']);
	}

	public function testResourceTemplatesListIsHandled(): void
	{
		$response = $this->makeServer()->handleRequest([
			'jsonrpc' => '2.0',
			'id' => 2,
			'method' => 'resources/templates/list',
		]);

		$this->assertArrayNotHasKey('error', $response);
		$this->assertCount(1, $response['result']['resourceTemplates']);
		$this->assertSame('test://item/{id}', $response['result']['resourceTemplates'][0]['uriTemplate']);
	}

	public function testToolFailureLogIncludesErrorText(): void
	{
		$messages = [];
		$server = $this->makeServer();
		$server->setLogger(function (string $message) use (&$messages) {
			$messages[] = $message;
		});

		$response = $server->handleRequest([
			'jsonrpc' => '2.0',
			'id' => 3,
			'method' => 'tools/call',
			'params' => ['name' => 'failing_tool', 'arguments' => []],
		]);

		$this->assertNotEmpty($response['result']['isError']);

		$log = implode("\n", $messages);
		$this->assertStringContainsString("tools/call 'failing_tool'", $log);
		$this->assertStringContainsString('the specific failure reason', $log);
	}

	public function testToolWarningReturnsNonErrorResult(): void
	{
		$messages = [];
		$server = $this->makeServer();
		$server->setLogger(function (string $message) use (&$messages) {
			$messages[] = $message;
		});

		$response = $server->handleRequest([
			'jsonrpc' => '2.0',
			'id' => 5,
			'method' => 'tools/call',
			'params' => ['name' => 'timeout_tool', 'arguments' => []],
		]);

		// Warning: normal result, NOT isError
		$this->assertArrayHasKey('result', $response);
		$this->assertArrayNotHasKey('isError', $response['result']);
		$this->assertStringEndsWith('Verify the state on the server before retrying.', $response['result']['content'][0]['text']);
		$this->assertStringContainsString('timed out', $response['result']['content'][0]['text']);

		$log = implode("\n", $messages);
		$this->assertStringContainsString("Warning tools/call 'timeout_tool'", $log);
	}

	public function testToolCallLogsArgumentDigestsNotValues(): void
	{
		$messages = [];
		$server = $this->makeServer();
		$server->setLogger(function (string $message) use (&$messages) {
			$messages[] = $message;
		});

		$server->handleRequest([
			'jsonrpc' => '2.0',
			'id' => 4,
			'method' => 'tools/call',
			'params' => ['name' => 'failing_tool', 'arguments' => ['secret' => 'super-secret-value', 'page' => 3]],
		]);

		$log = implode("\n", $messages);
		$this->assertStringContainsString('secret(len=' . strlen('super-secret-value') . ' sha256=' . hash('sha256', 'super-secret-value') . ')', $log);
		$this->assertStringContainsString('page=3', $log);
		$this->assertStringNotContainsString('"super-secret-value"', $log);
		$this->assertStringNotContainsString('=super-secret-value', $log);
	}
}
