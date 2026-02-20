#!/bin/bash
# =============================================================================
# DepoPazar – Deploy Script (Forge / Awapanel / manuel)
# Web Root: php-app/public (panelde mutlaka bu dizin seçilmeli, yoksa 403)
# Kullanım: Panelde "Deploy" veya sunucuda: cd /path/to/proje && ./deploy.sh
# İlk kurulum: docs/SETUP.md
# =============================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Proje kökü: panel FORGE_SITE_PATH veya SITE_PATH veriyorsa onu kullan, yoksa script'in bulunduğu dizin
if [ -n "${FORGE_SITE_PATH}" ]; then
  ROOT="${FORGE_SITE_PATH}"
elif [ -n "${SITE_PATH}" ]; then
  ROOT="${SITE_PATH}"
else
  ROOT="$(cd "$(dirname "$0")" && pwd)"
fi
cd "$ROOT"

if [ ! -d "$ROOT/php-app/public" ]; then
  echo "Hata: php-app/public bulunamadı. ROOT yanlış olabilir (şu an: $ROOT)."
  echo "Forge'da Deploy Script'e sadece şunu yapıştırın: cd \$FORGE_SITE_PATH && bash deploy.sh"
  exit 1
fi

echo -e "${GREEN}[1/8] Deploy başlatıldı (ROOT=$ROOT)${NC}"

# -----------------------------------------------------------------------------
# Git güncelleme (branch: Forge'daki Site → Branch ayarı; güncelleme gelmiyorsa orayı kontrol edin)
# php-app/public içindeki dosyalar www-data tarafından oluşturulmuşsa git reset "Permission denied" verir; önce sahipliği düzeltiyoruz.
# -----------------------------------------------------------------------------
BRANCH="${FORGE_SITE_BRANCH:-main}"
if [ "${SKIP_GIT}" != "1" ]; then
  if [ -d "$ROOT/php-app/public" ]; then
    DEPLOY_USER=$(whoami 2>/dev/null || id -un 2>/dev/null)
    if [ -n "$DEPLOY_USER" ]; then
      sudo chown -R "$DEPLOY_USER:$DEPLOY_USER" "$ROOT/php-app/public" 2>/dev/null || true
    fi
    chmod -R u+w "$ROOT/php-app/public" 2>/dev/null || true
  fi
  echo -e "${YELLOW}[2/8] Kod güncelleniyor (branch: $BRANCH)...${NC}"
  git fetch origin
  git reset --hard "origin/$BRANCH"
  cd "$ROOT"
  COMMIT=$(git rev-parse --short HEAD 2>/dev/null || true)
  COMMIT_MSG=$(git log -1 --format='%s' 2>/dev/null || true)
  echo "  Deploy edilen commit: ${COMMIT} - ${COMMIT_MSG}"
else
  echo -e "${YELLOW}[2/8] Git atlandı (SKIP_GIT=1)${NC}"
fi

# -----------------------------------------------------------------------------
# .env yükleme ve db.local.php oluşturma
# Sunucuda .env genelde olmaz (git'te yok); Forge'da Site → Environment'ta
# DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD tanımlı olmalı.
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[3/8] Yapılandırma kontrol ediliyor...${NC}"

if [ -f "$ROOT/.env" ]; then
  set -a
  source "$ROOT/.env" 2>/dev/null || true
  set +a
fi

# Forge ortam değişkenleri zaten export edilmiş olabilir; yoksa .env'den veya varsayılan
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-depotakip}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

if [ -z "$DB_USERNAME" ] || [ "$DB_USERNAME" = "root" ]; then
  if [ ! -f "$ROOT/.env" ]; then
    echo -e "  ${YELLOW}Uyarı: .env yok ve DB_USERNAME boş. Forge → Site → Environment'ta DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD ekleyin.${NC}" >&2
  fi
fi

# php-app/config/db.local.php - deploy sırasında .env veya Forge env değerlerinden oluşturulur
mkdir -p "$ROOT/php-app/config"
# Şifredeki tek tırnakları güvenli şekilde escape et
DB_PASS_ESC=$(echo "$DB_PASSWORD" | sed "s/'/'\\\\''/g")
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

# -----------------------------------------------------------------------------
# Veritabanı: schema + migrations (php artisan migrate = eksik tablo/sütunları ekler)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[4/8] Veritabanı güncelleniyor (schema + migrations)...${NC}"
if [ -f "$ROOT/artisan" ]; then
  if (cd "$ROOT" && php artisan migrate --force); then
    echo -e "  ${GREEN}Artisan migrate tamamlandı${NC}"
  else
    echo -e "  ${RED}Hata: php artisan migrate başarısız.${NC}" >&2
    echo "  Forge'da Site → Environment'a DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD ekleyin (sunucuda .env yok)." >&2
    echo "  Manuel test: cd $ROOT && php artisan migrate --force" >&2
    exit 1
  fi
else
  echo "  (artisan bulunamadı - atlanıyor)"
fi

# -----------------------------------------------------------------------------
# Composer (php-app: web-push vb. bağımlılıklar)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[5/8] Composer (php-app)...${NC}"
if command -v composer &> /dev/null && [ -f "$ROOT/php-app/composer.json" ]; then
  if (cd "$ROOT/php-app" && composer install --no-dev --optimize-autoloader); then
    echo -e "  ${GREEN}Composer OK${NC}"
  else
    echo -e "  ${RED}Composer HATASI - vendor/ eksik olabilir, 500 hatası alırsınız!${NC}" >&2
    echo "  Sunucuda manuel: cd php-app && composer install --no-dev --optimize-autoloader" >&2
  fi
else
  echo "  (composer yok veya php-app/composer.json yok - atlanıyor)"
fi

# -----------------------------------------------------------------------------
# Seed: Super admin kullanıcı + varsayılan şirket (yoksa oluştur)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[6/8] Seed kontrol ediliyor...${NC}"
if [ -f "$ROOT/php-app/seed.php" ] && [ -f "$ROOT/php-app/config/db.local.php" ]; then
  cd "$ROOT/php-app" && php seed.php 2>/dev/null || true
  cd "$ROOT"
else
  echo "  (seed.php veya db.local.php yok - atlanıyor)"
fi

# -----------------------------------------------------------------------------
# Dizinler ve izinler (403 önlemi: Nginx www-data php-app/public'e erişebilmeli)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[7/8] Dizin izinleri ayarlanıyor...${NC}"
mkdir -p "$ROOT/php-app/public/uploads/company"
chmod 755 "$ROOT/php-app" 2>/dev/null || true
chmod -R 755 "$ROOT/php-app/public" 2>/dev/null || true
chmod -R 755 "$ROOT/php-app/public/uploads" 2>/dev/null || true
[ -f "$ROOT/php-app/public/index.php" ] && chmod 644 "$ROOT/php-app/public/index.php" 2>/dev/null || true

# -----------------------------------------------------------------------------
# VAPID (push bildirimleri) – .env'de tanımlı olmalı; yoksa bildirimler sadece panelde
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[8/8] Kontrol: VAPID / push${NC}"
if [ -n "$VAPID_PUBLIC_KEY" ] && [ -n "$VAPID_PRIVATE_KEY" ]; then
  echo "  VAPID anahtarları .env'de tanımlı (cihaz bildirimleri açık)"
else
  echo "  VAPID yok – cihaz bildirimleri için .env'e VAPID_PUBLIC_KEY ve VAPID_PRIVATE_KEY ekleyin (bkz. docs/PUSH-BILDIRIMLER.md)"
fi

echo -e "${GREEN}Deploy tamamlandı.${NC}"
echo ""
echo "  Web Root:    $ROOT/php-app/public"
echo "  Panelde (Forge/Awapanel) Web Directory / Document Root = php-app/public olmalı (403 önlemi)."
echo "  İlk kurulum: docs/SETUP.md"
echo ""
