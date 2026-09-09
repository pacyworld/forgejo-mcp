# Setup Guide

## Installation

### Option A: PHAR (recommended)

```sh
curl -LO https://pacyworld.dev/pacyworld/forgejo-mcp/releases/latest/download/forgejo-mcp.phar
chmod +x forgejo-mcp.phar
php forgejo-mcp.phar --version
```

### Option B: From source

```sh
git clone https://pacyworld.dev/pacyworld/forgejo-mcp.git
cd forgejo-mcp
php bin/forgejo-mcp --version
```

### Option C: FreeBSD package (future)

```sh
pkg install forgejo-mcp
```

## Configuration

### Create instances.json

```sh
cp config/instances.json.sample config/instances.json
```

Edit `config/instances.json` with your Forgejo instances and access tokens.

### Multi-instance layout

Each top-level key under `instances` is a Forgejo server. Each instance has a `users` map containing named identities with their own tokens:

```json
{
    "default_instance": "production",
    "default_user": "admin",
    "instances": {
        "production": {
            "url": "https://forge.example.com",
            "description": "Production Forgejo",
            "verify_ssl": true,
            "timeout": 30,
            "users": {
                "admin": {
                    "token": "admin-access-token",
                    "description": "Admin user"
                },
                "deploy": {
                    "token": "deploy-bot-token",
                    "description": "Deploy bot (limited permissions)"
                }
            }
        },
        "staging": {
            "url": "https://staging.forge.example.com",
            "users": {
                "dev": {
                    "token": "dev-token",
                    "description": "Developer account"
                }
            }
        }
    }
}
```

### Configuration file locations

The server searches these paths in order (first found wins):

1. `--config=/path/to/instances.json` CLI argument
2. `FORGEJO_MCP_CONFIG` environment variable
3. `config/instances.json` relative to the binary/source
4. `~/.config/forgejo-mcp/instances.json`
5. `/usr/local/etc/forgejo-mcp/instances.json`

### Generating access tokens

1. Log into your Forgejo instance
2. Go to **Settings → Applications → Access Tokens**
3. Create a token with the scopes you need:
   - `read:repository` + `write:repository` for repo operations
   - `read:issue` + `write:issue` for issue/PR operations
   - `read:organization` + `write:organization` for org operations
   - `read:user` for user info
   - Or simply select **all** scopes for full access

## IDE Configuration

### Windsurf / Cursor / Claude Desktop

Add to your MCP configuration:

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

### VS Code (with MCP extension)

Same format in your VS Code MCP settings.

## Selecting instances and users

Every tool call takes `instance` and `user` parameters (both required) — there is no global "active" context to switch. Use `list_forgejo_instances` to see the configured instances and their users.

## Logging

Durable diagnostic logging is available via environment variables or CLI flags. This is the primary tool for pinpointing protocol, transport, or API problems — stdout is reserved for the JSON-RPC protocol and most MCP hosts capture stderr only transiently.

| Environment variable    | CLI flag            | Default | Purpose                                            |
|-------------------------|---------------------|---------|----------------------------------------------------|
| `FORGEJO_MCP_LOG`       | `--log=PATH`        | off     | Log file path (enables durable file logging)       |
| `FORGEJO_MCP_LOG_LEVEL` | `--log-level=LEVEL` | `debug` | Minimum level: `debug`, `info`, or `error`         |
| `FORGEJO_MCP_LOG_STDERR`| —                   | off     | Mirror log lines to stderr (`1` enables)           |
| `FORGEJO_MCP_IO_MODE`   | `--io-mode=MODE`    | `auto`  | Transport I/O: `auto`, `reactor`, or `blocking`    |

### Transport I/O mode

`auto` is correct for every supported platform and should not normally be
changed:

- **POSIX (FreeBSD, Linux, macOS)** — `reactor`. stdin is registered with
  the [Comal](https://git.morante.net/Enchilada/Comal) event reactor and
  each request runs in a Fiber, so a tool that yields keeps the protocol
  channel live: `ping` is answered and `notifications/progress` keeps
  flowing *while the call is still running*. Install `php84-pecl-ev` to
  get libev/kqueue multiplexing instead of the `stream_select()`
  fallback.
- **Windows** — `blocking`. An anonymous stdin pipe cannot be polled from
  PHP there: `stream_select()` returns immediately always claiming the
  pipe is readable (php-src #64770 / GH-16889), and
  `stream_set_blocking($pipe, false)` is a no-op, so the read that
  follows blocks for an unbounded time. Requests are handled
  synchronously; progress notifications still flow from tools' own yield
  points, but mid-call pings cannot be answered.

Forcing `reactor` on Windows is supported only for diagnostics, and logs
a note saying so.

Example MCP host configuration with logging enabled:

```json
{
    "mcpServers": {
        "forgejo": {
            "command": "php",
            "args": ["/path/to/forgejo-mcp.phar", "--config=/path/to/instances.json"],
            "env": {
                "FORGEJO_MCP_LOG": "/home/admin/.config/forgejo-mcp/forgejo-mcp.log",
                "FORGEJO_MCP_LOG_LEVEL": "debug",
                "FORGEJO_MCP_LOG_STDERR": "0"
            }
        }
    }
}
```

What is logged at `debug` level:

- **Lifecycle** — startup (version, pid, PHP version), config file used, tool registration, shutdown.
- **Transport** — every inbound/outbound JSON-RPC line with byte length, SHA-256 digest, and a 200-character preview; invalid JSON; stdout write failures; EOF.
- **Protocol** — every request with method, id, tool name, per-argument digests, duration in ms, and outcome (OK / tool error / exception).
- **HTTP** — every Forgejo API call with method, URL, body digest, status code, and duration; errors include the failure reason.

Privacy: secrets and tokens are **never** written to the log. Request/response bodies and string arguments are reduced to `len=N sha256=...` digests (byte-exactness can still be verified by comparing digests), Authorization headers are never logged, and `token=`/`access_token=` URL parameters are redacted.

## Troubleshooting

### "No configuration file found"
Ensure `instances.json` exists in one of the searched paths. Use `--config=` to specify explicitly.

### "Authentication failed (401)"
Your access token is invalid or expired. Generate a new one from the Forgejo web UI.

### "Access denied (403)"
The token lacks the required scope for this operation. Check your token permissions.

### Connection timeouts
Increase the `timeout` value in your instance configuration (default: 30 seconds).
