#!/bin/bash

# DepoPazar Valet Kurulum Scripti
# Bu script'i çalıştırmak için: bash setup-valet.sh

echo "🚀 DepoPazar Valet Kurulumu Başlatılıyor..."

# 1. Valet link oluştur
echo "📌 Valet link oluşturuluyor..."
cd /Users/erkanulker/depopazar
valet link depotakip-v1

# 2. Nginx yapılandırmasını kopyala
echo "📝 Nginx yapılandırması güncelleniyor..."
CONFIG_FILE="$HOME/.config/valet/Nginx/depotakip-v1.test"

if [ -f "$CONFIG_FILE" ]; then
    cp valet-dev.conf "$CONFIG_FILE"
    echo "✅ Nginx yapılandırması güncellendi: $CONFIG_FILE"
else
    echo "⚠️  Nginx yapılandırma dosyası bulunamadı: $CONFIG_FILE"
    echo "   Valet link komutunu manuel olarak çalıştırın: valet link depotakip-v1"
fi

# 3. Valet'i yeniden başlat
echo "🔄 Valet yeniden başlatılıyor..."
valet restart

echo ""
echo "✅ Kurulum tamamlandı!"
echo ""
echo "📋 Sonraki adımlar:"
echo "1. Backend'i başlatın: cd backend && npm run start:dev"
echo "2. Frontend'i başlatın: cd frontend && npm run dev"
echo "3. Tarayıcıda açın: http://depotakip-v1.test"
echo ""
