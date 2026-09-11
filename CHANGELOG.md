# Changelog

## [Unreleased]

### Fixed
- `sync_push_mirror` failed with HTTP 405: it posted to `repos/{owner}/{repo}/push_mirrors/sync`, which is not a Forgejo route. It now posts to the documented `repos/{owner}/{repo}/push_mirrors-sync` endpoint.

## [1.3.0] - 2026-09-10

### Upgrade Notes
- **Vendored library layout changed.** The wire transports (`StdioTransport`, `HttpSseTransport`, `EmbeddedHttpTransport`) moved out of `libraries/EnchiladaMCP/` into the new standalone `Enchilada/Tortilla` repository, vendored as `libraries/Enchilada/Tortilla/`; the HTTP clients moved from `libraries/EnchiladaHTTP/` to eponymous directories (`libraries/EnchiladaHTTP/` + `libraries/EnchiladaMultiHTTP/`), the layout the framework autoloader resolves natively for legacy global classes. `Liveness`/`LivenessSink` are gone: their fiber-park machinery is absorbed by `Enchilada\Tortilla\HttpClient`, and their notification push is a plain `setNotifier()` callable.
- **New vendored dependency: `Enchilada\Comal`** (the event reactor) in `libraries/Enchilada/Comal/`. The stdio transport uses it on POSIX via Tortilla's `ComalEventLoop` adapter. Installing `php84-pecl-ev` is recommended — Comal then multiplexes with libev/kqueue instead of `stream_select()`.
- **New: `--io-mode=` / `FORGEJO_MCP_IO_MODE`** (`auto`|`reactor`|`blocking`, default `auto`). `auto` selects the reactor on POSIX and blocking reads on Windows; override only when diagnosing transport behaviour.

### Fixed
- **The server no longer goes silent during long tool calls, which agent hosts interpret as a dead connection and close.** The stdio transport was a single `stream_select()` loop, so for the whole duration of a call it could not answer `ping` and never emitted `notifications/progress` — even though hosts send a `progressToken` asking for exactly that. Two pieces fix it: the transport now dispatches each request inside a Fiber on an injected event loop (on POSIX), and `Forgejo\Client` talks to Forgejo through `Enchilada\Tortilla\HttpClient` over `curl_multi` instead of a blocking `curl_exec()`. During a Forgejo API wait the fiber parks and the transport's loop keeps running — pings are answered mid-call and progress notifications keep flowing. Verified on FreeBSD with a ping answered **0.01s into an 8s call**.
- **Windows startup/stall fixes.** On Windows an anonymous stdin pipe cannot be polled from PHP: `stream_select()` returns in 0ms always reporting the pipe readable, and `stream_set_blocking($pipe, false)` is a no-op so the following read blocks unbounded. The transport now uses blocking reads there, and progress notifications still flow during Forgejo API waits: `HttpClient`'s blocking poll loop drives the curl state machine in place and invokes the server's progress emitter on every iteration. Verified on Windows 11 / PHP 8.4.25.
- **A stray PHP warning can no longer corrupt the protocol channel.** Several `php.ini` defaults route `display_errors` to STDOUT, which is the JSON-RPC channel; the transport now pins error display to stderr.
- **A closed OAuth/callback stream registered with `addStream()` no longer kills the server.** PHP 8 throws a `TypeError` for closed streams passed to `stream_select()` (which `@` does not suppress); such streams are now validated and unregistered.
- Partial `fwrite()` to stdout is looped to completion instead of silently truncating a response.

### Added
- `tests/transport-e2e.php` — drives the real `bin/forgejo-mcp` over pipes and asserts the host-facing transport contract (prompt `initialize`, protocol-only stdout, full 134-tool `tools/list`, idle responsiveness, clean EOF shutdown) in both I/O modes.
- `tests/transport-liveness.php` — asserts pings are answered and progress flows *during* a slow call, and pins the known limitation that a non-yielding tool still starves the channel.

## v1.2.0 — 2026-08-31

### Upgrade Notes
- **`forgejo_list_instances` was renamed to `list_forgejo_instances`.** Update any saved prompts or scripts referencing the old name. Calls to the old name return an unknown-tool error that suggests the new name.
- **New: durable diagnostic logging.** Set `FORGEJO_MCP_LOG=/path/to/log` in your MCP host configuration to enable it (see docs/SETUP.md). Recommended when diagnosing tool-call issues.
- No configuration format changes; existing `instances.json` files work unchanged.

### Fixed
- **Timeouts and transport failures no longer silently succeed.** Previously, when curl failed without an HTTP status (timeout, DNS failure, connection refused), the API client returned an empty result `[]` as if the call succeeded — e.g. a PR merge POST that timed out after 30s was reported to the agent as successful. Now such failures are surfaced with the curl error and logged. **Timeouts are returned as a normal (non-error) tool result** carrying an explanatory message: the timeout is a known issue with long-running server-side operations (e.g. merges on large repositories), the server may still have completed the operation, and state should be verified before retrying (the long-term fix is async request handling). Other transport failures (DNS, connection refused) remain errors. EnchiladaHTTP gained `getLastCurlErrno()`/`getLastCurlError()` accessors; EnchiladaMCP gained `ToolWarningInterface` — tool exceptions implementing it are returned as non-error results.
- `resources/list` is now handled (returns registered static resources). MCP clients such as rmcp (Devin CLI) call it after initialize when the server advertises the resources capability; it previously returned -32601 Method not found.
- Tool-level failures (e.g. "Missing required argument") now include the tool's error text in the log line instead of just "tool reported failure".
- **Unknown tool names no longer kill the client connection.** `tools/call` for an unregistered tool previously returned a protocol-level `-32602` error, which some MCP clients (Windsurf/rmcp) surface as "Failed to connect to MCP server" before tearing down and restarting the server process. It now returns a tool-level error result (`isError: true`) with the closest matching tool names (token-overlap ranking handles hallucinated vendor prefixes like `forgejo_repo_search` → `search_repos`), letting the agent self-correct without a restart.
- Timeout warning message is now terse: "Request timed out. The operation may still be processing or may have already completed on the server. Do not retry unless an error was returned." (URL, duration, and curl detail remain in the log file.)

### Changed
- **`merge_pull_request` verifies server state on timeout instead of returning a vague warning.** Forgejo ≥ 14 continues a merge server-side after the client disconnects (upstream forgejo/forgejo#11850, `context.WithoutCancel` bounded by `Git.Timeout.Default`), so a client-side timeout is not a failure. On timeout the tool now does a cheap follow-up GET and returns grounded status: `merged` (with the merge commit SHA) or `in_progress` (PR state/mergeable), always with explicit "do not retry" instructions. Only if the status check itself fails does it fall back to the plain timeout message.
- **`forgejo_list_instances` renamed to `list_forgejo_instances`.** It was the only tool with a leading vendor prefix, which taught LLM clients a false naming pattern (hallucinated calls like `forgejo_repo_search`). The new name matches the mid-name style of `get_forgejo_version` / `get_forgejo_mcp_server_version`; MCP clients already prevent cross-server collisions with their own prefixes. Calls to the old name now return an unknown-tool error with the new name as the guaranteed top suggestion (via `renamedFrom` metadata, EnchiladaMCP PR #23).

### Fixed (docs)
- SETUP.md / TOOLS.md / README.md documented phantom `forgejo_switch_instance` and `forgejo_switch_user` tools that no longer exist (instance and user are required parameters on every call); removed.

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
