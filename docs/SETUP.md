# DepoPazar – İlk Kurulum (Sunucu)

Bu rehberi takip ederseniz ilk deploy’da **403 Forbidden**, **migration** ve **veritabanı bağlantı** hataları oluşmaz.

---

## 1. Panelde site oluşturun

Forge, Awapanel veya kullandığınız panelde siteyi ekleyin (domain, PHP sürümü 8.0+). Repo’yu bağlayın (örn. `erkanulker23/depopazar`), branch’i seçin.

**Laravel Forge:** Proje kökünde minimal bir `composer.json` vardır (ilk kurulumda Forge "composer.json bulunamadı" vermesin diye). Asıl bağımlılıklar **php-app/** içinde; `deploy.sh` orada `composer install` çalıştırır. İsterseniz **Install Composer Dependencies** açık bırakabilirsiniz (kökte boş kurulum yapılır).

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

**Bir sitede güncelleme gelmiyor, diğer sitelerde geliyorsa:** Forge’da o site için **Settings → General → Branch** değerini kontrol edin. Push yaptığınız dal (genelde `main`) seçili olmalı. Yanlış veya eski bir dal seçiliyse deploy aynı eski kodu çeker.

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
- **Deploy: "unable to unlink ... Permission denied"** – `php-app/public` veya `uploads` içindeki dosyalar web sunucusu (www-data) kullanıcısına ait olduğu için git güncelleyemiyor. Deploy script artık önce bu dizinin sahipliğini düzeltmeyi deniyor. Hata devam ederse sunucuda bir kez çalıştırın: `sudo chown -R forge:forge /home/forge/SITENIZ.com/php-app/public` (SITENIZ.com yerine kendi site yolunuz). Sonra tekrar deploy alın.

---

## "Depo/Müşteri eklenemedi" – Foreign key (company_id) hatası

**Hata:** `Cannot add or update a child row: a foreign key constraint fails ... fk_customers_company` veya `fk_warehouses_company`.

**Sebep:** Veritabanında ya hiç şirket yok ya da giriş yapan kullanıcının hesabı artık var olmayan bir şirkete bağlı (`users.company_id` geçersiz).

**Yapmanız gereken (sunucuda, proje kökünden):**

- **Tek komut (tablolar + varsayılan veri):**
  ```bash
  cd /home/forge/celebi.awapanel.com   # kendi site yolunuz
  php php-app/seed.php
  ```
  Bu komut önce tüm tabloları ve sütunları oluşturur (içinde veri olması zorunlu değil), sonra yoksa varsayılan şirket ve super admin kullanıcı ekler. Mevcut veriyi bozmaz.

- **İsterseniz ayrı ayrı:**
  1. `php artisan migrate --force` — Sadece tablolar/eksik sütunlar oluşturulur; **mevcut veri bozulmaz** (IF NOT EXISTS / güvenli ALTER).
  2. `php php-app/seed.php` — Şirket ve kullanıcı yoksa eklenir; varsa sadece super admin şifresi güncellenir.

3. **Giriş yaptığınız kullanıcı şirket sahibi/personel ise** ve hata devam ediyorsa, o kullanıcının `company_id` değeri geçersiz olabilir. Geçerli bir şirket ID’si atamak için (MySQL/phpMyAdmin veya SSH):
   ```sql
   -- Önce mevcut şirket ID’sini görün
   SELECT id, name FROM companies WHERE deleted_at IS NULL;

   -- Kullanıcının company_id’sini bu ID ile güncelleyin (EMAIL ve COMPANY_ID’yi değiştirin)
   UPDATE users SET company_id = 'BURAYA_SIRKET_ID' WHERE email = 'giris_yaptiginiz@email.com' AND deleted_at IS NULL;
   ```

Bu adımlardan sonra depo ve müşteri ekleme çalışır. Uygulama artık bu durumda veritabanı hatası yerine “Şirket kaydı bulunamadı…” uyarısını da gösterecektir.

---

## Nginx örnek config

- **Forge site.conf** (`/etc/nginx/forge-conf/SITE_ID/site.conf`): İçinde `root` yoksa en üste `root /home/forge/SITENIZ/php-app/public;` ekleyin. Tam örnek: **`scripts/forge-site-conf-ORNEK.conf`**.
- Genel örnek: **`scripts/nginx-forge-awapanel.conf`** (PROJE_KOYU ve PHP-FPM socket’i kendi sunucunuza göre düzenleyin).

---

## SSL: "Bu siteye ulaşılamıyor" / ERR_SSL_UNRECOGNIZED_NAME_ALERT

Bu hata, tarayıcının site adresini (örn. **celebi.awapanel.com**) SSL sertifikasındaki adla eşleştirememesinden kaynaklanır. Sertifika yok veya farklı bir domain için (örn. www. veya başka alt alan adı) olabilir.

**Laravel Forge’da:**
1. Siteyi seçin → **SSL** (veya **Domains** / sertifika bölümü).
2. **Let's Encrypt** ile sertifika ekleyin; domain olarak **celebi.awapanel.com** yazın (www kullanmıyorsanız sadece bu).
3. Sertifika oluşturulduktan sonra **Force HTTPS** açabilirsiniz.

DNS’te bu domain’in sunucu IP’sine yönlendiğinden emin olun. Sertifika yayına geçene kadar birkaç dakika sürebilir.

---

## Özet kontrol listesi

- [ ] Web Directory / Document Root = **php-app/public**
- [ ] Environment’ta **DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD** tanımlı
- [ ] Deploy script: **cd … && bash deploy.sh**
- [ ] İlk deploy çalıştırıldı
- [ ] SSL sertifikası site domain’i için kuruldu (HTTPS açılmadan önce)

Bu adımlarla proje ilk yüklemede 403, veritabanı ve SSL hataları olmadan çalışır.
