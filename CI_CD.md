# CI/CD Pipeline

This repository now includes a GitHub Actions pipeline at `.github/workflows/ci-cd.yml`.

## What It Does

CI runs on pushes and pull requests to `main`, `master`, `develop`, and `recovered-project`:

- Laravel backend:
  - installs Composer dependencies
  - builds Vite assets
  - runs `php artisan test`
- Flutter app:
  - runs `flutter analyze --no-fatal-infos`
  - runs `flutter test`

Delivery runs on `main`, `recovered-project`, version tags, and manual dispatch:

- publishes the backend Docker image to GitHub Container Registry:
  - `ghcr.io/<owner>/<repo>-backend`
- deploys the backend automatically to a Linux VPS over SSH on `main` or manual dispatch
- builds and uploads a Flutter Android APK artifact

## Backend Auto-Deploy

The deploy job uses the production stack in `deploy/vps/backend` and assumes your server already has:

- Docker
- Docker Compose
- SSH access from GitHub Actions

The job uploads:

- `deploy/vps/backend/docker-compose.yml`
- `deploy/vps/backend/deploy.sh`
- your Laravel production `.env`

Then it:

- logs in to `ghcr.io`
- pulls the newest backend image
- restarts the backend container
- runs Laravel migrations
- refreshes Laravel caches

If the required deployment secrets are not configured yet, the deploy job now skips itself cleanly instead of failing the whole workflow.

## Required GitHub Secrets

Add these in `Settings > Secrets and variables > Actions`:

- `VPS_HOST`
  - your server IP or hostname
- `VPS_PORT`
  - optional, defaults to `22`
- `VPS_USERNAME`
  - SSH user on the server
- `VPS_SSH_KEY`
  - private SSH key GitHub Actions should use
- `GHCR_USERNAME`
  - GitHub username that can pull the backend package
- `GHCR_TOKEN`
  - GitHub token or PAT with `read:packages`
- `LARAVEL_ENV_FILE`
  - the full production Laravel `.env` content as a multiline secret

Use `deploy/vps/backend/.env.example` as the template for `LARAVEL_ENV_FILE`.
Use `deploy/vps/backend/GITHUB_SECRETS_TEMPLATE.md` if you want copy-ready values and examples.

## Optional GitHub Variables

- `FLUTTER_API_BASE_URL`
  - build-time API base URL for the mobile app
- `VPS_DEPLOY_PATH`
  - server folder for the deployment stack
  - defaults to `/opt/techquiz/backend`
- `VPS_APP_PORT`
  - public backend port on the VPS
  - defaults to `8001`

## Notes

- The backend production image now starts Nginx and PHP-FPM in one container so the VPS deploy only needs a single service plus persistent Laravel storage.
- Flutter is still delivered as an APK artifact. Releasing to Google Play would need separate signing and Play Console credentials.
