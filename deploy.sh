#!/bin/bash
set -e

echo "🚀 Deploying updates..."

# 1. Pull latest code — reset local changes dulu agar pull tidak gagal
echo "📥 Pulling from git..."
git fetch origin main
git reset --hard origin/main

# 2. Rebuild the image (Required since code is baked into image)
echo "🔨 Building Docker image..."
docker compose build --no-cache demakai-franken

# 3. Recreate containers
echo "🔄 Recreating containers..."
docker compose up -d --remove-orphans

# 4. Wait for containers to be ready
echo "⏳ Waiting for containers to start..."
sleep 5

# 5. Run migrations (safe: won't drop/delete anything)
echo "📦 Running migrations..."
docker compose exec -T demakai-franken php artisan migrate --force

# 6. Clear and rebuild cache
echo "🧹 Clearing and optimizing cache..."
docker compose exec -T demakai-franken php artisan optimize:clear
docker compose exec -T demakai-franken php artisan optimize

# 7. Restart queue workers to pick up new code
echo "🔄 Restarting queue workers..."
docker compose exec -T demakai-franken php artisan queue:restart

echo "✅ Deployment finished!"
echo ""
echo "📋 Container status:"
docker compose ps
