# Tool Reference

All tools accept optional `instance` and `user` parameters to target a specific Forgejo server and user identity. When omitted, the current default is used.

## Instance Management

| Tool | Description |
|------|-------------|
| `forgejo_list_instances` | List all configured instances with users and current defaults |
| `forgejo_switch_instance` | Switch the active Forgejo instance |
| `forgejo_switch_user` | Switch the active user within the current instance |
| `get_forgejo_mcp_server_version` | Get MCP server name and version |

## User

| Tool | Description |
|------|-------------|
| `get_my_user_info` | Get the authenticated user's profile |
| `search_users` | Search users by username or email |

## Repository

| Tool | Description |
|------|-------------|
| `list_my_repos` | List repos owned by the authenticated user |
| `search_repos` | Search repositories |
| `create_repo` | Create a repo (personal or under an organization) |
| `fork_repo` | Fork a repository |
| `list_repo_contents` | List files/dirs at a path |
| `get_repo_tree` | Get Git tree (optionally recursive) |

## Branch

| Tool | Description |
|------|-------------|
| `list_branches` | List repository branches |
| `create_branch` | Create a branch |
| `delete_branch` | Delete a branch |

## File Operations

| Tool | Description |
|------|-------------|
| `get_file_content` | Read file content (auto-decodes base64) |
| `create_file` | Create a file via commit |
| `update_file` | Update a file (requires current SHA) |
| `delete_file` | Delete a file (requires current SHA) |

## Commits

| Tool | Description |
|------|-------------|
| `list_repo_commits` | List commits with branch/path filtering |

## Issues

| Tool | Description |
|------|-------------|
| `list_repo_issues` | List issues with state/label/milestone filters |
| `get_issue_by_index` | Get issue by index number |
| `create_issue` | Create an issue |
| `update_issue` | Update issue fields |
| `issue_state_change` | Open or close an issue |

## Labels

| Tool | Description |
|------|-------------|
| `list_repo_labels` | List repository labels |
| `list_org_labels` | List organization labels |
| `add_issue_labels` | Add labels to an issue |
| `remove_issue_labels` | Remove a label from an issue |

## Milestones

| Tool | Description |
|------|-------------|
| `list_repo_milestones` | List milestones |

## Comments

| Tool | Description |
|------|-------------|
| `list_issue_comments` | List comments on an issue |
| `get_issue_comment` | Get a comment by ID |
| `create_issue_comment` | Add a comment |
| `edit_issue_comment` | Edit a comment |
| `delete_issue_comment` | Delete a comment |

## Pull Requests

| Tool | Description |
|------|-------------|
| `list_repo_pull_requests` | List PRs with state/sort/label filters |
| `get_pull_request_by_index` | Get PR by index |
| `create_pull_request` | Create a PR |
| `update_pull_request` | Update PR fields |
| `merge_pull_request` | Merge a PR (merge/rebase/squash) |
| `list_pull_request_files` | List changed files |
| `get_pull_request_diff` | Get unified diff (plain text) |

## Reviews

| Tool | Description |
|------|-------------|
| `list_pull_reviews` | List reviews on a PR |
| `get_pull_review` | Get a specific review |
| `list_pull_review_comments` | List inline review comments |
| `create_pull_review` | Create a review with optional inline comments |
| `submit_pull_review` | Submit a pending review |
| `delete_pull_review` | Delete a pending review |
| `dismiss_pull_review` | Dismiss a review |
| `create_review_requests` | Request reviews from users/teams |
| `delete_review_requests` | Cancel review requests |

## Notifications

| Tool | Description |
|------|-------------|
| `check_notifications` | List notifications |
| `get_notification_thread` | Get a notification thread |
| `mark_notification_read` | Mark one notification as read |
| `mark_all_notifications_read` | Mark all as read |
| `list_repo_notifications` | List notifications for a repo |
| `mark_repo_notifications_read` | Mark all in a repo as read |

## Releases

| Tool | Description |
|------|-------------|
| `list_releases` | List releases |
| `get_release_by_id` | Get release by ID |
| `get_release_by_tag` | Get release by tag name |
| `get_latest_release` | Get latest non-draft release |
| `create_release` | Create a release |
| `edit_release` | Edit a release |
| `delete_release` | Delete by ID |
| `delete_release_by_tag` | Delete by tag |

## Attachments

All three attachment scopes (issue, comment, release) support the same operations:

| Tool Pattern | Description |
|-------------|-------------|
| `list_*_attachments` | List attachments |
| `get_*_attachment` | Get attachment metadata |
| `create_*_attachment` | Upload (base64 content → multipart) |
| `edit_*_attachment` | Rename |
| `download_*_attachment` | Get metadata + download URL |
| `delete_*_attachment` | Delete |

## Workflows & Actions

| Tool | Description |
|------|-------------|
| `dispatch_workflow` | Trigger a workflow run |
| `list_workflow_runs` | List runs with status filter |
| `get_workflow_run` | Get run details |
| `get_workflow_run_jobs` | List jobs in a run |
| `get_workflow_job_logs` | Download job logs (plain text) |

## Action Secrets

| Tool | Description |
|------|-------------|
| `list_repo_action_secrets` | List repo secrets (names only) |
| `create_or_update_repo_action_secret` | Set a repo secret |
| `delete_repo_action_secret` | Delete a repo secret |
| `list_org_action_secrets` | List org secrets |
| `create_or_update_org_action_secret` | Set an org secret |
| `delete_org_action_secret` | Delete an org secret |

## Time Tracking

| Tool | Description |
|------|-------------|
| `list_issue_tracked_times` | Tracked times on an issue |
| `list_repo_tracked_times` | All tracked times in a repo |
| `list_my_tracked_times` | My tracked times (all repos) |
| `add_issue_time` | Log time (seconds) |
| `reset_issue_time` | Delete ALL time entries on an issue |
| `delete_issue_time_entry` | Delete a single time entry |
| `start_issue_stopwatch` | Start a stopwatch |
| `stop_issue_stopwatch` | Stop and record time |
| `cancel_issue_stopwatch` | Cancel without recording |
| `list_my_stopwatches` | List running stopwatches |

## Organizations

| Tool | Description |
|------|-------------|
| `get_org` | Get org details |
| `create_org` | Create an organization |
| `edit_org` | Edit org settings |
| `delete_org` | Delete org (destructive!) |
| `list_my_orgs` | My organizations |
| `list_user_orgs` | A user's organizations |
| `list_org_members` | Org members |
| `check_org_membership` | Check if user is in org |
| `remove_org_member` | Remove from org |
| `list_org_teams` | List teams |
| `search_org_teams` | Search teams by name |
| `create_org_team` | Create a team |
| `add_team_member` | Add user to team |
| `remove_team_member` | Remove from team |
| `add_team_repo` | Add repo to team |
| `remove_team_repo` | Remove repo from team |

## Tags

| Tool | Description |
|------|-------------|
| `list_tags` | List repository tags |
| `get_tag` | Get tag by name |
| `create_tag` | Create a tag |
| `delete_tag` | Delete a tag |

## Packages

| Tool | Description |
|------|-------------|
| `list_packages` | List packages for an owner |
| `get_package` | Get package details |
| `delete_package` | Delete a package version |
| `list_package_files` | List files in a package |

## Push Mirrors

| Tool | Description |
|------|-------------|
| `list_push_mirrors` | List push mirrors |
| `add_push_mirror` | Add a push mirror |
| `get_push_mirror` | Get mirror details |
| `delete_push_mirror` | Remove a mirror |
| `sync_push_mirror` | Trigger sync now |
