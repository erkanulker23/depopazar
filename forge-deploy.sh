#!/bin/bash
# =============================================================================
# DepoPazar – Forge zero-downtime deploy adımları (git YOK)
#
# Forge Deploy Script:
#   cd $FORGE_SITE_PATH
#   $CREATE_RELEASE()
#   cd $FORGE_RELEASE_DIRECTORY
#   bash ./forge-deploy.sh
#   $ACTIVATE_RELEASE()
#   $RESTART_QUEUES()
#
# ÖNEMLİ: Deploy script'inizde "git pull" OLMAMALI.
# =============================================================================

set -e

resolve_root() {
  if [ -n "${FORGE_RELEASE_DIRECTORY:-}" ] && [ -d "${FORGE_RELEASE_DIRECTORY}/php-app/public" ]; then
    printf '%s\n' "${FORGE_RELEASE_DIRECTORY}"
    return 0
  fi
  if [ -d "$(pwd)/php-app/public" ]; then
    printf '%s\n' "$(pwd)"
    return 0
  fi
  if [ -n "${FORGE_SITE_PATH:-}" ] && [ -d "${FORGE_SITE_PATH}/current/php-app/public" ]; then
    printf '%s\n' "${FORGE_SITE_PATH}/current"
    return 0
  fi
  local script_dir=""
  if [ -n "${BASH_SOURCE[0]:-}" ]; then
    script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" 2>/dev/null && pwd)" || script_dir=""
  fi
  if [ -n "$script_dir" ] && [[ "$script_dir" != /dev/fd* ]] && [ -d "$script_dir/php-app/public" ]; then
    printf '%s\n' "$script_dir"
    return 0
  fi
  return 1
}

if ! ROOT="$(resolve_root)"; then
  echo "Hata: proje kokü bulunamadi." >&2
  echo "  FORGE_RELEASE_DIRECTORY=${FORGE_RELEASE_DIRECTORY:-<yok>}" >&2
  echo "  PWD=$(pwd)" >&2
  echo "  Deploy script'te once: cd \$FORGE_RELEASE_DIRECTORY" >&2
  echo "  sonra: bash ./forge-deploy.sh" >&2
  exit 1
fi

cd "$ROOT"
echo "[forge-deploy] ROOT=$ROOT"

# Composer (kök → php-app/vendor post-install script ile)
if command -v composer >/dev/null 2>&1 && [ -f "$ROOT/composer.json" ]; then
  echo "[forge-deploy] composer install..."
  (cd "$ROOT" && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader)
fi

# NPM build (no-op script)
if [ -f "$ROOT/package.json" ] && command -v npm >/dev/null 2>&1; then
  echo "[forge-deploy] npm install && npm run build..."
  (cd "$ROOT" && { npm ci 2>/dev/null || npm install; })
  (cd "$ROOT" && npm run build)
fi

# Migration + seed
if [ -f "$ROOT/artisan" ]; then
  echo "[forge-deploy] php artisan migrate --force..."
  (cd "$ROOT" && php artisan migrate --force)
fi

mkdir -p "$ROOT/php-app/public/uploads/company"
chmod -R 755 "$ROOT/php-app/public" 2>/dev/null || true
chmod -R 755 "$ROOT/php-app/public/uploads" 2>/dev/null || true

echo "[forge-deploy] Tamamlandi."
