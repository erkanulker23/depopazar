# Forge / Awapanel Deploy

**Tüm kurulum (Web Directory, Environment, deploy script):** **[SETUP.md](SETUP.md)**

**Forge deploy script (diğer Laravel sitelerinizle aynı):** **[FORGE-DEPLOY-YAPISTIR.txt](FORGE-DEPLOY-YAPISTIR.txt)**

Bu proje Forge'un **standart Laravel deploy** akışıyla uyumludur. Özel `deploy.sh` zorunlu değildir.

Forge Site → Settings:

- **Install Composer Dependencies:** Açık
- **Install NPM Dependencies:** Açık
- **Web directory:** `php-app/public`

Deploy script olarak diğer Laravel sitelerinizde kullandığınız zero-downtime script'i aynen kullanın (`composer` + `npm run build` + `php artisan migrate --force`).

Manuel deploy veya Awapanel için `deploy.sh` hâlâ kullanılabilir.
