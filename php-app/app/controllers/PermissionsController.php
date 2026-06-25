<?php
class PermissionsController
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin', 'company_owner']);
        $roles = [
            ['id' => 'super_admin', 'name' => 'Süper Admin', 'desc' => 'Tüm şirketler ve kullanıcılar; rol atama, ayarlar, raporlar, depo/müşteri/ödeme yönetimi'],
            ['id' => 'company_owner', 'name' => 'Şirket Sahibi', 'desc' => 'Kendi şirketinde tam yönetim; personel ekleme, depo, müşteri, ödeme, raporlar'],
            ['id' => 'company_staff', 'name' => 'Personel', 'desc' => 'Depo girişi, müşteri, ödeme tahsilatı, nakliye işleri, günlük operasyon'],
            ['id' => 'data_entry', 'name' => 'Veri Girişi', 'desc' => 'Müşteri ve depo kayıtları; sınırlı rapor erişimi'],
            ['id' => 'accounting', 'name' => 'Muhasebe', 'desc' => 'Ödemeler, masraflar, banka/kasa raporları, cari takibi'],
            ['id' => 'customer', 'name' => 'Müşteri', 'desc' => 'Müşteri paneli; kendi sözleşme ve ödeme bilgileri'],
        ];
        require __DIR__ . '/../../views/permissions/index.php';
    }
}
