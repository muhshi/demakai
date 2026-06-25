#!/bin/bash
set -e

echo "🚀 Deploying updates..."

# 1. Pull latest code
echo "📥 Pulling from git..."
git fetch origin main
git reset --hard origin/main

# 2. Try pulling pre-built image from GHCR, fallback to local build
echo "📦 Pulling Docker image from GHCR..."
if docker compose pull demakai-franken demakai-worker 2>/dev/null; then
    echo "✅ Laravel image pulled from GHCR"
else
    echo "⚠️  GHCR pull failed, building locally..."
    docker compose build --no-cache demakai-franken
fi

# 2b. Build Python Search API container (always local build)
echo "🐍 Building Python Search API..."
docker compose build demakai-python

# 3. Recreate containers
echo "🔄 Recreating containers..."
docker compose up -d --remove-orphans

# 4. Wait for containers to be ready
echo "⏳ Waiting for containers to start..."
sleep 5

# 4b. Verify Python Search API is running
echo "🔍 Checking Python Search API health..."
if docker exec demakai-franken curl -sf http://demakai-python:8000/health > /dev/null 2>&1; then
    echo "✅ Python Search API is healthy"
else
    echo "⚠️  Python Search API not responding yet (may still be starting up)"
fi

# 5. Run migrations (safe: won't drop/delete anything)
echo "📦 Running migrations..."
docker compose exec -T demakai-franken php artisan migrate --force

# 5b. Seed KBLI Hierarchy data (auto-skip jika sudah terisi, gunakan --fresh untuk force update)
echo "🌱 Seeding KBLI 2025 Hierarchy data..."
docker compose exec -T demakai-franken php artisan db:seed --class=KbliHierarchySeeder --force || echo "⚠️  Seeder skipped atau error."
# Catatan: untuk force update ulang (misal data JSON berubah), jalankan manual:
#   docker compose exec demakai-franken php artisan db:seed --class=KbliHierarchySeeder --force --fresh

# 6. Clear and rebuild cache
echo "🧹 Clearing and optimizing cache..."
docker compose exec -T demakai-franken php artisan optimize:clear
docker compose exec -T demakai-franken php artisan optimize

# 7. Publish Livewire assets
echo "📦 Publishing Livewire assets..."
docker compose exec -T demakai-franken php artisan livewire:publish --assets

# 8. Restart queue workers to pick up new code
echo "🔄 Restarting queue workers..."
docker compose exec -T demakai-franken php artisan queue:restart

echo "✅ Deployment finished!"
echo ""
echo "📋 Container status:"
docker compose ps
