#!/bin/bash

# Valet'i yeniden başlat ve yapılandırmayı kontrol et

echo "🔄 Valet yeniden başlatılıyor..."
valet restart

echo ""
echo "✅ Valet yeniden başlatıldı!"
echo ""
echo "📋 Kontrol:"
echo "1. Vite dev server çalışıyor mu? (http://localhost:3180)"
echo "2. Backend çalışıyor mu? (http://localhost:4100/api)"
echo "3. Site açılıyor mu? (https://depotakip-v1.test)"
echo ""
