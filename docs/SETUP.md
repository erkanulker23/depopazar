# DepoPazar – İlk Kurulum (Sunucu)

Bu rehberi takip ederseniz ilk deploy’da **403 Forbidden**, **migration** ve **veritabanı bağlantı** hataları oluşmaz.

---

## 1. Panelde site oluşturun

Forge, Awapanel veya kullandığınız panelde siteyi ekleyin (domain, PHP sürümü 8.0+). Repo’yu bağlayın (örn. `erkanulker23/depopazar`), branch’i seçin.

---

## 2. Web Directory / Document Root (zorunlu – 403 önlemi)

**Site ayarlarında web kökünü mutlaka `php-app/public` yapın.** Proje kökü *değil*.

| Panel         | Ayar adı          | Değer |
|---------------|--------------------|--------|
| Laravel Forge | Web directory      | `php-app/public` |
| Awapanel      | Document Root      | `php-app/public` |

- **Forge’da:** “Web directory” alanına sadece **`php-app/public`** yazın (başında/sonunda **boşluk olmasın**).
- Tam yol kullanıyorsanız: `/home/forge/celebi.awapanel.com/php-app/public` (`.com` ile `/php-app` arasında boşluk olmamalı).

Nginx’te `root` şöyle olmalı:

```nginx
root /home/forge/SITENIZ/php-app/public;
```

**Yanlış:** `root /home/forge/SITENIZ;` veya `root .../public;` (Laravel gibi) → 403 Forbidden. Bu projede giriş noktası **php-app/public** dizinidir.

---

## 3. Environment (veritabanı)

Sunucuda `.env` dosyası genelde yoktur (git’te yok). Bu yüzden **panelde Environment alanına** aşağıdaki değişkenleri ekleyin.

Proje kökündeki **`.env.example`** dosyasındaki değerleri kopyalayıp paneldeki Environment’a yapıştırın. En az şunlar **mutlaka** dolu olmalı:

- `DB_HOST` (örn. 127.0.0.1)
- `DB_PORT` (3306)
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD` (boş bırakmayın; “Access denied” alırsınız)

Forge’da: **Site → Environment**  
Awapanel’de: Site ayarlarında **Environment** / **Ortam değişkenleri** benzeri alan.

---

## 4. Deploy script (çok önemli)

**Forge’da “Deploy Script” alanına sadece aşağıdaki tek satırı yapıştırın.** Kendi yazdığınız veya farklı siteden kopyaladığınız script’i (örn. `depo.awapanel.com` yolu geçen) **kullanmayın**; yanlış dizine deploy edilir ve 403 / çalışmama olur.

**Kopyalanacak satır:**

```bash
cd $FORGE_SITE_PATH && bash deploy.sh
```

- `$FORGE_SITE_PATH` Forge tarafından otomatik verilir (o anki site: celebi.awapanel.com ise o dizin olur).
- Proje içindeki `deploy.sh` kullanılır; git pull sonrası güncel script çalışır.

Hazır metin: **`docs/FORGE-DEPLOY-YAPISTIR.txt`**

`deploy.sh` şunları yapar:

- Git pull / fetch
- `.env` veya panel Environment’tan `db.local.php` oluşturur
- `php artisan migrate --force` (schema + migrations)
- Composer install (php-app)
- Seed (ilk şirket + super admin)
- `php-app/public` ve uploads izinleri (403 önlemi)

---

## 5. İlk deploy

Panelden **Deploy Now** / **Deploy** çalıştırın.

### Hata alırsanız

- **403 Forbidden**
  - **Laravel Forge:** Site → **Settings** → **General** → **Web directory** alanı **mutlaka** `php-app/public` olmalı (varsayılan `public` **yanlış**). Kaydedin; Forge Nginx config’i bu alana göre günceller.
  - **Nginx’i elle düzenliyorsanız:** `root` satırı `.../php-app/public` olmalı; `.../public` (Laravel gibi) 403 verir.
  - Web directory’de başında/sonunda veya path içinde **boşluk olmamalı**.
  - Deploy script olarak **sadece** `cd $FORGE_SITE_PATH && bash deploy.sh` kullanın.
  - Deploy’u tekrar çalıştırın (izinler güncellenir). Hâlâ 403 ise sunucuda `ls -la /home/forge/celebi.awapanel.com/php-app/public/` ile `index.php` var mı ve okunabilir mi kontrol edin.
- **Veritabanı / Access denied:** Environment’ta DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD dolu mu kontrol edin.
- **Migration hatası:** Aynı şekilde DB_* değişkenleri; gerekirse sunucuda `cd $FORGE_SITE_PATH && php artisan migrate --force` çalıştırın.
- **Giriş: "Geçersiz e-posta veya şifre"** – Varsayılan giriş: `erkanulker0@gmail.com` / `password`. Seed her deploy’da bu kullanıcının şifresini `password` yapar. Yine giremiyorsanız sunucuda: `cd $FORGE_SITE_PATH/php-app && php set-password.php erkanulker0@gmail.com password`

---

## Nginx örnek config

- **Forge site.conf** (`/etc/nginx/forge-conf/SITE_ID/site.conf`): İçinde `root` yoksa en üste `root /home/forge/SITENIZ/php-app/public;` ekleyin. Tam örnek: **`scripts/forge-site-conf-ORNEK.conf`**.
- Genel örnek: **`scripts/nginx-forge-awapanel.conf`** (PROJE_KOYU ve PHP-FPM socket’i kendi sunucunuza göre düzenleyin).

---

## Özet kontrol listesi

- [ ] Web Directory / Document Root = **php-app/public**
- [ ] Environment’ta **DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD** tanımlı
- [ ] Deploy script: **cd … && bash deploy.sh**
- [ ] İlk deploy çalıştırıldı

Bu adımlarla proje ilk yüklemede 403 ve veritabanı hataları olmadan çalışır.
