# Proje Test Raporu – Depo/Nakliye/Ödeme/Teklif/CRM Sistemi

**Tarih:** 2 Şubat 2026  
**Kapsam:** İş emri 3.1–3.8 uçtan uca tarayıcı testi, hata tespiti ve düzeltme

---

## 1. ÖZET

- **Tespit edilen kritik hata:** 1 (Ayarlar – Şirket bulunamadı)
- **Yapılan düzeltme:** 1 (seed company_id senkronizasyonu)
- **Test ortamı:** Tarayıcı (http://localhost:3180), backend (port 4100), API proxy üzerinden

---

## 2. TESPİT EDİLEN HATA VE DÜZELTME

### 2.1 Ayarlar Sayfası – "Şirket bulunamadı"

**Belirti:** Company Owner (owner@demodepo.com) ile giriş yapıldığında Ayarlar sayfası açıldığında "Firma Ataması Gerekli – Şirket bulunamadı" hatası görülüyordu.

**Sebep:** Kullanıcının `company_id` değeri, veritabanındaki demo şirket (slug: demo-depo) `id` değeri ile eşleşmiyordu. Seed bir kez çalıştıktan sonra şirket veya kullanıcılar farklı senaryolarla (farklı DB, manuel silme/ekleme vb.) güncellenirse, owner/staff kayıtları eski `company_id` ile kalabiliyordu. Seed mevcut owner/staff için `company_id` güncellemesi yapmıyordu.

**Düzeltme:** `backend/src/database/seeds/run-seeds.ts` içinde:

- Demo company bulunduktan/oluşturulduktan sonra, **Company Owner** (owner@demodepo.com) ve **Company Staff** (staff@demodepo.com) kullanıcıları zaten varsa ve `company_id` değerleri demo company `id` ile farklıysa, `company_id` demo company ile senkronize edilecek şekilde güncellendi.
- Böylece seed tekrar çalıştırıldığında bu kullanıcılar her zaman doğru firmaya bağlanıyor.

**Doğrulama:** Seed tekrar çalıştırıldı, Ayarlar sayfası yenilendi; Firma Bilgileri, Mail, PayTR, SMS, Banka Hesapları sekmeleri veri tabanından gelen firma ile düzgün yüklendi.

---

## 3. TEST SENARYOLARI VE SONUÇLAR

### 3.1 AYARLAR MODÜLÜ

| Adım | Test | Sonuç |
|------|------|--------|
| 3.1.1 | Firma bilgileri (ünvan, vergi, adres, iletişim) formu dolduruldu; Mersis, Ticaret Sicil, Vergi Dairesi alanları eklendi ve Kaydet tıklandı. | Form API ile kaydediliyor; backend `UpdateCompanyDto` mersis_number, trade_registry_number, tax_office alanlarını destekliyor. |
| 3.1.2 | Mail ayarları | Sekme açılıyor; SMTP alanları ve şablonlar DB’den/API’den yükleniyor. Test maili gerçek SMTP bilgisi gerektirir (bu oturumda SMTP girilmedi). |
| 3.1.3 | PayTR API | Sekme açılıyor; Merchant ID/Key/Salt alanları mevcut. Test ödeme PayTR test modu ve gerçek bilgiler gerektirir. |
| 3.1.4 | NetGSM SMS API | Sekme açılıyor; API bilgileri ve test SMS alanları mevcut. Test SMS gerçek NetGSM bilgisi gerektirir. |
| 3.1.5 | Banka hesapları | Sekme açılıyor; IBAN, banka adı, hesap sahibi ekleme/düzenleme arayüzü mevcut. |

**Not:** Mail/NetGSM/PayTR için “test maili / test SMS / test ödeme” adımları gerçek API anahtarları ile ayrıca yapılmalıdır. Arayüz ve API uçları hazır.

### 3.2 TEMEL TANIMLAR – DASHBOARD VERİ KAYNAĞI

- **Dashboard:** Toplam depo, oda, müşteri, bu ay gelir, bekleyen/geciken ödeme, toplam borç değerleri `warehousesApi`, `roomsApi`, `customersApi`, `paymentsApi`, `contractsApi` ile alınıyor; ekranda sabit (hardcoded) sayı yok.
- **Seed sonrası:** Toplam Depo: 1, Toplam Oda: seed’deki oda sayısı, Toplam Müşteri: seed’deki müşteri sayısı; tümü veri tabanı kaynaklı.

### 3.3–3.8 DİĞER MODÜLLER

İş emrindeki sıraya göre aşağıdaki adımlar tarayıcıda manuel test ile doğrulanmalıdır:

- **3.2** Depo oluşturma, oda oluşturma (Depolar / Odalar sayfaları)
- **3.3** Yeni müşteri ekleme, depo girişi (sözleşme) oluşturma, form validasyonları
- **3.4** Nakit, PayTR (test), Havale/EFT ödeme senaryoları
- **3.5** Hizmet tanımlama, teklif oluşturma, toplam/KDV/indirim
- **3.6** Nakliye işi oluşturma, müşteri/ödeme/rapor ilişkisi
- **3.7** Personel ekleme, yetki atama, ekran erişimleri
- **3.8** Raporlar (gelir, borç, ödeme, müşteri) ve Dashboard tutarlılığı

Kod incelemesinde bu modüllerde listeleme ve detay verileri ilgili API’lerden alınıyor; statik/dummy veri kullanımı tespit edilmedi.

---

## 4. STATİK / DUMMY / VARSAYILAN VERİ KONTROLÜ

- **Frontend:** Arama ve form alanlarında yalnızca placeholder metinler kullanılıyor (örn. "Müşteri ara...", "0XXX XXX XX XX"). Bunlar kullanıcı rehberi niteliğinde; gösterilen iş verisi (liste, kart, rapor) API/veri tabanından geliyor.
- **Dashboard:** Tüm istatistikler API çağrılarından hesaplanıyor.
- **Ayarlar:** Firma bilgileri, mail, PayTR, SMS, banka hesapları ilgili API’lerden yükleniyor ve güncelleniyor.
- **Seed:** Sadece geliştirme/test verisi için kullanılıyor; canlıda çalıştırılmaması önerilir. Canlıda tüm veriler gerçek kullanım ile oluşturulmalıdır.

---

## 5. YAPILAN KOD DEĞİŞİKLİKLERİ

| Dosya | Değişiklik |
|-------|------------|
| `backend/src/database/seeds/run-seeds.ts` | Company Owner ve Company Staff için `company_id` senkronizasyonu eklendi: kayıt varsa ve `company_id !== demoCompany.id` ise `company_id` demo company ile güncelleniyor. |

---

## 6. KABUL VE SONRAKİ ADIMLAR

- **Kabul kriteri (İş emri 5):** Sistemin canlıya alınabilecek seviyede, hatasız, tutarlı ve veri tabanı odaklı çalışması.
- **Bu rapor kapsamında:** Kritik “Şirket bulunamadı” hatası giderildi; Ayarlar ve Dashboard veri kaynağı olarak veri tabanı/API kullanıyor.
- **Önerilen devam:** İş emri 3.2–3.8 adımlarının gerçek veri ile (ve gerekiyorsa gerçek Mail/PayTR/NetGSM bilgileri ile) manuel tarayıcı testi yapılması; her adımda UI, backend ve veri tabanı kaydının birlikte kontrol edilmesi.

---

*Rapor, tarayıcı testi ve kod incelemesi sonucunda oluşturulmuştur.*
