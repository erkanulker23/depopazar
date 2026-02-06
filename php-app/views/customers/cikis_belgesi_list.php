<?php
$currentPage = 'musteriler';
$contracts = $contracts ?? [];
$customerName = $customerName ?? '';
$customerId = $customerId ?? '';
ob_start();
?>
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/musteriler" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium">Müşteriler</a>
        <i class="bi bi-chevron-right"></i>
        <a href="/musteriler/<?= htmlspecialchars($customerId) ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium"><?= htmlspecialchars($customerName) ?></a>
        <i class="bi bi-chevron-right"></i>
        <span class="text-gray-700 dark:text-gray-300 font-medium">Çıkış belgesi</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Çıkış belgesi oluştur</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">Hangi sözleşme için çıkış belgesi yazdırmak istiyorsunuz?</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($contracts)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Bu müşteriye ait sözleşme yok.</div>
    <?php else: ?>
        <ul class="divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($contracts as $c): ?>
                <li class="p-4 flex flex-wrap items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?> · <?= date('d.m.Y', strtotime($c['start_date'] ?? '')) ?> – <?= date('d.m.Y', strtotime($c['end_date'] ?? '')) ?></p>
                    </div>
                    <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>/cikis-belgesi" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 font-medium hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                        <i class="bi bi-download"></i> Çıkış belgesi yazdır
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<div class="mt-4">
    <a href="/musteriler/<?= htmlspecialchars($customerId) ?>" class="inline-flex items-center gap-2 text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium"><i class="bi bi-arrow-left"></i> Müşteri detayına dön</a>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
