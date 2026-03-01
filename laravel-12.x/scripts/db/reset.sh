#!/bin/bash

echo "================================="
echo "TechQuiz DB Reset Automation"
echo "================================="

# Safety check (VERY IMPORTANT)
if [ "$APP_ENV" = "production" ]; then
  echo "❌ ERROR: Cannot reset database in production!"
  exit 1
fi

echo "📦 Starting Docker containers..."
docker compose up -d

echo "🗑 Resetting database..."
docker compose exec app php artisan migrate:fresh --seed

echo "🧹 Clearing caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear

echo "✅ Database reset complete!"