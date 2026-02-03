#!/bin/bash
# =============================================================================
# DepoPazar – Forge Quick Deploy uyumlu deploy script
# Tek .env proje kökünde; backend ve frontend bu dosyadan beslenir.
# Kullanım: Forge’da “Deploy Now” veya sunucuda: ./deploy.sh
# =============================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo -e "${GREEN}[1/6] Deploy başlatıldı (ROOT=$ROOT)${NC}"

# -----------------------------------------------------------------------------
# Git güncelleme (Forge branch’i kullanır; SKIP_GIT=1 ile atlanabilir)
# -----------------------------------------------------------------------------
if [ "${SKIP_GIT}" != "1" ]; then
  echo -e "${YELLOW}[2/6] Kod güncelleniyor...${NC}"
  git fetch origin
  if [ -n "${FORGE_SITE_BRANCH}" ]; then
    git reset --hard "origin/${FORGE_SITE_BRANCH}"
  else
    git reset --hard origin/main
  fi
  cd "$ROOT"
else
  echo -e "${YELLOW}[2/6] Git atlandı (SKIP_GIT=1)${NC}"
fi

# -----------------------------------------------------------------------------
# Tek .env – proje kökünde zorunlu (production’da)
# -----------------------------------------------------------------------------
if [ ! -f "$ROOT/.env" ]; then
  if [ "${NODE_ENV}" = "production" ] || [ "${DEPLOY_STRICT}" = "1" ]; then
    echo -e "${RED}HATA: $ROOT/.env bulunamadı. Production için zorunludur.${NC}"
    exit 1
  fi
  echo -e "${YELLOW}UYARI: .env yok; .env.example kopyalanıyor. Değerleri doldurup tekrar deploy edin.${NC}"
  cp "$ROOT/.env.example" "$ROOT/.env"
  exit 1
fi

# -----------------------------------------------------------------------------
# Backend: dizinler, izinler, bağımlılık, migration, build, PM2
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[3/6] Backend işlemleri...${NC}"
cd "$ROOT/backend"

mkdir -p uploads backups logs
chmod 755 uploads backups logs 2>/dev/null || true

npm ci --legacy-peer-deps --prefer-offline --no-audit

echo "Migration çalıştırılıyor..."
npm run migration:run

echo "Backend build..."
npm run build

echo "PM2 güncelleniyor..."
pm2 reload ecosystem.config.js --env production 2>/dev/null || pm2 start ecosystem.config.js --env production

cd "$ROOT"

# -----------------------------------------------------------------------------
# Frontend: bağımlılık, build (root .env envDir ile okunur)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[4/6] Frontend işlemleri...${NC}"
cd "$ROOT/frontend"

npm ci --legacy-peer-deps --prefer-offline --no-audit
npm run build

cd "$ROOT"

# -----------------------------------------------------------------------------
# Frontend dağıtım dizini (Nginx root = frontend/dist)
# -----------------------------------------------------------------------------
echo -e "${YELLOW}[5/6] Build çıktısı kontrol ediliyor...${NC}"
if [ ! -d "$ROOT/frontend/dist" ] || [ -z "$(ls -A "$ROOT/frontend/dist" 2>/dev/null)" ]; then
  echo -e "${RED}HATA: frontend/dist boş veya yok. Frontend build başarısız olmuş olabilir.${NC}"
  exit 1
fi

echo -e "${GREEN}[6/6] Deploy tamamlandı.${NC}"
echo ""
echo "  Backend:  node backend/dist/main.js (PM2 ile çalışıyor)"
echo "  Frontend: frontend/dist (Nginx Web directory olarak ayarlayın)"
echo "  Nginx:    location /api -> proxy_pass http://127.0.0.1:\${PORT:-4100};"
echo ""
