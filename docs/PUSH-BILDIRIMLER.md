# Telefon / Cihaz Bildirimleri (Web Push)

Kullanıcı sisteme giriş yaptığında, bildirimlere izin verdiğinde ve sistemde bir işlem olduğunda (sözleşme, ödeme, müşteri, depo vb.) ilgili kullanıcıların **telefonlarına ve cihazlarına** push bildirim gider.

**Deploy:** `deploy.sh` çalıştığında (Forge veya manuel) otomatik olarak migration'lar (push_subscriptions tablosu dahil) ve `php-app` için `composer install` yapılır; VAPID anahtarlarınız `.env` içinde ise cihaz bildirimleri deploy sonrası da çalışır.

## Nasıl çalışır

1. **Kullanıcı giriş yapar** (PHP panel veya React).
2. **Bildirim izni**: Kullanıcı bildirimler açılırken **"Telefon/cihaz bildirimlerini aç"** linkine tıklar ve tarayıcıda "İzin ver" der.
3. Abonelik (cihaz bilgisi) sunucuya kaydedilir.
4. **İşlem olduğunda** (sözleşme oluşturuldu, ödeme alındı, müşteri eklendi vb.) hem paneldeki bildirim listesine hem de **izin veren tüm cihazlara** push bildirim gider.

## Kurulum

### 1. Veritabanı

Push abonelikleri tablosunu ekleyin:

```bash
mysql -u kullanici -p veritabani < php-app/sql/migrations/add_push_subscriptions.sql
```

### 2. Composer ve Web Push kütüphanesi

```bash
cd php-app
composer install
```

(minishlink/web-push kurulur; PHP 8.2+ gerekebilir. Eski PHP için `composer.json` içinde uyumlu bir sürüm seçebilirsiniz.)

### 3. VAPID anahtarları

Web Push için sunucu kimlik anahtarları (VAPID) gerekiyor:

```bash
cd php-app
composer install   # henüz yapmadıysanız
php scripts/generate-vapid-keys.php
```

Çıktıdaki `vapid_public_key` ve `vapid_private_key` değerlerini:

- **config/config.php** içine ekleyin:

```php
'vapid_public_key'  => '...',
'vapid_private_key' => '...',
```

veya

- Ortam değişkeni olarak verin: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`.

### 4. PHP bootstrap (Composer autoload)

`public/index.php` içinde Composer autoload yüklü değilse ekleyin (push için zorunlu değil; yoksa push gönderilmez, panel bildirimleri çalışır):

```php
// public/index.php en üste (config'den önce)
if (is_file(APP_ROOT . '/vendor/autoload.php')) {
    require APP_ROOT . '/vendor/autoload.php';
}
```

Projede `PushService` zaten `vendor/autoload.php` dosyasını kendi içinde kontrol edip yüklüyor; index’te ekstra require gerekmez.

## Kullanıcı tarafı

- **PHP panel**: Giriş sonrası sağ üstteki **Bildirimler** (zil) menüsünü açın. Altta **"Telefon/cihaz bildirimlerini aç"** linkine tıklayıp tarayıcıda bildirim iznini verin. İzin verdikten sonra aynı cihazda ve diğer açtığınız cihazlarda işlem olduğunda push bildirim gelir.
- **HTTPS**: Push bildirimleri çalışması için site **HTTPS** (veya localhost) üzerinden açılmalıdır.

## Nerede tetikleniyor

Aşağıdaki işlemlerde hem panel bildirimi hem de (abonelik varsa) push gönderilir:

- Sözleşme oluşturma / silme  
- Ödeme alındı  
- Müşteri ekleme / toplu import  
- Depo / oda ekleme, güncelleme, silme  
- Nakliye işi ekleme, güncelleme, silme  
- Teklif oluşturma / silme  
- Kullanıcı ekleme, güncelleme, silme  
- Banka hesabı ekleme  
- Firma bilgileri güncelleme  
- Hizmet / kategori ekleme, silme  

## Dosya özeti

| Dosya | Açıklama |
|-------|----------|
| `php-app/sql/migrations/add_push_subscriptions.sql` | Push abonelik tablosu |
| `php-app/app/models/PushSubscription.php` | Abonelik kaydetme / listeleme |
| `php-app/app/PushService.php` | Push gönderme (VAPID + minishlink/web-push) |
| `php-app/app/models/Notification.php` | Bildirim oluşturulunca PushService çağrısı |
| `php-app/app/controllers/NotificationsController.php` | `apiVapidPublic`, `apiPushSubscribe` |
| `php-app/public/sw.js` | Service Worker (push event, bildirim gösterme) |
| `php-app/views/layout.php` | "Telefon/cihaz bildirimlerini aç" + abonelik scripti |
| `php-app/scripts/generate-vapid-keys.php` | VAPID anahtarı üretme |
