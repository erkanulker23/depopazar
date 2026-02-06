# Araçlar (Vehicles) API

Sol menüde **Araçlar** sayfası, nakliye işleri ve Yeni Satış (sözleşme) girişlerindeki plaka bilgisini toplayıp plaka bazlı rapor sunar. Kasko tarihi, muayene tarihi, model yılı ve kasa m³ bilgisi için uygulamada `vehicles` tablosu ve PHP sayfaları kullanılır.

## Veritabanı

Migration dosyası: `php-app/sql/migrations/01_add_vehicles_table.sql`

```bash
mysql -u kullanici -p veritabani < php-app/sql/migrations/01_add_vehicles_table.sql
```

Tablo: `vehicles`  
- `id` (CHAR 36), `company_id`, `plate`, `model_year`, `kasko_date`, `inspection_date`, `cargo_volume_m3`, `notes`, `created_at`, `updated_at`, `deleted_at`

## Frontend’in beklediği API uçları

Base URL: `/api` (veya `VITE_API_URL`)

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/vehicles` | Şirkete ait tüm araç listesi (soft-delete hariç) |
| GET | `/vehicles/:id` | Tek araç |
| GET | `/vehicles/report` | (Opsiyonel) Rapor + yaklaşan kasko/muayene listesi |
| GET | `/vehicles/upcoming-deadlines?days=30` | (Opsiyonel) Yaklaşan kasko ve muayene tarihleri |
| POST | `/vehicles` | Yeni araç (body: plate, model_year, kasko_date, inspection_date, cargo_volume_m3, notes) |
| PATCH | `/vehicles/:id` | Araç güncelle |
| DELETE | `/vehicles/:id` | Araç sil (soft-delete önerilir) |

Tüm uçlar JWT ile korunmalı; `company_id` giriş yapan kullanıcının şirketinden alınmalı.

## Rapor ve bildirimler

- **Rapor:** Frontend, Nakliye İşleri ve Sözleşmeler API’lerinden gelen `vehicle_plate` alanlarını toplayıp plaka bazlı nakliye sayısını kendisi hesaplıyor. Araç master verisi (kasko, muayene, model, kasa) `/vehicles` ile gelirse tabloda birleştirilir.
- **Kasko / muayene bildirimi:** Araçlar sayfasında, kasko veya muayene tarihi önümüzdeki 30 gün içindeyse sarı kutu ile uyarı gösterilir. İsterseniz sunucuda periyodik bir görev (cron) ile yaklaşan tarihleri kontrol edip `notifications` tablosuna kayıt ekleyerek push bildirimi de tetikleyebilirsiniz.

## Özet

- Sol menüde **Araçlar** eklendi.
- **Araç raporu:** Her plaka için Nakliye İşleri sayısı, Yeni Satış (sözleşme) sayısı, toplam; plaka, model yılı, kasko tarihi, muayene tarihi, kasa m³ (API varsa).
- **Bildirim:** Kasko ve muayene tarihi yaklaşan araçlar Araçlar sayfasında uyarı kutularında listelenir.
- Backend’de `vehicles` tablosu ve yukarıdaki API’ler tanımlandığında Araç Ekle/Düzenle/Sil tam çalışır.
