#!/bin/bash
# DepoPazar – Tüm servisleri durdur
# Kullanım: ./scripts/stop-all.sh veya proje kökünden ./stop-all.sh

echo "🛑 DepoPazar servisleri durduruluyor..."

for pidfile in /tmp/depopazar-backend.pid /tmp/depopazar-frontend.pid; do
  if [ -f "$pidfile" ]; then
    pid=$(cat "$pidfile")
    if kill -0 "$pid" 2>/dev/null; then
      kill "$pid" 2>/dev/null
      echo "✅ Process durduruldu (PID: $pid)"
    fi
    rm -f "$pidfile"
  fi
done

pkill -f "nest start --watch" 2>/dev/null
pkill -f "vite" 2>/dev/null
echo "✅ Tüm servisler durduruldu"
