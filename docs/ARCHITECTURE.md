# ForgejoMCP Architecture

A multi-instance, multi-user MCP server for Forgejo, built as a pure
**composition root** over the three-library Enchilada MCP stack. The app
owns no wire or protocol logic of its own — it wires vendored libraries
together in `bin/forgejo-mcp`.

```
┌─────────────────────────────────────────────────────────────┐
│ ForgejoMCP (this repo)                                      │
│                                                             │
│  classes/Forgejo/                                           │
│    InstanceManager  configs → lazy cached Clients           │
│      (subclass of EnchiladaMCP\InstanceRegistry)            │
│    Client           Forgejo REST API calls                  │
│                                                             │
│  config/  system/  bin/  tests/                             │
└──────┬───────────────────────────┬──────────────────────────┘
       │ wires primitives          │ wires primitives
┌──────▼───────┐           ┌───────▼────────┐
│ libraries/   │           │ libraries/     │
│ EnchiladaMCP │           │ Enchilada/     │
│ (Extras)     │           │ Tortilla       │
│ protocol core│           │ wire transports│
└──────────────┘           └───────┬────────┘
                                   │ waits over
                           ┌───────▼────────┐
                           │ libraries/     │
                           │ EnchiladaHTTP/ │
                           │ EnchiladaMulti~│  (Extras, eponymous dirs)
                           └────────────────┘
```

## Vendored libraries

All libraries are vendored byte-identical from their upstream repos
(no Composer, no symlinks). Re-vendor from canonical source; never copy
between consumers.

| Path | Upstream | Contents |
|---|---|---|
| `libraries/EnchiladaMCP/` | Enchilada/Extras `MCP/` | Protocol core: `McpServer`, tool registry/result/resource, `ToolWarningInterface`, `InstanceRegistry`, `Logger`. Transport-agnostic, `array → array`. |
| `libraries/Enchilada/Tortilla/` | Enchilada/Tortilla `src/` | `StdioTransport`, `HttpSseTransport`, `EmbeddedHttpTransport`, `EventLoop` port + `ComalEventLoop` adapter, `HttpClient` (loop-aware wait over the curl_multi engine), `RequestEra`. |
| `libraries/EnchiladaHTTP/` | Enchilada/Extras `HTTP/` | `EnchiladaHTTP.class.php`, eponymous dir. Blocking client (legacy global class). |
| `libraries/EnchiladaMultiHTTP/` | Enchilada/Extras `HTTP/` | `EnchiladaMultiHTTP.class.php`, eponymous dir. curl_multi engine. |
| `libraries/Enchilada/Comal/` | Enchilada/Comal | Reactor (`ReactorFactory` picks kqueue/EV/select) + `Async` fibers. Enables reactor I/O mode. |

Legacy global classes live at **eponymous paths** so the framework
autoloader resolves them natively; there are deliberately no
`class_exists`/`require_once` guards anywhere.

## Composition root (`bin/forgejo-mcp`)

The only place that knows both the protocol server and the transport:

1. `$server = new McpServer(APPLICATION_NAME, APPLICATION_VERSION)`
2. `$loop = ComalEventLoop::create()` — loop when Comal is present and
   platform supports it, `null` otherwise.
3. `$manager->setHttpTransport($loop, $server->tick(...))` — every
   `Forgejo\Client` API call runs through `Tortilla\HttpClient`: with a
   loop the dispatch fiber parks and the reactor drives the engine (a
   `ping` received from the host is answered mid-call); without one the
   client polls in place and invokes the progress callable each iteration
   (so blocking-mode hosts still see `notifications/progress` liveness).
4. `$transport = new StdioTransport($server->handleRequest(...), $server->tick(...))`
   — primitives, never the `McpServer` object.
5. `$transport->setLoop($loop)` opts into reactor stdio I/O when available
   (`--io-mode=` / `FORGEJO_MCP_IO_MODE` can force reactor|blocking).
6. `$server->setNotifier($transport->sendNotification(...))` for
   server-initiated notifications.

## I/O modes

`auto` (default) = reactor when ComalEventLoop is available, blocking
otherwise (Windows default). Blocking mode is a supported configuration,
not a fallback hack — the liveness contract for modern MCP hosts
(`notifications/progress` since 2026-07-28 removed `ping`) is served by
`HttpClient`'s poll loop through the same progress callable.

## Regression gates

- `phpunit` — unit suite (includes the vendored-library layout checks).
- `engineering-docs/enchilada-extras/mcp-liveness-suite` — the stacked
  protocol gate; run it against this repo's vendored copies:
  `php transport-liveness.php --lib=path/to/ForgejoMCP/libraries`
  (reactor mode, since Comal is present).
- Phar smoke after `bin/build-phar.php` (initialize, tools/list,
  tools/call offline, ping, clean stderr, clean EOF).
