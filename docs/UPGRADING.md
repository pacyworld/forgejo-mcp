# UPGRADING — Forgejo MCP Server

Developer/agent-facing. Read this **before** touching `libraries/`,
`bin/`, or any network I/O. Architecture details: `docs/ARCHITECTURE.md`.

## 1. The transport split (September 2026) changed the rules

Three independent libraries, vendored byte-identical from canonical
upstream **master** (pull first, then copy — never from another
consumer's tree):

| Vendored dir | Source | What it is |
|---|---|---|
| `libraries/EnchiladaMCP/` | `Enchilada/Extras` MCP/ | Protocol core. No I/O, no loop, no framework deps. |
| `libraries/Enchilada/Tortilla/` | `Enchilada/Tortilla` src/ | Wire transports, `EventLoop` port, `ComalEventLoop`, `HttpClient`, `RequestEra`. |
| `libraries/EnchiladaHTTP/` | Extras HTTP/ | Blocking engine (legacy global class). **Frozen.** |
| `libraries/EnchiladaMultiHTTP/` | Extras HTTP/ | curl_multi engine (legacy global class). **Frozen.** |
| `libraries/Enchilada/Comal/` | `Enchilada/Comal` | Reactor (kqueue/ev). Presence opts stdio into reactor mode. |

## 2. Non-negotiables

1. **Eponymous vendoring, no guards.** Legacy global classes live in
   `libraries/<Class>/<Class>.class.php`. Never add
   `class_exists`/`require_once` guards: the framework autoloader is
   golden; a miss means wrong placement or namespace — fix that.
2. **Only `bin/forgejo-mcp` knows both sides.** Transports take
   primitives (`handler`, `progress` callables, version lists), never
   `McpServer`.
3. **The event loop is opt-in and app-owned:**
   `ComalEventLoop::create()` → `$transport->setLoop($loop)`.
4. **Blocking mode must still breathe.** `ping` is gone in MCP revision
   2026-07-28; `notifications/progress` is the only in-call liveness.
   Any bounded-poll wait must invoke its injected progress callable on
   every slice.
5. **No blocking network I/O inside tools.** All Forgejo API traffic
   goes through `Forgejo\Client` → `Tortilla\HttpClient`. New endpoints
   extend the Client; nothing in `tools/` may instantiate raw HTTP.
   - `EventLoop` has no writability watcher: any future socket client
     must probe writability between parked slices (see
     `SocketImapClient` in mail-mcp for the pattern; mail-mcp#27
     review).
   - `EnchiladaHTTP`/`EnchiladaMultiHTTP` are frozen: no MCP hooks, no
     behavior changes.

## 3. This server's wiring

- `bin/forgejo-mcp` is the composition root: `$loop =
  ComalEventLoop::create()` → `StdioTransport` primitives →
  `$manager->setHttpTransport($loop, $server->tick(...))` —
  every `Forgejo\Client` parks its dispatch fiber on the loop during
  network waits; pings are answered mid-call in reactor mode.
- `--io-mode=` / `FORGEJO_MCP_IO_MODE` (auto|reactor|blocking) selects
  the stdio I/O strategy.
- **Releases**: `release.yml` does NOT stamp the version from the tag;
  bump `APPLICATION_VERSION` in `system/app.conf.php` manually before
  tagging.

## 4. Regression gates (before every commit)

- `phpunit` — green (suite includes vendored-layout checks).
- Liveness suite:
  `php ~/Documents/Projects/engineering-docs/enchilada-extras/mcp-liveness-suite/transport-liveness.php --lib=libraries`
  (reactor mode) — 9/9.
- Phar build + smoke (init/version/tools/ping/stderr/EOF).

## 5. Canonical references

- `engineering-docs/enchilada-extras/PLAN-TRANSPORT-SPLIT.md`
- `engineering-docs/enchilada-extras/DESIGN-RATIONALE-TRANSPORT-SPLIT.md`
- `engineering-docs/enchilada-extras/mcp-liveness-suite/README.md`
- `Enchilada/Extras` README (vendoring rules), `Enchilada/Tortilla` README
