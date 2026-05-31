# Forgejo MCP Server

A PHP Model Context Protocol server for Forgejo instances, built on the [Enchilada Framework](https://buenapp.org/enchilada). Supports multiple instances and multiple users per instance.

## Features

- **125+ MCP tools** covering repositories, issues, pull requests, releases, workflows, organizations, and more
- **7 resource templates** using the `forgejo://` URI scheme for content-addressable entity access
- **Multi-instance** — manage multiple Forgejo/Gitea servers from a single MCP server
- **Multi-user** — switch between user identities within each instance (tokens in config, no env vars)
- **PHAR deployable** — single-file distribution for easy installation
- **No Composer** — pure PHP with Enchilada Framework autoloading

## Quick Start

### 1. Download

```sh
curl -LO https://pacyworld.dev/pacyworld/forgejo-mcp/releases/latest/download/forgejo-mcp.phar
chmod +x forgejo-mcp.phar
```

Or clone and run from source:

```sh
git clone https://pacyworld.dev/pacyworld/forgejo-mcp.git
cd forgejo-mcp
php bin/forgejo-mcp --version
```

### 2. Configure

Copy `config/instances.json.sample` to `config/instances.json` and add your Forgejo instances and tokens:

```json
{
    "default_instance": "pacyworld",
    "default_user": "admin",
    "instances": {
        "pacyworld": {
            "url": "https://pacyworld.dev",
            "description": "Pacy World Forgejo",
            "users": {
                "admin": {
                    "token": "your-personal-access-token",
                    "description": "Admin account"
                },
                "ci": {
                    "token": "ci-bot-token",
                    "description": "CI/CD bot"
                }
            }
        }
    }
}
```

Generate a token at: **Settings → Applications → Access Tokens** on your Forgejo instance.

### 3. Add to your AI assistant

```json
{
    "mcpServers": {
        "forgejo": {
            "command": "php",
            "args": ["/path/to/forgejo-mcp.phar", "--config=/path/to/instances.json"]
        }
    }
}
```

Or if running from source:

```json
{
    "mcpServers": {
        "forgejo": {
            "command": "php",
            "args": ["/path/to/forgejo-mcp/bin/forgejo-mcp"]
        }
    }
}
```

Config file is auto-discovered from these locations (first found wins):
1. `--config=` CLI argument
2. `FORGEJO_MCP_CONFIG` environment variable
3. `config/instances.json` (relative to binary)
4. `~/.config/forgejo-mcp/instances.json`
5. `/usr/local/etc/forgejo-mcp/instances.json`

## Tools

### Repository
`list_my_repos`, `search_repos`, `create_repo`, `fork_repo`, `list_repo_contents`, `get_repo_tree`

### Branch
`list_branches`, `create_branch`, `delete_branch`

### File
`get_file_content`, `create_file`, `update_file`, `delete_file`

### Commit
`list_repo_commits`

### Issue
`list_repo_issues`, `get_issue_by_index`, `create_issue`, `update_issue`, `issue_state_change`

### Labels
`list_repo_labels`, `list_org_labels`, `add_issue_labels`, `remove_issue_labels`

### Milestones
`list_repo_milestones`

### Comments
`list_issue_comments`, `get_issue_comment`, `create_issue_comment`, `edit_issue_comment`, `delete_issue_comment`

### Pull Requests
`list_repo_pull_requests`, `get_pull_request_by_index`, `create_pull_request`, `update_pull_request`, `merge_pull_request`, `list_pull_request_files`, `get_pull_request_diff`

### Reviews
`list_pull_reviews`, `get_pull_review`, `list_pull_review_comments`, `create_pull_review`, `submit_pull_review`, `delete_pull_review`, `dismiss_pull_review`, `create_review_requests`, `delete_review_requests`

### Notifications
`check_notifications`, `get_notification_thread`, `mark_notification_read`, `mark_all_notifications_read`, `list_repo_notifications`, `mark_repo_notifications_read`

### Releases
`list_releases`, `get_release_by_id`, `get_release_by_tag`, `get_latest_release`, `create_release`, `edit_release`, `delete_release`, `delete_release_by_tag`

### Attachments (Issue / Comment / Release)
`list_*_attachments`, `get_*_attachment`, `create_*_attachment`, `edit_*_attachment`, `download_*_attachment`, `delete_*_attachment`

### Workflows & Actions
`dispatch_workflow`, `list_workflow_runs`, `get_workflow_run`, `get_workflow_run_jobs`, `get_workflow_job_logs`

### Action Secrets
`list_repo_action_secrets`, `create_or_update_repo_action_secret`, `delete_repo_action_secret`, `list_org_action_secrets`, `create_or_update_org_action_secret`, `delete_org_action_secret`

### Time Tracking
`list_issue_tracked_times`, `list_repo_tracked_times`, `list_my_tracked_times`, `add_issue_time`, `reset_issue_time`, `delete_issue_time_entry`, `start_issue_stopwatch`, `stop_issue_stopwatch`, `cancel_issue_stopwatch`, `list_my_stopwatches`

### Organizations
`get_org`, `create_org`, `edit_org`, `delete_org`, `list_my_orgs`, `list_user_orgs`, `list_org_members`, `check_org_membership`, `remove_org_member`, `list_org_teams`, `search_org_teams`, `create_org_team`, `add_team_member`, `remove_team_member`, `add_team_repo`, `remove_team_repo`

### Tags
`list_tags`, `get_tag`, `create_tag`, `delete_tag`

### Packages
`list_packages`, `get_package`, `delete_package`, `list_package_files`

### Push Mirrors
`list_push_mirrors`, `add_push_mirror`, `get_push_mirror`, `delete_push_mirror`, `sync_push_mirror`

### Users
`get_my_user_info`, `search_users`

### Instance Management
`forgejo_list_instances`, `forgejo_switch_instance`, `forgejo_switch_user`

### Server
`get_forgejo_mcp_server_version`

## Resources

MCP resource templates expose Forgejo entities as URI-addressable resources using the `forgejo://` scheme.

| URI Template | Description |
|-------------|-------------|
| `forgejo://owner/{owner}` | User or organization profile |
| `forgejo://repo/{owner}/{repo}` | Repository details |
| `forgejo://repo/{owner}/{repo}/commit/{sha}` | Commit details |
| `forgejo://repo/{owner}/{repo}/commit/{sha}/status` | Commit CI/CD status |
| `forgejo://repo/{owner}/{repo}/issue/{index}` | Issue with comments (capped at 30) |
| `forgejo://repo/{owner}/{repo}/{kind}/{index}/comment/{id}` | Single comment |
| `forgejo://repo/{owner}/{repo}/pr/{index}` | Pull request with reviews (capped at 30) |

Resources are additive — every tool remains available. Prefer resources when you have a specific SHA or index; prefer tools for listing or searching.

## Requirements

- PHP 8.4+ with `openssl` and `curl` extensions
- A Forgejo (or Gitea) instance with API access

## Building the PHAR

```sh
php -d phar.readonly=0 bin/build-phar.php
```

## Running Tests

```sh
phpunit
```

## License

BSD 2-Clause — see [LICENSE](LICENSE).

## Credits

Built with the [Enchilada Framework](https://buenapp.org/enchilada) by [The Daniel Morante Company, Inc.](https://pacyworld.dev)
