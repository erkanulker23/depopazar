<?php
$currentPage = 'odalar';
$statusLabels = ['empty' => 'Boş', 'occupied' => 'Dolu', 'reserved' => 'Rezerve', 'locked' => 'Kilitli'];
$statusClass = ($room['status'] ?? '') === 'empty' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : (($room['status'] ?? '') === 'occupied' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : (($room['status'] ?? '') === 'reserved' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' : 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200'));
ob_start();
?>
<div class="mb-4">
    <a href="/odalar" class="inline-flex items-center text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
        <i class="bi bi-arrow-left mr-1"></i> Odalara dön
    </a>
</div>
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Oda: <?= htmlspecialchars($room['room_number']) ?></h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <dl class="space-y-3">
                    <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo</dt><dd class="text-gray-900 dark:text-white"><?= htmlspecialchars($room['warehouse_name'] ?? '-') ?></dd></div>
                    <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Alan (m²)</dt><dd class="text-gray-900 dark:text-white"><?= number_format((float)$room['area_m2'], 2, ',', '.') ?></dd></div>
                    <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık Fiyat</dt><dd class="text-gray-900 dark:text-white"><?= number_format((float)$room['monthly_price'], 2, ',', '.') ?> ₺</dd></div>
                    <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</dt><dd><span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $statusClass ?>"><?= $statusLabels[$room['status'] ?? ''] ?? $room['status'] ?? '-' ?></span></dd></div>
                    <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Kat / Blok / Koridor</dt><dd class="text-gray-900 dark:text-white"><?= htmlspecialchars(trim(($room['floor'] ?? '') . ' / ' . ($room['block'] ?? '') . ' / ' . ($room['corridor'] ?? '')) ?: '-') ?></dd></div>
                </dl>
            </div>
            <div>
                <?php if (!empty($room['description'])): ?>
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Açıklama</p>
                    <p class="text-gray-700 dark:text-gray-300 mb-4"><?= nl2br(htmlspecialchars($room['description'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($room['notes'])): ?>
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Notlar</p>
                    <p class="text-gray-700 dark:text-gray-300"><?= nl2br(htmlspecialchars($room['notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <hr class="my-6 border-gray-100 dark:border-gray-700">
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2"><i class="bi bi-people text-emerald-600"></i> Odayı Kullanan Müşteriler</h3>
        <?php $contracts = $contracts ?? []; if (empty($contracts)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Bu odayı kullanan müşteri yok.</p>
        <?php else: ?>
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2 text-left font-bold text-gray-700 dark:text-gray-300">Müşteri</th>
                            <th class="px-4 py-2 text-left font-bold text-gray-700 dark:text-gray-300">Sözleşme</th>
                            <th class="px-4 py-2 text-left font-bold text-gray-700 dark:text-gray-300">Kiralama Durumu</th>
                            <th class="px-4 py-2 text-right font-bold text-gray-700 dark:text-gray-300">Borç</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                        <?php foreach ($contracts as $c): $debt = (float)($c['debt'] ?? 0); ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-2"><a href="/musteriler/<?= htmlspecialchars($c['customer_id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium"><?= htmlspecialchars(trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''))) ?></a></td>
                            <td class="px-4 py-2"><a href="/girisler/<?= htmlspecialchars($c['id']) ?>" class="text-gray-700 dark:text-gray-300 hover:underline"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></a></td>
                            <td class="px-4 py-2"><?= !empty($c['is_active']) ? '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Aktif</span>' : '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300">Sonlandı</span>' ?></td>
                            <td class="px-4 py-2 text-right font-medium <?= $debt > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' ?>"><?= number_format($debt, 2, ',', '.') ?> ₺</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div class="flex flex-wrap gap-2">
            <a href="/odalar" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Listeye dön</a>
            <a href="/odalar" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">Oda listesinde düzenle</a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
