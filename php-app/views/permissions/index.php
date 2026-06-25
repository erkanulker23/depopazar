<?php
$currentPage = 'yetkiler';
$roles = $roles ?? [];
$modules = $modules ?? [];
$actionLabels = $actionLabels ?? [];
$matrix = $matrix ?? [];
$selectedRole = $selectedRole ?? 'company_staff';
$roleSummary = $roleSummary ?? [];
$staff = $staff ?? [];
$companies = $companies ?? [];
$roleLabels = RolePermissions::roleLabels();

function permIcon(bool $allowed, string $type = 'check'): string {
    if ($allowed) {
        return '<span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300" title="İzin var"><i class="bi bi-check-lg text-sm"></i></span>';
    }
    return '<span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700/60 text-gray-400 dark:text-gray-500" title="İzin yok"><i class="bi bi-dash-lg text-sm"></i></span>';
}

function permBadge(bool $allowed, string $label): string {
    if ($allowed) {
        return '<span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">' . htmlspecialchars($label) . '</span>';
    }
    return '';
}

ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Kullanıcı Yetkileri</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Rol bazlı görüntüleme, düzenleme ve silme matrisi</p>
</div>

<div class="mb-6 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 text-sm text-blue-900 dark:text-blue-200">
    <p class="mb-2"><strong>Süper Admin</strong> olarak hangi rolün hangi sayfada ne yapabildiğini buradan inceleyebilirsiniz.</p>
    <p>Kullanıcılara rol atamak için <a href="/kullanicilar" class="font-semibold underline hover:no-underline">Kullanıcılar</a> sayfasını kullanın. Yetkiler role göre otomatik uygulanır.</p>
</div>

<!-- Rol seçici -->
<div class="mb-6">
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Rol seçin — detaylı yetki tablosu:</p>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($roles as $r): ?>
            <a href="/yetkiler?role=<?= urlencode($r['id']) ?>#rol-matrisi"
               class="px-4 py-2 rounded-xl text-sm font-medium transition-colors <?= $selectedRole === $r['id']
                   ? 'bg-emerald-600 text-white shadow-md'
                   : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                <?= htmlspecialchars($r['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
    $currentRoleMeta = null;
    foreach ($roles as $r) {
        if ($r['id'] === $selectedRole) {
            $currentRoleMeta = $r;
            break;
        }
    }
    ?>
    <?php if ($currentRoleMeta): ?>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($currentRoleMeta['desc']) ?></p>
    <?php endif; ?>
</div>

<!-- Seçili rol detay tablosu -->
<div id="rol-matrisi" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-8">
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
            <?= htmlspecialchars($roleLabels[$selectedRole] ?? $selectedRole) ?> — Sayfa ve işlem yetkileri
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest min-w-[180px]">Sayfa / Modül</th>
                    <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Menü</th>
                    <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Görüntüle</th>
                    <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ekle</th>
                    <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Düzenle</th>
                    <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sil</th>
                    <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Dışa aktar</th>
                    <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Yazdır</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase min-w-[200px]">Özel işlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                <?php foreach ($roleSummary as $row):
                    $m = $row['module'];
                    $a = $row['actions'];
                    $hasAny = ($a['view'] ?? false) || ($a['nav'] ?? false);
                ?>
                    <tr class="<?= $hasAny ? 'hover:bg-gray-50 dark:hover:bg-gray-700/50' : 'opacity-50 bg-gray-50/50 dark:bg-gray-900/20' ?>">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($m['label']) ?></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono"><?= htmlspecialchars($m['href']) ?></div>
                        </td>
                        <td class="px-3 py-3 text-center"><?= permIcon($a['nav'] ?? false) ?></td>
                        <td class="px-3 py-3 text-center"><?= permIcon($a['view'] ?? false) ?></td>
                        <td class="px-3 py-3 text-center"><?= permIcon($a['create'] ?? false) ?></td>
                        <td class="px-3 py-3 text-center"><?= permIcon($a['edit'] ?? false) ?></td>
                        <td class="px-3 py-3 text-center"><?= permIcon($a['delete'] ?? false) ?></td>
                        <td class="px-3 py-3 text-center"><?= permIcon($a['export'] ?? false) ?></td>
                        <td class="px-3 py-3 text-center"><?= permIcon($a['print'] ?? false) ?></td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400"><?= $hasAny ? htmlspecialchars($m['special']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tüm roller karşılaştırma -->
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-8">
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Rol karşılaştırması — Görüntüleme yetkisi</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Yeşil: sayfayı görüntüleyebilir · Gri: erişemez</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase sticky left-0 bg-gray-50 dark:bg-gray-700/50">Modül</th>
                    <?php foreach (RolePermissions::STAFF_ROLES as $rid): ?>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap"><?= htmlspecialchars($roleLabels[$rid] ?? $rid) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                <?php foreach ($modules as $m): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-gray-800"><?= htmlspecialchars($m['label']) ?></td>
                        <?php foreach (RolePermissions::STAFF_ROLES as $rid):
                            $canView = !empty($matrix[$rid][$m['id']]['view']);
                        ?>
                            <td class="px-3 py-2 text-center">
                                <?= permIcon($canView) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Kullanıcı listesi -->
<div id="kullanicilar" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Personel ve etkin yetkileri</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= count($staff) ?> kullanıcı — rolüne göre yukarıdaki matris uygulanır</p>
        </div>
        <a href="/kullanicilar" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
            <i class="bi bi-people"></i> Kullanıcı yönetimi
        </a>
    </div>

    <?php if (empty($staff)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz personel kaydı yok.</div>
    <?php else: ?>
        <div class="divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($staff as $u):
                $uid = 'user-perms-' . htmlspecialchars($u['id']);
                $perms = $u['permissions'] ?? [];
                $allowedRows = array_filter($perms, fn($p) => ($p['actions']['view'] ?? false));
            ?>
                <details class="group">
                    <summary class="px-4 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 flex flex-col sm:flex-row sm:items-center gap-3 list-none">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <span class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-sm">
                                <?= strtoupper(mb_substr($u['first_name'] ?? '?', 0, 1) . mb_substr($u['last_name'] ?? '', 0, 1)) ?>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">
                                    <?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?>
                                    <?php if (empty($u['is_active'])): ?>
                                        <span class="ml-1 text-xs font-normal text-gray-500">(Pasif)</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($u['email'] ?? '') ?></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300"><?= htmlspecialchars($u['role_label']) ?></span>
                            <?php if (!empty($u['company_name'])): ?>
                                <span class="px-2.5 py-1 rounded-lg text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"><?= htmlspecialchars($u['company_name']) ?></span>
                            <?php endif; ?>
                            <span class="text-xs text-gray-500 dark:text-gray-400"><?= (int)($u['allowed_modules'] ?? 0) ?> modül</span>
                            <i class="bi bi-chevron-down text-gray-400 group-open:rotate-180 transition-transform"></i>
                        </div>
                    </summary>
                    <div class="px-4 pb-4 pt-0 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                            <?php foreach ($allowedRows as $p):
                                $m = $p['module'];
                                $a = $p['actions'];
                                $badges = [];
                                if ($a['nav'] ?? false) $badges[] = 'Menü';
                                if ($a['create'] ?? false) $badges[] = 'Ekle';
                                if ($a['edit'] ?? false) $badges[] = 'Düzenle';
                                if ($a['delete'] ?? false) $badges[] = 'Sil';
                                if ($a['export'] ?? false) $badges[] = 'Dışa aktar';
                                if ($a['print'] ?? false) $badges[] = 'Yazdır';
                            ?>
                                <div class="p-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                                    <p class="font-medium text-gray-900 dark:text-white text-sm mb-1"><?= htmlspecialchars($m['label']) ?></p>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($badges as $b): ?>
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300"><?= htmlspecialchars($b) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($badges)): ?>
                                            <span class="text-xs text-gray-500">Sadece görüntüleme</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($allowedRows)): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400 col-span-full">Bu role tanımlı modül erişimi yok.</p>
                            <?php endif; ?>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            Rol değiştirmek için
                            <a href="/kullanicilar/<?= htmlspecialchars($u['id']) ?>/duzenle" class="text-emerald-600 dark:text-emerald-400 hover:underline">kullanıcıyı düzenleyin</a>.
                        </p>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Rol açıklamaları -->
<div class="mt-8 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Rol tanımları</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Rol</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Açıklama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                <?php foreach ($roles as $r): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap"><?= htmlspecialchars($r['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($r['desc']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
