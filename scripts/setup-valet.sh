#!/bin/bash
# Valet kurulumu (yerel ortam). Proje kökü: scripts/ bir üst dizin.
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "🚀 DepoPazar Valet kurulumu..."

cd "$ROOT"
valet link depotakip-v1

CONFIG_FILE="$HOME/.config/valet/Nginx/depotakip-v1.test"
VALET_CONF="$ROOT/scripts/valet-dev.conf"
if [ -f "$VALET_CONF" ]; then
  cp "$VALET_CONF" "$CONFIG_FILE"
  echo "✅ Nginx yapılandırması kopyalandı"
fi

valet restart
echo "✅ Kurulum tamamlandı. Backend: cd backend && npm run start:dev  |  Frontend: cd frontend && npm run dev"
echo ""
