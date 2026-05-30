<?php

use PHPUnit\Framework\TestCase;
use Forgejo\InstanceManager;

class InstanceManagerTest extends TestCase
{
	private function sampleConfig(): array
	{
		return [
			'pacyworld' => [
				'url' => 'https://pacyworld.dev',
				'description' => 'Pacy World Forgejo',
				'users' => [
					'admin' => ['token' => 'token-admin', 'description' => 'Admin'],
					'ci' => ['token' => 'token-ci', 'description' => 'CI bot'],
				],
			],
			'codeberg' => [
				'url' => 'https://codeberg.org',
				'description' => 'Codeberg',
				'users' => [
					'personal' => ['token' => 'token-personal', 'description' => 'Personal'],
				],
			],
		];
	}

	public function testConstructorSetsDefaults(): void
	{
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin');
		$this->assertEquals('pacyworld', $manager->getDefaultInstance());
		$this->assertEquals('admin', $manager->getDefaultUser());
	}

	public function testConstructorRejectsInvalidInstance(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new InstanceManager($this->sampleConfig(), 'nonexistent', 'admin');
	}

	public function testConstructorRejectsInvalidUser(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new InstanceManager($this->sampleConfig(), 'pacyworld', 'nonexistent');
	}

	public function testConstructorRejectsEmpty(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new InstanceManager([], 'x', 'y');
	}

	public function testListInstances(): void
	{
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin');
		$list = $manager->listInstances();

		$this->assertArrayHasKey('pacyworld', $list);
		$this->assertArrayHasKey('codeberg', $list);
		$this->assertTrue($list['pacyworld']['is_default']);
		$this->assertFalse($list['codeberg']['is_default']);
		$this->assertArrayHasKey('admin', $list['pacyworld']['users']);
		$this->assertArrayHasKey('ci', $list['pacyworld']['users']);
	}

	public function testSwitchInstance(): void
	{
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin');
		$manager->setDefaultInstance('codeberg');
		$this->assertEquals('codeberg', $manager->getDefaultInstance());
		$this->assertEquals('personal', $manager->getDefaultUser());
	}

	public function testSwitchInstanceRejectsInvalid(): void
	{
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin');
		$this->expectException(\InvalidArgumentException::class);
		$manager->setDefaultInstance('nonexistent');
	}

	public function testSwitchUser(): void
	{
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin');
		$manager->setDefaultUser('ci');
		$this->assertEquals('ci', $manager->getDefaultUser());
	}

	public function testSwitchUserRejectsInvalid(): void
	{
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin');
		$this->expectException(\InvalidArgumentException::class);
		$manager->setDefaultUser('nonexistent');
	}

	public function testGetClientReturnsClient(): void
	{
		$httpClient = fn() => ['code' => 200, 'body' => '{}'];
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin', $httpClient);
		$client = $manager->getClient();
		$this->assertInstanceOf(\Forgejo\Client::class, $client);
		$this->assertEquals('https://pacyworld.dev', $client->getBaseUrl());
	}

	public function testGetClientCachesInstances(): void
	{
		$httpClient = fn() => ['code' => 200, 'body' => '{}'];
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin', $httpClient);
		$client1 = $manager->getClient();
		$client2 = $manager->getClient();
		$this->assertSame($client1, $client2);
	}

	public function testGetClientDifferentUsers(): void
	{
		$httpClient = fn() => ['code' => 200, 'body' => '{}'];
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin', $httpClient);
		$admin = $manager->getClient('pacyworld', 'admin');
		$ci = $manager->getClient('pacyworld', 'ci');
		$this->assertNotSame($admin, $ci);
	}

	public function testFromFile(): void
	{
		$tmpFile = tempnam(sys_get_temp_dir(), 'forgejo_test_');
		file_put_contents($tmpFile, json_encode([
			'default_instance' => 'test',
			'default_user' => 'me',
			'instances' => [
				'test' => [
					'url' => 'https://example.com',
					'users' => ['me' => ['token' => 'abc']],
				],
			],
		]));

		$manager = InstanceManager::fromFile($tmpFile);
		$this->assertEquals('test', $manager->getDefaultInstance());
		$this->assertEquals('me', $manager->getDefaultUser());
		$this->assertEquals(1, $manager->count());

		unlink($tmpFile);
	}

	public function testFromFileMissingThrows(): void
	{
		$this->expectException(\RuntimeException::class);
		InstanceManager::fromFile('/nonexistent/path.json');
	}

	public function testHasInstance(): void
	{
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin');
		$this->assertTrue($manager->hasInstance('pacyworld'));
		$this->assertFalse($manager->hasInstance('nonexistent'));
	}

	public function testCount(): void
	{
		$manager = new InstanceManager($this->sampleConfig(), 'pacyworld', 'admin');
		$this->assertEquals(2, $manager->count());
	}
}
