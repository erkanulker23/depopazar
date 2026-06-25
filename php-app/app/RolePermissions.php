<?php
/**
 * Rol bazlı yetki tanımları — menü görünürlüğü ve modül işlemleri.
 * Süper Admin /yetkiler sayfasında bu matris görüntülenir.
 */
class RolePermissions
{
    public const STAFF_ROLES = ['super_admin', 'company_owner', 'company_staff', 'data_entry', 'accounting'];

    public static function roleLabels(): array
    {
        return [
            'super_admin' => 'Süper Admin',
            'company_owner' => 'Şirket Sahibi',
            'company_staff' => 'Personel',
            'data_entry' => 'Veri Girişi',
            'accounting' => 'Muhasebe',
            'customer' => 'Müşteri',
        ];
    }

    public static function roleDescriptions(): array
    {
        return [
            'super_admin' => 'Tüm şirketler; kullanıcı/rol yönetimi, ayarlar, raporlar ve operasyonel tüm modüller.',
            'company_owner' => 'Kendi şirketinde tam yönetim; personel, depo, müşteri, ödeme, masraf ve raporlar.',
            'company_staff' => 'Günlük operasyon: girişler, müşteri, tahsilat, nakliye, depo/oda; masraf ve ayarlar menüde yok.',
            'data_entry' => 'Müşteri ve depo kayıtları; raporlar, masraflar, kullanıcılar ve ayarlar menüde yok.',
            'accounting' => 'Ödemeler, masraflar, raporlar; kullanıcı/ayar yönetimi yok.',
        ];
    }

    public static function actionLabels(): array
    {
        return [
            'nav' => 'Menüde görünür',
            'view' => 'Görüntüleme',
            'create' => 'Ekleme',
            'edit' => 'Düzenleme',
            'delete' => 'Silme',
            'export' => 'Dışa aktarma',
            'print' => 'Yazdırma',
        ];
    }

    /** Modül tanımları: id, etiket, href, özel işlemler açıklaması */
    public static function modules(): array
    {
        return [
            ['id' => 'genel_bakis', 'label' => 'Genel Bakış', 'href' => '/genel-bakis', 'special' => 'Kurulum rehberi, haftalık özet panelleri'],
            ['id' => 'girisler', 'label' => 'Tüm Girişler / Depo Girişi', 'href' => '/girisler', 'special' => 'Sözleşme sonlandırma, çıkış belgesi, toplu silme, yeni satış'],
            ['id' => 'odemeler', 'label' => 'Ödemeler / Tahsilat', 'href' => '/odemeler', 'special' => 'Tahsilat alma, ödenmiş ödemeyi iptal, makbuz yazdır'],
            ['id' => 'nakliye', 'label' => 'Nakliye İşler', 'href' => '/nakliye-isler', 'special' => 'İşe masraf ekleme'],
            ['id' => 'araclar', 'label' => 'Araçlar', 'href' => '/araclar', 'special' => 'Trafik sigortası, kasko, kaza kayıtları ve belge yükleme'],
            ['id' => 'hizmetler', 'label' => 'Hizmetler', 'href' => '/hizmetler', 'special' => 'Kategori ve hizmet tanımları'],
            ['id' => 'teklifler', 'label' => 'Teklifler', 'href' => '/teklifler', 'special' => 'Durum güncelleme, teklif yazdırma'],
            ['id' => 'kullanicilar', 'label' => 'Kullanıcılar', 'href' => '/kullanicilar', 'special' => 'Rol atama, şifre değiştirme (sadece yöneticiler)'],
            ['id' => 'yetkiler', 'label' => 'Kullanıcı Yetkileri', 'href' => '/yetkiler', 'special' => 'Rol yetki matrisini görüntüleme (Süper Admin)'],
            ['id' => 'depolar', 'label' => 'Depolar', 'href' => '/depolar', 'special' => 'Excel içe/dışa aktarma'],
            ['id' => 'odalar', 'label' => 'Odalar', 'href' => '/odalar', 'special' => 'Excel içe/dışa aktarma'],
            ['id' => 'musteriler', 'label' => 'Müşteriler', 'href' => '/musteriler', 'special' => 'Borçlandırma, belge, SMS, barkod, toplu silme, Excel'],
            ['id' => 'masraflar', 'label' => 'Masraflar', 'href' => '/masraflar', 'special' => 'Masraf kategorileri, banka/kredi kartı kaynağı'],
            ['id' => 'raporlar', 'label' => 'Raporlar', 'href' => '/raporlar', 'special' => 'Banka hesapları, masraf raporları; banka raporundan tahsilat iptali'],
            ['id' => 'bildirimler', 'label' => 'Bildirimler', 'href' => '/bildirimler', 'special' => 'Okundu işaretleme, bildirim silme (kendi bildirimleri)'],
            ['id' => 'ayarlar', 'label' => 'Ayarlar', 'href' => '/ayarlar', 'special' => 'Firma, PayTR, banka, kredi kartı, e-posta, SMS, şablonlar'],
        ];
    }

    /**
     * Rol × modül × işlem matrisi.
     * @return array<string, array<string, array<string, bool>>>
     */
    public static function matrix(): array
    {
        static $matrix = null;
        if ($matrix !== null) {
            return $matrix;
        }

        $fullCrudExport = ['view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => true, 'print' => true];
        $fullCrud = ['view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => false, 'print' => true];
        $payments = ['view' => true, 'create' => true, 'edit' => false, 'delete' => false, 'export' => false, 'print' => true];
        $notifications = ['view' => true, 'create' => false, 'edit' => false, 'delete' => true, 'export' => false, 'print' => false];
        $reports = ['view' => true, 'create' => false, 'edit' => false, 'delete' => false, 'export' => false, 'print' => false];
        $dashboard = ['view' => true, 'create' => false, 'edit' => false, 'delete' => false, 'export' => false, 'print' => false];
        $none = ['view' => false, 'create' => false, 'edit' => false, 'delete' => false, 'export' => false, 'print' => false];
        $usersOwner = ['view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => false, 'print' => false];
        $usersSuper = ['view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => false, 'print' => false];
        $settings = ['view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => false, 'print' => false];
        $permissionsView = ['view' => true, 'create' => false, 'edit' => false, 'delete' => false, 'export' => false, 'print' => false];

        $build = function (array $overrides) use ($dashboard, $fullCrud, $fullCrudExport, $payments, $notifications, $reports, $none, $usersOwner, $usersSuper, $settings, $permissionsView) {
            $base = [
                'genel_bakis' => $dashboard,
                'girisler' => $fullCrud,
                'odemeler' => $payments,
                'nakliye' => $fullCrud,
                'araclar' => $fullCrud,
                'hizmetler' => $fullCrud,
                'teklifler' => $fullCrud,
                'kullanicilar' => $none,
                'yetkiler' => $none,
                'depolar' => $fullCrudExport,
                'odalar' => $fullCrudExport,
                'musteriler' => $fullCrudExport,
                'masraflar' => $none,
                'raporlar' => $none,
                'bildirimler' => $notifications,
                'ayarlar' => $none,
            ];
            foreach ($overrides as $moduleId => $perms) {
                $base[$moduleId] = array_merge($base[$moduleId] ?? $none, $perms);
            }
            return $base;
        };

        $matrix = [
            'super_admin' => $build([
                'kullanicilar' => $usersSuper,
                'yetkiler' => $permissionsView,
                'masraflar' => $fullCrud,
                'raporlar' => $reports,
                'ayarlar' => $settings,
            ]),
            'company_owner' => $build([
                'kullanicilar' => $usersOwner,
                'masraflar' => $fullCrud,
                'raporlar' => $reports,
                'ayarlar' => $settings,
            ]),
            'company_staff' => $build([
                'raporlar' => $reports,
            ]),
            'data_entry' => $build([]),
            'accounting' => $build([
                'masraflar' => $fullCrud,
                'raporlar' => $reports,
            ]),
        ];

        return $matrix;
    }

    /** Menüde görünür mü (href bazlı) */
    public static function canViewNav(string $role, string $href): bool
    {
        if ($role === 'customer') {
            return false;
        }
        if (in_array($role, ['super_admin', 'company_owner'], true)) {
            if ($role === 'company_owner' && self::hrefMatches($href, '/yetkiler')) {
                return false;
            }
            return true;
        }

        $path = self::normalizeHref($href);
        $restricted = [
            '/ayarlar' => ['super_admin', 'company_owner'],
            '/kullanicilar' => ['super_admin', 'company_owner'],
            '/yetkiler' => ['super_admin'],
            '/raporlar' => ['super_admin', 'company_owner', 'accounting', 'company_staff'],
            '/masraflar' => ['super_admin', 'company_owner', 'accounting'],
        ];
        foreach ($restricted as $prefix => $allowed) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return in_array($role, $allowed, true);
            }
        }
        if ($role === 'data_entry') {
            return !in_array($path, ['/ayarlar', '/kullanicilar', '/yetkiler', '/masraflar'], true);
        }
        return true;
    }

    public static function can(string $role, string $moduleId, string $action): bool
    {
        if ($action === 'nav') {
            $module = self::findModule($moduleId);
            if (!$module) {
                return false;
            }
            return self::canViewNav($role, $module['href']);
        }
        $matrix = self::matrix();
        return !empty($matrix[$role][$moduleId][$action]);
    }

    public static function findModule(string $moduleId): ?array
    {
        foreach (self::modules() as $m) {
            if ($m['id'] === $moduleId) {
                return $m;
            }
        }
        return null;
    }

    /** Kullanıcı için özet: modül başına izinler */
    public static function summarizeForRole(string $role): array
    {
        $matrix = self::matrix()[$role] ?? [];
        $actions = array_keys(self::actionLabels());
        $out = [];
        foreach (self::modules() as $module) {
            $id = $module['id'];
            $perms = $matrix[$id] ?? [];
            $nav = self::canViewNav($role, $module['href']);
            $row = ['module' => $module, 'nav' => $nav, 'actions' => []];
            foreach ($actions as $action) {
                if ($action === 'nav') {
                    $row['actions']['nav'] = $nav;
                } else {
                    $row['actions'][$action] = !empty($perms[$action]);
                }
            }
            $out[] = $row;
        }
        return $out;
    }

    public static function countAllowedModules(string $role): int
    {
        return count(array_filter(self::summarizeForRole($role), fn($r) => $r['actions']['view'] ?? false));
    }

    private static function normalizeHref(string $href): string
    {
        $path = $href;
        if (($q = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $q);
        }
        return $path;
    }

    private static function hrefMatches(string $href, string $prefix): bool
    {
        $path = self::normalizeHref($href);
        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
}
