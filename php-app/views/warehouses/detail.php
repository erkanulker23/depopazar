<?php
$currentPage = 'depolar';
$warehouse = $warehouse ?? [];
$rooms = $rooms ?? [];
$roomCustomerCounts = $roomCustomerCounts ?? [];
$statusLabels = ['empty' => 'Boş', 'occupied' => 'Dolu', 'reserved' => 'Rezerve', 'locked' => 'Kilitli'];
ob_start();
?>
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="/depolar" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Depolar</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-gray-700 dark:text-gray-300 font-medium"><?= htmlspecialchars($warehouse['name'] ?? '') ?></span>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Depo Detayı</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($warehouse['name'] ?? '') ?></p>
    </div>
    <a href="/depolar" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
        <i class="bi bi-pencil mr-2"></i> Depo Listesinde Düzenle
    </a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-building text-emerald-600"></i> Depo Bilgileri
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo Adı</dt><dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($warehouse['name'] ?? '-') ?></dd></div>
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Adres</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= nl2br(htmlspecialchars($warehouse['address'] ?? '-')) ?></dd></div>
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İl / İlçe</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= htmlspecialchars(trim(($warehouse['city'] ?? '') . ' / ' . ($warehouse['district'] ?? '')) ?: '-') ?></dd></div>
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Oda Sayısı</dt><dd class="mt-1 text-gray-900 dark:text-white"><?= count($rooms) ?></dd></div>
            </dl>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-grid-3x3 text-emerald-600"></i> Odalar
            </h2>
            <?php if (empty($rooms)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Bu depoda oda bulunmuyor. <a href="/odalar?warehouse_id=<?= urlencode($warehouse['id']) ?>&add=1" class="text-emerald-600 hover:underline">Oda ekle</a></div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Oda No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Alan (m²)</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Aylık Fiyat</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Kullanan Müşteri</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($rooms as $r): $st = $r['status'] ?? 'empty'; ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="/odalar/<?= htmlspecialchars($r['id']) ?>" class="text-emerald-600 hover:underline"><?= htmlspecialchars($r['room_number']) ?></a></td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?= number_format((float)($r['area_m2'] ?? 0), 2, ',', '.') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?= fmtPrice($r['monthly_price'] ?? 0) ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?= (int)($roomCustomerCounts[$r['id']] ?? 0) ?></td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $st === 'empty' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : ($st === 'occupied' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300') ?>"><?= $statusLabels[$st] ?? $st ?></span></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="/odalar/<?= htmlspecialchars($r['id']) ?>" class="text-sm text-emerald-600 hover:underline">Detay</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div>
        <a href="/odalar?warehouse_id=<?= urlencode($warehouse['id']) ?>&add=1" class="block w-full p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-medium hover:bg-emerald-100 dark:hover:bg-emerald-900/30 text-center">
            <i class="bi bi-plus-circle text-xl block mb-2"></i> Oda Ekle
        </a>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';