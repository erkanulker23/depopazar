#!/bin/bash

# Valet Sorunu Düzeltme Scripti

echo "🔧 Valet Sorunu Düzeltiliyor..."
echo ""

# 1. Hosts dosyasına domain ekle
echo "1. Hosts dosyasına domain ekleniyor..."
if ! grep -q "depotakip-v1.test" /etc/hosts; then
    echo "   ⚠️  Domain hosts dosyasında yok"
    echo "   📝 Manuel olarak eklemeniz gerekiyor:"
    echo "      sudo nano /etc/hosts"
    echo "      Şu satırı ekleyin: 127.0.0.1 depotakip-v1.test"
else
    echo "   ✅ Domain hosts dosyasında mevcut"
fi

# 2. Valet link kontrolü
echo ""
echo "2. Valet link kontrolü..."
if valet links 2>/dev/null | grep -q "depotakip-v1"; then
    echo "   ✅ Valet link mevcut"
else
    echo "   ⚠️  Valet link oluşturulmamış"
    echo "   📝 Şu komutu çalıştırın (sudo şifresi isteyecek):"
    echo "      cd /Users/erkanulker/depopazar"
    echo "      valet link depotakip-v1"
fi

# 3. Nginx yapılandırması kontrolü
echo ""
echo "3. Nginx yapılandırması kontrolü..."
CONFIG_FILE="$HOME/.config/valet/Nginx/depotakip-v1.test"
if [ -f "$CONFIG_FILE" ]; then
    echo "   ✅ Nginx yapılandırma dosyası mevcut"
    echo "   📍 Konum: $CONFIG_FILE"
    
    # Yapılandırmanın doğru olup olmadığını kontrol et
    if grep -q "proxy_pass http://127.0.0.1:3180" "$CONFIG_FILE"; then
        echo "   ✅ Yapılandırma doğru görünüyor"
    else
        echo "   ⚠️  Yapılandırma güncelleniyor..."
        cp /Users/erkanulker/depopazar/valet-dev.conf "$CONFIG_FILE"
        echo "   ✅ Yapılandırma güncellendi"
    fi
else
    echo "   ⚠️  Nginx yapılandırma dosyası bulunamadı"
    echo "   📝 Oluşturuluyor..."
    mkdir -p "$HOME/.config/valet/Nginx"
    cp /Users/erkanulker/depopazar/valet-dev.conf "$CONFIG_FILE"
    echo "   ✅ Yapılandırma dosyası oluşturuldu"
fi

# 4. Valet restart önerisi
echo ""
echo "4. Valet yeniden başlatma..."
echo "   📝 Şu komutu çalıştırın:"
echo "      valet restart"
echo ""

# 5. Alternatif çözüm: Vite base URL
echo "5. Alternatif Çözüm (Valet olmadan):"
echo "   Eğer Valet kullanmak istemiyorsanız:"
echo "   - Frontend: http://localhost:3180"
echo "   - Backend: http://localhost:4100/api"
echo ""

echo "✅ Kontrol tamamlandı!"
echo ""
echo "📋 Sonraki adımlar:"
echo "1. Hosts dosyasına domain ekleyin (sudo gerekli)"
echo "2. Valet link oluşturun: valet link depotakip-v1"
echo "3. Valet'i yeniden başlatın: valet restart"
echo "4. Servisleri başlatın: bash run-all.sh"
echo ""
