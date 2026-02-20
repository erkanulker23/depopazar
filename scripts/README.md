# DepoPazar – Scripts

**Sunucu ilk kurulum:** Proje kökündeki [docs/SETUP.md](../docs/SETUP.md). Nginx/web root mutlaka `php-app/public` olmalı.

---

## Sunucuya atmadan önce: pre-deploy.sh

Projeyi sunucuya push etmeden önce çalıştırın:

```bash
./scripts/pre-deploy.sh
```

- Composer install + dump-autoload
- Migration sırası kontrolü
- İsteğe bağlı: yerel veritabanı migration'ları

---

## depotakip-v1.test (PHP uygulaması)

PHP uygulamasını yerelde çalıştırmak için:

```bash
./scripts/setup-valet.sh
```

Bu script:

1. Eski Nginx config’i kaldırır (varsa).
2. `valet link depotakip-v1` ile `php-app/public` dizinini `depotakip-v1.test` adresine bağlar.
3. Valet’i yeniden başlatır.

**Gereksinimler:** Laravel Valet kurulu ve çalışır olmalı (`valet install`).  
**Veritabanı:** `php-app/config/db.local.php` (örnek: `php-app/config/db.local.php.example` kopyalayıp düzenleyin).

---

- `nginx.conf` / `nginx-forge-awapanel.conf` / `valet.conf` / `laragon.conf`: Referans config’ler; root her zaman `php-app/public`. Sunucuda panelde Web Directory = `php-app/public` seçin (bkz. SETUP.md).
