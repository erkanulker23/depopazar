# DepoPazar

Depo ve oda kiralama yönetim sistemi – PHP uygulaması.

## Kurulum

### Gereksinimler

- PHP 8.0+
- MySQL 5.7+ / 8.0
- Apache (mod_rewrite) veya Nginx

### Yerel Geliştirme (Valet)

```bash
./scripts/setup-valet.sh
```

`php-app/config/db.local.php.example` dosyasını `db.local.php` olarak kopyalayıp veritabanı bilgilerinizi girin.

### Laravel Forge ile Deploy

Ayrıntılı adımlar için: [docs/FORGE-DEPLOY-SCRIPT.md](docs/FORGE-DEPLOY-SCRIPT.md)

- **Web Directory**: `php-app/public`
- **Deploy Script**: `./deploy.sh`
- **Environment**: `.env.example` içindeki değişkenleri Forge Environment'a ekleyin

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
