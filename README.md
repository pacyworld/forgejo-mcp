# Forgejo MCP Server

A PHP Model Context Protocol server for Forgejo instances, built on the [Enchilada Framework](https://buenapp.org/enchilada). Supports multiple instances and multiple users per instance.

## Features

- **33 MCP tools** covering repositories, issues, pull requests, releases, workflows, organizations, and more — one tool per resource family, selected by an `action` enum. The whole catalog costs ~5.8k tokens in client context (down from ~15.5k in v1.x)
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

Since v2.0.0 most resource families are **one tool per family**, selected by an `action` enum; each tool description lists its actions and their required parameters (enforced server-side). High-traffic tools stay standalone.

**Standalone:** `list_repo_issues`, `get_issue_by_index`, `create_issue`, `create_pull_request`, `merge_pull_request`, `get_file_content`, `search_repos`, `search_users`, `list_repo_commits`, `list_repo_milestones`, `get_my_user_info`, `list_forgejo_instances`, `get_forgejo_mcp_server_version`, `get_forgejo_version`

**Consolidated (action-dispatched):**

| Tool | Actions |
|------|---------|
| `attachment` (`target`: `issue`/`comment`/`release`) | `list` `get` `download` `create` `edit` `delete` |
| `branch` | `list` `create` `delete` |
| `repo` | `list_mine` `create` `fork` |
| `file` | `create` `update` `delete` `list_contents` `tree` |
| `issue` | `update` `state_change` |
| `issue_comment` | `list` `get` `create` `edit` `delete` |
| `label` | `list_repo` `list_org` `add` `remove` |
| `pull_request` | `list` `get` `update` `files` `diff` |
| `pull_review` | `list` `get` `comments` `create` `submit` `delete` `dismiss` `request_reviewers` `delete_requests` |
| `release` | `list` `get_by_id` `get_by_tag` `latest` `create` `edit` `delete` `delete_by_tag` |
| `notification` | `check` `list_repo` `get_thread` `mark_read` `mark_all_read` `mark_repo_read` |
| `org` | `get` `create` `edit` `delete` `list_mine` `list_user` `list_members` `check_membership` `remove_member` |
| `team` | `list` `search` `create` `add_member` `remove_member` `add_repo` `remove_repo` |
| `tag` | `list` `get` `create` `delete` |
| `package` | `list` `get` `list_files` `delete` |
| `push_mirror` | `list` `add` `get` `delete` `sync` |
| `time_tracking` | `list_issue` `list_repo` `list_mine` `add` `reset` `delete_entry` `start_stopwatch` `stop_stopwatch` `cancel_stopwatch` `list_stopwatches` |
| `workflow` | `dispatch` `list_runs` `get_run` `list_jobs` `job_logs` `job_logs_by_id` `download_run_logs` |
| `action_secret` | `list_repo` `set_repo` `delete_repo` `list_org` `set_org` `delete_org` |

Calling a v1.x tool name returns an error naming the replacement tool and action. Full mapping: [docs/TOOLS.md](docs/TOOLS.md).

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
