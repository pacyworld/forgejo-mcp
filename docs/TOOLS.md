# Tool Reference

All tools take `instance` and `user` to target a specific Forgejo server and
user identity (`list_forgejo_instances` shows the valid values). Since 2.0.0
most resource families are one tool per family, selected by an `action` enum;
the tool description lists each action's required parameters. Per-action
requirements are enforced server-side: a call missing one fails with an error
naming the missing parameters.

## Standalone (high-traffic)

| Tool | Description |
|------|-------------|
| `list_repo_issues` | List issues (or PRs) in a repository |
| `get_issue_by_index` | Get an issue by index |
| `create_issue` | Create an issue |
| `create_pull_request` | Create a pull request |
| `merge_pull_request` | Merge a PR (a timeout is NOT a failure, never retry) |
| `get_file_content` | Get a file: metadata, sha, decoded content |

## Consolidated (action-dispatched)

| Tool | Actions | Former tools |
|------|---------|--------------|
| `attachment` | `list` `get` `download` `create` `edit` `delete` (+ `target`: `issue`/`comment`/`release`) | the 18 `*_attachment(s)` tools |
| `branch` | `list` `create` `delete` | `list_branches`, `create_branch`, `delete_branch` |
| `issue` | `update` `state_change` | `update_issue`, `issue_state_change` |
| `issue_comment` | `list` `get` `create` `edit` `delete` | the 5 `*_issue_comment(s)` tools |
| `file` | `create` `update` `delete` `list_contents` `tree` | `create_file`, `update_file`, `delete_file`, `list_repo_contents`, `get_repo_tree` |
| `label` | `list_repo` `list_org` `add` `remove` | the 4 `*label*` tools |
| `notification` | `check` `list_repo` `get_thread` `mark_read` `mark_all_read` `mark_repo_read` | the 6 `*notification*` tools |
| `org` | `get` `create` `edit` `delete` `list_mine` `list_user` `list_members` `check_membership` `remove_member` | the 9 `*_org*` tools |
| `team` | `list` `search` `create` `add_member` `remove_member` `add_repo` `remove_repo` | `*_org_teams` + `*_team_*` tools |
| `package` | `list` `get` `list_files` `delete` | the 4 `*package*` tools |
| `pull_request` | `list` `get` `update` `files` `diff` | `list_repo_pull_requests`, `get_pull_request_by_index`, `update_pull_request`, `list_pull_request_files`, `get_pull_request_diff` |
| `pull_review` | `list` `get` `comments` `create` `submit` `delete` `dismiss` `request_reviewers` `delete_requests` | the 9 `*review*` tools |
| `push_mirror` | `list` `add` `get` `delete` `sync` | the 5 `*_push_mirror(s)` tools |
| `release` | `list` `get_by_id` `get_by_tag` `latest` `create` `edit` `delete` `delete_by_tag` | the 8 `*release*` tools |
| `repo` | `list_mine` `create` `fork` | `list_my_repos`, `create_repo`, `fork_repo` |
| `tag` | `list` `get` `create` `delete` | the 4 `*tag*` tools |
| `time_tracking` | `list_issue` `list_repo` `list_mine` `add` `reset` `delete_entry` `start_stopwatch` `stop_stopwatch` `cancel_stopwatch` `list_stopwatches` | the 10 time/stopwatch tools |
| `workflow` | `dispatch` `list_runs` `get_run` `list_jobs` `job_logs` `job_logs_by_id` `download_run_logs` | the 7 workflow-logs tools (`job_logs_by_id`/`download_run_logs` need Forgejo 16+) |
| `action_secret` | `list_repo` `set_repo` `delete_repo` `list_org` `set_org` `delete_org` | the 6 `*action_secret*` tools (values are write-only) |

## Instance / server / misc

| Tool | Description |
|------|-------------|
| `list_forgejo_instances` | List configured instances, users, and the defaults |
| `get_forgejo_mcp_server_version` | MCP server name and version |
| `get_forgejo_version` | Forgejo server version and version-gated features |
| `get_my_user_info` | Authenticated user's profile |
| `search_users` | Search users |
| `search_repos` | Search repositories |
| `list_repo_commits` | List commits |
| `list_repo_milestones` | List milestones |

## Unknown-tool errors

Calling a pre-2.0.0 tool name returns an error whose suggestion names the
replacement tool and action, e.g.
`Unknown tool: 'list_push_mirrors'. Closest matches: push_mirror action=list, ...`

## Resources

Read-only `forgejo://` resource templates remain available for direct
entity access — see `resources/templates/list`.
