#!/bin/bash
# =============================================================================
# DepoPazar – Sunucuya atmadan önce çalıştırılacak hazırlık scripti
# Bu scripti çalıştırdıktan sonra projeyi sunucuya push edebilirsiniz.
# =============================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo -e "${GREEN}=== DepoPazar Pre-Deploy Hazırlık ===${NC}"
echo ""

# -----------------------------------------------------------------------------
# 1. Composer install (php-app)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[1/4] Composer install (php-app)...${NC}"
if [ -f "$ROOT/php-app/composer.json" ]; then
  (cd "$ROOT/php-app" && composer install --no-dev --optimize-autoloader 2>/dev/null) || (cd "$ROOT/php-app" && composer install --optimize-autoloader)
  echo -e "  ${GREEN}Composer tamamlandı${NC}"
else
  echo -e "  ${RED}php-app/composer.json bulunamadı${NC}"
  exit 1
fi
echo ""

# -----------------------------------------------------------------------------
# 2. Composer dump-autoload (autoload sınıflarının güncel olduğundan emin ol)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[2/4] Composer dump-autoload...${NC}"
(cd "$ROOT/php-app" && composer dump-autoload -o)
echo -e "  ${GREEN}Autoload güncellendi${NC}"
echo ""

# -----------------------------------------------------------------------------
# 3. Migration dosyalarını doğrula (sıra ve varlık)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[3/4] Migration dosyaları kontrol ediliyor...${NC}"
MIG_DIR="$ROOT/php-app/sql/migrations"
if [ ! -d "$MIG_DIR" ]; then
  echo -e "  ${RED}Migrations dizini yok: $MIG_DIR${NC}"
  exit 1
fi

# 01_add_vehicles_table.sql en başta olmalı (diğer vehicle_* tabloları buna bağlı)
FIRST_MIG=$(ls -1 "$MIG_DIR"/*.sql 2>/dev/null | head -1)
if [ -n "$FIRST_MIG" ]; then
  FIRST_NAME=$(basename "$FIRST_MIG")
  if [[ "$FIRST_NAME" == 01_add_vehicles_table.sql ]]; then
    echo -e "  ${GREEN}Migration sırası doğru (vehicles tablosu önce)${NC}"
  else
    echo -e "  ${YELLOW}Uyarı: İlk migration $FIRST_NAME. vehicles tablosu 01_add_vehicles_table.sql ile önce çalışmalı.${NC}"
  fi
fi

COUNT=$(ls -1 "$MIG_DIR"/*.sql 2>/dev/null | wc -l | tr -d ' ')
echo -e "  $COUNT migration dosyası bulundu"
echo ""

# -----------------------------------------------------------------------------
# 4. Veritabanı migration'ları (opsiyonel - .env veya db.local.php varsa)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[4/4] Veritabanı migration'ları (yerel test)...${NC}"
if [ -f "$ROOT/.env" ] || [ -f "$ROOT/php-app/.env" ] || [ -f "$ROOT/php-app/config/db.local.php" ]; then
  if command -v php &> /dev/null; then
    if (cd "$ROOT/php-app" && php scripts/run-migrations.php 2>/dev/null); then
      echo -e "  ${GREEN}Yerel veritabanı migration'ları tamamlandı${NC}"
    else
      echo -e "  ${YELLOW}Uyarı: Migration scripti hata verdi veya bazı migration'lar mysql CLI gerektirir (DELIMITER). Sunucuda deploy.sh çalışınca mysql ile düzgün çalışacaktır.${NC}"
    fi
  else
    echo -e "  ${YELLOW}PHP bulunamadı - migration atlandı${NC}"
  fi
else
  echo -e "  ${YELLOW}.env veya db.local.php yok - migration atlandı. Sunucuda deploy sırasında çalışacak.${NC}"
fi
echo ""

# -----------------------------------------------------------------------------
# Özet
# -----------------------------------------------------------------------------
echo -e "${GREEN}=== Pre-deploy tamamlandı ===${NC}"
echo ""
echo "Sunucuya push edebilirsiniz. Deploy sırasında deploy.sh şunları yapacak:"
echo "  - schema.sql + tüm migration'lar (mysql ile)"
echo "  - composer install"
echo "  - seed.php (ilk kurulum)"
echo ""
