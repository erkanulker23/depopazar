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

echo -e "${GREEN}[1/5] Deploy başlatıldı (ROOT=$ROOT)${NC}"

# -----------------------------------------------------------------------------
# Git güncelleme
# -----------------------------------------------------------------------------
if [ "${SKIP_GIT}" != "1" ]; then
  echo -e "${YELLOW}[2/5] Kod güncelleniyor...${NC}"
  git fetch origin
  if [ -n "${FORGE_SITE_BRANCH}" ]; then
    git reset --hard "origin/${FORGE_SITE_BRANCH}"
  else
    git reset --hard origin/main
  fi
  cd "$ROOT"
else
  echo -e "${YELLOW}[2/5] Git atlandı (SKIP_GIT=1)${NC}"
fi

# -----------------------------------------------------------------------------
# .env yükleme ve db.local.php oluşturma
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[3/5] Yapılandırma kontrol ediliyor...${NC}"

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
# Veritabanı schema (ilk kurulum - CREATE IF NOT EXISTS kullandığı için güvenli)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[4/5] Veritabanı güncelleniyor...${NC}"
if [ -f "$ROOT/php-app/sql/schema.sql" ] && command -v mysql &> /dev/null; then
  if [ -n "$DB_PASSWORD" ]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$ROOT/php-app/sql/schema.sql" 2>/dev/null || echo "Schema import atlandı (tablolar mevcut olabilir)"
  else
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" < "$ROOT/php-app/sql/schema.sql" 2>/dev/null || echo "Schema import atlandı"
  fi
else
  echo "  (MySQL client yok veya schema bulunamadı - atlanıyor)"
fi

# -----------------------------------------------------------------------------
# Dizinler ve izinler
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[5/5] Dizin izinleri ayarlanıyor...${NC}"
mkdir -p "$ROOT/php-app/uploads"
chmod -R 755 "$ROOT/php-app/uploads" 2>/dev/null || true

# Forge'da PHP-FPM kullanıcısı (forge veya www-data)
WEB_USER="${WEB_USER:-$FORGE_SSH_USER}"
WEB_USER="${WEB_USER:-www-data}"
if id "$WEB_USER" &>/dev/null 2>&1; then
  chown -R "$WEB_USER:$WEB_USER" "$ROOT/php-app/uploads" 2>/dev/null || true
fi

echo -e "${GREEN}Deploy tamamlandı.${NC}"
echo ""
echo "  Web Root:    php-app/public"
echo "  Nginx:       root -> $ROOT/php-app/public"
echo "  PHP:         8.0+ önerilir"
echo ""
