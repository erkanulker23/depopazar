# 403 Forbidden

Bu projede web kökü **`php-app/public`** olmalıdır. Panelde yanlış ayarlanırsa 403 alırsınız.

**Nginx:** `root` mutlaka `.../php-app/public` olmalı; `.../public` (Laravel gibi) **yanlış**.

**Çözüm:** Site ayarlarında **Web Directory** = **`php-app/public`** ve Nginx’te `root .../php-app/public;` yapın.

Tüm ilk kurulum adımları: **[docs/SETUP.md](SETUP.md)** (özellikle “Web Directory” bölümü).
