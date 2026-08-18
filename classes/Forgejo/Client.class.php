<?php
/**
 * Forgejo MCP Server — API Client
 *
 * HTTP client for the Forgejo REST API.
 * Uses EnchiladaHTTP for HTTP transport with token-based authentication.
 *
 * @package    ForgejoMCP\Forgejo
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace Forgejo;

class Client
{
	/** Minimum Forgejo version exposing the action log download REST API */
	public const ACTION_LOGS_API_MIN_VERSION = '16.0.0';

	/** @var string Base URL of the Forgejo instance */
	private string $baseUrl;

	/** @var string API access token */
	private string $token;

	/** @var \EnchiladaHTTP */
	private \EnchiladaHTTP $http;

	/** @var bool */
	private bool $verifySsl;

	/** @var int Request timeout in seconds */
	private int $timeout;

	/** @var callable|null Optional HTTP callable for testing */
	private $httpClient;

	/** @var string|null Cached server version (null = not yet fetched) */
	private ?string $serverVersion = null;

	/** @var \EnchiladaMCP\Logger|null Optional logger for HTTP request diagnostics */
	private ?\EnchiladaMCP\Logger $logger = null;

	/** @var int HTTP status code of the most recent request (0 = none yet) */
	private int $lastHttpCode = 0;

	/**
	 * Create a new Forgejo API client.
	 *
	 * @param string        $baseUrl    Base URL (e.g., "https://codeberg.org")
	 * @param string        $token      Personal access token
	 * @param bool          $verifySsl  Verify SSL certificates (default: true)
	 * @param int           $timeout    Request timeout in seconds (default: 30)
	 * @param callable|null $httpClient Optional HTTP callable for testing
	 */
	public function __construct(
		string $baseUrl,
		string $token,
		bool $verifySsl = true,
		int $timeout = 30,
		?callable $httpClient = null
	) {
		$this->baseUrl = rtrim($baseUrl, '/');
		$this->token = $token;
		$this->verifySsl = $verifySsl;
		$this->timeout = $timeout;
		$this->httpClient = $httpClient;

		$this->http = new \EnchiladaHTTP($this->baseUrl);
		$this->http->setTimeout($timeout);
		$this->http->setVerifySsl($verifySsl);
	}

	/**
	 * Set a logger for HTTP request/response diagnostics.
	 *
	 * Every API call is logged with method, path, body digest, status code,
	 * and duration. Access tokens and request/response bodies are never
	 * logged — bodies are reduced to length + SHA-256 digest.
	 *
	 * @param \EnchiladaMCP\Logger|null $logger Logger instance, or null to disable
	 */
	public function setLogger(?\EnchiladaMCP\Logger $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * Perform a GET request.
	 *
	 * @param  string $endpoint API endpoint (e.g., "repos/owner/repo")
	 * @param  array  $query    Query parameters
	 * @return array            Decoded JSON response
	 * @throws ClientException
	 */
	public function get(string $endpoint, array $query = []): array
	{
		return $this->request('GET', $endpoint, null, $query);
	}

	/**
	 * Perform a POST request.
	 *
	 * @param  string     $endpoint API endpoint
	 * @param  array|null $data     Request body data (JSON-encoded)
	 * @return array                Decoded JSON response
	 * @throws ClientException
	 */
	public function post(string $endpoint, ?array $data = null): array
	{
		return $this->request('POST', $endpoint, $data);
	}

	/**
	 * Perform a PATCH request.
	 *
	 * @param  string     $endpoint API endpoint
	 * @param  array|null $data     Request body data
	 * @return array                Decoded JSON response
	 * @throws ClientException
	 */
	public function patch(string $endpoint, ?array $data = null): array
	{
		return $this->request('PATCH', $endpoint, $data);
	}

	/**
	 * Perform a PUT request.
	 *
	 * @param  string     $endpoint API endpoint
	 * @param  array|null $data     Request body data
	 * @return array                Decoded JSON response
	 * @throws ClientException
	 */
	public function put(string $endpoint, ?array $data = null): array
	{
		return $this->request('PUT', $endpoint, $data);
	}

	/**
	 * Perform a DELETE request.
	 *
	 * @param  string     $endpoint API endpoint
	 * @param  array|null $data     Optional JSON body (some Forgejo endpoints require it)
	 * @return array                Decoded JSON response (may be empty)
	 * @throws ClientException
	 */
	public function delete(string $endpoint, ?array $data = null): array
	{
		return $this->request('DELETE', $endpoint, $data);
	}

	/**
	 * Perform a GET request that returns raw text (not JSON).
	 *
	 * Used for diffs, logs, and other plain-text endpoints.
	 *
	 * @param  string $endpoint API endpoint
	 * @param  array  $query    Query parameters
	 * @return string           Raw response body
	 * @throws ClientException
	 */
	public function getRaw(string $endpoint, array $query = [], bool $webRoute = false): string
	{
		$path = $webRoute ? ltrim($endpoint, '/') : 'api/v1/' . ltrim($endpoint, '/');
		if (!empty($query)) {
			$path .= '?' . http_build_query($query);
		}

		$url = $this->baseUrl . '/' . $path;
		$headers = [
			'Accept: text/plain',
			'Authorization: token ' . $this->token,
		];

		$started = microtime(true);
		$this->log('debug', 'HTTP GET (raw) ' . self::redactUrl($url));

		if ($this->httpClient !== null) {
			$response = ($this->httpClient)('GET', $url, $headers, null);
			$this->lastHttpCode = $response['code'];
			if ($response['code'] >= 400) {
				$this->log('error', 'HTTP GET (raw) ' . self::redactUrl($url)
					. " failed after " . $this->elapsedMs($started) . "ms: HTTP {$response['code']}");
				throw new ClientException("HTTP {$response['code']} for {$url}", $response['code']);
			}
			$this->log('debug', 'HTTP GET (raw) ' . self::redactUrl($url)
				. " -> {$response['code']} in " . $this->elapsedMs($started)
				. 'ms response(' . \EnchiladaMCP\Logger::digest($response['body']) . ')');
			return $response['body'];
		}

		try {
			$result = $this->http->call($path, null, 'GET', $headers, null, 'raw');
		} catch (\Exception $e) {
			$this->log('error', 'HTTP GET (raw) ' . self::redactUrl($url)
				. " failed after " . $this->elapsedMs($started) . "ms: {$e->getMessage()}");
			throw new ClientException("HTTP error: " . $e->getMessage(), 0);
		}

		$httpCode = $this->http->getHttpCode();
		$this->lastHttpCode = $httpCode;
		if ($httpCode === 0 || $this->http->getLastCurlErrno() !== 0) {
			$this->log('error', 'HTTP GET (raw) ' . self::redactUrl($url)
				. " transport failure after " . $this->elapsedMs($started) . 'ms: ' . $this->http->getLastCurlError());
			throw $this->transportError($url);
		}
		if ($httpCode >= 400) {
			$this->log('error', 'HTTP GET (raw) ' . self::redactUrl($url)
				. " failed after " . $this->elapsedMs($started) . "ms: HTTP {$httpCode}");
			throw new ClientException("API error ({$httpCode}) for {$url}", $httpCode);
		}

		$body = is_string($result) ? $result : '';
		$this->log('debug', 'HTTP GET (raw) ' . self::redactUrl($url)
			. " -> {$httpCode} in " . $this->elapsedMs($started)
			. 'ms response(' . \EnchiladaMCP\Logger::digest($body) . ')');

		return $body;
	}

	/**
	 * Upload a file via multipart/form-data POST.
	 *
	 * Used for attachment uploads (issues, comments, releases).
	 *
	 * @param  string $endpoint  API endpoint
	 * @param  string $fieldName Form field name (usually "attachment")
	 * @param  string $filename  Filename for the upload
	 * @param  string $content   Raw binary file content
	 * @return array             Decoded JSON response
	 * @throws ClientException
	 */
	public function uploadFile(string $endpoint, string $fieldName, string $filename, string $content): array
	{
		$path = 'api/v1/' . ltrim($endpoint, '/');
		$url = $this->baseUrl . '/' . $path;
		$boundary = 'EnchiladaBoundary' . uniqid();

		$body = "--{$boundary}\r\n"
			. "Content-Disposition: form-data; name=\"{$fieldName}\"; filename=\"{$filename}\"\r\n"
			. "Content-Type: application/octet-stream\r\n\r\n"
			. $content . "\r\n"
			. "--{$boundary}--\r\n";

		$headers = [
			'Accept: application/json',
			'Authorization: token ' . $this->token,
			'Content-Type: multipart/form-data; boundary=' . $boundary,
		];

		$started = microtime(true);
		$this->log('debug', "HTTP POST (upload {$filename}) " . self::redactUrl($url)
			. ' body(' . \EnchiladaMCP\Logger::digest($body) . ')');

		if ($this->httpClient !== null) {
			$response = ($this->httpClient)('POST', $url, $headers, $body);
			$result = $this->handleResponse($response['code'], $response['body'], $url);
			$this->log('debug', "HTTP POST (upload {$filename}) " . self::redactUrl($url)
				. " -> {$this->lastHttpCode} in " . $this->elapsedMs($started) . 'ms');
			return $result;
		}

		try {
			$result = $this->http->call($path, $body, 'POST', $headers, null, 'json');
		} catch (\Exception $e) {
			$this->log('error', "HTTP POST (upload {$filename}) " . self::redactUrl($url)
				. " failed after " . $this->elapsedMs($started) . "ms: {$e->getMessage()}");
			throw new ClientException("Upload error: " . $e->getMessage(), 0);
		}

		$httpCode = $this->http->getHttpCode();
		$this->lastHttpCode = $httpCode;
		if ($httpCode === 0 || $this->http->getLastCurlErrno() !== 0) {
			$this->log('error', "HTTP POST (upload {$filename}) " . self::redactUrl($url)
				. " transport failure after " . $this->elapsedMs($started) . 'ms: ' . $this->http->getLastCurlError());
			throw $this->transportError($url);
		}
		if ($httpCode >= 400) {
			$this->log('error', "HTTP POST (upload {$filename}) " . self::redactUrl($url)
				. " failed after " . $this->elapsedMs($started) . "ms: HTTP {$httpCode}");
			throw new ClientException("Upload failed ({$httpCode}) for {$url}", $httpCode);
		}

		$this->log('debug', "HTTP POST (upload {$filename}) " . self::redactUrl($url)
			. " -> {$httpCode} in " . $this->elapsedMs($started) . 'ms');

		return is_array($result) ? $result : [];
	}

	/**
	 * Get the base URL.
	 *
	 * @return string
	 */
	public function getBaseUrl(): string
	{
		return $this->baseUrl;
	}

	/**
	 * Get the Forgejo server version string (cached per client).
	 *
	 * Uses GET /api/v1/version which exists on all Forgejo versions.
	 * Returns an empty string when the version cannot be determined;
	 * callers must treat an unknown version as "feature unsupported".
	 *
	 * @param  bool $refresh Force a fresh lookup
	 * @return string        Version string (e.g. "16.0.0"), or '' on failure
	 */
	public function getServerVersion(bool $refresh = false): string
	{
		if ($this->serverVersion === null || $refresh) {
			$this->serverVersion = '';
			try {
				$result = $this->get('version');
				if (isset($result['version']) && is_string($result['version'])) {
					$this->serverVersion = $result['version'];
				}
			} catch (ClientException $e) {
				// Leave empty — callers treat an unknown version as unsupported
			}
		}
		return $this->serverVersion;
	}

	/**
	 * Check whether the connected server runs at least the given version.
	 *
	 * @param  string $minimum Minimum version (e.g. "16.0.0")
	 * @return bool            false when the server version is unknown or unparseable
	 */
	public function versionAtLeast(string $minimum): bool
	{
		if (preg_match('/\d+(?:\.\d+)*/', $this->getServerVersion(), $m) !== 1) {
			return false;
		}
		return version_compare($m[0], $minimum, '>=');
	}

	/**
	 * Whether the server exposes the Forgejo 16+ action log download API
	 * (GET /repos/{owner}/{repo}/actions/jobs/{job_id}/logs and
	 * GET /repos/{owner}/{repo}/actions/runs/{run_id}/logs).
	 *
	 * @return bool
	 */
	public function supportsActionLogsApi(): bool
	{
		return $this->versionAtLeast(self::ACTION_LOGS_API_MIN_VERSION);
	}

	/**
	 * Perform an HTTP request to the Forgejo API.
	 *
	 * @param  string     $method   HTTP method
	 * @param  string     $endpoint API endpoint relative to /api/v1/
	 * @param  array|null $data     Optional request body data
	 * @param  array      $query    Optional query parameters
	 * @return array                Decoded JSON response
	 * @throws ClientException
	 */
	private function request(string $method, string $endpoint, ?array $data = null, array $query = []): array
	{
		$path = 'api/v1/' . ltrim($endpoint, '/');
		if (!empty($query)) {
			$path .= '?' . http_build_query($query);
		}

		$url = $this->baseUrl . '/' . $path;
		$headers = [
			'Accept: application/json',
			'Authorization: token ' . $this->token,
		];

		if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE']) && $data !== null) {
			$headers[] = 'Content-Type: application/json';
		}

		$body = ($data !== null) ? json_encode($data) : null;

		$started = microtime(true);
		$this->log('debug', "HTTP {$method} " . self::redactUrl($url)
			. ($body !== null ? ' body(' . \EnchiladaMCP\Logger::digest($body) . ')' : ''));

		try {
			// Use injected HTTP client if available (for testing)
			if ($this->httpClient !== null) {
				$response = ($this->httpClient)($method, $url, $headers, $body);
				$result = $this->handleResponse($response['code'], $response['body'], $url);
			} else {
				$result = $this->enchiladaRequest($method, $path, $data, $headers);
			}
		} catch (ClientException $e) {
			$this->log('error', "HTTP {$method} " . self::redactUrl($url)
				. " failed after " . $this->elapsedMs($started) . "ms: {$e->getMessage()}");
			throw $e;
		}

		$this->log('debug', "HTTP {$method} " . self::redactUrl($url)
			. " -> {$this->lastHttpCode} in " . $this->elapsedMs($started) . 'ms');

		return $result;
	}

	/**
	 * Execute request via EnchiladaHTTP.
	 *
	 * @param  string     $method  HTTP method
	 * @param  string     $path    Full API path
	 * @param  array|null $data    Request body
	 * @param  array      $headers Headers
	 * @return array               Decoded JSON
	 * @throws ClientException
	 */
	private function enchiladaRequest(string $method, string $path, ?array $data, array $headers): array
	{
		try {
			$result = $this->http->call(
				$path,
				$data,
				$method,
				$headers,
				null,
				'json'
			);
		} catch (\Exception $e) {
			throw new ClientException("HTTP error: " . $e->getMessage(), 0);
		}

		$httpCode = $this->http->getHttpCode();
		$this->lastHttpCode = $httpCode;

		// Handle 204 No Content (common for DELETE, some PUT)
		if ($httpCode === 204) {
			return [];
		}

		// Transport-level failure (timeout, DNS, connection refused): curl
		// returned false with no HTTP status. Never treat as success.
		if ($httpCode === 0 || $this->http->getLastCurlErrno() !== 0) {
			throw $this->transportError("{$this->baseUrl}/{$path}");
		}

		if ($result === false || $result === null) {
			if ($httpCode >= 400) {
				throw new ClientException("API error ({$httpCode}) for {$this->baseUrl}/{$path}", $httpCode);
			}
			return [];
		}

		return is_array($result) ? $result : [];
	}

	/**
	 * Handle HTTP response from injected client (testing path).
	 *
	 * @param  int    $httpCode     HTTP status code
	 * @param  string $responseBody Raw response body
	 * @param  string $url          Request URL
	 * @return array                Decoded JSON
	 * @throws ClientException
	 */
	private function handleResponse(int $httpCode, string $responseBody, string $url): array
	{
		$this->lastHttpCode = $httpCode;

		if ($httpCode === 204) {
			return [];
		}

		if ($httpCode === 0) {
			throw new ClientException("Transport error for {$url}: no HTTP response received", 0);
		}

		if ($httpCode === 401) {
			throw new ClientException("Authentication failed (401) for {$url}. Check access token.", 401);
		}

		if ($httpCode === 403) {
			throw new ClientException("Access denied (403) for {$url}. Insufficient token permissions.", 403);
		}

		if ($httpCode === 404) {
			throw new ClientException("Not found (404) for {$url}.", 404);
		}

		if ($httpCode === 409) {
			throw new ClientException("Conflict (409) for {$url}: {$responseBody}", 409);
		}

		if ($httpCode === 422) {
			throw new ClientException("Validation error (422) for {$url}: {$responseBody}", 422);
		}

		if ($httpCode >= 500) {
			throw new ClientException("Server error ({$httpCode}) for {$url}: {$responseBody}", $httpCode);
		}

		if ($httpCode >= 400) {
			throw new ClientException("Client error ({$httpCode}) for {$url}: {$responseBody}", $httpCode);
		}

		if (empty($responseBody)) {
			return [];
		}

		$decoded = json_decode($responseBody, true);
		if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
			throw new ClientException(
				"Invalid JSON response from {$url}: " . json_last_error_msg(),
				0
			);
		}

		return $decoded ?? [];
	}

	/**
	 * Build an exception for a transport-level failure (no HTTP response).
	 *
	 * Timeouts get an explanatory message: they are a known issue with
	 * long-running server-side operations (e.g. merges on large
	 * repositories), and the server may still have completed the
	 * operation — callers must verify state before retrying.
	 *
	 * @param  string $url Request URL (already credential-safe)
	 * @return ClientException
	 */
	private function transportError(string $url): ClientException
	{
		$errno = $this->http->getLastCurlErrno();
		$error = $this->http->getLastCurlError();

		if ($errno === CURLE_OPERATION_TIMEDOUT) {
			return new ClientException(
				"Request timed out after {$this->timeout}s for {$url}. "
				. "This is a known issue with long-running server-side operations "
				. "(e.g. merges on large repositories); the server may still be processing "
				. "or may have already completed the operation. "
				. "Verify the state on the server before retrying. "
				. "If this happens repeatedly, increase the 'timeout' value for this "
				. "instance in instances.json.",
				0
			);
		}

		return new ClientException("Transport error for {$url}: {$error} (curl errno {$errno})", 0);
	}

	/**
	 * Log a message at the given level via the configured logger.
	 *
	 * @param string $level   Level name: debug, info, error
	 * @param string $message Message text
	 */
	private function log(string $level, string $message): void
	{
		if ($this->logger === null) {
			return;
		}
		try {
			$this->logger->{$level}($message);
		} catch (\Throwable $e) {
			// Logging must never break API calls
		}
	}

	/**
	 * Elapsed milliseconds since the given microtime.
	 *
	 * @param  float $started microtime(true) at start of operation
	 * @return float          Elapsed milliseconds (1 decimal)
	 */
	private function elapsedMs(float $started): float
	{
		return round((microtime(true) - $started) * 1000, 1);
	}

	/**
	 * Redact credential material that may appear in URL query strings.
	 *
	 * @param  string $url URL to sanitize
	 * @return string      URL with token/access_token parameter values masked
	 */
	private static function redactUrl(string $url): string
	{
		return preg_replace('/([?&](?:token|access_token|private_token)=)[^&]*/i', '$1[REDACTED]', $url);
	}
}
