#!/bin/bash
# DepoPazar – Servisleri durdur (scripts/stop-all.sh’i çağırır)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
exec "$SCRIPT_DIR/scripts/stop-all.sh" "$@"
