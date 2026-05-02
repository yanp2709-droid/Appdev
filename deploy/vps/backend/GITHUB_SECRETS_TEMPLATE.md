# GitHub Secrets Template

Add these in your GitHub repository:

- `Settings`
- `Environments`
- `production`

Then add the following under `Environment secrets`.

## Secrets

### `VPS_HOST`

```text
your-server-ip-or-domain
```

Example:

```text
203.0.113.25
```

### `VPS_PORT`

```text
22
```

Leave this as `22` unless your SSH server uses a different port.

### `VPS_USERNAME`

```text
your-ssh-user
```

Example:

```text
ubuntu
```

### `VPS_SSH_KEY`

Paste your full private SSH key:

```text
-----BEGIN OPENSSH PRIVATE KEY-----
your-private-key-content
-----END OPENSSH PRIVATE KEY-----
```

### `GHCR_USERNAME`

```text
your-github-username
```

### `GHCR_TOKEN`

Paste a GitHub personal access token that can read packages from GitHub Container Registry.

Recommended permission:

```text
read:packages
```

If you also want to push packages manually from that same token, include:

```text
write:packages
```

### `LARAVEL_ENV_FILE`

Paste the full production Laravel `.env` content as one multiline secret.

Starter template:

```dotenv
APP_NAME=Laravel
APP_ENV=production
APP_KEY=base64:replace-with-your-production-key
APP_DEBUG=false
APP_URL=https://your-domain.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/storage/db/database.sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

CACHE_STORE=file

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

Generate a real Laravel app key with:

```powershell
cd "C:\Users\tonzk\Downloads\Appdev-recovered-project\Appdev-recovered-project\Backend\laravel-12.x"
php artisan key:generate --show
```

## Optional Environment Variables

Add these under `Environment variables` in the same `production` environment.

### `VPS_DEPLOY_PATH`

```text
/opt/techquiz/backend
```

### `VPS_APP_PORT`

```text
8001
```

### `FLUTTER_API_BASE_URL`

```text
https://your-domain.com/api
```

## Related Files

- Use [`.env.example`](C:/Users/tonzk/Downloads/Appdev-recovered-project/Appdev-recovered-project/deploy/vps/backend/.env.example:1) as the backend environment reference.
- The deploy workflow is in [`ci-cd.yml`](C:/Users/tonzk/Downloads/Appdev-recovered-project/Appdev-recovered-project/.github/workflows/ci-cd.yml:1).
