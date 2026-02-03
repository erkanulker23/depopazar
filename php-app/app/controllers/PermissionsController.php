<?php
class PermissionsController
{
    public function index(): void
    {
        Auth::requireStaff();
        $roles = [
            ['id' => 'super_admin', 'name' => 'Süper Admin', 'desc' => 'Tüm sisteme erişim'],
            ['id' => 'company_owner', 'name' => 'Şirket Sahibi', 'desc' => 'Şirket yönetimi'],
            ['id' => 'company_staff', 'name' => 'Personel', 'desc' => 'Şirket personeli'],
            ['id' => 'data_entry', 'name' => 'Veri Girişi', 'desc' => 'Veri giriş yetkisi'],
            ['id' => 'accounting', 'name' => 'Muhasebe', 'desc' => 'Muhasebe işlemleri'],
            ['id' => 'customer', 'name' => 'Müşteri', 'desc' => 'Müşteri paneli'],
        ];
        require __DIR__ . '/../../views/permissions/index.php';
    }
}
