<?php
/**
 * Rol bazlı yetki tanımları — menü görünürlüğü ve modül işlemleri.
 * Süper Admin /yetkiler sayfasında bu matris görüntülenir.
 */
class RolePermissions
{
    public const STAFF_ROLES = ['super_admin', 'company_owner', 'company_staff', 'data_entry', 'accounting', 'warehouse_manager'];

    /** Şirkete bağlı operasyonel roller (bildirim, nakliye personel listesi vb.) */
    public const COMPANY_OPERATIVE_ROLES = ['company_owner', 'company_staff', 'data_entry', 'accounting', 'warehouse_manager'];

    /** Süper admin dışındaki roller düzenlenebilir */
    public const EDITABLE_ROLES = ['company_owner', 'company_staff', 'data_entry', 'accounting', 'warehouse_manager'];

    private static ?array $matrixCache = null;

    public static function roleLabels(): array
    {
        return [
            'super_admin' => 'Süper Admin',
            'company_owner' => 'Şirket Sahibi',
            'company_staff' => 'Operasyon Kullanıcısı',
            'data_entry' => 'Veri Girişi',
            'accounting' => 'Muhasebe',
            'warehouse_manager' => 'Depo Sorumlusu',
            'customer' => 'Müşteri',
        ];
    }

    /** Kullanıcı ekleme/düzenleme formlarında gösterilecek roller (sıralı) */
    public static function formRoleOptions(bool $allowSuperAdmin = false): array
    {
        $order = ['company_owner', 'warehouse_manager', 'company_staff', 'data_entry', 'accounting'];
        if ($allowSuperAdmin) {
            $order[] = 'super_admin';
        }
        $labels = self::roleLabels();
        $out = [];
        foreach ($order as $key) {
            if (isset($labels[$key])) {
                $out[$key] = $labels[$key];
            }
        }
        return $out;
    }

    /** SQL IN (...) için şirket operasyonel rolleri */
    public static function sqlCompanyOperativeRoles(): string
    {
        return "'" . implode("','", self::COMPANY_OPERATIVE_ROLES) . "'";
    }

    /** SQL IN (...) için tüm personel rolleri */
    public static function sqlStaffRoles(): string
    {
        return "'" . implode("','", self::STAFF_ROLES) . "'";
    }

    public static function roleDescriptions(): array
    {
        return [
            'super_admin' => 'Tüm şirketler; kullanıcı/rol yönetimi, ayarlar, raporlar ve operasyonel tüm modüller.',
            'company_owner' => 'Kendi şirketinde tam yönetim; kullanıcı, saha personeli, depo, müşteri, ödeme, masraf ve raporlar.',
            'company_staff' => 'Günlük operasyon: girişler, müşteri, tahsilat, nakliye, depo/oda; masraf, kullanıcılar ve ayarlar menüde yok.',
            'warehouse_manager' => 'Depo ve oda yönetimi, girişler, müşteri ve tahsilat; araç/hizmet/teklif menüde yok.',
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
            ['id' => 'personel', 'label' => 'Personel', 'href' => '/personel', 'special' => 'Saha personeli (şoför, taşımacı vb.) — sisteme giriş yapmaz'],
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
     * Rol × modül × işlem matrisi (varsayılan + kayıtlı özelleştirmeler).
     * @return array<string, array<string, array<string, bool>>>
     */
    public static function matrix(): array
    {
        if (self::$matrixCache !== null) {
            return self::$matrixCache;
        }
        self::$matrixCache = self::mergeMatrix(self::defaultMatrix(), self::loadOverrides());
        return self::$matrixCache;
    }

    public static function clearCache(): void
    {
        self::$matrixCache = null;
    }

    /** @return array<string, array<string, array<string, bool>>> */
    public static function loadOverrides(): array
    {
        $path = self::overridesPath();
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, array<string, array<string, bool>>> $overrides */
    public static function saveOverrides(array $overrides): void
    {
        $path = self::overridesPath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        self::clearCache();
    }

    private static function overridesPath(): string
    {
        return (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__)) . '/storage/role_permissions.json';
    }

    /**
     * @param array<string, array<string, array<string, bool>>> $defaults
     * @param array<string, array<string, array<string, bool>>> $overrides
     * @return array<string, array<string, array<string, bool>>>
     */
    private static function mergeMatrix(array $defaults, array $overrides): array
    {
        $out = $defaults;
        foreach ($overrides as $role => $modules) {
            if (!is_array($modules) || !in_array($role, self::STAFF_ROLES, true)) {
                continue;
            }
            foreach ($modules as $moduleId => $perms) {
                if (!is_array($perms)) {
                    continue;
                }
                if (!isset($out[$role][$moduleId])) {
                    $out[$role][$moduleId] = [];
                }
                foreach ($perms as $action => $allowed) {
                    $out[$role][$moduleId][$action] = (bool) $allowed;
                }
            }
        }
        return $out;
    }

    /**
     * Kod içi varsayılan matris.
     * @return array<string, array<string, array<string, bool>>>
     */
    public static function defaultMatrix(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
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
                'personel' => $fullCrud,
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

        $cached = [
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
            'warehouse_manager' => $build([
                'araclar' => $none,
                'hizmetler' => $none,
                'teklifler' => $none,
            ]),
        ];

        return $cached;
    }

    /** Menüde görünür mü (href bazlı) */
    public static function canViewNav(string $role, string $href): bool
    {
        if ($role === 'customer') {
            return false;
        }
        if ($role === 'super_admin') {
            return true;
        }
        if ($role === 'company_owner' && self::hrefMatches($href, '/yetkiler')) {
            return false;
        }

        $module = self::findModuleByHref($href);
        if ($module !== null) {
            $perms = self::matrix()[$role][$module['id']] ?? [];
            if (array_key_exists('nav', $perms)) {
                return !empty($perms['nav']);
            }
            return !empty($perms['view']);
        }

        return true;
    }

    public static function can(string $role, string $moduleId, string $action): bool
    {
        if ($role === 'super_admin') {
            return true;
        }
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

    public static function findModuleByHref(string $href): ?array
    {
        $path = self::normalizeHref($href);
        $best = null;
        $bestLen = 0;
        foreach (self::modules() as $m) {
            $mh = $m['href'];
            if ($path === $mh || str_starts_with($path, $mh . '/')) {
                $len = strlen($mh);
                if ($len > $bestLen) {
                    $best = $m;
                    $bestLen = $len;
                }
            }
        }
        return $best;
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
            $nav = array_key_exists('nav', $perms)
                ? !empty($perms['nav'])
                : self::canViewNav($role, $module['href']);
            $row = ['module' => $module, 'nav' => $nav, 'actions' => []];
            foreach ($actions as $action) {
                if ($action === 'nav') {
                    $row['actions']['nav'] = $nav;
                } elseif ($role === 'super_admin') {
                    $row['actions'][$action] = true;
                } else {
                    $row['actions'][$action] = !empty($perms[$action]);
                }
            }
            $out[] = $row;
        }
        return $out;
    }

    /** Rol için kayıtlı özelleştirme var mı */
    public static function hasCustomOverrides(string $role): bool
    {
        $overrides = self::loadOverrides();
        return !empty($overrides[$role]);
    }

    /** Rol özelleştirmesini varsayılana döndür */
    public static function resetRoleOverrides(string $role): void
    {
        $overrides = self::loadOverrides();
        unset($overrides[$role]);
        self::saveOverrides($overrides);
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
