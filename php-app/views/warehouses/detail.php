<?php
$currentPage = 'depolar';
require __DIR__ . '/../partials/warehouse_list_helpers.php';
$warehouse = $warehouse ?? [];
$rooms = $rooms ?? [];
$roomCustomerCounts = $roomCustomerCounts ?? [];
$roomCustomers = $roomCustomers ?? [];
$warehouseCustomers = $warehouseCustomers ?? [];
$statusLabels = ['empty' => 'Boş', 'occupied' => 'Dolu', 'reserved' => 'Rezerve', 'locked' => 'Kilitli'];
$roomCount = count($rooms);
$customerCount = count($warehouseCustomers);
$whId = $warehouse['id'] ?? '';
ob_start();
?>
<div class="page-header mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-3">
        <a href="/depolar" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Depolar</a>
        <i class="bi bi-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-300 font-medium truncate"><?= htmlspecialchars($warehouse['name'] ?? '') ?></span>
    </div>
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="flex items-start gap-4 min-w-0">
            <?php $size = 'lg'; require __DIR__ . '/../partials/warehouse_logo.php'; ?>
            <div class="min-w-0">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1 truncate"><?= htmlspecialchars($warehouse['name'] ?? 'Depo Detayı') ?></h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2"><?= htmlspecialchars(warehouseLocationLine($warehouse)) ?></p>
                <?php if (warehouseHasContact($warehouse)): ?>
                    <div class="mt-3">
                        <?php $layout = 'chips'; require __DIR__ . '/../partials/warehouse_contact_display.php'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="page-header-actions flex flex-wrap gap-2 shrink-0">
            <a href="/odalar?warehouse_id=<?= urlencode($whId) ?>&add=1" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 btn-touch">
                <i class="bi bi-plus-circle mr-2"></i> Oda Ekle
            </a>
            <a href="/odalar?warehouse_id=<?= urlencode($whId) ?>" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 btn-touch">
                <i class="bi bi-grid-3x3-gap mr-2"></i> Odalar
            </a>
            <button type="button" onclick='openEditWarehouse(<?= json_encode(warehouseEditPayload($warehouse), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 btn-touch">
                <i class="bi bi-pencil mr-2"></i> Düzenle
            </button>
        </div>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Oda</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?= $roomCount ?></p>
    </div>
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?= $customerCount ?></p>
    </div>
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</p>
        <p class="mt-1">
            <?php if (!empty($warehouse['is_active'])): ?>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
            <?php else: ?>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm col-span-2 md:col-span-1">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık Ücret</p>
        <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white"><?= ($warehouse['monthly_base_fee'] ?? null) !== null && $warehouse['monthly_base_fee'] !== '' ? fmtPrice($warehouse['monthly_base_fee']) : '—' ?></p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 md:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-building text-emerald-600"></i> Depo Bilgileri
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo Adı</dt><dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($warehouse['name'] ?? '-') ?></dd></div>
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İl / İlçe</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= htmlspecialchars(trim(($warehouse['city'] ?? '') . ' / ' . ($warehouse['district'] ?? '')) ?: '-') ?></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Adres</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= nl2br(htmlspecialchars($warehouse['address'] ?? '-')) ?></dd></div>
                <?php if (!empty($warehouse['description'])): ?>
                <div class="sm:col-span-2"><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Açıklama</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= nl2br(htmlspecialchars($warehouse['description'])) ?></dd></div>
                <?php endif; ?>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İletişim</dt>
                    <dd class="mt-2">
                        <?php if (warehouseHasContact($warehouse)): ?>
                            <?php $layout = 'stack'; require __DIR__ . '/../partials/warehouse_contact_display.php'; ?>
                        <?php else: ?>
                            <span class="text-gray-500 dark:text-gray-400">Henüz iletişim bilgisi girilmemiş.</span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-people text-emerald-600"></i> Depodaki Müşteriler
                <span class="ml-auto text-sm font-normal text-gray-500 dark:text-gray-400"><?= $customerCount ?></span>
            </h2>
            <?php if (empty($warehouseCustomers)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Bu depoda aktif sözleşmeli müşteri yok.</div>
            <?php else: ?>
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($warehouseCustomers as $wc): $name = trim(($wc['first_name'] ?? '') . ' ' . ($wc['last_name'] ?? '')); ?>
                    <div class="mobile-data-card">
                        <a href="/musteriler/<?= htmlspecialchars($wc['customer_id']) ?>" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($name) ?></a>
                        <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <?php foreach ($wc['rooms'] ?? [] as $ro): ?>
                                <p>
                                    <a href="/odalar/<?= htmlspecialchars($ro['room_id']) ?>" class="text-gray-800 dark:text-gray-200 hover:underline">Oda <?= htmlspecialchars($ro['room_number'] ?? '-') ?></a>
                                    <?php if (!empty($ro['contract_id'])): ?>
                                        · <a href="/girisler/<?= htmlspecialchars($ro['contract_id']) ?>" class="hover:underline"><?= htmlspecialchars($ro['contract_number'] ?? '-') ?></a>
                                    <?php endif; ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Müşteri</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Oda / Sözleşme</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($warehouseCustomers as $wc): $name = trim(($wc['first_name'] ?? '') . ' ' . ($wc['last_name'] ?? '')); ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    <a href="/musteriler/<?= htmlspecialchars($wc['customer_id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($name) ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php
                                    $roomParts = [];
                                    foreach ($wc['rooms'] ?? [] as $ro):
                                        $rNum = htmlspecialchars($ro['room_number'] ?? '-');
                                        $rLink = '<a href="/odalar/' . htmlspecialchars($ro['room_id']) . '" class="text-emerald-600 dark:text-emerald-400 hover:underline">' . $rNum . '</a>';
                                        $cLink = $ro['contract_id'] ? '<a href="/girisler/' . htmlspecialchars($ro['contract_id']) . '" class="text-gray-600 dark:text-gray-400 hover:underline">' . htmlspecialchars($ro['contract_number'] ?? '-') . '</a>' : '-';
                                        $roomParts[] = $rLink . ' / ' . $cLink;
                                    endforeach;
                                    echo implode('<br>', $roomParts) ?: '—';
                                    ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="/musteriler/<?= htmlspecialchars($wc['customer_id']) ?>" class="text-sm text-emerald-600 hover:underline">Detay</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-grid-3x3 text-emerald-600"></i> Odalar
                <span class="ml-auto text-sm font-normal text-gray-500 dark:text-gray-400"><?= $roomCount ?></span>
            </h2>
            <?php if (empty($rooms)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    Bu depoda oda bulunmuyor.
                    <a href="/odalar?warehouse_id=<?= urlencode($whId) ?>&add=1" class="block mt-2 text-emerald-600 hover:underline font-medium">İlk odayı ekle</a>
                </div>
            <?php else: ?>
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($rooms as $r): $st = $r['status'] ?? 'empty'; ?>
                    <div class="mobile-data-card">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <a href="/odalar/<?= htmlspecialchars($r['id']) ?>" class="font-semibold text-gray-900 dark:text-white hover:text-emerald-600">Oda <?= htmlspecialchars($r['room_number']) ?></a>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= number_format((float)($r['area_m2'] ?? 0), 2, ',', '.') ?> m² · <?= fmtPrice($r['monthly_price'] ?? 0) ?></p>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full <?= $st === 'empty' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : ($st === 'occupied' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300') ?>"><?= $statusLabels[$st] ?? $st ?></span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                            <?php
                            $custList = $roomCustomers[$r['id']] ?? [];
                            if (empty($custList)): ?>
                                <span class="text-gray-400">Müşteri yok</span>
                            <?php else:
                                $names = [];
                                foreach ($custList as $cu) {
                                    $names[] = '<a href="/musteriler/' . htmlspecialchars($cu['customer_id']) . '" class="text-emerald-600 dark:text-emerald-400 hover:underline">' . htmlspecialchars(trim(($cu['customer_first_name'] ?? '') . ' ' . ($cu['customer_last_name'] ?? ''))) . '</a>';
                                }
                                echo implode(', ', $names);
                            endif;
                            ?>
                        </p>
                        <a href="/odalar/<?= htmlspecialchars($r['id']) ?>" class="inline-flex mt-3 text-sm font-medium text-emerald-600 hover:underline">Oda detayı</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="hidden md:block overflow-x-auto">
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
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php
                                    $custList = $roomCustomers[$r['id']] ?? [];
                                    if (empty($custList)): ?>
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    <?php else:
                                        $links = [];
                                        foreach ($custList as $cu):
                                            $name = trim(($cu['customer_first_name'] ?? '') . ' ' . ($cu['customer_last_name'] ?? ''));
                                            $links[] = '<a href="/musteriler/' . htmlspecialchars($cu['customer_id']) . '" class="text-emerald-600 dark:text-emerald-400 hover:underline">' . htmlspecialchars($name) . '</a>';
                                        endforeach;
                                        echo implode(', ', $links);
                                    endif;
                                    ?>
                                </td>
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

    <div class="hidden xl:block">
        <div class="sticky top-4 space-y-4">
            <a href="/odalar?warehouse_id=<?= urlencode($whId) ?>&add=1" class="block w-full p-5 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-medium hover:bg-emerald-100 dark:hover:bg-emerald-900/30 text-center">
                <i class="bi bi-plus-circle text-2xl block mb-2"></i> Oda Ekle
            </a>
            <a href="/odalar?warehouse_id=<?= urlencode($whId) ?>" class="block w-full p-4 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 text-center">
                <i class="bi bi-grid-3x3-gap mr-2"></i> Tüm Odalar
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_edit_modal.php'; ?>
<?php require __DIR__ . '/_warehouse_form_scripts.php'; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
