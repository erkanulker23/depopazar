#!/bin/bash

# DepoPazar - Tüm Servisleri Başlatma Scripti

echo "🚀 DepoPazar Servisleri Başlatılıyor..."
echo ""

# Renkli çıktı için
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Docker servisleri
echo -e "${YELLOW}1. Docker servisleri başlatılıyor...${NC}"
if command -v docker &> /dev/null; then
    cd /Users/erkanulker/depopazar
    docker compose up -d postgres redis 2>&1 | tail -3
    echo -e "${GREEN}   ✅ Docker servisleri başlatıldı${NC}"
else
    echo -e "${RED}   ⚠️  Docker bulunamadı, atlanıyor${NC}"
fi

# 2. Backend
echo -e "${YELLOW}2. Backend başlatılıyor...${NC}"
cd /Users/erkanulker/depopazar/backend

# Backend'i arka planda başlat
npm run start:dev > /tmp/depopazar-backend.log 2>&1 &
BACKEND_PID=$!
echo $BACKEND_PID > /tmp/depopazar-backend.pid
echo -e "${GREEN}   ✅ Backend başlatıldı (PID: $BACKEND_PID)${NC}"
echo "   📝 Log: /tmp/depopazar-backend.log"

# 3. Frontend
echo -e "${YELLOW}3. Frontend başlatılıyor...${NC}"
cd /Users/erkanulker/depopazar/frontend

# Frontend'i arka planda başlat
npm run dev > /tmp/depopazar-frontend.log 2>&1 &
FRONTEND_PID=$!
echo $FRONTEND_PID > /tmp/depopazar-frontend.pid
echo -e "${GREEN}   ✅ Frontend başlatıldı (PID: $FRONTEND_PID)${NC}"
echo "   📝 Log: /tmp/depopazar-frontend.log"

echo ""
echo -e "${GREEN}✅ Tüm servisler başlatıldı!${NC}"
echo ""
echo "📋 Erişim:"
echo "   - Frontend: http://depotakip-v1.test"
echo "   - Backend API: http://depotakip-v1.test/api"
echo "   - Swagger: http://depotakip-v1.test/api/docs"
echo ""
echo "🛑 Servisleri durdurmak için:"
echo "   bash stop-all.sh"
echo ""
