# DepoPazar / Depotakip – Scripts

## depotakip-v1.test (PHP uygulaması)

PHP uygulamasını yerelde çalıştırmak için:

```bash
./scripts/setup-valet.sh
```

Bu script:

1. Eski Nginx config’i kaldırır (varsa, React build’e yönlendiren).
2. `valet link depotakip-v1` ile `php-app/public` dizinini `depotakip-v1.test` adresine bağlar.
3. Valet’i yeniden başlatır.

**Gereksinimler:** Laravel Valet kurulu ve çalışır olmalı (`valet install`).  
**Veritabanı:** `php-app/config/db.local.php` (örnek: `php-app/config/db.local.php.example` kopyalayıp düzenleyin).

---

- `valet.conf` / `nginx.conf`: Eski React + API proxy ayarları (PHP app için **kullanmayın**; PHP için sadece `setup-valet.sh` yeterli).
