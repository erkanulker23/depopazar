# DepoPazar – İlk Kurulum (Sunucu)

Bu rehberi takip ederseniz ilk deploy’da **403 Forbidden**, **migration** ve **veritabanı bağlantı** hataları oluşmaz.

---

## 1. Panelde site oluşturun

Forge, Awapanel veya kullandığınız panelde siteyi ekleyin (domain, PHP sürümü 8.0+).

---

## 2. Web Directory / Document Root (zorunlu – 403 önlemi)

**Site ayarlarında web kökünü mutlaka `php-app/public` yapın.** Proje kökü *değil*.

| Panel       | Ayar adı          | Değer                          |
|------------|--------------------|--------------------------------|
| Laravel Forge | Web Directory    | `php-app/public`               |
| Awapanel   | Document Root      | `php-app/public` veya tam yol* |

\* Tam yol örneği: `/home/forge/siteniz.com/php-app/public`

Nginx’te `root` şöyle olmalı:

```nginx
root /home/forge/SITENIZ/php-app/public;
```

**Yanlış:** `root /home/forge/SITENIZ;` → 403 Forbidden.

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

## 4. Deploy script

Panelde “Deploy Script” alanına **tek satır** yeterli (script proje içinde):

```bash
cd $FORGE_SITE_PATH && bash deploy.sh
```

Veya panel `FORGE_SITE_PATH` vermiyorsa, proje köküne göre:

```bash
cd /home/forge/celebi.awapanel.com && bash deploy.sh
```

`deploy.sh` şunları yapar:

- Git pull / fetch
- `.env` veya panel Environment’tan `db.local.php` oluşturur
- `php artisan migrate --force` (schema + migrations)
- Composer install (php-app)
- Seed (ilk şirket + super admin)
- `php-app/public` ve uploads izinleri (403 önlemi)

---

## 5. İlk deploy

Panelden **Deploy Now** / **Deploy** çalıştırın. Hata alırsanız:

- **403:** Web Directory’nin `php-app/public` olduğunu kontrol edin (adım 2).
- **Veritabanı / Access denied:** Environment’ta DB_* değişkenlerini kontrol edin (adım 3).
- **Migration hatası:** Aynı şekilde DB_* ve gerekirse `php artisan migrate --force` komutunu sunucuda manuel çalıştırın.

---

## Nginx örnek config

Proje içi örnek: **`scripts/nginx-forge-awapanel.conf`**  
`PROJE_KOYU` yerine kendi site yolunuzu yazın (örn. `/home/forge/celebi.awapanel.com`). PHP-FPM socket yolunu (örn. `php8.2-fpm.sock`) sunucunuza göre düzenleyin.

---

## Özet kontrol listesi

- [ ] Web Directory / Document Root = **php-app/public**
- [ ] Environment’ta **DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD** tanımlı
- [ ] Deploy script: **cd … && bash deploy.sh**
- [ ] İlk deploy çalıştırıldı

Bu adımlarla proje ilk yüklemede 403 ve veritabanı hataları olmadan çalışır.
