<?php

use PHPUnit\Framework\TestCase;
use Forgejo\InstanceManager;
use EnchiladaMCP\McpServer;

require_once APPLICATION_ROOT . 'tools/InstanceTools.php';

class InstanceToolsTest extends TestCase
{
	private function makeManager(): InstanceManager
	{
		return new InstanceManager([
			'test' => [
				'url' => 'https://forgejo.example.com',
				'users' => ['me' => ['token' => 'abc']],
			],
		], 'test', 'me');
	}

	public function testListForgejoInstances(): void
	{
		$tools = new InstanceTools($this->makeManager());
		$result = $tools->list_forgejo_instances();

		$this->assertSame(1, $result['count']);
		$this->assertArrayHasKey('test', $result['instances']);
		$this->assertArrayHasKey('me', $result['instances']['test']['users']);
	}

	public function testRegisteredNameHasNoVendorPrefix(): void
	{
		// The tool list must not contain a leading vendor prefix — a lone
		// 'forgejo_*' tool taught clients a false naming pattern that caused
		// hallucinated tool names (e.g. 'forgejo_repo_search').
		$server = new McpServer('test', '0.0.1');
		$server->register(new InstanceTools($this->makeManager()));
		$tools = $server->handleRequest(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])['result']['tools'];

		$names = array_column($tools, 'name');
		$this->assertContains('list_forgejo_instances', $names);
		foreach ($names as $name) {
			$this->assertStringStartsNotWith('forgejo_', $name);
		}
	}
}
