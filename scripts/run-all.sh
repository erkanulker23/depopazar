#!/bin/bash
# DepoPazar – Yerel geliştirme ortamı (Docker: MySQL + Redis)
# Uygulama: PHP (php-app). Web sunucusu için Valet, Laragon veya php -S kullanın.
# Kullanım: ./scripts/run-all.sh

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "🚀 DepoPazar yerel ortamı..."
echo ""

if command -v docker &> /dev/null; then
  echo -e "${YELLOW}Docker servisleri (MySQL + Redis)...${NC}"
  cd "$ROOT" && docker compose up -d mysql redis 2>&1 | tail -3
  echo -e "${GREEN}   ✅ Docker servisleri başlatıldı${NC}"
else
  echo -e "${RED}   ⚠️  Docker bulunamadı (yerel MySQL kullanın)${NC}"
fi

echo ""
echo -e "${GREEN}Proje düz PHP ile çalışıyor.${NC}"
echo "   Web: php-app/public (Valet/Laragon/nginx veya: cd php-app/public && php -S localhost:8080)"
echo "   Durdurmak için: ./scripts/stop-all.sh"
echo ""
