# Changelog

## v1.0.1 — 2026-05-31

### Bug Fixes
- **list_workflow_runs**: Return results in descending order (newest first). The Forgejo API returns workflow runs in ascending order by default, which made the most recent runs appear last.

## v1.0.0 — 2026-05-30

Initial release.

### Features
- 125+ MCP tools with full Forgejo API coverage
- 7 resource templates (`forgejo://` URI scheme)
- Multi-instance configuration (multiple Forgejo servers)
- Multi-user per instance (tokens in config file)
- PHAR archive distribution
- CI/CD workflows (lint, test, release)

### Tool Categories
- **Repository**: create (personal + org), fork, search, list contents, git tree
- **Branch**: create, delete, list
- **File**: read, create, update, delete (with proper DELETE body support)
- **Commit**: list with branch/path filtering
- **Issue**: full CRUD, state changes, labels, milestones
- **Comment**: create, edit, delete, list
- **Pull Request**: create, update, merge, diff, file list
- **Review**: create, submit, dismiss, delete, review requests
- **Notification**: check, mark read (individual, repo, all)
- **Release**: full CRUD, tag-based lookup, latest release
- **Attachments**: upload (multipart), rename, delete for issues, comments, and releases
- **Workflow**: dispatch, list/get runs, list jobs, download logs
- **Action Secrets**: list, create/update, delete (repo + org scope)
- **Time Tracking**: add time, stopwatch start/stop/cancel, tracked times
- **Organization**: full CRUD, membership, teams, team members, team repos
- **Tag**: create, list, get, delete
- **Package**: list, get, delete, list files
- **Push Mirror**: list, add, get, delete, sync
- **User**: profile, search
- **Instance**: list, switch instance, switch user

### Resources
- `forgejo://owner/{owner}` — user/org profile
- `forgejo://repo/{owner}/{repo}` — repository
- `forgejo://repo/{owner}/{repo}/commit/{sha}` — commit
- `forgejo://repo/{owner}/{repo}/commit/{sha}/status` — CI status
- `forgejo://repo/{owner}/{repo}/issue/{index}` — issue + comments
- `forgejo://repo/{owner}/{repo}/{kind}/{index}/comment/{id}` — comment
- `forgejo://repo/{owner}/{repo}/pr/{index}` — PR + reviews

### Infrastructure
- Enchilada Framework 3.0 with MCP resource support (new `McpResource` attribute)
- EnchiladaHTTP client with `getRaw()` for plain-text endpoints and `uploadFile()` for multipart
- PHPUnit test suite (26 tests, 51 assertions)
- Forgejo Actions CI + release workflows
- PHAR builder
