# Forge / Awapanel Deploy

**Kurulum:** **[SETUP.md](SETUP.md)**

**Forge deploy script (kopyala-yapıştır):** **[FORGE-DEPLOY-YAPISTIR.txt](FORGE-DEPLOY-YAPISTIR.txt)**

## Zero-downtime (Forge)

Deploy script'te **`git pull` olmamalı** — site kökü git repo değildir.

```bash
cd $FORGE_SITE_PATH
$CREATE_RELEASE()
cd $FORGE_RELEASE_DIRECTORY
set -e && bash forge-deploy.sh
$ACTIVATE_RELEASE()
$RESTART_QUEUES()
```

`forge-deploy.sh`: composer + npm build + `php artisan migrate --force` (git yok).

**Deploy veri güvenliği:** Migration'lar `schema_migrations` tablosu ile yalnızca bir kez uygulanır. `seed.php` deploy sırasında çalışmaz (şifre ve müşteri verileri korunur). Yüklenen dosyalar `shared/uploads` altında symlink ile kalıcıdır.

İlk kurulum (SSH): `ARTISAN_RUN_SEED=1 php artisan migrate --force`

Manuel / Awapanel için `deploy.sh` kullanılabilir.
