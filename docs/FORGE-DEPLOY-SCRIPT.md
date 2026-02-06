# Laravel Forge ile DepoPazar (PHP) Kurulumu

Bu doküman, DepoPazar PHP uygulamasını Laravel Forge üzerinde sıfırdan kurmak için gereken adımları açıklar.

---

## Sunucuya atmadan önce (Pre-Deploy)

Projeyi sunucuya push etmeden önce yerelde şu scripti çalıştırın:

```bash
./scripts/pre-deploy.sh
```

Bu script:
- `composer install` ve `composer dump-autoload` çalıştırır
- Migration dosyalarının sırasını kontrol eder (vehicles tablosu önce olmalı)
- Varsa yerel veritabanına migration dener (opsiyonel)

Böylece "tablo yok" veya "sütun yok" hatalarının önüne geçilir.

---

## 1. Ön Gereksinimler

| Gereksinim | Açıklama |
|------------|----------|
| **GitHub** | Repo erişimi: `https://github.com/erkanulker23/depopazar` |
| **Forge** | Laravel Forge hesabı ve en az bir server |
| **MySQL** | Forge üzerinde oluşturulacak veritabanı |
| **Domain** | Site için kullanılacak domain (örn. `depopazar.com` veya `app.awapanel.com`) |

---

## 2. Forge’da Site Oluşturma

Forge panelinde sırayla şunları yapın:

1. **Server** sayfasına gidin, site kuracağınız sunucuyu seçin.
2. **Sites** sekmesine tıklayın → **Create Site**.
3. Açılan formda alanları şöyle doldurun:

| Alan | Ne yazılacak | Not |
|------|---------------------|-----|
| **Domain** | `your-domain.com` veya `app.yourdomain.com` | SSL sonra eklenebilir. |
| **Project Type** | **PHP** | Listeden "PHP" seçin. |
| **Web Directory** | `php-app/public` | **Mutlaka** bu değer olmalı; yanlış olursa 403/404 alırsınız. |
| **PHP Version** | **8.0** veya **8.1** / **8.2** | 8.0 ve üzeri önerilir. |

4. **Add Site** (veya **Create Site**) ile kaydedin.

**Önemli:** Site oluştuktan sonra Forge size **site path** gösterir. Genelde şu formattadır:

- `/home/forge/your-domain.com`

Bu path’i not alın; deploy script ve Nginx ayarlarında kullanacaksınız.

---

## 3. GitHub (Source Control) Bağlantısı

1. Oluşturduğunuz sitenin sayfasında **Source Control** sekmesine gidin.
2. **Install Git Repository** veya **Connect Repository** ile GitHub hesabınızı bağlayın (henüz bağlı değilse).
3. Repository bilgilerini girin:

| Alan | Değer |
|------|--------|
| **Provider** | GitHub |
| **Repository** | `erkanulker23/depopazar` |
| **Branch** | `main` |

4. **Deploy Script** alanına ya proje kökündeki `deploy.sh` kullanılacak şekilde bırakın ya da bu dokümandaki **Tam Deploy Script** bölümündeki script’i yapıştırın (tercih edilen: dokümandaki script, çünkü `ROOT` path’i sizin site path’inize göre düzenlenir).

---

## 4. Tam Deploy Script (Forge’a yapıştırılacak)

Aşağıdaki script’i Forge’daki **Deploy Script** alanına kopyalayın. **Sadece** `ROOT=` satırındaki path’i kendi site path’inizle değiştirin (Forge’da site detayında “Path” olarak görünür).

**Değiştirilecek:** `ROOT=/home/forge/your-domain.com` → kendi path’iniz, örn. `ROOT=/home/forge/general.awapanel.com`

```bash
# DepoPazar – Forge Deploy Script
# ROOT'u kendi site path'inizle değiştirin (örn. /home/forge/general.awapanel.com)
ROOT=/home/forge/your-domain.com
set -e

cd "$ROOT"

# 1) Kod güncelleme
git fetch origin
if [ -n "${FORGE_SITE_BRANCH}" ]; then
  git reset --hard "origin/${FORGE_SITE_BRANCH}"
else
  git reset --hard origin/main
fi
cd "$ROOT"

# 2) .env yükle (Forge Environment .env yazar)
if [ -f "$ROOT/.env" ]; then
  set -a
  source "$ROOT/.env" 2>/dev/null || true
  set +a
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-depotakip}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

# 3) db.local.php oluştur (şifrede özel karakter güvenliği – tek tırnak escape)
mkdir -p "$ROOT/php-app/config"
DB_PASS_ESC=$(echo "$DB_PASSWORD" | sed "s/'/'\\\\''/g")
cat > "$ROOT/php-app/config/db.local.php" << DBCONFIG
<?php
\$host = '${DB_HOST}';
\$port = '${DB_PORT}';
\$db   = '${DB_DATABASE}';
\$user = '${DB_USERNAME}';
\$pass = '${DB_PASS_ESC}';
\$dsn  = "mysql:host=\$host;port=\$port;dbname=\$db;charset=utf8mb4";
\$pdo  = new PDO(\$dsn, \$user, \$pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
return \$pdo;
DBCONFIG
chmod 640 "$ROOT/php-app/config/db.local.php" 2>/dev/null || true

# 4) Veritabanı schema + migrations (push_subscriptions, vehicle_plate, vehicles vb.)
if [ -f "$ROOT/php-app/sql/schema.sql" ] && command -v mysql &> /dev/null; then
  if [ -n "$DB_PASSWORD" ]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$ROOT/php-app/sql/schema.sql" 2>/dev/null || true
  else
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" < "$ROOT/php-app/sql/schema.sql" 2>/dev/null || true
  fi
fi
if command -v mysql &> /dev/null && [ -d "$ROOT/php-app/sql/migrations" ]; then
  for f in "$ROOT/php-app/sql/migrations"/*.sql; do
    [ -f "$f" ] || continue
    if [ -n "$DB_PASSWORD" ]; then
      mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$f" 2>/dev/null || true
    else
      mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" < "$f" 2>/dev/null || true
    fi
  done
fi

# 5) Composer (php-app: web-push vb. – cihaz bildirimleri için)
if command -v composer &> /dev/null && [ -f "$ROOT/php-app/composer.json" ]; then
  (cd "$ROOT/php-app" && composer install --no-dev --optimize-autoloader) 2>/dev/null || true
fi

# 6) Super admin kullanıcı + varsayılan şirket (yoksa oluştur)
if [ -f "$ROOT/php-app/seed.php" ] && [ -f "$ROOT/php-app/config/db.local.php" ]; then
  (cd "$ROOT/php-app" && php seed.php) 2>/dev/null || true
fi

# 7) Uploads dizini (logo vb. – php-app/public/uploads kullanılıyor)
mkdir -p "$ROOT/php-app/public/uploads/company"
chmod -R 755 "$ROOT/php-app/public/uploads" 2>/dev/null || true
```

**Not:** Farklı domain/path kullanıyorsanız sadece script’in en üstündeki `ROOT=` satırını kendi site path’inizle değiştirin. Proje kökündeki `deploy.sh` da Forge’da kullanılabilir; o script `ROOT`’u otomatik bulur, ancak uploads dizini için `php-app/public/uploads` path’ini kullandığınızdan emin olun.

---

## 5. Environment (.env) Değişkenleri

Forge’da ilgili siteyi açın → **Environment** sekmesi. Aşağıdaki değişkenleri ekleyin veya düzenleyin. Forge bu değerleri repo köküne `.env` dosyasına yazar; deploy script bu dosyayı okuyup `db.local.php` üretir.

Aşağıdaki blokları **olduğu gibi** Environment kutusuna yapıştırıp kendi değerlerinizle değiştirebilirsiniz:

```env
# --- Uygulama ---
APP_NAME=DepoPazar
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# APP_URL: Tarayıcıda açacağınız tam adres (https ile, sonunda / yok).
# Örnek: https://depopazar.com veya https://app.awapanel.com

# --- Veritabanı (Forge Databases’te oluşturduğunuz MySQL) ---
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=depotakip
DB_USERNAME=forge
DB_PASSWORD=buraya_gercek_sifre_yazin

# DB_DATABASE: Forge’da oluşturduğunuz veritabanı adı.
# DB_USERNAME / DB_PASSWORD: Forge’da o veritabanı için tanımladığınız kullanıcı ve şifre.

# --- Push bildirimleri (telefon/cihaz) – opsiyonel ---
# Cihaz bildirimleri için VAPID anahtarları. Yoksa sadece panel bildirimleri çalışır.
# Anahtar üretmek: sunucuda cd php-app && composer install && php scripts/generate-vapid-keys.php
# VAPID_PUBLIC_KEY=...
# VAPID_PRIVATE_KEY=...
# PUSH_CONTACT_EMAIL=noreply@your-domain.com
```

Push bildirimleri (telefon/cihaz) için isteğe bağlı olarak `VAPID_PUBLIC_KEY` ve `VAPID_PRIVATE_KEY` ekleyin; yoksa sadece panel bildirimleri çalışır (bkz. `docs/PUSH-BILDIRIMLER.md`).

**Kontrol listesi:**

- `APP_URL` → Sitenin gerçek adresi (https, sonunda `/` yok).
- `DB_DATABASE` → Forge **Databases**’te oluşturduğunuz veritabanı adı.
- `DB_USERNAME` / `DB_PASSWORD` → Aynı veritabanına Forge’da atadığınız kullanıcı ve şifre.

Environment’ı kaydettikten sonra bir kez **Deploy Now** yapın; `.env` yazılır ve deploy script `db.local.php`’yi üretir.

---

## 6. MySQL Veritabanı (Forge’da)

1. Forge’da **Databases** sekmesine gidin (sunucu veya site seviyesinde, kullandığınız yapıya göre).
2. **Create Database** ile yeni veritabanı ekleyin.
3. **Database name:** `depotakip` (veya kullanmak istediğiniz ad – `.env`’deki `DB_DATABASE` ile aynı olmalı).
4. Kullanıcı ve şifre oluşturun; bu bilgileri **Environment**’taki `DB_USERNAME` ve `DB_PASSWORD` ile aynı yapın.

---

## 7. Nginx Yapılandırması

Forge, **Web Directory**’yi `php-app/public` yaptığınızda genelde gerekli Nginx ayarını kendisi yapar. Özel düzenleme yapmanız gerekirse Forge’da **Files** veya **Nginx Configuration** ile aşağıdaki yapıyı referans alabilirsiniz.

**Önemli:** `root` mutlaka `.../php-app/public` olmalı.

```nginx
# DepoPazar – PHP (Forge)
# root path'i kendi site path'inize göre değiştirin: /home/forge/SITE_PATH/php-app/public

server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    root /home/forge/your-domain.com/php-app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Düzenlemeniz gerekenler:**

| Değer | Açıklama |
|-------|----------|
| `server_name` | Sitenizin domain’i (örn. `general.awapanel.com`). |
| `root` | `/home/forge/SITE_PATH/php-app/public` (SITE_PATH = Forge’da gördüğünüz site path). |
| `fastcgi_pass` | Sunucudaki PHP-FPM socket. Forge’da PHP sürümüne göre `php8.0-fpm.sock`, `php8.2-fpm.sock` vb. olabilir; Forge varsayılan config’teki ile aynı yapın. |

SSL (HTTPS) Forge üzerinden **SSL** sekmesinden “Let’s Encrypt” ile eklenir; Nginx’e 443 server bloğu Forge tarafından eklenir.

---

## 8. İlk Deploy ve Giriş

1. Forge’da **Deploy Now** butonuna tıklayın.
2. Log’da hata yoksa site `APP_URL`’deki adreste açılır.
3. **İlk giriş** (seed ile oluşturulan süper admin):
   - **URL:** `https://your-domain.com/giris`
   - **E-posta:** `erkanulker0@gmail.com`
   - **Şifre:** `password`
4. İlk girişten sonra şifreyi mutlaka değiştirin. Başka kullanıcı şifresi için sunucuda:  
   `php php-app/set-password.php <email> <yeni_sifre>`

---

## 9. Komut Satırı (CLI) Özeti

Sunucuda proje köküne gidin (örn. `cd /home/forge/your-domain.com`), sonra:

| Komut | Açıklama |
|--------|----------|
| `php php-app/seed.php` | Super admin yoksa oluşturur (erkanulker0@gmail.com / password). Varsa atlar. |
| `php php-app/set-password.php <email> <yeni_sifre>` | Belirtilen e-postanın şifresini günceller. |

Örnek:

```bash
cd /home/forge/your-domain.com
php php-app/seed.php
php php-app/set-password.php erkanulker0@gmail.com yeniSifre123
```

---

## 10. Kurulum Özeti

| Ayar | Değer |
|------|--------|
| **Web Directory** | `php-app/public` |
| **PHP** | 8.0 veya üzeri |
| **Branch** | `main` |
| **Deploy script** | Yukarıdaki tam script (ROOT path’i kendi sitenize göre değiştirilmiş) |
| **İlk giriş** | `/giris` → erkanulker0@gmail.com / password |

---

## Sorun Giderme

### HTTP 500 – “Bu isteği işleme alamıyor”

1. **Sağlık kontrolü:** Tarayıcıda `https://your-domain.com/health.php` açın. Hangi adımda hata varsa (config, db.local.php, veritabanı) sayfa yazar.
2. **Sık nedenler:**
   - **`db.local.php` yok:** Environment doldurulup **Deploy Now** yapılmamış. Environment’ı kaydedip tekrar **Deploy Now** çalıştırın.
   - **Veritabanı bilgisi hatalı:** Environment’ta `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` Forge’daki MySQL bilgileriyle aynı olmalı. Sonra tekrar **Deploy Now**.
   - **Web Directory yanlış:** Site ayarlarında **Web Directory** mutlaka `php-app/public` olmalı.
   - **PHP eklentisi:** Sunucuda `pdo_mysql` açık olmalı (Forge → PHP → Extensions).
3. **Log:** Forge **Logs** veya sunucuda Nginx/PHP-FPM hata loglarına bakın.
4. **Güvenlik:** Sorun bittikten sonra `php-app/public/health.php` dosyasını silebilir veya erişimi kapatabilirsiniz.

### 403 Forbidden

- **Web Directory**’nin `php-app/public` olduğundan emin olun. Nginx `root` değeri `.../php-app/public` olmalı.

### 404 – index.php bulunamıyor

- Nginx’te `root` tam olarak `.../php-app/public` ve `try_files ... /index.php?$query_string;` satırı mevcut olmalı.
