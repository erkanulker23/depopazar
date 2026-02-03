#!/bin/bash
# Valet sorunu düzeltme (yerel ortam). Proje kökü: scripts/ bir üst dizin.
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "🔧 Valet sorunu düzeltiliyor..."
echo ""

if ! grep -q "depotakip-v1.test" /etc/hosts 2>/dev/null; then
  echo "1. Domain hosts dosyasında yok. Manuel ekleyin: 127.0.0.1 depotakip-v1.test"
else
  echo "1. ✅ Domain hosts dosyasında mevcut"
fi

if valet links 2>/dev/null | grep -q "depotakip-v1"; then
  echo "2. ✅ Valet link mevcut"
else
  echo "2. Valet link oluşturun: cd $ROOT && valet link depotakip-v1"
fi

CONFIG_FILE="$HOME/.config/valet/Nginx/depotakip-v1.test"
VALET_CONF="$ROOT/scripts/valet-dev.conf"
if [ -f "$VALET_CONF" ]; then
  mkdir -p "$(dirname "$CONFIG_FILE")"
  cp "$VALET_CONF" "$CONFIG_FILE"
  echo "3. ✅ Nginx yapılandırması güncellendi"
else
  echo "3. scripts/valet-dev.conf bulunamadı"
fi

echo ""
echo "Sonraki: valet restart  ve  ./run-all.sh"
echo ""
