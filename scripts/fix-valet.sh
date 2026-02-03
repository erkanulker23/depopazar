#!/bin/bash
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CONFIG_FILE="$HOME/.config/valet/Nginx/depotakip-v1.test"

echo "Valet: depotakip-v1 -> PHP app (php-app/public)"

valet unlink depotakip-v1 2>/dev/null || true
rm -f "$CONFIG_FILE"
cd "$ROOT/php-app/public" && valet link depotakip-v1
valet restart

echo "Tamam. Ac: http://depotakip-v1.test"
echo "DB icin: php-app/config/db.local.php olustur (db.local.php.example kopyala)"
