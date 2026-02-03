<?php
$currentPage = 'yetkiler';
$roles = $roles ?? [];
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">Kullanıcı Yetkileri</h1>
    <p class="text-xs text-gray-500 uppercase tracking-widest font-bold">Rol ve yetkiler</p>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Rol</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Açıklama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($roles as $r): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($r['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($r['desc']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
