#!/bin/bash
# =============================================================================
# DepoPazar – Kesintisiz deploy (multi-domain / subdomain, DB izolasyonu)
# Her domain/subdomain için bu script ilgili dizinde, kendi .env ile çalıştırılır.
# Sıra: migration -> build -> PM2 reload (başarısızda eski sürüm çalışmaya devam).
# =============================================================================

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

set -e

echo -e "${GREEN}Deploy başlatılıyor...${NC}"

# 1. Kod güncelleme (opsiyonel – CI/CD’de genelde dışarıdan yapılır)
if [ "${SKIP_GIT}" != "1" ]; then
  echo -e "${YELLOW}Kodlar güncelleniyor...${NC}"
  git fetch origin
  git reset --hard origin/main
fi

# 2. Backend
echo -e "${YELLOW}Backend işlemleri...${NC}"
cd "$(dirname "$0")/backend"

# Production’da .env zorunlu (hardcoded DB yok)
if [ "${NODE_ENV}" = "production" ] || [ "${DEPLOY_STRICT}" = "1" ]; then
  if [ ! -f .env ]; then
    echo -e "${RED}HATA: backend/.env bulunamadı. Her kurulum için ayrı .env zorunludur.${NC}"
    exit 1
  fi
else
  if [ ! -f .env ]; then
    echo -e "${YELLOW}UYARI: .env yok, .env.example kopyalanıyor. Düzenleyip tekrar çalıştırın.${NC}"
    cp .env.example .env
    exit 1
  fi
fi

mkdir -p uploads backups logs
chmod 755 uploads backups logs 2>/dev/null || true

echo "Bağımlılıklar yükleniyor..."
npm ci --legacy-peer-deps

# Önce migration (başarısızsa build/reload yapılmaz – zero-downtime güvenliği)
echo "Veritabanı migration’ları çalıştırılıyor..."
npm run migration:run

echo "Build alınıyor..."
npm run build

# PM2: reload = kesintisiz; yoksa start
echo "PM2 process güncelleniyor..."
pm2 reload ecosystem.config.js --env production || pm2 start ecosystem.config.js --env production

cd ..

# 3. Frontend (bu repo tek frontend ise; çoklu domain’de her domain ayrı build olabilir)
echo -e "${YELLOW}Frontend işlemleri...${NC}"
cd frontend
if [ ! -f .env ]; then
  echo -e "${YELLOW}UYARI: frontend/.env yok. VITE_API_URL bu domain’in API adresi olmalı.${NC}"
  [ -f .env.example ] && cp .env.example .env
fi
npm ci --legacy-peer-deps
npm run build
cd ..

echo -e "${GREEN}Deploy tamamlandı.${NC}"
