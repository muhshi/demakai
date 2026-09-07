#!/bin/bash
set -e

# ==============================================================================
# PINTAR KBLI - Smart Deployment Script
# Build ulang hanya dilakukan jika terdeteksi perubahan pada file terkait
# atau jika di-trigger secara manual menggunakan flags:
#   ./deploy.sh                  (Smart build: cek perubahan git & keberadaan image)
#   ./deploy.sh -f | --force     (Paksa build ulang semua container)
#   ./deploy.sh --python         (Paksa build ulang container Python)
#   ./deploy.sh --laravel        (Paksa build ulang container Laravel/Franken)
#   ./deploy.sh --no-cache       (Build tanpa cache Docker)
# ==============================================================================

FORCE_ALL=false
FORCE_PYTHON=false
FORCE_LARAVEL=false
NO_CACHE_FLAG=""

for arg in "$@"; do
    case $arg in
        -f|--force|--rebuild)
            FORCE_ALL=true
            ;;
        --python)
            FORCE_PYTHON=true
            ;;
        --laravel)
            FORCE_LARAVEL=true
            ;;
        --no-cache)
            NO_CACHE_FLAG="--no-cache"
            ;;
        -h|--help)
            echo "Usage: ./deploy.sh [OPTIONS]"
            echo "Options:"
            echo "  -f, --force, --rebuild   Force rebuild all containers"
            echo "  --python                 Force rebuild only Python container"
            echo "  --laravel                Force rebuild only Laravel container"
            echo "  --no-cache               Build Docker images without cache"
            echo "  -h, --help               Show this help message"
            exit 0
            ;;
        *)
            echo "⚠️  Unknown option: $arg"
            ;;
    esac
done

echo "🚀 Deploying updates..."

# 1. Catat commit saat ini dan perubahan uncommitted lokal sebelum git pull
PREV_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "")
LOCAL_CHANGES=$(git status --porcelain 2>/dev/null | awk '{print $2}' || true)

# 2. Tarik update terbaru dari origin/main
echo "📥 Fetching and syncing latest code from origin/main..."
git fetch origin main
TARGET_COMMIT=$(git rev-parse origin/main 2>/dev/null || echo "")

# Identifikasi file apa saja yang berubah
CHANGED_FILES=""
if [ -n "$PREV_COMMIT" ] && [ -n "$TARGET_COMMIT" ]; then
    if [ "$PREV_COMMIT" != "$TARGET_COMMIT" ]; then
        CHANGED_FILES=$(git diff --name-only "$PREV_COMMIT" "$TARGET_COMMIT" || true)
    fi
    if [ -n "$LOCAL_CHANGES" ]; then
        CHANGED_FILES=$(printf "%s\n%s" "$CHANGED_FILES" "$LOCAL_CHANGES" | sort -u)
    fi
else
    CHANGED_FILES="ALL"
fi

git reset --hard origin/main

# Fungsi bantu untuk mengecek apakah image Docker sudah ada
has_image() {
    local service="$1"
    if [ -n "$(docker compose images -q "$service" 2>/dev/null)" ]; then
        return 0
    fi
    if [ "$service" = "demakai-franken" ] || [ "$service" = "demakai-worker" ]; then
        if docker image inspect ghcr.io/muhshi/demakai:latest >/dev/null 2>&1; then
            return 0
        fi
    fi
    return 1
}

# 3. Evaluasi apakah container Laravel/FrankenPHP perlu di-build/pull ulang
BUILD_LARAVEL=false
if [ "$FORCE_ALL" = true ] || [ "$FORCE_LARAVEL" = true ]; then
    BUILD_LARAVEL=true
    echo "🔧 Flag force build aktif untuk Laravel/FrankenPHP."
elif ! has_image "demakai-franken"; then
    BUILD_LARAVEL=true
    echo "ℹ️  Image demakai-franken belum ada di Docker host. Memerlukan build/pull awal."
elif [ "$CHANGED_FILES" = "ALL" ]; then
    BUILD_LARAVEL=true
    echo "ℹ️  Kondisi commit awal tidak dapat ditentukan, memicu build Laravel."
else
    # Filter file yang mempengaruhi Laravel & Frontend
    LARAVEL_CHANGES=$(echo "$CHANGED_FILES" | grep -v -E "^(python/|docs/|Jurnal/|\.github/|\.cursor/|\.agents/|.*\.md$|\.gitignore$|deploy\.sh$)" | grep -v "^$" || true)
    if [ -n "$LARAVEL_CHANGES" ]; then
        BUILD_LARAVEL=true
        echo "ℹ️  Perubahan terdeteksi pada kode Laravel / Frontend:"
        echo "$LARAVEL_CHANGES" | sed 's/^/   • /'
    fi
fi

# 4. Evaluasi apakah container Python Search API perlu di-build ulang
BUILD_PYTHON=false
if [ "$FORCE_ALL" = true ] || [ "$FORCE_PYTHON" = true ]; then
    BUILD_PYTHON=true
    echo "🔧 Flag force build aktif untuk Python Search API."
elif ! has_image "demakai-python"; then
    BUILD_PYTHON=true
    echo "ℹ️  Image demakai-python belum ada di Docker host. Memerlukan build awal."
elif [ "$CHANGED_FILES" = "ALL" ]; then
    BUILD_PYTHON=true
    echo "ℹ️  Kondisi commit awal tidak dapat ditentukan, memicu build Python."
else
    PYTHON_CHANGES=$(echo "$CHANGED_FILES" | grep -E "^python/" || true)
    if [ -n "$PYTHON_CHANGES" ]; then
        BUILD_PYTHON=true
        echo "ℹ️  Perubahan terdeteksi pada folder python/:"
        echo "$PYTHON_CHANGES" | sed 's/^/   • /'
    fi
fi

# 5. Eksekusi pull / build Laravel
if [ "$BUILD_LARAVEL" = true ]; then
    echo "📦 Menyiapkan image Laravel (demakai-franken & demakai-worker)..."
    echo "   Mencoba pull pre-built image dari GHCR..."
    if docker compose pull demakai-franken demakai-worker 2>/dev/null; then
        echo "✅ Laravel image berhasil di-pull dari GHCR."
    else
        echo "⚠️  Pull GHCR tidak tersedia/gagal. Melakukan build lokal dengan layer cache..."
        docker compose build $NO_CACHE_FLAG demakai-franken demakai-worker
        echo "✅ Build lokal Laravel selesai."
    fi
else
    echo "⏩ Kode Laravel tidak berubah & image sudah ada. Melewati build Laravel."
fi

# 6. Eksekusi build Python
if [ "$BUILD_PYTHON" = true ]; then
    echo "🐍 Membangun container Python Search API..."
    docker compose build $NO_CACHE_FLAG demakai-python
    echo "✅ Build Python Search API selesai."
else
    echo "⏩ Kode Python tidak berubah & image sudah ada. Melewati build Python."
fi

# 7. Recreate containers
echo "🔄 Recreating containers..."
docker compose up -d --remove-orphans

# 8. Wait for containers to be ready
echo "⏳ Waiting for containers to start..."
sleep 5

# 9. Verify Python Search API is running
echo "🔍 Checking Python Search API health..."
if docker exec demakai-franken curl -sf http://demakai-python:8000/health > /dev/null 2>&1; then
    echo "✅ Python Search API is healthy"
else
    echo "⚠️  Python Search API not responding yet (may still be starting up)"
fi

# 10. Run migrations (safe: won't drop/delete anything)
echo "📦 Running migrations..."
docker compose exec -T demakai-franken php artisan migrate --force

# 11. Seed KBLI Hierarchy data (auto-skip jika sudah terisi, gunakan --fresh untuk force update)
echo "🌱 Seeding KBLI 2025 Hierarchy data..."
docker compose exec -T demakai-franken php artisan db:seed --class=KbliHierarchySeeder --force || echo "⚠️  Seeder skipped atau error."

# 12. Clear and rebuild cache
echo "🧹 Clearing and optimizing cache..."
docker compose exec -T demakai-franken php artisan optimize:clear
docker compose exec -T demakai-franken php artisan optimize

# 13. Publish Livewire assets
echo "📦 Publishing Livewire assets..."
docker compose exec -T demakai-franken php artisan livewire:publish --assets

# 14. Generate/Update Offline Bundle for Flutter mobile sync
echo "📱 Exporting offline SQLite bundle for mobile sync..."
docker compose exec -T demakai-franken php artisan kbli:export-bundle || echo "⚠️  Offline bundle export skipped/warning."

# 15. Restart queue workers to pick up new code
echo "🔄 Restarting queue workers..."
docker compose exec -T demakai-franken php artisan queue:restart

echo "✅ Deployment finished!"
echo ""
echo "📋 Container status:"
docker compose ps

