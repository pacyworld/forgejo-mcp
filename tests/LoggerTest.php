<?php

use PHPUnit\Framework\TestCase;
use EnchiladaMCP\Logger;

class LoggerTest extends TestCase
{
	private string $logFile;

	protected function setUp(): void
	{
		$this->logFile = tempnam(sys_get_temp_dir(), 'forgejo-mcp-log-test-');
	}

	protected function tearDown(): void
	{
		if (file_exists($this->logFile)) {
			unlink($this->logFile);
		}
	}

	public function testWritesToFileWithLevelAndTag(): void
	{
		$logger = new Logger($this->logFile, Logger::LEVEL_DEBUG, false, 'forgejo-mcp');
		$logger->info('Server started');

		$contents = file_get_contents($this->logFile);
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2} /', $contents);
		$this->assertStringContainsString('[forgejo-mcp]', $contents);
		$this->assertStringContainsString('[INFO] Server started', $contents);
	}

	public function testLevelFiltering(): void
	{
		$logger = new Logger($this->logFile, Logger::LEVEL_ERROR, false, 'test');
		$logger->debug('hidden debug');
		$logger->info('hidden info');
		$logger->error('visible error');

		$contents = file_get_contents($this->logFile);
		$this->assertStringNotContainsString('hidden', $contents);
		$this->assertStringContainsString('[ERROR] visible error', $contents);
	}

	public function testDisabledLoggerWritesNothing(): void
	{
		$logger = new Logger(null, Logger::LEVEL_DEBUG, false, 'test');
		$this->assertFalse($logger->enabled());

		// Must not throw, must not create output anywhere
		$logger->debug('nothing');
		$logger->error('nothing');
		$this->addToAssertionCount(1);
	}

	public function testIsCallableForSetLogger(): void
	{
		$logger = new Logger($this->logFile, Logger::LEVEL_DEBUG, false, 'test');
		$this->assertIsCallable($logger);

		$logger('transport message');
		$contents = file_get_contents($this->logFile);
		$this->assertStringContainsString('[DEBUG] transport message', $contents);
	}

	public function testNewlinesAreFlattened(): void
	{
		$logger = new Logger($this->logFile, Logger::LEVEL_DEBUG, false, 'test');
		$logger->info("line one\nline two");

		$lines = array_values(array_filter(explode("\n", file_get_contents($this->logFile))));
		$this->assertCount(1, $lines);
		$this->assertStringContainsString('line one\nline two', $lines[0]);
	}

	public function testIsDebug(): void
	{
		$debug = new Logger($this->logFile, Logger::LEVEL_DEBUG, false);
		$info = new Logger($this->logFile, Logger::LEVEL_INFO, false);
		$off = new Logger(null);

		$this->assertTrue($debug->isDebug());
		$this->assertFalse($info->isDebug());
		$this->assertFalse($off->isDebug());
	}

	public function testLevelFromString(): void
	{
		$this->assertSame(Logger::LEVEL_DEBUG, Logger::levelFromString('debug'));
		$this->assertSame(Logger::LEVEL_INFO, Logger::levelFromString('INFO'));
		$this->assertSame(Logger::LEVEL_ERROR, Logger::levelFromString('Error'));
		$this->assertNull(Logger::levelFromString('bogus'));
	}

	public function testDigest(): void
	{
		$digest = Logger::digest('hello');
		$this->assertSame('len=5 sha256=' . hash('sha256', 'hello'), $digest);
	}

	public function testTruncate(): void
	{
		$this->assertSame('short', Logger::truncate('short'));
		$this->assertSame(str_repeat('a', 200) . '...', Logger::truncate(str_repeat('a', 250)));
	}

	public function testUnwritablePathDoesNotThrow(): void
	{
		$logger = new Logger('/nonexistent-directory/deep/log.txt', Logger::LEVEL_DEBUG, false, 'test');
		$logger->info('must not throw');
		$this->addToAssertionCount(1);
	}
}
