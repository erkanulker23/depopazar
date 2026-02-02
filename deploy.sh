#!/bin/bash

# Renkli çıktılar için
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}Deploy işlemi başlatılıyor...${NC}"

# Hata durumunda durdur
set -e

# 1. Kodları güncelle
echo -e "${YELLOW}Kodlar güncelleniyor...${NC}"
git fetch origin
git reset --hard origin/main # main veya master, projenizin ana dalı
# git pull origin main

# 2. Backend İşlemleri
echo -e "${YELLOW}Backend işlemleri yapılıyor...${NC}"
cd backend

# .env kontrolü
if [ ! -f .env ]; then
    echo -e "${RED}UYARI: backend/.env dosyası bulunamadı! .env.example kopyalanıyor...${NC}"
    cp .env.example .env
    echo -e "${YELLOW}Lütfen .env dosyasını düzenleyin ve tekrar çalıştırın.${NC}"
    # exit 1 # İlk kurulumda durmasını isterseniz açın
fi

# Dizinleri oluştur
mkdir -p uploads backups
chmod 755 uploads backups 2>/dev/null || true

echo "Bağımlılıklar yükleniyor..."
npm ci --legacy-peer-deps # install yerine ci daha güvenli, lock dosyasını kullanır

echo "Build alınıyor..."
npm run build

echo "Veritabanı migrasyonları çalıştırılıyor..."
npm run migration:run

echo "PM2 process'leri güncelleniyor..."
# pm2 start ecosystem.config.js --env production # İlk kez çalıştırıyorsanız
pm2 reload ecosystem.config.js --env production || pm2 start ecosystem.config.js --env production

cd ..

# 3. Frontend İşlemleri
echo -e "${YELLOW}Frontend işlemleri yapılıyor...${NC}"
cd frontend

# .env kontrolü
if [ ! -f .env ]; then
    echo -e "${RED}UYARI: frontend/.env dosyası bulunamadı! .env.example kopyalanıyor...${NC}"
    cp .env.example .env
    echo -e "${YELLOW}Lütfen frontend/.env dosyasını düzenleyin.${NC}"
fi

echo "Bağımlılıklar yükleniyor..."
npm ci --legacy-peer-deps

echo "Build alınıyor..."
npm run build

echo -e "${GREEN}Deploy başarıyla tamamlandı!${NC}"
