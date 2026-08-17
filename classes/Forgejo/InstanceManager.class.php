<?php
/**
 * Forgejo MCP Server — Instance Manager
 *
 * Multi-instance, multi-user configuration registry.
 * Each instance is a Forgejo server, each instance has one or more users
 * with their own access tokens.
 *
 * @package    ForgejoMCP\Forgejo
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace Forgejo;

class InstanceManager
{
	/** @var array<string,array> Instance configurations indexed by name */
	private array $instances;

	/** @var string Name of the current default instance */
	private string $defaultInstance;

	/** @var string Name of the current default user (within the default instance) */
	private string $defaultUser;

	/** @var array<string,Client> Cached clients indexed by "instance:user" */
	private array $clients = [];

	/** @var callable|null Optional HTTP client callable for testing */
	private $httpClient;

	/** @var \EnchiladaMCP\Logger|null Optional logger propagated to created Clients */
	private ?\EnchiladaMCP\Logger $logger = null;

	/**
	 * Create a new InstanceManager.
	 *
	 * @param array<string,array>  $instances       Instance configurations
	 * @param string               $defaultInstance Default instance name
	 * @param string               $defaultUser     Default user name
	 * @param callable|null        $httpClient      Optional HTTP callable for testing
	 */
	public function __construct(array $instances, string $defaultInstance = '', string $defaultUser = '', ?callable $httpClient = null)
	{
		if (empty($instances)) {
			throw new \InvalidArgumentException('At least one Forgejo instance must be configured.');
		}

		$this->instances = $instances;
		$this->defaultInstance = $defaultInstance;
		$this->defaultUser = $defaultUser;
		$this->httpClient = $httpClient;
	}

	/**
	 * Create an InstanceManager from a JSON configuration file.
	 *
	 * @param  string        $path       Path to instances.json
	 * @param  callable|null $httpClient Optional HTTP callable for testing
	 * @return self
	 */
	public static function fromFile(string $path, ?callable $httpClient = null): self
	{
		if (!file_exists($path)) {
			throw new \RuntimeException("Configuration file not found: {$path}");
		}

		$json = file_get_contents($path);
		if ($json === false) {
			throw new \RuntimeException("Failed to read configuration file: {$path}");
		}

		$config = json_decode($json, true);
		if ($config === null && json_last_error() !== JSON_ERROR_NONE) {
			throw new \RuntimeException(
				"Invalid JSON in configuration file {$path}: " . json_last_error_msg()
			);
		}

		$instances = $config['instances'] ?? [];
		$defaultInstance = $config['default_instance'] ?? '';
		$defaultUser = $config['default_user'] ?? '';

		return new self($instances, $defaultInstance, $defaultUser, $httpClient);
	}

	/**
	 * Set a logger propagated to every Client created by this manager.
	 *
	 * @param \EnchiladaMCP\Logger|null $logger Logger instance, or null to disable
	 */
	public function setLogger(?\EnchiladaMCP\Logger $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * Get a Client for the specified instance and user.
	 *
	 * @param  string $instance Instance name (required)
	 * @param  string $user     User name (required)
	 * @return Client             Forgejo API client
	 * @throws \InvalidArgumentException If instance/user not found or empty
	 */
	public function getClient(string $instance, string $user): Client
	{
		if (empty($instance)) {
			throw new \InvalidArgumentException('Instance name is required. Pass the instance parameter to every tool call.');
		}
		if (empty($user)) {
			throw new \InvalidArgumentException('User name is required. Pass the user parameter to every tool call.');
		}

		if (!isset($this->instances[$instance])) {
			$available = implode(', ', array_keys($this->instances));
			throw new \InvalidArgumentException(
				"Unknown instance '{$instance}'. Available: {$available}"
			);
		}

		$instanceConfig = $this->instances[$instance];
		$users = $instanceConfig['users'] ?? [];

		if (!isset($users[$user])) {
			$available = implode(', ', array_keys($users));
			throw new \InvalidArgumentException(
				"Unknown user '{$user}' for instance '{$instance}'. Available: {$available}"
			);
		}

		$cacheKey = "{$instance}:{$user}";

		if (!isset($this->clients[$cacheKey])) {
			$this->clients[$cacheKey] = new Client(
				$instanceConfig['url'],
				$users[$user]['token'],
				$instanceConfig['verify_ssl'] ?? true,
				$instanceConfig['timeout'] ?? 30,
				$this->httpClient
			);
			$this->clients[$cacheKey]->setLogger($this->logger);
			if ($this->logger !== null) {
				$this->logger->debug("Created client for {$cacheKey} ({$instanceConfig['url']}, timeout=" . ($instanceConfig['timeout'] ?? 30) . 's, verify_ssl=' . (($instanceConfig['verify_ssl'] ?? true) ? 'true' : 'false') . ')');
			}
		}

		return $this->clients[$cacheKey];
	}

	/**
	 * List all configured instances with their users.
	 *
	 * @return array Instance summaries
	 */
	public function listInstances(): array
	{
		$result = [];
		foreach ($this->instances as $name => $config) {
			$users = [];
			foreach (($config['users'] ?? []) as $userName => $userConfig) {
				$users[$userName] = [
					'description' => $userConfig['description'] ?? '',
				];
			}

			$result[$name] = [
				'url' => $config['url'],
				'description' => $config['description'] ?? '',
				'users' => $users,
			];
		}
		return $result;
	}

	public function getDefaultInstance(): string
	{
		return $this->defaultInstance;
	}

	public function getDefaultUser(): string
	{
		return $this->defaultUser;
	}

	public function setDefaultInstance(string $name): void
	{
		if (!isset($this->instances[$name])) {
			$available = implode(', ', array_keys($this->instances));
			throw new \InvalidArgumentException(
				"Unknown instance '{$name}'. Available: {$available}"
			);
		}

		$this->defaultInstance = $name;

		// Reset default user to first user of new instance
		$users = $this->instances[$name]['users'] ?? [];
		$this->defaultUser = !empty($users) ? array_key_first($users) : '';
	}

	public function setDefaultUser(string $user): void
	{
		$users = $this->instances[$this->defaultInstance]['users'] ?? [];
		if (!isset($users[$user])) {
			$available = implode(', ', array_keys($users));
			throw new \InvalidArgumentException(
				"Unknown user '{$user}' for instance '{$this->defaultInstance}'. Available: {$available}"
			);
		}

		$this->defaultUser = $user;
	}

	public function hasInstance(string $name): bool
	{
		return isset($this->instances[$name]);
	}

	public function count(): int
	{
		return count($this->instances);
	}
}
