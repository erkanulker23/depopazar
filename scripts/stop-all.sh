#!/bin/bash
# DepoPazar – Yerel servisleri durdur (eski backend/frontend pid dosyaları varsa temizle)
# Kullanım: ./scripts/stop-all.sh

echo "🛑 DepoPazar servisleri durduruluyor..."

for pidfile in /tmp/depopazar-backend.pid /tmp/depopazar-frontend.pid; do
  if [ -f "$pidfile" ]; then
    pid=$(cat "$pidfile")
    if kill -0 "$pid" 2>/dev/null; then
      kill "$pid" 2>/dev/null
      echo "   Process durduruldu (PID: $pid)"
    fi
    rm -f "$pidfile"
  fi
done

if command -v docker &> /dev/null; then
  ROOT="$(cd "$(dirname "$0")/.." && pwd)"
  cd "$ROOT" && docker compose stop mysql redis 2>/dev/null && echo "   Docker (mysql, redis) durduruldu" || true
fi

echo "✅ Tamamlandı"
