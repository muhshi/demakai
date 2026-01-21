#!/bin/bash

echo "🚀 Deploying updates..."

# 1. Pull latest code
echo "📥 Pulling from git..."
git pull origin main

# 2. Rebuild the image (Required since code is baked into image)
echo "🔨 Building Docker image..."
docker compose build demakai-franken demakai-worker

# 3. Recreate containers
echo "🔄 Recreating containers..."
docker compose up -d --remove-orphans

# 4. Clear cache
echo "🧹 Clearing application cache..."
docker compose exec -T demakai-franken php artisan optimize:clear
docker compose exec -T demakai-franken php artisan config:clear
docker compose exec -T demakai-franken php artisan view:clear

echo "✅ Deployment finished!"
