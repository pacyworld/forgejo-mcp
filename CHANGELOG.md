# Changelog

## Unreleased

### Fixed
- **Timeouts and transport failures no longer silently succeed.** Previously, when curl failed without an HTTP status (timeout, DNS failure, connection refused), the API client returned an empty result `[]` as if the call succeeded — e.g. a PR merge POST that timed out after 30s was reported to the agent as successful. Now such failures are surfaced with the curl error and logged. **Timeouts are returned as a normal (non-error) tool result** carrying an explanatory message: the timeout is a known issue with long-running server-side operations (e.g. merges on large repositories), the server may still have completed the operation, and state should be verified before retrying (the long-term fix is async request handling). Other transport failures (DNS, connection refused) remain errors. EnchiladaHTTP gained `getLastCurlErrno()`/`getLastCurlError()` accessors; EnchiladaMCP gained `ToolWarningInterface` — tool exceptions implementing it are returned as non-error results.
- `resources/list` is now handled (returns registered static resources). MCP clients such as rmcp (Devin CLI) call it after initialize when the server advertises the resources capability; it previously returned -32601 Method not found.
- Tool-level failures (e.g. "Missing required argument") now include the tool's error text in the log line instead of just "tool reported failure".
- **Unknown tool names no longer kill the client connection.** `tools/call` for an unregistered tool previously returned a protocol-level `-32602` error, which some MCP clients (Windsurf/rmcp) surface as "Failed to connect to MCP server" before tearing down and restarting the server process. It now returns a tool-level error result (`isError: true`) with the closest matching tool names (token-overlap ranking handles hallucinated vendor prefixes like `forgejo_repo_search` → `search_repos`), letting the agent self-correct without a restart.
- Timeout warning message is now terse: "Request timed out. This is a known issue on large repositories; and may still be processing or already completed. Re-trying is not needed." (URL, duration, and curl detail remain in the log file.)

### Added
- `merge_pull_request` gained an optional `timeout` parameter (seconds, default 90) so merges on large repositories aren't cut off by the instance default (typically 30s). Backed by a new optional per-request timeout on `Client::post()`.
- **Diagnostic logging** across all layers, enabled via `FORGEJO_MCP_LOG=/path/to/file` (or `--log=`), with `FORGEJO_MCP_LOG_LEVEL` (`debug`|`info`|`error`, default `debug`) and `FORGEJO_MCP_LOG_STDERR` (default on). Stdout remains protocol-only.
  - Transport: every inbound/outbound JSON-RPC line with byte length, SHA-256 digest, and 200-char preview; invalid JSON, write failures, EOF.
  - Protocol (`McpServer`): every request with method, id, tool name, per-argument digests, duration, and outcome.
  - HTTP (`Client`): every API call with method, URL, body digest, status code, and duration; errors logged at `error` level.
  - `InstanceManager` logs client creation per instance:user.
- Secret-safe by design: string arguments and request/response bodies are logged as `len=N sha256=...` digests only; Authorization headers are never logged; `token=`/`access_token=` URL parameters are redacted. Digests allow byte-exactness verification without exposing secret material.
- New `EnchiladaMCP\Logger` (callable, level-filtered, failure-proof file/stderr logger), vendored from Enchilada Extras.

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
