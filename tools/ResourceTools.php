<?php
/**
 * Forgejo MCP Server — Resource Templates
 *
 * Exposes Forgejo entities as URI-addressable resources using the forgejo:// scheme.
 * Resources are read-only and additive — they do not replace tools.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpResource;
use Forgejo\InstanceManager;

class ResourceTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Get user or organization profile.
	 */
	#[McpResource(
		uriTemplate: 'forgejo://owner/{owner}',
		name: 'User or Organization',
		description: 'Get profile information for a user or organization.'
	)]
	public function owner(string $owner): array
	{
		$client = $this->manager->getClient();

		// Try user first, fall back to org
		try {
			return $client->get("users/{$owner}");
		} catch (\Throwable $e) {
			try {
				return $client->get("orgs/{$owner}");
			} catch (\Throwable $e2) {
				throw new \RuntimeException("User or organization '{$owner}' not found.");
			}
		}
	}

	/**
	 * Get repository details.
	 */
	#[McpResource(
		uriTemplate: 'forgejo://repo/{owner}/{repo}',
		name: 'Repository',
		description: 'Get full details of a repository including settings, permissions, and metadata.'
	)]
	public function repo(string $owner, string $repo): array
	{
		$client = $this->manager->getClient();
		return $client->get("repos/{$owner}/{$repo}");
	}

	/**
	 * Get commit details.
	 */
	#[McpResource(
		uriTemplate: 'forgejo://repo/{owner}/{repo}/commit/{sha}',
		name: 'Commit',
		description: 'Get details of a specific commit by SHA.'
	)]
	public function commit(string $owner, string $repo, string $sha): array
	{
		$client = $this->manager->getClient();
		return $client->get("repos/{$owner}/{$repo}/git/commits/{$sha}");
	}

	/**
	 * Get commit statuses (CI/CD results).
	 */
	#[McpResource(
		uriTemplate: 'forgejo://repo/{owner}/{repo}/commit/{sha}/status',
		name: 'Commit Status',
		description: 'Get combined CI/CD status for a commit.'
	)]
	public function commitStatus(string $owner, string $repo, string $sha): array
	{
		$client = $this->manager->getClient();
		return $client->get("repos/{$owner}/{$repo}/statuses/{$sha}");
	}

	/**
	 * Get issue with embedded comments (capped at 30).
	 */
	#[McpResource(
		uriTemplate: 'forgejo://repo/{owner}/{repo}/issue/{index}',
		name: 'Issue',
		description: 'Get an issue with its comments (first 30). Use list_issue_comments tool for full list.'
	)]
	public function issue(string $owner, string $repo, string $index): array
	{
		$client = $this->manager->getClient();
		$issue = $client->get("repos/{$owner}/{$repo}/issues/{$index}");
		$comments = $client->get("repos/{$owner}/{$repo}/issues/{$index}/comments", ['limit' => 30]);

		$result = $issue;
		$result['comments'] = $comments;
		if (count($comments) >= 30) {
			$result['_truncated'] = true;
			$result['_hint'] = 'Comments truncated at 30. Use list_issue_comments tool for full list.';
		}

		return $result;
	}

	/**
	 * Get a specific comment.
	 */
	#[McpResource(
		uriTemplate: 'forgejo://repo/{owner}/{repo}/{kind}/{index}/comment/{id}',
		name: 'Comment',
		description: 'Get a specific issue or PR comment by ID.'
	)]
	public function comment(string $owner, string $repo, string $kind, string $index, string $id): array
	{
		$client = $this->manager->getClient();
		return $client->get("repos/{$owner}/{$repo}/issues/comments/{$id}");
	}

	/**
	 * Get pull request with embedded reviews (capped at 30).
	 */
	#[McpResource(
		uriTemplate: 'forgejo://repo/{owner}/{repo}/pr/{index}',
		name: 'Pull Request',
		description: 'Get a pull request with its reviews (first 30). Use list_pull_reviews tool for full list.'
	)]
	public function pullRequest(string $owner, string $repo, string $index): array
	{
		$client = $this->manager->getClient();
		$pr = $client->get("repos/{$owner}/{$repo}/pulls/{$index}");
		$reviews = $client->get("repos/{$owner}/{$repo}/pulls/{$index}/reviews", ['limit' => 30]);

		$result = $pr;
		$result['reviews'] = $reviews;
		if (count($reviews) >= 30) {
			$result['_truncated'] = true;
			$result['_hint'] = 'Reviews truncated at 30. Use list_pull_reviews tool for full list.';
		}

		return $result;
	}
}
