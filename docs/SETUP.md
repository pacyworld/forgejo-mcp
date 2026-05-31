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

## Switching contexts at runtime

Use the instance management tools without restarting:

- `forgejo_list_instances` — see all configured instances and current default
- `forgejo_switch_instance` — change the active Forgejo server
- `forgejo_switch_user` — change the active user within the current instance

All other tools accept optional `instance` and `user` parameters to override the default for a single call.

## Troubleshooting

### "No configuration file found"
Ensure `instances.json` exists in one of the searched paths. Use `--config=` to specify explicitly.

### "Authentication failed (401)"
Your access token is invalid or expired. Generate a new one from the Forgejo web UI.

### "Access denied (403)"
The token lacks the required scope for this operation. Check your token permissions.

### Connection timeouts
Increase the `timeout` value in your instance configuration (default: 30 seconds).
