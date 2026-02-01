#!/bin/bash

# Bağlantı Test Scripti

echo "🔍 DepoPazar Bağlantı Testleri"
echo ""

# 1. Vite dev server testi
echo "1. Vite dev server testi..."
if curl -s http://localhost:3180 > /dev/null 2>&1; then
    echo "   ✅ Vite dev server çalışıyor (port 3180)"
else
    echo "   ❌ Vite dev server çalışmıyor"
    echo "      Çözüm: cd frontend && npm run dev"
fi

# 2. Backend API testi
echo "2. Backend API testi..."
if curl -s http://localhost:4100/api > /dev/null 2>&1; then
    echo "   ✅ Backend API çalışıyor (port 4100)"
else
    echo "   ❌ Backend API çalışmıyor"
    echo "      Çözüm: cd backend && npm run start:dev"
fi

# 3. Valet link testi
echo "3. Valet link testi..."
if valet links 2>/dev/null | grep -q "depotakip-v1"; then
    echo "   ✅ Valet link oluşturulmuş"
else
    echo "   ❌ Valet link oluşturulmamış"
    echo "      Çözüm: valet link depotakip-v1"
fi

# 4. Nginx yapılandırması testi
echo "4. Nginx yapılandırması testi..."
if [ -f ~/.config/valet/Nginx/depotakip-v1.test ]; then
    echo "   ✅ Nginx yapılandırma dosyası mevcut"
else
    echo "   ❌ Nginx yapılandırma dosyası bulunamadı"
    echo "      Çözüm: cp valet-dev.conf ~/.config/valet/Nginx/depotakip-v1.test"
fi

# 5. Domain testi
echo "5. Domain testi..."
if curl -s http://depotakip-v1.test > /dev/null 2>&1; then
    echo "   ✅ Domain erişilebilir: http://depotakip-v1.test"
else
    echo "   ❌ Domain erişilemiyor"
    echo "      Çözüm: valet restart"
fi

echo ""
echo "📋 Test tamamlandı!"
