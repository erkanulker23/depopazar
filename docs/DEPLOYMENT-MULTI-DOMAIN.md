# DepoPazar – Çoklu Domain / Subdomain, Veri Tabanı İzolasyonu ve Kesintisiz Deploy

## 1. Amaç ve Kurallar

- **Her domain / subdomain = ayrı proje = ayrı veri tabanı.** Tablo paylaşımı, veri erişimi ve log karışması yok.
- Kod içinde domain, subdomain veya DB bilgisi **hardcoded olmaz**; tümü `.env` üzerinden okunur.
- **synchronize: true** production’da kesinlikle kullanılmaz; sadece migration kullanılır.

## 2. Domain – Veri Tabanı Eşleşmesi

| Örnek domain / subdomain   | Örnek DB adı           |
|---------------------------|------------------------|
| firma1.depopazar.com      | depopazar_firma1       |
| firma2.depopazar.com      | depopazar_firma2       |
| depopazar-firma3.com      | depopazar_firma3       |

Her kurulum yalnızca kendi `.env` içindeki `DB_DATABASE` ile tek bir veri tabanına bağlanır.

## 3. Her Deploy İçin Zorunlu .env (Backend)

Backend için `backend/.env` her domain/subdomain için ayrı oluşturulmalıdır. Şablon: `backend/.env.example`.

**Önemli:** `NODE_ENV=production` **olmalı**. `NODE_ENV=development` iken uygulama DB bilgisi .env’de olmasa bile **varsayılan değerlerle** (127.0.0.1, 3307, depopazar vb.) bağlanır; bu yüzden .env’i değiştirseniz veya DB satırlarını silseniz bile proje çalışmaya devam edebilir. Canlıda mutlaka `NODE_ENV=production` ve tüm `DB_*` değişkenlerini tanımlayın.

Zorunlu alanlar (production):

- `NODE_ENV=production`
- `APP_NAME`, `APP_ENV`, `APP_DOMAIN`, `APP_URL`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET`, `JWT_REFRESH_SECRET`
- `FRONTEND_URL` veya `CORS_ORIGINS` (CORS izinleri)

### Laravel Forge – Tek .env ve Root directory

Proje **tek bir .env** kullanır: **proje kökü** (backend ve frontend klasörlerinin bulunduğu dizin), örn. `/home/forge/general.awapanel.com/.env`.

- **Forge → Settings → General**
  - **Root directory:** `backend` değil, **boş bırakın** (veya `/`) — site kökü = proje kökü.
  - **Web directory (public):** `frontend/dist` — Nginx React build’i buradan sunar; **backend veya backend/public olmamalı.**
- **Background process (Supervisor):** Komut `node backend/dist/main.js`, çalışma dizini proje kökü (örn. `/home/forge/general.awapanel.com`).
- **Environment:** Proje kökündeki tek .env buradan düzenlenir.

Canlıda **mutlaka** şunları ekleyin; aksi halde uygulama `NODE_ENV=development` sayılıp varsayılan DB ile çalışır:

- `NODE_ENV=production`
- `DB_HOST=...`, `DB_PORT=3306`, `DB_DATABASE=...`, `DB_USERNAME=...`, `DB_PASSWORD=...`

Örnek (general.awapanel.com):

```env
APP_NAME=GeneralDepo
APP_ENV=production
APP_DOMAIN=general.awapanel.com
APP_URL=https://general.awapanel.com
NODE_ENV=production
PORT=4100
FRONTEND_URL=https://general.awapanel.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=general
DB_USERNAME=forge
DB_PASSWORD=...

JWT_SECRET=...
JWT_REFRESH_SECRET=...
JWT_EXPIRES_IN=1d
JWT_REFRESH_EXPIRES_IN=7d

SWAGGER_ENABLED=false
```

**Not:** .env değiştirdikten sonra değişikliğin uygulanması için **deploy tekrar çalıştırılmalı** veya sunucuda `pm2 restart all` yapılmalıdır. Uygulama açılışında logda `[env] NODE_ENV=... DB_DATABASE=...` satırı görünür; hangi değerlerin yüklendiğini buradan kontrol edebilirsiniz.

## 4. Backend (NestJS) Özeti

- **Database modülü:** Tüm DB bilgisi `ConfigService` (yani `.env`) üzerinden; production’da default yok, eksik env’de uygulama başlamaz.
- **Migration:** TypeORM migration zorunlu; deploy sırasında `npm run migration:run` çalıştırılır.
- **CORS:** `FRONTEND_URL` veya `CORS_ORIGINS` ile ayarlanır; origin’ler kod içinde sabit değildir.

## 5. PM2 – Process ve Log İzolasyonu

- Backend, PM2 ile tek instance (daemon) olarak çalışır.
- `ecosystem.config.js` içinde uygulama adı ve log dosyaları `APP_DOMAIN` / `.env` ile türetilir:
  - Her kurulumun ayrı process adı: `depopazar-<domain>`
  - Loglar: `backend/logs/depopazar-<domain>-out.log`, `-error.log`
- Aynı sunucuda birden fazla kurulum varsa her biri kendi dizininde kendi `.env` ve kendi PM2 process’i ile çalıştırılır.

## 6. Kesintisiz Deploy Akışı

`deploy.sh` sırası:

1. (İsteğe bağlı) Kod güncelleme (`SKIP_GIT=1` ile atlanabilir)
2. Backend:
   - Production’da `.env` yoksa script hata verir ve çıkar.
   - `npm ci` → **migration:run** → **build** → **pm2 reload**
3. Migration veya build hata verirse `reload` yapılmaz; mevcut process çalışmaya devam eder (zero-downtime).
4. Frontend: Kendi `.env` (özellikle `VITE_API_URL`) ile build alınır.

İlk kurulumda: `pm2 start ecosystem.config.js --env production`. Sonraki deploy’larda: `pm2 reload ...`.

## 7. Frontend – Domain Bazlı API

- API adresi: build zamanında `VITE_API_URL` veya çalışma zamanında `window.location.origin + '/api'`.
- Her domain’in frontend build’i, yalnızca o domain’in backend’ine istek atacak şekilde ayarlanmalı (aynı domain’de backend `/api` altındaysa `VITE_API_URL` boş bırakılabilir).

## 8. Background İşler (Mail, SMS, Ödeme, Rapor)

İş emrinde istenen: Mail, SMS, ödeme callback’leri, rapor hesaplamaları ve zamanlanmış görevler ana request lifecycle’dan ayrılmalı; hata alsa bile ana uygulamayı kilitlememeli.

Mevcut yapı: Bu işler doğrudan controller/service içinde senkron tetikleniyor. İleride:

- Kuyruk (örn. Bull + Redis) veya
- Ayrı worker process’ler

ile ana uygulamadan ayrılabilir. Bu doküman sadece gereksinimi kayıt altına alır; implementasyon aşamasında kuyruk/worker tasarımı yapılmalıdır.

## 9. Canlı Sistemde Veri Tabanı Değişiklikleri

- Var olan kolon **silinmez**, geriye dönük **uyumsuz değiştirilmez**.
- Yeni alanlar **nullable** veya **default değerli** eklenir.
- Yeni deploy’lar eski verilerle uyumlu olmalı; boş alanlara toleranslı olunmalı.

## 10. Test Senaryoları (Kabul Kriterleri)

1. **Aynı sunucuda 2 subdomain, 2 DB**
   - İki ayrı dizin (veya aynı repo, iki farklı `.env`) ile iki ayrı PM2 process.
   - Her biri farklı `DB_DATABASE` kullanır; birinde yapılan veri değişikliği diğerinde görünmez.

2. **Bir subdomain kapatıldığında**
   - `pm2 stop depopazar-firma1` vb. ile bir process durdurulduğunda diğer subdomain’in process’i etkilenmez ve çalışmaya devam eder.

3. **Migration izolasyonu**
   - Bir projede `migration:run` çalıştırıldığında sadece o projenin `.env`’indeki veri tabanı güncellenir; diğer projelerin DB’leri etkilenmez.

4. **Deploy kesintisi**
   - Migration/build hata verdiğinde PM2 reload çalışmaz; mevcut sürüm aynen çalışır.

Bu şartlar sağlanmadan çoklu domain / izolasyon / kesintisiz deploy tamamlanmış kabul edilmemelidir.
