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
#
# Veri güvenliği:
# - Migration'lar schema_migrations ile yalnızca bir kez uygulanır
# - Mevcut veritabanı tespit edilirse SQL tekrar çalıştırılmaz
# - seed.php deploy sırasında ÇALIŞMAZ (şifre/veri korunur)
# - uploads ve loglar shared/ dizinine symlink (release değişince dosya kaybolmaz)
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

# Release icindeki dizini Forge shared/ altina bagla (zero-downtime'da dosyalar korunur)
link_shared_dir() {
  local shared_target="$1"
  local release_path="$2"
  if [ -z "$shared_target" ] || [ -z "$release_path" ]; then
    return 0
  fi
  mkdir -p "$shared_target"
  if [ -d "$release_path" ] && [ ! -L "$release_path" ]; then
    if [ "$(ls -A "$release_path" 2>/dev/null)" ]; then
      echo "[forge-deploy] Mevcut dosyalar shared'a tasiniyor: $release_path -> $shared_target"
      cp -a "$release_path/." "$shared_target/" 2>/dev/null || true
    fi
    rm -rf "$release_path"
  fi
  ln -nfs "$shared_target" "$release_path"
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

# Forge Environment -> db.local.php (her release; web + CLI baglantisi)
if [ -n "${DB_HOST:-}" ] || [ -n "${DB_DATABASE:-}" ] || [ -n "${DB_USERNAME:-}" ]; then
  DB_HOST="${DB_HOST:-127.0.0.1}"
  DB_PORT="${DB_PORT:-3306}"
  DB_DATABASE="${DB_DATABASE:-depotakip}"
  DB_USERNAME="${DB_USERNAME:-root}"
  DB_PASSWORD="${DB_PASSWORD:-}"
  DB_PASS_ESC=$(printf '%s' "$DB_PASSWORD" | sed "s/'/'\\\\''/g")
  mkdir -p "$ROOT/php-app/config"
  cat > "$ROOT/php-app/config/db.local.php" << DBCONFIG
<?php
\$host = '${DB_HOST}';
\$port = '${DB_PORT}';
\$db   = '${DB_DATABASE}';
\$user = '${DB_USERNAME}';
\$pass = '${DB_PASS_ESC}';
\$dsn  = "mysql:host=\$host;port=\$port;dbname=\$db;charset=utf8mb4";
\$pdo  = new PDO(\$dsn, \$user, \$pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
return \$pdo;
DBCONFIG
  chmod 640 "$ROOT/php-app/config/db.local.php" 2>/dev/null || true
  echo "[forge-deploy] db.local.php olusturuldu (Forge Environment)."
fi

# Forge .env symlink (varsa)
if [ -n "${FORGE_SITE_PATH:-}" ] && [ -f "${FORGE_SITE_PATH}/.env" ]; then
  ln -nfs "${FORGE_SITE_PATH}/.env" "$ROOT/.env"
fi

# Composer (kok → php-app/vendor post-install script ile)
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

# Paylasilan dosyalar (Forge zero-downtime: her release yeni klasor)
if [ -n "${FORGE_SITE_PATH:-}" ]; then
  SHARED="$FORGE_SITE_PATH/shared"
  echo "[forge-deploy] Shared storage symlink..."
  link_shared_dir "$SHARED/uploads" "$ROOT/php-app/public/uploads"
  link_shared_dir "$SHARED/storage/logs" "$ROOT/php-app/storage/logs"
  link_shared_dir "$SHARED/storage/cache" "$ROOT/php-app/storage/cache"
else
  mkdir -p "$ROOT/php-app/public/uploads/company"
  mkdir -p "$ROOT/php-app/storage/logs"
fi

# Migration (seed deploy'da calismaz — mevcut veri korunur)
if [ -f "$ROOT/artisan" ]; then
  echo "[forge-deploy] php artisan migrate --force..."
  export ARTISAN_SKIP_SEED=1
  (cd "$ROOT" && php artisan migrate --force)
fi

chmod -R 755 "$ROOT/php-app/public" 2>/dev/null || true
chmod -R 755 "$ROOT/php-app/public/uploads" 2>/dev/null || true

echo "[forge-deploy] Tamamlandi."
