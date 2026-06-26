#!/bin/bash
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CONFIG_FILE="$HOME/.config/valet/Nginx/depo-v1.test"

echo "Valet: depo-v1 -> PHP app (php-app/public)"

valet unlink depotakip-v1 2>/dev/null || true
valet unlink depo-v1 2>/dev/null || true
rm -f "$HOME/.config/valet/Nginx/depotakip-v1.test" "$CONFIG_FILE"
cd "$ROOT/php-app/public" && valet link depo-v1
valet secure depo-v1 2>/dev/null || true
valet restart

echo "Tamam. Ac: https://depo-v1.test"
echo "DB icin: php-app/config/db.local.php olustur (db.local.php.example kopyala)"
