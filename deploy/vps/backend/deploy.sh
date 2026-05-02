#!/bin/sh
set -eu

: "${BACKEND_IMAGE:?BACKEND_IMAGE is required}"
: "${GHCR_USERNAME:?GHCR_USERNAME is required}"

APP_PORT="${APP_PORT:-8001}"

if ! IFS= read -r GHCR_TOKEN; then
    echo "GHCR token was not provided on stdin." >&2
    exit 1
fi

echo "$GHCR_TOKEN" | docker login ghcr.io -u "$GHCR_USERNAME" --password-stdin

export BACKEND_IMAGE
export APP_PORT

docker compose pull
docker compose up -d --remove-orphans

sleep 10

docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan storage:link || true
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app curl -fsS http://127.0.0.1/ >/dev/null
docker compose ps
