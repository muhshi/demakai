#!/bin/bash
set -e

echo "🚀 Deploying updates..."

# 1. Pull latest code
echo "📥 Pulling from git..."
git fetch origin main
git reset --hard origin/main

# 2. Pull latest Docker image from GHCR (no build needed on server)
echo "📦 Pulling latest Docker image..."
docker compose pull

# 3. Recreate containers
echo "🔄 Recreating containers..."
docker compose up -d --remove-orphans

# 4. Wait for containers to be ready
echo "⏳ Waiting for containers to start..."
sleep 5

# 5. Run migrations (safe: won't drop/delete anything)
echo "📦 Running migrations..."
docker compose exec -T demakai-franken php artisan migrate --force

# 5b. Seed KBLI Hierarchy data (idempotent: skip if already populated)
echo "🌱 Seeding KBLI 2025 Hierarchy data..."
docker compose exec -T demakai-franken php artisan db:seed --class=KbliHierarchySeeder --force || echo "⚠️  Seeder skipped or already ran."

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
