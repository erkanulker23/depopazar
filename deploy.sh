#!/bin/bash
# =============================================================================
# DepoPazar – Laravel Forge Deploy Script (PHP Uygulaması)
# Proje: php-app (PHP + MySQL)
# Web Root: php-app/public
# Kullanım: Forge'da "Deploy Now" veya sunucuda: ./deploy.sh
# =============================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo -e "${GREEN}[1/8] Deploy başlatıldı (ROOT=$ROOT)${NC}"

# -----------------------------------------------------------------------------
# Git güncelleme
# -----------------------------------------------------------------------------
if [ "${SKIP_GIT}" != "1" ]; then
  echo -e "${YELLOW}[2/8] Kod güncelleniyor...${NC}"
  git fetch origin
  if [ -n "${FORGE_SITE_BRANCH}" ]; then
    git reset --hard "origin/${FORGE_SITE_BRANCH}"
  else
    git reset --hard origin/main
  fi
  cd "$ROOT"
else
  echo -e "${YELLOW}[2/8] Git atlandı (SKIP_GIT=1)${NC}"
fi

# -----------------------------------------------------------------------------
# .env yükleme ve db.local.php oluşturma
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[3/8] Yapılandırma kontrol ediliyor...${NC}"

if [ -f "$ROOT/.env" ]; then
  set -a
  source "$ROOT/.env" 2>/dev/null || true
  set +a
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-depotakip}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

# php-app/config/db.local.php - deploy sırasında .env değerlerinden oluşturulur
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
  if (cd "$ROOT" && php artisan migrate 2>/dev/null); then
    echo -e "  ${GREEN}Artisan migrate tamamlandı${NC}"
  else
    echo -e "  ${YELLOW}Uyarı: php artisan migrate hata verdi (mysql client gerekebilir)${NC}" >&2
    echo "  Sunucuda manuel: cd $ROOT && php artisan migrate" >&2
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
# Dizinler ve izinler (uygulama php-app/public/uploads kullanıyor)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[7/8] Dizin izinleri ayarlanıyor...${NC}"
mkdir -p "$ROOT/php-app/public/uploads/company"
chmod -R 755 "$ROOT/php-app/public/uploads" 2>/dev/null || true

# Forge'da forge kullanıcısı chown yapamaz (Operation not permitted).
# Sadece izinler yeterli; chown atlanıyor (uploads dizini zaten forge tarafından oluşturuluyor).

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
echo "  Web Root:    php-app/public"
echo "  Nginx:       root -> $ROOT/php-app/public"
echo "  PHP:         8.1+ (push için 8.1+ önerilir)"
echo ""
