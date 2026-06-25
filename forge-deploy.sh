#!/bin/bash
# =============================================================================
# DepoPazar – Forge zero-downtime deploy adımları (git YOK)
# Forge Deploy Script içinde CREATE_RELEASE sonrası çalıştırılır:
#
#   cd $FORGE_SITE_PATH
#   $CREATE_RELEASE()
#   cd $FORGE_RELEASE_DIRECTORY
#   set -e && bash forge-deploy.sh
#   $ACTIVATE_RELEASE()
#   $RESTART_QUEUES()
#
# ÖNEMLİ: Deploy script'inizde "git pull" veya "git fetch" OLMAMALI.
# Zero-downtime'da site kökü ($FORGE_SITE_PATH) git repo değildir.
# =============================================================================

set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo "[forge-deploy] ROOT=$ROOT"

if [ ! -d "$ROOT/php-app/public" ]; then
  echo "Hata: php-app/public bulunamadi (ROOT=$ROOT)" >&2
  exit 1
fi

# Composer (kök → php-app/vendor post-install script ile)
if command -v composer >/dev/null 2>&1 && [ -f "$ROOT/composer.json" ]; then
  echo "[forge-deploy] composer install..."
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

# NPM build (no-op script; Forge npm adimini kirmamak icin)
if [ -f "$ROOT/package.json" ] && command -v npm >/dev/null 2>&1; then
  echo "[forge-deploy] npm install && npm run build..."
  npm ci 2>/dev/null || npm install
  npm run build
fi

# Migration + seed
if [ -f "$ROOT/artisan" ]; then
  echo "[forge-deploy] php artisan migrate --force..."
  php artisan migrate --force
fi

mkdir -p "$ROOT/php-app/public/uploads/company"
chmod -R 755 "$ROOT/php-app/public" 2>/dev/null || true
chmod -R 755 "$ROOT/php-app/public/uploads" 2>/dev/null || true

echo "[forge-deploy] Tamamlandi."
