# DepoPazar

Depo ve oda kiralama yönetim sistemi – PHP uygulaması.

## Kurulum

### Gereksinimler

- PHP 8.0+
- MySQL 5.7+ / 8.0
- Apache (mod_rewrite) veya Nginx

### İlk kurulum (sunucu – Forge / Awapanel)

Projeyi ilk kez sunucuya kurarken **tek rehber:** **[docs/SETUP.md](docs/SETUP.md)**

Özet:

1. **Web Directory / Document Root** = `php-app/public` (aksi halde 403 Forbidden).
2. Panel **Environment**’a `.env.example` içindeki değişkenleri ekleyin (özellikle DB_*).
3. Deploy script: Forge’da **sadece** `cd $FORGE_SITE_PATH && bash deploy.sh` (bkz. `docs/FORGE-DEPLOY-YAPISTIR.txt`).

Bu üç adım tamamsa migration ve 403 sorunu çıkmaz.

### Yerel Geliştirme (Valet)

```bash
./scripts/setup-valet.sh
```

`php-app/config/db.local.php.example` dosyasını `db.local.php` olarak kopyalayıp veritabanı bilgilerinizi girin.

### Komut satırı (CLI)

```bash
# Super admin yoksa oluşturur (erkanulker0@gmail.com / password)
php php-app/seed.php

# Kullanıcı şifresi sıfırlama
php php-app/set-password.php <email> <yeni_sifre>
```

## Proje Yapısı

```
depopazar/
├── php-app/           # PHP uygulaması
│   ├── app/           # Controller, Model, Router
│   ├── config/        # Yapılandırma
│   ├── public/        # Web root (index.php, .htaccess)
│   ├── sql/           # Veritabanı şeması
│   └── views/         # PHP şablonları
├── deploy.sh          # Forge deploy script
└── docs/              # Dokümantasyon
```

## Lisans

Özel proje.
