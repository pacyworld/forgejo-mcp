<?php
/**
 * Forgejo MCP Server — Attachment Tools
 *
 * Consolidated: one `attachment` tool covering issue, comment and release
 * attachments, selected by `target` + `action`. The per-target method
 * families remain as internal handlers in CommentAttachmentTools,
 * IssueAttachmentTools and ReleaseAttachmentTools.
 *
 * @package    ForgejoMCP\Tools
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Forgejo\ConsolidatedToolBase;
use Forgejo\InstanceManager;

// The registration loop loads files in directory order; the internal
// handler families this consolidated tool wraps must be available now.
require_once __DIR__ . '/CommentAttachmentTools.php';
require_once __DIR__ . '/IssueAttachmentTools.php';
require_once __DIR__ . '/ReleaseAttachmentTools.php';

class AttachmentTools extends ConsolidatedToolBase
{
	private CommentAttachmentTools $comments;
	private IssueAttachmentTools $issues;
	private ReleaseAttachmentTools $releases;

	public function __construct(InstanceManager $manager)
	{
		parent::__construct($manager);
		$this->comments = new CommentAttachmentTools($manager);
		$this->issues = new IssueAttachmentTools($manager);
		$this->releases = new ReleaseAttachmentTools($manager);
	}

	#[McpTool(
		name: 'attachment',
		description: 'Manage issue/comment/release attachments. target says which ID identifies the parent: issue => index, comment => comment_id, release => release_id. Actions: list(parent), get(parent, attachment_id), download(parent, attachment_id; metadata including browser_download_url), create(parent, filename, content base64), edit(parent, attachment_id, name), delete(parent, attachment_id). All actions also take owner, repo.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action' => ['type' => 'string', 'enum' => ['list', 'get', 'download', 'create', 'edit', 'delete']],
				'target' => ['type' => 'string', 'enum' => ['issue', 'comment', 'release']],
				'owner' => ['type' => 'string'],
				'repo' => ['type' => 'string'],
				'index' => ['type' => 'integer', 'description' => 'Issue or PR number (target=issue)'],
				'comment_id' => ['type' => 'integer', 'description' => 'target=comment'],
				'release_id' => ['type' => 'integer', 'description' => 'target=release'],
				'attachment_id' => ['type' => 'integer'],
				'filename' => ['type' => 'string'],
				'content' => ['type' => 'string', 'description' => 'Base64'],
				'name' => ['type' => 'string', 'description' => 'New filename'],
				'instance' => ['type' => 'string'],
				'user' => ['type' => 'string'],
			],
			'required' => ['action', 'target', 'instance', 'user'],
		],
		renamedFrom: [
			'list_issue_attachments' => 'attachment action=list target=issue',
			'get_issue_attachment' => 'attachment action=get target=issue',
			'download_issue_attachment' => 'attachment action=download target=issue',
			'create_issue_attachment' => 'attachment action=create target=issue',
			'edit_issue_attachment' => 'attachment action=edit target=issue',
			'delete_issue_attachment' => 'attachment action=delete target=issue',
			'list_comment_attachments' => 'attachment action=list target=comment',
			'get_comment_attachment' => 'attachment action=get target=comment',
			'download_comment_attachment' => 'attachment action=download target=comment',
			'create_comment_attachment' => 'attachment action=create target=comment',
			'edit_comment_attachment' => 'attachment action=edit target=comment',
			'delete_comment_attachment' => 'attachment action=delete target=comment',
			'list_release_attachments' => 'attachment action=list target=release',
			'get_release_attachment' => 'attachment action=get target=release',
			'download_release_attachment' => 'attachment action=download target=release',
			'create_release_attachment' => 'attachment action=create target=release',
			'edit_release_attachment' => 'attachment action=edit target=release',
			'delete_release_attachment' => 'attachment action=delete target=release',
		]
	)]
	public function attachment(string $action, ?string $target = null, ?string $owner = null, ?string $repo = null, ?int $index = null, ?int $comment_id = null, ?int $release_id = null, ?int $attachment_id = null, ?string $filename = null, ?string $content = null, ?string $name = null, string $instance = '', string $user = ''): mixed
	{
		$targets = ['issue' => [$this->issues, 'issue', 'index'], 'comment' => [$this->comments, 'comment', 'comment_id'], 'release' => [$this->releases, 'release', 'release_id']];
		if ($target === null || !isset($targets[$target])) {
			throw new \InvalidArgumentException("attachment: unknown target '{$target}'. Valid: issue, comment, release");
		}
		[$handler, $prefix, $parent] = $targets[$target];
		$verbs = [
			'list' => [[$handler, "list_{$prefix}_attachments"], [$parent]],
			'get' => [[$handler, "get_{$prefix}_attachment"], [$parent, 'attachment_id']],
			'download' => [[$handler, "download_{$prefix}_attachment"], [$parent, 'attachment_id']],
			'create' => [[$handler, "create_{$prefix}_attachment"], [$parent, 'filename', 'content']],
			'edit' => [[$handler, "edit_{$prefix}_attachment"], [$parent, 'attachment_id', 'name']],
			'delete' => [[$handler, "delete_{$prefix}_attachment"], [$parent, 'attachment_id']],
		];

		$ops = [];
		foreach ($verbs as $verb => [$callable, $required]) {
			$ops[$verb] = [
				'handler' => $callable,
				'required' => array_merge(['owner', 'repo'], $required),
				'args' => ['owner', 'repo', $parent, 'attachment_id', 'filename', 'content', 'name', 'instance', 'user'],
			];
		}
		return $this->dispatch('attachment', $action, get_defined_vars(), $ops);
	}
}
