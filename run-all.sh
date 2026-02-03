#!/bin/bash
# DepoPazar – Servisleri başlat (scripts/run-all.sh’i çağırır)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
exec "$SCRIPT_DIR/scripts/run-all.sh" "$@"
