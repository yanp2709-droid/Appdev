# reset.ps1 — fully working Windows + Docker + Laravel DB reset

# 1. Detect project root
$projectRoot = Resolve-Path "..\..\" | Select-Object -ExpandProperty Path

# 2. Set environment
$env:APP_ENV="local"

Write-Host "🚀 Starting DB reset..."

# 3. Start Docker container (service name = app)
docker-compose -f "$projectRoot\docker-compose.yml" up -d app

# 4. Wait for container to be ready
Start-Sleep -Seconds 3

# 5. Run Laravel migrations and seed inside container
docker exec -it techquiz_app sh -c "php artisan migrate:fresh --seed"

# 6. Clear Laravel caches
docker exec -it techquiz_app sh -c "php artisan config:clear"
docker exec -it techquiz_app sh -c "php artisan cache:clear"

Write-Host "✅ DB reset completed!"