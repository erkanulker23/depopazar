# Laravel Forge Deploy – DepoPazar (PHP)

DepoPazar PHP uygulaması Laravel Forge ile deploy edilir. Bu dokümanda Forge kurulumu adım adım açıklanır.

---

## 1. GitHub Repo

- Repo: `https://github.com/erkanulker23/depopazar`
- Branch: `main` (veya Forge'da seçeceğiniz branch)

---

## 2. Forge Site Oluşturma

1. **Server** seçin veya yeni bir server ekleyin
2. **Sites** → **Create Site**
3. **Domain**: `your-domain.com` (veya alt domain)
4. **Project Type**: PHP
5. **Web Directory**: `php-app/public` ← **Önemli**
6. **PHP Version**: 8.0 veya üzeri önerilir

---

## 3. GitHub Bağlantısı

1. **Source Control** → GitHub hesabınızı bağlayın
2. **Repository**: `erkanulker23/depopazar`
3. **Branch**: `main`
4. **Deploy Script**: Aşağıdaki metni kullanın veya proje kökündeki `deploy.sh` kullanılacak

---

## 4. Deploy Script (Forge’a yapıştır)

**Önce** site path’ini kontrol edin (Forge’da site ayarlarında görünür). Genelde `general.awapanel.com` için path: `/home/forge/general.awapanel.com`. Aşağıdaki script’te `ROOT=` satırını kendi path’inize göre değiştirin.

```bash
cd /home/forge/general.awapanel.com
set -e

# ROOT'u açıkça ver (Forge'da $0 güvenilir olmayabilir)
ROOT=/home/forge/general.awapanel.com
cd "$ROOT"

# Git güncelleme
git fetch origin
if [ -n "${FORGE_SITE_BRANCH}" ]; then
  git reset --hard "origin/${FORGE_SITE_BRANCH}"
else
  git reset --hard origin/main
fi
cd "$ROOT"

# .env'den değişkenleri yükle (Forge Environment otomatik .env yazar)
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

# db.local.php oluştur (şifrede özel karakter olsa da güvenli)
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

# Schema (ilk kurulum)
if [ -f "$ROOT/php-app/sql/schema.sql" ] && command -v mysql &> /dev/null; then
  if [ -n "$DB_PASSWORD" ]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$ROOT/php-app/sql/schema.sql" 2>/dev/null || true
  else
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" < "$ROOT/php-app/sql/schema.sql" 2>/dev/null || true
  fi
fi

# Seed: Super admin (erkanulker0@gmail.com / password) yoksa oluştur
if [ -f "$ROOT/php-app/seed.php" ] && [ -f "$ROOT/php-app/config/db.local.php" ]; then
  (cd "$ROOT/php-app" && php seed.php) 2>/dev/null || true
fi

# Uploads dizini
mkdir -p "$ROOT/php-app/uploads"
chmod -R 755 "$ROOT/php-app/uploads" 2>/dev/null || true
```

**Not:** Farklı bir domain/path kullanıyorsanız yalnızca `cd` ve `ROOT=` satırlarındaki `/home/forge/general.awapanel.com` kısmını kendi path’inizle değiştirin.

---

## 5. Environment (.env)

Forge **Environment** sekmesinde aşağıdaki değişkenleri tanımlayın:

```
APP_NAME=DepoPazar
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=depotakip
DB_USERNAME=forge
DB_PASSWORD=your_db_password
```

Forge bu değerleri `.env` dosyasına yazar. Deploy script bu dosyayı okur ve `db.local.php` oluşturur.

---

## 6. MySQL Veritabanı

1. Forge **Databases** → **Create Database**
2. Database adı: `depotakip` (veya farklı bir ad – `.env`'deki `DB_DATABASE` ile eşleşmeli)
3. Kullanıcı ve şifre oluşturun
4. Bu bilgileri `.env` (Environment) içine yazın

---

## 7. Nginx Ayarları

Forge genelde otomatik ayarlar. Özel gereksinim varsa:

**Web Directory** mutlaka `php-app/public` olmalı:

```nginx
root /home/forge/your-domain.com/php-app/public;
```

**PHP** ve **index.php** yönlendirmesi Forge varsayılanı ile çalışır.

---

## 8. İlk Deploy

1. **Deploy Now** butonuna tıklayın
2. Hata yoksa site `https://your-domain.com` adresinde açılır
3. **İlk giriş:** Deploy sırasında otomatik olarak bir **süper admin** kullanıcı oluşturulur (yoksa):
   - **E-posta:** `erkanulker0@gmail.com`
   - **Şifre:** `password`
   - Giriş adresi: `/giris`
4. Başka kullanıcı şifresi sıfırlamak için sunucuda: `php php-app/set-password.php <email> <yeni_sifre>`

---

## 9. Komut satırı (CLI)

Sunucuda SSH veya yerelde terminalden çalıştırabilirsiniz (proje kökü: repo root veya `/home/forge/general.awapanel.com`).

| Komut | Açıklama |
|--------|----------|
| `php php-app/seed.php` | Super admin yoksa oluşturur (erkanulker0@gmail.com / password). Var ise atlar. |
| `php php-app/set-password.php <email> <yeni_sifre>` | Belirtilen e-postanın şifresini günceller. |

Örnek (sunucuda):

```bash
cd /home/forge/general.awapanel.com
php php-app/seed.php
php php-app/set-password.php erkanulker0@gmail.com yeniSifre123
```

---

## 10. Özet

| Ayar           | Değer                |
|----------------|----------------------|
| Web Directory  | `php-app/public`     |
| PHP            | 8.0+                 |
| Deploy Script  | Yukarıdaki script    |
| Branch         | `main`               |
| Seed (manuel)  | `php php-app/seed.php` |

---

## Sorun Giderme

### HTTP 500 – "Bu isteği işleme alamıyor"

1. **Sağlık kontrolü sayfasını açın**  
   Tarayıcıda şu adrese gidin:  
   `https://general.awapanel.com/health.php`  
   Bu sayfa hangi adımda hata veriyorsa (config, db.local.php yok, veritabanı bağlantısı vb.) onu gösterir.

2. **Sık nedenler**
   - **`db.local.php` yok**: Deploy script çalışmamış demektir. Forge'da **Deploy Now** yapın. Deploy script `.env` değişkenlerinden `php-app/config/db.local.php` dosyasını oluşturur.
   - **Veritabanı bilgileri yanlış**: Forge **Environment** sekmesinde `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` doğru olmalı. Değiştirdikten sonra tekrar **Deploy Now** yapın.
   - **Web Directory yanlış**: Forge site ayarlarında **Web Directory** mutlaka `php-app/public` olmalı.
   - **PHP eklentisi**: Sunucuda `pdo_mysql` (PHP → Extensions) açık olmalı.

3. **Loglara bakma**
   - Forge'da site **Logs** veya sunucuda: `~/general.awapanel.com/logs/` (veya Forge'un gösterdiği path), Nginx: `/var/log/nginx/error.log`, PHP-FPM error log.

4. **Güvenlik**: Sorun çözüldükten sonra `php-app/public/health.php` dosyasını sunucudan silebilir veya erişimi kapatabilirsiniz.

- **403 Forbidden**: Web Directory'nin `php-app/public` olduğundan emin olun.
