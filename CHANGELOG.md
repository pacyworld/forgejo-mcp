# Changelog

## v1.1.0 — 2026-08-07

### Upgrade Notes
- **Install the PHP zip extension (`pecl-zip`) on the host running this MCP server** — recommended when upgrading. `download_action_run_logs` extracts the server's per-run log ZIP inline only when `ext-zip` is available; without it the tool still works but returns the archive base64-encoded instead of per-job log text. On FreeBSD: `pkg install php84-zip` (adjust for your PHP version; on some versions the package is `php84-pecl-zip`). No other changes are required — the feature degrades gracefully.
- No configuration changes needed; existing `instances.json` files work unchanged.

### Added
- **Forgejo 16 action log download API** support (upstream PR forgejo/forgejo#12666):
  - `get_action_job_logs` — plaintext logs of a single job by job ID, with optional 1-based `attempt` (latest when omitted). Works for public and private repositories via API token.
  - `download_action_run_logs` — logs for every job in a run (server-side ZIP). Entries are extracted inline when the PHP zip extension is available on the MCP host, otherwise the archive is returned base64-encoded. Jobs that have not started or whose logs expired are flagged `missing`.
  - Both tools check the connected server version first and return a structured error (`detected_version`, `required_version`, `workaround`) on servers older than Forgejo 16.0 instead of calling endpoints that do not exist.
- `list_workflow_run_jobs` — list jobs of a workflow run (id, name, status, attempt). Provides the job IDs required by `get_action_job_logs`.
- `get_forgejo_version` — report the connected instance's Forgejo version and which version-gated API features it supports.
- `Client::getServerVersion()`, `Client::versionAtLeast()`, `Client::supportsActionLogsApi()` — cached server-version detection via `GET /api/v1/version`.

### Changed
- `get_workflow_job_logs` now uses the Forgejo 16+ REST API when the connected server supports it (job index is resolved to a job ID via the run jobs listing, enabling API-token log access for private repositories). On older servers it keeps the legacy web-route behavior; its 404 guidance now points at the Forgejo 16 upgrade path.

### Fixed
- docs/TOOLS.md documented a non-existent `get_workflow_run_jobs` tool; replaced with the actual `list_workflow_run_jobs`.

## v1.0.2 — 2026-06-30

### Added
- MCP tool annotations (`readOnlyHint`) on all read-only tools (list/get/search/check/download operations), vendored from the updated EnchiladaMCP library. Lets MCP clients (e.g. Windsurf Ask mode) distinguish safe read-only calls from tools that mutate Forgejo state.

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
