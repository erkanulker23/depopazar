# Eski Proje (React/NestJS) – PHP Eşleşme Rehberi

Bu dokümanda eski projedeki sayfaların nasıl çalıştığı ve PHP uygulamasındaki karşılıkları listelenir.

---

## 1. Girişler (Sözleşmeler) – ContractsPage

### Eski projede
- **URL:** `newSale=true` veya `/contracts/new` → Yeni Satış modalı açılır.
- **Filtreler:** Durum (Tümü / Aktif / Sonlandırılanlar), Ödeme (Tümü / Ödeme yapanlar / yapmayanlar), Borç (Tümü / Borcu olanlar / olmayanlar).
- **Uyarı:** Birden fazla aktif sözleşmesi olan müşteriler listelenir (`getCustomersWithMultipleContracts`).
- **Liste:** Sayfalama (page/limit), checkbox ile çoklu seçim.
- **İşlemler:** Sözleşme no’ya tıklanınca detay sayfası, **Ödeme Al**, **Sonlandır**, **Sil** (tekil ve toplu).
- **Yeni Satış modalı (NewSaleModal):**
  - Müşteri: **Arama (Combobox)** + **Yeni müşteri ekle** butonu (AddCustomerModal).
  - Depo / Oda: Sadece **status=empty** odalar listelenir.
  - Başlangıç–Bitiş tarihi; seçime göre aylar üretilir.
  - **Aylık fiyatlandırma:** Her ay için fiyat (ve not) – `contract_monthly_prices` + sözleşme kaydından sonra **payments** oluşturulur.
  - Personel seçimi (çoklu), Satışı yapan kişi (depo sahibi).
  - Nakliye: ücret, plaka, şoför adı/telefon.
  - İndirim, eşya listesi (opsiyonel), PDF sözleşme (opsiyonel), notlar.

### PHP’de şu an
- `/girisler`, `/girisler?newSale=1` → Liste + Yeni Satış modalı.
- Modal: Müşteri (select), Depo, Oda (depoya göre), Tarihler, Personel (checkbox). **Eksik:** müşteri arama, yeni müşteri ekle, sadece boş oda, aylık fiyatlar, nakliye/indirim, sözleşme sonrası ödeme kayıtları.
- Liste: Filtre yok, sayfalama yok, Ödeme Al / Sonlandır / Sil / detay linki yok.

---

## 2. Ödeme Al (Collect Payment)

### Eski projede
- Girişler sayfasında sözleşme satırında **Ödeme Al** butonu → **PaymentModal** açılır.
- Ödeme türü: **Ay ödemesi** (bekleyen aylardan biri), **Ara ödeme**, **Toplam ödeme**.
- Ay ödemesinde: ilgili ay seçilir, mevcut payment kaydı `status=paid` yapılır.
- Ara/Toplam: yeni `payments` kaydı oluşturulur (amount, due_date, paid_at, payment_method, notes).

### PHP’de şu an
- Ödemeler sayfası sadece liste. **Ödeme Al** modalı veya formu yok; Girişler’de de buton yok.

---

## 3. Nakliye İşleri – TransportationJobsPage

### Eski projede
- Liste, **Müşteri ara**, **Yıl / Ay** filtresi, **Yeni Nakliye İşi Ekle** butonu.
- Modal: Müşteri (arama + yeni müşteri), iş tipi, alış/teslim adres, kat/asansör, oda sayısı, personel, fiyat, KDV, PDF, notlar, tarih, durum, ödendi.
- Düzenle / Sil. Genişletilmiş satırda detay gösterimi.

### PHP’de şu an
- Liste + müşteri/yıl/ay filtresi var. **Yeni Nakliye İşi Ekle** butonu sadece görsel; ekleme/düzenleme/silme yok.

---

## 4. Müşteriler – CustomersPage

### Eski projede
- Arama, sayfalama, **Yeni müşteri** (AddCustomerModal), Excel export/import.
- Satırda genişlet (expand) ile **detay:** sözleşmeler, ödemeler, barkod PDF, ödeme al, sözleşme gönder.

### PHP’de şu an
- Liste + hover’da tooltip (telefon, e-posta, adres, not). **Eksik:** yeni müşteri ekleme, genişletilmiş detay, Excel, ödeme al/sözleşme gönder.

---

## 5. Hizmetler – ServicesPage

### Eski projede
- **Kategoriler** (service_categories): ekle/düzenle/sil. **Hizmetler** (services): kategoriye göre listelenir, ekle/düzenle/sil.

### PHP’de şu an
- Sadece hizmet listesi (kategori adıyla). Kategori/hizmet ekleme-düzenleme-silme yok.

---

## 6. Teklifler – ProposalsPage

### Eski projede
- Teklif listesi, **Yeni teklif** (CreateProposalPage), düzenle/sil, PDF.

### PHP’de şu an
- Sadece teklif listesi. Oluşturma/düzenleme/silme yok.

---

## 7. Kullanıcılar (Personel) – StaffPage

### Eski projede
- Personel listesi, **Yeni personel** (AddStaffModal), düzenle/sil, detay sayfası.

### PHP’de şu an
- Sadece personel listesi. Ekleme/düzenleme/silme yok.

---

## 8. Ödemeler – PaymentsPage

### Eski projede
- Ödeme listesi, filtreler, sözleşmeye göre **Ödeme Al** (CollectPaymentModal) akışı.

### PHP’de şu an
- Sadece ödeme listesi. Ödeme al (tahsilat girişi) formu/modalı yok.

---

## 9. Raporlar – ReportsPage

### Eski projede
- Rapor türleri, banka hesabına göre ödemeler vb.

### PHP’de şu an
- Placeholder sayfa.

---

## 10. Ayarlar – SettingsPage

### Eski projede
- Şirket bilgileri, tema, bildirim ayarları vb.

### PHP’de şu an
- Placeholder sayfa.

---

## Öncelikli Yapılacaklar (PHP)

1. **Yeni Satış (Girişler):** Sadece **boş** odaları listele; sözleşme kaydından sonra başlangıç–bitiş arası aylar için `contract_monthly_prices` + `payments` (pending) oluştur.
2. **Ödeme Al:** Girişler veya Ödemeler sayfasında sözleşme seçip ödeme girişi (mevcut payment’ı paid yap veya yeni payment ekle).
3. **Müşteri ekleme:** Müşteriler sayfasında “Yeni müşteri” modal/form.
4. **Nakliye:** Yeni nakliye işi ekleme formu ve mümkünse düzenleme/silme.
5. **Hizmetler:** Kategori ve hizmet ekleme/düzenleme/silme.
6. **Girişler liste:** Filtreler (durum/borç), sözleşme detay linki, Sonlandır/Sil (tekil).

Bu dosya, eski proje davranışını PHP’ye taşırken referans olarak kullanılabilir.
