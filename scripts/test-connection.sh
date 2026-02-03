#!/bin/bash
# Yerel API ve frontend bağlantı testi
echo "🔍 DepoPazar bağlantı testleri"
echo ""

for port in 3180 4100; do
  if curl -s "http://localhost:$port" > /dev/null 2>&1 || curl -s "http://localhost:$port/api" > /dev/null 2>&1; then
    echo "   ✅ localhost:$port yanıt veriyor"
  else
    echo "   ❌ localhost:$port yanıt vermiyor"
  fi
done

if valet links 2>/dev/null | grep -q "depotakip-v1"; then
  echo "   ✅ Valet link: depotakip-v1.test"
else
  echo "   ⚠️  Valet link yok (opsiyonel)"
fi
echo ""
