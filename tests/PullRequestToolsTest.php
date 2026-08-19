<?php

use PHPUnit\Framework\TestCase;
use Forgejo\InstanceManager;
use EnchiladaMCP\McpServer;

require_once APPLICATION_ROOT . 'tools/PullRequestTools.php';

class PullRequestToolsTest extends TestCase
{
	private function makeTools(callable $handler): PullRequestTools
	{
		$manager = new InstanceManager([
			'test' => [
				'url' => 'https://forgejo.example.com',
				'users' => ['me' => ['token' => 'abc']],
			],
		], 'test', 'me', $handler);
		return new PullRequestTools($manager);
	}

	public function testMergePullRequest(): void
	{
		$tools = $this->makeTools(function ($method, $url, $headers, $body) {
			$this->assertSame('POST', $method);
			$this->assertStringContainsString('api/v1/repos/o/r/pulls/5/merge', $url);
			$decoded = json_decode($body, true);
			$this->assertSame('merge', $decoded['Do']);
			$this->assertTrue($decoded['delete_branch_after_merge']);
			$this->assertSame('ship it', $decoded['merge_message_field']);
			return ['code' => 200, 'body' => ''];
		});

		$result = $tools->merge_pull_request('o', 'r', 5, 'merge', 'ship it', true, 90, 'test', 'me');
		$this->assertSame([], $result);
	}

	public function testMergePullRequestTimeoutDefaultsTo90(): void
	{
		$method = new \ReflectionMethod(PullRequestTools::class, 'merge_pull_request');
		$param = $method->getParameters()[6]; // after delete_branch_after_merge

		$this->assertSame('timeout', $param->getName());
		$this->assertTrue($param->isOptional());
		$this->assertSame(90, $param->getDefaultValue());
	}

	public function testMergePullRequestSchemaExposesOptionalTimeout(): void
	{
		$manager = new InstanceManager([
			'test' => [
				'url' => 'https://forgejo.example.com',
				'users' => ['me' => ['token' => 'abc']],
			],
		], 'test', 'me', fn() => ['code' => 200, 'body' => '{}']);

		$server = new McpServer('test', '0.0.1');
		$server->register(new PullRequestTools($manager));
		$tools = $server->handleRequest(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])['result']['tools'];

		$merge = null;
		foreach ($tools as $tool) {
			if ($tool['name'] === 'merge_pull_request') {
				$merge = $tool;
				break;
			}
		}

		$this->assertNotNull($merge);
		$this->assertArrayHasKey('timeout', $merge['inputSchema']['properties']);
		$this->assertSame('integer', $merge['inputSchema']['properties']['timeout']['type']);
		$this->assertNotContains('timeout', $merge['inputSchema']['required']);
	}
}
