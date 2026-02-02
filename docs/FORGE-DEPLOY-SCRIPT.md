# Forge / AWA – Deploy script ve yeni kurulum

## 404 (general.awapanel.com/login) ve boş log çözümü

Bu belgedeki adımlar **mutlaka** uygulanmalı; aksi halde `/login` 404 verir ve Site Log boş kalır.

1. **Settings → General**
   - **Web directory:** `/public` **olmasın.** Değeri **`frontend/dist`** yapın (projede `public` yok; React build `frontend/dist` içindedir).
   - **Root directory:** Boş bırakın (veya `/`) — site kökü = proje kökü.
2. **Settings → Processes**
   - **“No background processes yet”** ise **“New Daemon”** ile ekleyin:
   - **Command:** `node backend/dist/main.js`
   - **Directory:** Site kökü (örn. `/home/forge/general.awapanel.com`)
   - Kaydedip process’i **Start** edin.
3. **Nginx:** `/api` isteklerinin Node’a gitmesi için Forge’da **Site → Nginx → Edit** ile aşağıdaki **Custom Nginx Configuration** snippet’ini `server { ... }` bloğunun **içine** ekleyin (genelde `location /` bloğundan **önce**):

```nginx
# Backend API proxy (location / bloğundan önce ekleyin)
location /api {
    proxy_pass http://127.0.0.1:4100;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

4. **Logları nerede görürsünüz?**  
   Forge’daki **“Site Log”** genelde Nginx/PHP logudur; Node uygulaması oraya yazmaz. Backend hatalarını görmek için: **Settings → Processes** → ilgili process’in yanındaki **“Log”** (veya “View log”). Backend stdout/stderr orada görünür.

5. Deploy’u tekrar çalıştırın; ardından **Processes** ekranından Node process’ini **Restart** edin.

---

## Yeni site kurulumu (ilk kez)

1. **Site oluştur:** Static → Other (veya HTML).
2. **Settings → General**
   - Root directory: **boş** (veya `/`)
   - Web directory: **`frontend/dist`**
3. **Settings → Deployments → Deploy script:** Aşağıdaki script’i yapıştırın; `general.awapanel.com` yerine kendi site yolunuzu yazın.
4. **Settings → Environment:** Proje kökündeki `.env` buradan düzenlenir. `.env.example` içeriğini kopyalayıp **DB_USERNAME**, **DB_PASSWORD**, **JWT_SECRET**, **JWT_REFRESH_SECRET** doldurun; **NODE_ENV=production**, **SWAGGER_ENABLED=false** olsun.
5. **Settings → Processes → New Daemon:** Komut `node backend/dist/main.js`, çalışma dizini site kökü (örn. `/home/forge/general.awapanel.com`). Kaydedip **Start** edin.
6. **Nginx:** Yukarıdaki “Custom Nginx Configuration” snippet’ini ekleyin.
7. İlk deploy’u çalıştırın; deploy bittikten sonra Node process’i **Restart** edin.

---

## Deploy script (kopyala-yapıştır) – paralel, daha hızlı

`/home/forge/general.awapanel.com` yerine kendi site yolunuzu yazın.

- **Backend ve frontend** bağımlılık kurulumu ve build’i **paralel** çalışır; süre belirgin şekilde kısalır.
- `--prefer-offline --no-audit` ile npm biraz daha hızlanır.

```bash
set -e
cd /home/forge/general.awapanel.com
git pull origin $FORGE_SITE_BRANCH

ROOT="$PWD"

# 1) Paralel: backend ve frontend bağımlılıkları
(cd "$ROOT/backend"   && npm ci --legacy-peer-deps --prefer-offline --no-audit) &
(cd "$ROOT/frontend"  && npm ci --legacy-peer-deps --prefer-offline --no-audit) &
wait

# 2) Migration (sadece backend)
(cd "$ROOT/backend" && npm run migration:run)

# 3) Paralel: backend ve frontend build
(cd "$ROOT/backend"  && npm run build) &
(cd "$ROOT/frontend" && npm run build) &
wait
```

Deploy sonrası **Processes** ekranından Node process’ini **Restart** edin (Supervisor otomatik restart etmiyorsa).

---

### İsteğe bağlı: Daha da hızlı (npm install)

Bağımlılık listesini nadiren değiştiriyorsanız, `npm ci` yerine `npm install` kullanabilirsiniz: önceki `node_modules` silinmez, sadece değişen paketler güncellenir; deploy daha kısa sürer. Trade-off: `package-lock.json` ile tam uyum garisi `npm ci` kadar güçlü olmaz.

```bash
# Yukarıdaki script’te sadece bu iki satırı değiştirin:
(cd "$ROOT/backend"   && npm install --legacy-peer-deps --no-audit) &
(cd "$ROOT/frontend"  && npm install --legacy-peer-deps --no-audit) &
```
