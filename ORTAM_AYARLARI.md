# Ortam Ayarları ve Veritabanı

## Farklı Ortamlarda Kullanım

Projeyi 2 farklı yerde (ör. lokalde ve sunucuda) kullanırken **her ortamın kendi `.env` dosyası** olmalıdır. Aynı proje klasörünü 2 yerde kullanıyorsanız:

1. **Her ortamda ayrı `.env` dosyası oluşturun** – `.env` dosyası `.gitignore`'da olduğu için git ile paylaşılmaz.
2. **Veritabanı bilgilerini ortama göre ayarlayın** – Her ortam kendi veritabanına bağlanmalıdır:

```env
# Lokal geliştirme
DB_HOST=127.0.0.1
DB_PORT=3307
DB_USERNAME=root
DB_PASSWORD=123456
DB_DATABASE=depopazar_lokal

# Sunucu / Canlı
DB_HOST=db.sunucu.com
DB_PORT=3306
DB_USERNAME=canli_user
DB_PASSWORD=guclu_sifre
DB_DATABASE=depopazar_canli
```

3. **Backend'i her ortamda kendi .env ile çalıştırın** – `npm run start` veya `node dist/main.js` çalıştırıldığında, bulunduğu dizindeki `.env` dosyası okunur.

## Önemli Notlar

- `.env` dosyası proje kökünde olmalıdır (backend ve frontend ile aynı seviyede).
- Bir ortamda yapılan değişiklik diğerini etkilemez; veritabanları farklı olduğu sürece veriler karışmaz.
- Canlı ortamda `NODE_ENV=production` ve `SWAGGER_ENABLED=false` kullanın.
