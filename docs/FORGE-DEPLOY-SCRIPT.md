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

## 4. Deploy Script

Forge **Deploy Script** alanına şunu yazın (veya `deploy.sh` dosyası zaten projede mevcut – Forge bunu çalıştıracak):

```bash
cd /home/forge/your-domain.com
git pull origin main
```

**Veya** proje kökündeki `deploy.sh` kullanılacak şekilde:

```bash
cd /home/forge/your-domain.com
./deploy.sh
```

`deploy.sh` şunları yapar:
- `git fetch` + `git reset --hard origin/main`
- `.env` dosyasından DB bilgilerini okuyup `php-app/config/db.local.php` oluşturur
- İlk kurulumda `php-app/sql/schema.sql` ile tabloları oluşturur (CREATE IF NOT EXISTS)
- `php-app/uploads` dizinini oluşturur ve izinleri ayarlar

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

Forge bu değerleri `.env` dosyasına yazar. `deploy.sh` bu dosyayı okur ve `db.local.php` oluşturur.

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
3. Giriş: `/giris` – İlk kullanıcı için `php-app/set-password.php` ile şifre oluşturulabilir (sunucuda çalıştırılır)

---

## 9. Özet

| Ayar           | Değer                |
|----------------|----------------------|
| Web Directory  | `php-app/public`     |
| PHP            | 8.0+                 |
| Deploy Script  | `./deploy.sh`        |
| Branch         | `main`               |

---

## Sorun Giderme

- **500 hatası**: `php-app/storage/logs` veya PHP error log'una bakın
- **Veritabanı bağlantı hatası**: `.env` / Environment değişkenlerini kontrol edin; `db.local.php` deploy sonrası oluşmuş olmalı
- **403 Forbidden**: Web Directory'nin `php-app/public` olduğundan emin olun
