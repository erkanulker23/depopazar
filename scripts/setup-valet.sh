#!/bin/bash
# depo-v1.test -> PHP uygulaması (php-app/public)
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PUBLIC="$ROOT/php-app/public"

if ! command -v valet &>/dev/null; then
  echo "Hata: Valet yüklü değil. Önce: composer global require laravel/valet && valet install"
  exit 1
fi

# Eski Nginx config'i kaldır (varsa)
rm -f "$HOME/.config/valet/Nginx/depotakip-v1.test" 2>/dev/null || true
rm -f "$HOME/.config/valet/Nginx/depo-v1.test" 2>/dev/null || true

valet unlink depotakip-v1 2>/dev/null || true

cd "$PUBLIC"
valet link depo-v1
valet secure depo-v1 2>/dev/null || true
valet restart

echo ""
echo "OK: https://depo-v1.test -> $PUBLIC (SSL açık)"
echo "   Veritabanı: $ROOT/php-app/config/db.local.php"
echo ""
echo "Çalışmazsa:"
echo "  1. Tarayıcıda https://depo-v1.test/giris deneyin"
echo "  2. valet links ile linkin listelendiğini kontrol edin"
echo "  3. PHP driver: $PUBLIC/LocalValetDriver.php (BasicValetDriver kullanıyor)"
