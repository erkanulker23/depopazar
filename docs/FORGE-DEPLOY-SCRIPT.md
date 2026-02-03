# Forge / AWA – Deploy script ve yeni kurulum

**Kök deploy script:** Proje kökündeki `deploy.sh` Forge “Quick Deploy” ile uyumludur. Tek `.env` dosyası proje kökünde olmalı; backend ve frontend bu dosyadan beslenir. Script gerekli dizinleri (backend/uploads, backend/backups, backend/logs) oluşturur ve izinleri ayarlar.

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
3. **Nginx:** Forge’da **Site → Nginx → Edit** ile `server { ... }` bloğunun **içine** aşağıdakileri ekleyin veya mevcut `location /` ile değiştirin:

   - **SPA fallback:** `/staff`, `/services`, `/dashboard` vb. doğrudan açıldığında 404 vermemesi için `location /` mutlaka `try_files $uri $uri/ /index.html;` içermeli.
   - **API proxy:** `/api` istekleri Node’a yönlendirilmeli.

```nginx
# 1) Backend API proxy (location / bloğundan önce olmalı)
location /api {
    proxy_pass http://127.0.0.1:4100;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

# 2) SPA fallback: /staff, /services, /dashboard vb. doğrudan URL’ler index.html dönsün (404 vermesin)
location / {
    try_files $uri $uri/ /index.html;
}
```

4. **Logları nerede görürsünüz?**  
   Forge’daki **“Site Log”** genelde Nginx/PHP logudur; Node uygulaması oraya yazmaz. Backend hatalarını görmek için: **Settings → Processes** → ilgili process’in yanındaki **“Log”** (veya “View log”). Backend stdout/stderr orada görünür.

5. Deploy’u tekrar çalıştırın; ardından **Processes** ekranından Node process’ini **Restart** edin.

---

## EADDRINUSE: Port 4100 zaten kullanımda

Bu hata, **aynı anda birden fazla backend process** çalıştığında veya restart sırasında eski process portu hemen bırakmadığında oluşur.

**Yapılacaklar:**

1. **Tek process manager kullanın.** Backend’i **ya** Forge Daemon **ya** PM2 ile çalıştırın, ikisini birlikte kullanmayın. Forge kullanıyorsanız deploy script’te `pm2` komutları olmamalı; sadece Forge’daki **Processes → Daemon** ile `node backend/dist/main.js` çalışsın.
2. **Portu kullanan process’i durdurun.** Sunucuda SSH ile:
   ```bash
   # 4100 portunu kullanan process’i bulup kapatın
   sudo lsof -i :4100
   # veya
   sudo fuser -k 4100/tcp
   ```
   Sonra Forge’da **Processes** ekranından Node process’ini **Start** edin (tek bir tane olsun).
3. **Restart sırasında:** Forge’da önce **Stop**, birkaç saniye bekleyin, sonra **Start** yapın. Uygulama artık SIGTERM alınca graceful shutdown yapıyor; port daha hızlı serbest kalır.

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
