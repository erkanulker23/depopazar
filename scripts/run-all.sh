#!/bin/bash
# DepoPazar – Tüm servisleri başlat (Docker + Backend + Frontend)
# Kullanım: ./scripts/run-all.sh veya proje kökünden ./run-all.sh

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "🚀 DepoPazar servisleri başlatılıyor..."
echo ""

if command -v docker &> /dev/null; then
  echo -e "${YELLOW}1. Docker servisleri...${NC}"
  cd "$ROOT" && docker compose up -d postgres redis 2>&1 | tail -3
  echo -e "${GREEN}   ✅ Docker servisleri başlatıldı${NC}"
else
  echo -e "${RED}   ⚠️  Docker bulunamadı, atlanıyor${NC}"
fi

echo -e "${YELLOW}2. Backend...${NC}"
cd "$ROOT/backend"
npm run start:dev > /tmp/depopazar-backend.log 2>&1 &
echo $! > /tmp/depopazar-backend.pid
echo -e "${GREEN}   ✅ Backend başlatıldı (log: /tmp/depopazar-backend.log)${NC}"

echo -e "${YELLOW}3. Frontend...${NC}"
cd "$ROOT/frontend"
npm run dev > /tmp/depopazar-frontend.log 2>&1 &
echo $! > /tmp/depopazar-frontend.pid
echo -e "${GREEN}   ✅ Frontend başlatıldı (log: /tmp/depopazar-frontend.log)${NC}"

echo ""
echo -e "${GREEN}✅ Tüm servisler başlatıldı.${NC}"
echo "   Frontend: http://localhost:3180  |  Backend API: http://localhost:4100/api"
echo "   Durdurmak için: ./stop-all.sh veya ./scripts/stop-all.sh"
echo ""
