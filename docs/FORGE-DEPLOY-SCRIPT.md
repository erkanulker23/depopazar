# Forge / AWA – Deploy script ve yeni kurulum

## Yeni site kurulumu (ilk kez)

1. **Site oluştur:** Static → Other (veya HTML).
2. **Settings → General**
   - Root directory: **boş** (veya `/`)
   - Web directory: **`frontend/dist`**
3. **Settings → Deployments → Deploy script:** Aşağıdaki script’i yapıştırın; `general.awapanel.com` yerine kendi site yolunuzu yazın.
4. **Settings → Environment:** Proje kökündeki `.env` buradan düzenlenir. `.env.example` içeriğini kopyalayıp **DB_USERNAME**, **DB_PASSWORD**, **JWT_SECRET**, **JWT_REFRESH_SECRET** doldurun; **NODE_ENV=production**, **SWAGGER_ENABLED=false** olsun.
5. **Settings → Processes → Add process:** Komut `node backend/dist/main.js`, çalışma dizini site kökü (örn. `/home/forge/general.awapanel.com`).
6. İlk deploy’u çalıştırın; deploy bittikten sonra Node process’i **Restart** edin.

---

## Deploy script (kopyala-yapıştır)

`/home/forge/general.awapanel.com` yerine kendi site yolunuzu yazın.

```bash
cd /home/forge/general.awapanel.com
git pull origin $FORGE_SITE_BRANCH

# Backend
cd backend
npm ci --legacy-peer-deps
npm run migration:run
npm run build
cd ..

# Frontend
cd frontend
npm ci --legacy-peer-deps
npm run build
cd ..
```

Deploy sonrası **Processes** ekranından Node process’ini **Restart** edin (Supervisor otomatik restart etmiyorsa).
