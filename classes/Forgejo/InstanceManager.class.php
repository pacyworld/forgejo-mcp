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

	/**
	 * Create a new InstanceManager.
	 *
	 * @param array<string,array>  $instances       Instance configurations
	 * @param string               $defaultInstance Default instance name
	 * @param string               $defaultUser     Default user name
	 * @param callable|null        $httpClient      Optional HTTP callable for testing
	 */
	public function __construct(array $instances, string $defaultInstance, string $defaultUser, ?callable $httpClient = null)
	{
		if (empty($instances)) {
			throw new \InvalidArgumentException('At least one Forgejo instance must be configured.');
		}

		if (!isset($instances[$defaultInstance])) {
			throw new \InvalidArgumentException("Default instance '{$defaultInstance}' not found in configuration.");
		}

		$users = $instances[$defaultInstance]['users'] ?? [];
		if (!isset($users[$defaultUser])) {
			throw new \InvalidArgumentException("Default user '{$defaultUser}' not found in instance '{$defaultInstance}'.");
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

		if (empty($defaultInstance) && !empty($instances)) {
			$defaultInstance = array_key_first($instances);
		}

		if (empty($defaultUser) && !empty($instances[$defaultInstance]['users'] ?? [])) {
			$defaultUser = array_key_first($instances[$defaultInstance]['users']);
		}

		return new self($instances, $defaultInstance, $defaultUser, $httpClient);
	}

	/**
	 * Get a Client for the specified instance and user (or defaults).
	 *
	 * @param  string|null $instance Instance name (null = default)
	 * @param  string|null $user     User name (null = default for that instance)
	 * @return Client                Forgejo API client
	 */
	public function getClient(?string $instance = null, ?string $user = null): Client
	{
		$instance = $instance ?: $this->defaultInstance;
		$user = $user ?: ($instance === $this->defaultInstance ? $this->defaultUser : null);

		if (!isset($this->instances[$instance])) {
			$available = implode(', ', array_keys($this->instances));
			throw new \InvalidArgumentException(
				"Unknown instance '{$instance}'. Available: {$available}"
			);
		}

		$instanceConfig = $this->instances[$instance];
		$users = $instanceConfig['users'] ?? [];

		if ($user === null && !empty($users)) {
			$user = array_key_first($users);
		}

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
				'is_default' => ($name === $this->defaultInstance),
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
