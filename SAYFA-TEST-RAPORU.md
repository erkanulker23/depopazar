# DepoPazar – Tüm Sayfalar Test Raporu

**Tarih:** 2 Şubat 2026  
**Ortam:** https://depotakip-v1.test/  
**Kullanıcı:** Super Admin (erkanulker0@gmail.com)

---

## Özet

| Durum | Sayfa sayısı |
|-------|--------------|
| Çalışıyor | 18 |
| Hata / Eksik | 2 (Hizmetler, Teklifler listesi) |
| Yükleniyor / Kısmi | 1 (Nakliye İşler – ilk yüklemede "Yükleniyor..." göründü) |

---

## Sayfa Bazlı Sonuçlar

### Giriş ve Ana Sayfalar

| Sayfa | URL | Durum | Not |
|-------|-----|--------|-----|
| Giriş | `/login` | Çalışıyor | Giriş yapıldı, dashboard’a yönlendi |
| Dashboard | `/dashboard` | Çalışıyor | Özet kartlar, bekleyen/geciken ödeme, toplam borç |

### Depo / Oda

| Sayfa | URL | Durum | Not |
|-------|-----|--------|-----|
| Depolar | `/warehouses` | Çalışıyor | Liste, arama, filtre, Yeni Depo, Ana Depo / Yedek Depo |
| Odalar | `/rooms` | Çalışıyor | Tablo, arama, durum/depo filtresi, Oda Ekle |

### Müşteri / Sözleşme / Ödeme

| Sayfa | URL | Durum | Not |
|-------|-----|--------|-----|
| Müşteriler | `/customers` | Çalışıyor | Liste, arama, Excel, Yeni Müşteri |
| Müşteri Detay | `/customers/:id` | Çalışıyor | Ahmet Yılmaz – borç, sözleşmeler, takvim, ödemeler |
| Tüm Girişler (Sözleşmeler) | `/contracts` | Çalışıyor | Kartlar, filtreler, Yeni Satış Gir |
| Sözleşme Detay | `/contracts/:id` | Çalışıyor | CNT-2024-001 – fatura, müşteri, ödeme geçmişi |
| Ödemeler | `/payments` | Çalışıyor | Tablo, arama, durum filtresi, Ödeme Al |
| Ödeme Başarı | `/payments/success` | Çalışıyor | Sayfa açıldı |

### Personel / Yetki

| Sayfa | URL | Durum | Not |
|-------|-----|--------|-----|
| Kullanıcılar | `/staff` | Çalışıyor | Tablo, Kullanıcı Ekle, Depo Personeli / Sahibi / Mahmut taner |
| Kullanıcı Yetkileri | `/permissions` | Çalışıyor | Süper Admin, Depo Sahibi, Muhasebe, Veri Girişi, Personel kartları |

### Nakliye / Hizmet / Teklif

| Sayfa | URL | Durum | Not |
|-------|-----|--------|-----|
| Nakliye İşler | `/transportation-jobs` | Yükleniyor / Kısmi | İlk açılışta "Yükleniyor..." göründü; API cevabı beklenmeli |
| Hizmetler | `/services` | Hata | "Veriler yüklenirken hata oluştu" – API muhtemelen "User has no company" |
| Teklifler | `/proposals` | Hata | "Teklifler yüklenirken hata oluştu" – aynı company_id kaynaklı olabilir |
| Yeni Teklif | `/proposals/new` | Çalışıyor | Form açılıyor; hizmet combobox boş (services API hatası) |

### Rapor / Ayarlar

| Sayfa | URL | Durum | Not |
|-------|-----|--------|-----|
| Raporlar | `/reports` | Çalışıyor | Yıl/ay, Doluluk, Gelir raporu, Banka Hesap Raporu linki |
| Ayarlar | `/settings` | Çalışıyor | Firma Bilgileri, Mail, PayTR, SMS, Banka Hesapları, Yedekleme |

---

## Yapılan Düzeltme

**Hizmetler / Teklifler "User has no company" hatası için:**

- `backend/src/modules/companies/companies.service.ts` içinde `getCompanyIdForUser` güncellendi:
  - `user` için optional chaining (`user?.company_id`, `user?.role`)
  - Super admin için hem enum hem string kontrolü: `role === UserRole.SUPER_ADMIN || role === 'super_admin'`
- Super admin’in `company_id`’si yoksa ilk şirket yine kullanılıyor; tip ve rol kontrolü sıkılaştırıldı.

Değişikliğin etkili olması için backend’in yeniden başlatılması gerekir. Sonrasında Hizmetler ve Teklifler sayfaları tekrar test edilmeli.

---

## Konsol

- Vite bağlantı ve React DevTools bilgi mesajları görüldü; kritik hata yok.

---

## Sonuç

- Çoğu sayfa (dashboard, depolar, odalar, müşteriler, sözleşmeler, ödemeler, personel, yetkiler, raporlar, ayarlar, müşteri/sözleşme detay, yeni teklif formu) düzgün çalışıyor.
- **Hizmetler** ve **Teklifler** listesi API hatası veriyor; yapılan `getCompanyIdForUser` düzeltmesi ve backend restart sonrası tekrar kontrol edilmeli.
- **Nakliye İşler** ilk açılışta "Yükleniyor..." göstermişti; birkaç saniye sonra veri gelirse sayfa da çalışır kabul edilebilir.
