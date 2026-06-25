<?php
$currentPage = 'yetkiler';
$roles = $roles ?? [];
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Kullanıcı Yetkileri</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Rol tanımları</p>
</div>

<div class="mb-4 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 text-sm text-blue-900 dark:text-blue-200">
    Kullanıcılara rol atamak için <a href="/kullanicilar" class="font-semibold underline hover:no-underline">Kullanıcılar</a> sayfasından ilgili personeli düzenleyin.
    Süper Admin tüm rolleri (Personel, Muhasebe, Şirket Sahibi vb.) atayabilir ve şirket bağlayabilir.
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
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
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($r['name']) ?></td>
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
