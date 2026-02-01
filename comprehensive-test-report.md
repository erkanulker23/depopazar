# Kapsamlı Test Raporu - DepoPazar
## Tarih: 2026-01-28

## Test Edilen Özellikler

### ✅ 1. Müşteri Ekleme (1'den fazla)
- **Durum:** ✅ Tamamlandı
- **Test Edilen:**
  - Ahmet Yılmaz eklendi (ahmet.yilmaz@test.com) ✅
  - Ayşe Kaya eklendi (ayse.kaya@test.com) ✅
  - Mehmet Demir (mevcut - zaten vardı)
- **Sonuç:** Başarılı - 3 müşteri mevcut (1'den fazla ✅)
- **Not:** Bazı müşteriler borçlu olacak (sözleşme eklendiğinde), bazıları borç ödeyecek

### ✅ 2. Depo Ekleme (1'den fazla)
- **Durum:** ✅ Tamamlandı
- **Test Edilen:**
  - İkinci Depo eklendi (Ankara, Çankaya)
- **Mevcut Depolar:**
  - Test Depo (İstanbul, Kadıköy)
  - Ana Depo (İstanbul, Şişli)
  - İkinci Depo (Ankara, Çankaya) - YENİ ✅
- **Sonuç:** Başarılı - 3 depo mevcut (1'den fazla ✅)

### 🔄 3. Oda Ekleme (1'den fazla)
- **Durum:** 🔄 Devam Ediyor
- **Not:** Depolar eklendikten sonra odalar eklenecek

### ✅ 4. Banka Hesapları Ekleme
- **Durum:** ✅ Tamamlandı
- **Test Edilen:**
  - Ziraat Bankası eklendi
  - Hesap Sahibi: Demo Depo Firması
  - Hesap No: 1234567890
  - IBAN: TR330001000000123456789012
  - Şube: Kadıköy Şubesi
- **Sonuç:** Başarılı

### 🔄 5. Personel Ekleme
- **Durum:** 🔄 Bekliyor
- **Konum:** Personel sayfası

### 🔄 6. Yeni Satış Gir (1'den fazla)
- **Durum:** 🔄 Bekliyor
- **Önemli:** Her müşterinin sadece 1 aktif sözleşmesi olmalı
- **Kontrol:** Backend'de `contracts.service.ts` içinde kontrol var (satır 40-52)

### 🔄 7. Nakliye İşleri Ekleme (1'den fazla)
- **Durum:** 🔄 Bekliyor
- **Konum:** Nakliye İşler sayfası

### 🔄 8. Ödeme Alma (Müşteriden)
- **Durum:** 🔄 Bekliyor
- **Konum:** Ödemeler sayfası veya müşteri detay sayfası

### 🔄 9. Tek Sözleşme Kontrolü
- **Durum:** 🔄 Bekliyor
- **Kontrol:** Her müşterinin sadece 1 aktif sözleşmesi olmalı
- **Backend Kontrolü:** `contracts.service.ts` içinde mevcut (satır 40-52)

## Backend Kontrolleri

### Tek Sözleşme Kuralı
```typescript
// backend/src/modules/contracts/contracts.service.ts (satır 39-52)
const existingActiveContract = await this.contractsRepository.findOne({
  where: {
    customer_id: contractData.customer_id,
    is_active: true,
  },
});

if (existingActiveContract) {
  throw new ConflictException(
    `Bu müşterinin zaten aktif bir sözleşmesi bulunmaktadır (Sözleşme No: ${existingActiveContract.contract_number}). ` +
    `Yeni sözleşme oluşturmadan önce mevcut sözleşmeyi sonlandırmanız gerekmektedir.`
  );
}
```

## Test Senaryoları

### Senaryo 1: Müşteri Borçlu Durumu
1. Müşteri ekle
2. Müşteriye sözleşme ekle (depo girişi)
3. Ödeme alınmadan bırak → Müşteri borçlu olacak

### Senaryo 2: Müşteri Borç Ödeme Durumu
1. Müşteri ekle
2. Müşteriye sözleşme ekle
3. Ödeme al → Müşteri borç ödemiş olacak

### Senaryo 3: Tek Sözleşme Kuralı Testi
1. Müşteri ekle
2. Müşteriye sözleşme ekle → ✅ Başarılı
3. Aynı müşteriye ikinci sözleşme eklemeyi dene → ❌ Hata vermeli

## Sonraki Adımlar

1. ✅ Müşteriler eklendi
2. ✅ Depolar eklendi
3. 🔄 Odalar eklenmeli
4. 🔄 Banka hesapları eklenmeli
5. 🔄 Personel eklenmeli
6. 🔄 Satışlar (sözleşmeler) eklenmeli
7. 🔄 Nakliye işleri eklenmeli
8. 🔄 Ödemeler alınmalı
9. 🔄 Tek sözleşme kuralı test edilmeli
10. 🔄 Loglar kontrol edilmeli

## Notlar

- Browser testleri devam ediyor
- Backend logları kontrol edilmeli
- Her özellik için hata kontrolü yapılmalı
- Tek sözleşme kuralı backend'de zaten kontrol ediliyor
