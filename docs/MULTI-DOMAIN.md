# Çoklu domain: Her domain kendi veritabanı

DepoPazar **birden fazla domain’de** çalışacak şekilde tasarlanır; **her domain ayrı bir “proje”** olmalı ve **her projenin kendi veritabanı** olmalı. Örneğin:

- `https://general.awapanel.com/` → **general** veritabanı
- `https://depo.awapanel.com/` → **depo** veritabanı (farklı DB)

İki domain **aynı veritabanını** kullanıyorsa bunun nedeni Forge’da **tek site** ile yayına alınmalarıdır: aynı dizin, aynı `.env`, aynı Node process → aynı DB.

---

## “İki domain ayrı yerde yayınlıyorum ama ikisi aynı DB kullanıyor; env boş olsa da çalışıyor”

Bu durum şu anlama gelir:

1. **general.awapanel.com** ve **depo.awapanel.com** Forge’da **tek site** altında (veya depo, general’a **ek domain** olarak) tanımlıdır → **tek dizin**, **tek .env**, **tek Node process**. İkisi de aynı kodu ve aynı .env’i kullandığı için **aynı veritabanına** bağlanır.
2. **“Env boş olsa da çalışıyor”** demenizin nedeni: Depo için baktığınız Forge “Environment” ekranı o site’ın **kendi** .env’ini yazıyor olabilir; ama depo **gerçekte aynı dizinden** (general’ın dizininden) servis alıyorsa, çalışan process **general’ın .env**’ini okur. Yani depo’nun Environment’ı boş olsa bile, tek process olduğu için general’ın dolu .env’i kullanılıyor; bu yüzden “env boş olsa da çalışıyor” görünür.

**Ne yapmalısınız (kısa kontrol listesi):**

| Adım | Forge’da kontrol |
|------|-------------------|
| 1 | Sol menüde **iki ayrı site** var mı? (general.awapanel.com **ve** depo.awapanel.com ayrı satırlar). Yoksa depo büyük ihtimalle general’a “ek domain” olarak eklenmiş; tek site, tek DB. |
| 2 | **depo.awapanel.com** için **ayrı bir site** oluşturun (New Site → domain: depo.awapanel.com). Böylece **ayrı dizin** olur (örn. `/home/forge/depo.awapanel.com`). |
| 3 | depo site’ı için **ayrı veritabanı** oluşturun (Databases → New Database, örn. `depo`). |
| 4 | depo site’ında **Settings → Environment**’ı doldurun: `DB_DATABASE=depo`, `DB_PORT=3306`, `DB_USERNAME`, `DB_PASSWORD`, `APP_DOMAIN=depo.awapanel.com`, `APP_URL=https://depo.awapanel.com`, `FRONTEND_URL=...`, `PORT=4101` (general 4100 kullanıyorsa). **Save** edin. |
| 5 | depo site’ında **Processes → New Daemon:** `node backend/dist/main.js`, Directory = depo site kökü. **Nginx**’te bu site için `proxy_pass http://127.0.0.1:4101;` (veya depo’nun PORT’u) kullanın. |
| 6 | depo site’ına **Deploy** atın (aynı repo, farklı dizine deploy). Artık **iki process**, **iki .env**, **iki veritabanı** olur. |

---

## İki site, iki farklı veritabanı – Environment tablosu

Sunucuda **general** ve **depo** (veya depom) veritabanları varsa, her site’ın kendi DB’sine bağlanması için Environment şöyle olmalı:

| Değişken | general.awapanel.com | depo.awapanel.com |
|----------|----------------------|-------------------|
| `APP_DOMAIN` | general.awapanel.com | depo.awapanel.com |
| `APP_URL` | https://general.awapanel.com | https://depo.awapanel.com |
| `FRONTEND_URL` | https://general.awapanel.com | https://depo.awapanel.com |
| **`DB_DATABASE`** | **general** | **depo** (veya depom – Forge’da oluşturduğunuz DB adı) |
| **`DB_USERNAME`** | general’a erişen kullanıcı (örn. forge veya general_user) | depo’ya erişen kullanıcı |
| **`DB_PASSWORD`** | Bu kullanıcının şifresi | Bu kullanıcının şifresi |
| `DB_PORT` | 3306 | 3306 |
| `PORT` | 4100 | 4101 (aynı sunucuda iki process için farklı) |

- **general** site: **Settings → Environment** → `DB_DATABASE=general`, ilgili `DB_USERNAME`/`DB_PASSWORD` → **Save** → **Processes → Restart**.
- **depo** site: **Settings → Environment** → `DB_DATABASE=depo` (veya depom), depo DB’sine erişen kullanıcı ve şifre, `PORT=4101` → **Save** → **Processes → Restart**.
- depo site **Nginx**’te `location /api` → `proxy_pass http://127.0.0.1:4101;`.

Böylece **iki site, iki farklı veritabanı** kullanır.

Özet: **Şu an tek site (veya tek dizin) iki domain’e cevap veriyor → tek DB. İkinci domain’i gerçekten ayrı proje yapmak için Forge’da ayrı site + ayrı dizin + ayrı Environment + ayrı DB + ayrı process (farklı PORT) şart.**

---

## Doğru mimari: Domain = Site = Veritabanı

| Domain | Forge site (dizin) | Environment (.env) | Veritabanı |
|--------|--------------------|--------------------|------------|
| general.awapanel.com | `/home/forge/general.awapanel.com` | Bu site’a özel | `general` (veya sizin verdiğiniz ad) |
| depo.awapanel.com | `/home/forge/depo.awapanel.com` | Bu site’a özel | `depo` (veya sizin verdiğiniz ad) |

Her domain için **ayrı Forge site**, **ayrı Environment**, **ayrı veritabanı** olmalı. Böylece her proje kendi verisini kullanır.

---

## Forge’da yapmanız gerekenler

### 1. Her domain için ayrı site

- **general.awapanel.com** zaten bir site ise onu olduğu gibi bırakın.
- **depo.awapanel.com** için Forge’da **yeni bir site** oluşturun (New Site). Domain olarak `depo.awapanel.com` yazın. **Aynı sunucuda** olabilir; önemli olan **site’ın ayrı** olması (ayrı dizin, ayrı Environment).

### 2. Her site için ayrı veritabanı

Forge’da **Databases** bölümünde:

- general.awapanel.com için bir veritabanı (örn. `general`) — zaten varsa kullanın.
- depo.awapanel.com için **yeni** bir veritabanı oluşturun (örn. `depo`). Kullanıcı adı ve şifre atayın.

### 3. Her site’ın kendi Environment’ı

- **general.awapanel.com** site’ına girip **Settings → Environment** içinde:
  - `APP_DOMAIN=general.awapanel.com`
  - `APP_URL=https://general.awapanel.com`
  - `DB_DATABASE=general` (bu site’ın veritabanı adı)
  - `DB_USERNAME=...` ve `DB_PASSWORD=...` (bu DB’ye ait)
  - `FRONTEND_URL=https://general.awapanel.com`
- **depo.awapanel.com** site’ına girip **Settings → Environment** içinde:
  - `APP_DOMAIN=depo.awapanel.com`
  - `APP_URL=https://depo.awapanel.com`
  - `DB_DATABASE=depo` (depo site’ına özel DB)
  - `DB_USERNAME=...` ve `DB_PASSWORD=...` (depo DB’ye ait)
  - `FRONTEND_URL=https://depo.awapanel.com`

Her site’ın Environment’ı **sadece kendi veritabanına** bağlanmalı; iki site aynı `DB_DATABASE` / aynı şifreyi kullanmamalı.

### 4. Her site için ayrı deploy ve process

- Her site’ın kendi **Deploy Script**’i olacak (aynı repo’yu farklı dizine deploy edebilirsiniz; script’te `cd /home/forge/SITE_DOMAIN` o site’a özel olmalı).
- Her site’ta **Processes → New Daemon:** `node backend/dist/main.js`, Directory = o site’ın kökü.  
  **Önemli:** İki site farklı port kullanmalı (örn. general → 4100, depo → 4101). Aynı sunucuda iki site varsa Nginx’te her domain’in `/api` proxy’si ilgili porta yönlendirilmeli.

---

## Port çakışması (aynı sunucuda iki site)

Aynı sunucuda birden fazla DepoPazar site’ı çalışıyorsa her biri **farklı port** dinlemeli:

- general.awapanel.com → Environment’ta `PORT=4100`
- depo.awapanel.com → Environment’ta `PORT=4101`

Her site’ın Nginx konfigürasyonunda `location /api { proxy_pass http://127.0.0.1:PORT; ... }` o site’ın portuna yönlendirilmeli (general için 4100, depo için 4101).

---

## Özet

- **Bir domain = bir Forge site = bir dizin = bir .env = bir veritabanı.**
- general.awapanel.com ve depo.awapanel.com **aynı DB’yi kullanıyorsa** büyük ihtimalle depo için ayrı site yok; depo, general’ın alias’ı olarak aynı site’a gidiyordur.
- **Çözüm:** depo.awapanel.com için **yeni Forge site** açın, **yeni veritabanı** oluşturun, bu site’ın **Environment**’ında sadece bu DB’yi (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) ve bu domain’i (`APP_DOMAIN`, `APP_URL`, `FRONTEND_URL`) kullanın. Aynı sunucudaysa port’u (örn. 4101) ve Nginx proxy’yi buna göre ayarlayın.

Detaylı Forge adımları için `docs/FORGE-DEPLOY-SCRIPT.md` dosyasına bakın.

---

## İkinci sitede (depo) ECONNREFUSED 127.0.0.1:3307 veya MODULE_NOT_FOUND

- **ECONNREFUSED 3307:** Forge’da MySQL **3306** portunda çalışır. Bu site’ın **Environment**’ında **`DB_PORT=3306`** yazın (3307 olmasın). Kaydedip **Processes → Restart** yapın.
- **MODULE_NOT_FOUND (rxjs/operators, bcrypt):** Sunucuda bu site’ın `node_modules`’ı bozuk/yarım kalmış olabilir. Deploy script’te **npm ci’den önce** `rm -rf backend/node_modules frontend/node_modules` kullanın (bkz. `FORGE-DEPLOY-SCRIPT.md`). Sonra tekrar deploy alıp **Processes → Restart** yapın.
