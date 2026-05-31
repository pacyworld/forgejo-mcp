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
	/** @var string Base URL of the Forgejo instance */
	private string $baseUrl;

	/** @var string API access token */
	private string $token;

	/** @var \EnchiladaHTTP */
	private \EnchiladaHTTP $http;

	/** @var bool */
	private bool $verifySsl;

	/** @var callable|null Optional HTTP callable for testing */
	private $httpClient;

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
		$this->httpClient = $httpClient;

		$this->http = new \EnchiladaHTTP($this->baseUrl);
		$this->http->setTimeout($timeout);
		$this->http->setVerifySsl($verifySsl);
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
	public function getRaw(string $endpoint, array $query = []): string
	{
		$path = 'api/v1/' . ltrim($endpoint, '/');
		if (!empty($query)) {
			$path .= '?' . http_build_query($query);
		}

		$url = $this->baseUrl . '/' . $path;
		$headers = [
			'Accept: text/plain',
			'Authorization: token ' . $this->token,
		];

		if ($this->httpClient !== null) {
			$response = ($this->httpClient)('GET', $url, $headers, null);
			if ($response['code'] >= 400) {
				throw new ClientException("HTTP {$response['code']} for {$url}", $response['code']);
			}
			return $response['body'];
		}

		try {
			$result = $this->http->call($path, null, 'GET', $headers, null, 'raw');
		} catch (\Exception $e) {
			throw new ClientException("HTTP error: " . $e->getMessage(), 0);
		}

		$httpCode = $this->http->getHttpCode();
		if ($httpCode >= 400) {
			throw new ClientException("API error ({$httpCode}) for {$url}", $httpCode);
		}

		return is_string($result) ? $result : '';
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

		if ($this->httpClient !== null) {
			$response = ($this->httpClient)('POST', $url, $headers, $body);
			return $this->handleResponse($response['code'], $response['body'], $url);
		}

		try {
			$result = $this->http->call($path, $body, 'POST', $headers, null, 'json');
		} catch (\Exception $e) {
			throw new ClientException("Upload error: " . $e->getMessage(), 0);
		}

		$httpCode = $this->http->getHttpCode();
		if ($httpCode >= 400) {
			throw new ClientException("Upload failed ({$httpCode}) for {$url}", $httpCode);
		}

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

		// Use injected HTTP client if available (for testing)
		if ($this->httpClient !== null) {
			$response = ($this->httpClient)($method, $url, $headers, $body);
			return $this->handleResponse($response['code'], $response['body'], $url);
		}

		return $this->enchiladaRequest($method, $path, $data, $headers);
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

		// Handle 204 No Content (common for DELETE, some PUT)
		if ($httpCode === 204) {
			return [];
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
		if ($httpCode === 204) {
			return [];
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
}
