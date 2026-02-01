#!/bin/bash

# DepoPazar - Tüm Servisleri Durdurma Scripti

echo "🛑 DepoPazar Servisleri Durduruluyor..."

# Backend'i durdur
if [ -f /tmp/depopazar-backend.pid ]; then
    BACKEND_PID=$(cat /tmp/depopazar-backend.pid)
    if kill -0 $BACKEND_PID 2>/dev/null; then
        kill $BACKEND_PID
        echo "✅ Backend durduruldu (PID: $BACKEND_PID)"
    fi
    rm -f /tmp/depopazar-backend.pid
fi

# Frontend'i durdur
if [ -f /tmp/depopazar-frontend.pid ]; then
    FRONTEND_PID=$(cat /tmp/depopazar-frontend.pid)
    if kill -0 $FRONTEND_PID 2>/dev/null; then
        kill $FRONTEND_PID
        echo "✅ Frontend durduruldu (PID: $FRONTEND_PID)"
    fi
    rm -f /tmp/depopazar-frontend.pid
fi

# Node process'leri temizle
pkill -f "npm run start:dev" 2>/dev/null
pkill -f "npm run dev" 2>/dev/null
pkill -f "vite" 2>/dev/null

echo "✅ Tüm servisler durduruldu"
