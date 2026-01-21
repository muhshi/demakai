#!/bin/bash

echo "🚀 Deploying updates..."

# 1. Pull latest code
git pull origin main

# 2. Clear cache
echo "🧹 Clearing application cache..."
docker compose exec -T demakai-franken php artisan optimize:clear

# 3. Reload server (FrankenPHP/Octane needs this to see code changes)
echo "🔄 Reloading application..."
# Jika pakai Octane command:
# docker compose exec -T demakai-franken php artisan octane:reload
# Tapi restart container lebih pasti bersih:
docker compose restart demakai-franken

echo "✅ Deployment finished!"
