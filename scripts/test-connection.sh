#!/bin/bash
# PHP uygulaması ve Valet bağlantı testi
echo "🔍 DepoPazar bağlantı testleri"
echo ""

# Valet ile çalışıyorsa site adresi üzerinden test et
if valet links 2>/dev/null | grep -q "depotakip-v1\|depopazar"; then
  if curl -s -o /dev/null -w "%{http_code}" "http://depotakip-v1.test" 2>/dev/null | grep -q "200\|302"; then
    echo "   ✅ Site yanıt veriyor (Valet)"
  else
    echo "   ❌ Site yanıt vermiyor"
  fi
else
  echo "   ⚠️  Valet link yok; php-app/public için Valet/Laragon veya php -S kullanın"
fi
echo ""
