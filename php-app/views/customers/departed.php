<?php
$currentPage = 'musteriler';
$pageTitle = 'Depodan Ayrılanlar';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$customers = $customers ?? [];
$customersTotal = $customersTotal ?? 0;
$page = $page ?? max(1, (int) ($_GET['page'] ?? 1));
$perPage = $perPage ?? 50;
$totalPages = $totalPages ?? 1;
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Depodan Ayrılanlar</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        <span class="font-semibold text-emerald-700 dark:text-emerald-400"><?= number_format((int) $customersTotal, 0, ',', '.') ?> müşteri</span>
        <span class="text-gray-400 dark:text-gray-500 mx-1" aria-hidden="true">·</span>
        Sözleşmesi sonlandırılmış / çıkışı yapılmış müşteriler
    </p>
</div>

<div class="page-toolbar flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <form method="get" action="/musteriler/depodan-ayrilanlar" class="flex flex-col sm:flex-row gap-2 flex-1 min-w-0">
        <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ad, telefon, depo veya sözleşme ara..."
               class="w-full sm:max-w-md px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500">
        <button type="submit" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
            <i class="bi bi-search"></i> Ara
        </button>
        <?php if ($q !== ''): ?>
            <a href="/musteriler/depodan-ayrilanlar" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">Temizle</a>
        <?php endif; ?>
    </form>
    <a href="/musteriler" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
        <i class="bi bi-people"></i> Tüm müşteriler
    </a>
</div>

<?php if (isset($flashSuccess) && $flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (isset($flashError) && $flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
    <?php if (empty($customers)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <?= $q !== '' ? 'Aramaya uygun ayrılan müşteri bulunamadı.' : 'Depodan ayrılan müşteri kaydı yok.' ?>
        </div>
    <?php else: ?>
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($customers as $c):
                $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            ?>
            <div class="mobile-data-card space-y-2">
                <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($name) ?></a>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <?= htmlspecialchars($c['last_warehouse_name'] ?? '-') ?>
                    / Oda <?= htmlspecialchars($c['last_room_number'] ?? '-') ?>
                    · <a href="/girisler/<?= htmlspecialchars($c['last_contract_id'] ?? '') ?>" class="hover:underline"><?= htmlspecialchars($c['last_contract_number'] ?? '-') ?></a>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Çıkış: <?= !empty($c['last_terminated_at']) ? fmtDateTime($c['last_terminated_at']) : '–' ?>
                    <?php if (!empty($c['phone'])): ?> · <?= htmlspecialchars(formatPhoneDisplay($c['phone'])) ?><?php endif; ?>
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600">Detay</a>
                    <?php if (!empty($c['last_contract_id'])): ?>
                    <a href="/girisler/<?= htmlspecialchars($c['last_contract_id']) ?>/cikis-belgesi" target="_blank" rel="noopener" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20">
                        <i class="bi bi-download mr-1"></i> Çıkış belgesi
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Telefon</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Son depo / oda</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Çıkış tarihi</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($customers as $c):
                        $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($name) ?></a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?= !empty($c['phone']) ? htmlspecialchars(formatPhoneDisplay($c['phone'])) : '–' ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            <?= htmlspecialchars($c['last_warehouse_name'] ?? '-') ?>
                            /
                            <?php if (!empty($c['last_room_id'])): ?>
                                <a href="/odalar/<?= htmlspecialchars($c['last_room_id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($c['last_room_number'] ?? '-') ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($c['last_room_number'] ?? '-') ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            <?php if (!empty($c['last_contract_id'])): ?>
                                <a href="/girisler/<?= htmlspecialchars($c['last_contract_id']) ?>" class="hover:underline"><?= htmlspecialchars($c['last_contract_number'] ?? '-') ?></a>
                            <?php else: ?>
                                –
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?= !empty($c['last_terminated_at']) ? fmtDateTime($c['last_terminated_at']) : '–' ?></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>" class="text-sm text-emerald-600 hover:underline mr-2">Detay</a>
                            <?php if (!empty($c['last_contract_id'])): ?>
                                <a href="/girisler/<?= htmlspecialchars($c['last_contract_id']) ?>/cikis-belgesi" target="_blank" rel="noopener" class="text-sm text-red-600 dark:text-red-400 hover:underline">Çıkış belgesi</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
if (!empty($customers)):
    $paginationParams = array_filter([
        'q' => $q !== '' ? $q : null,
    ], fn($v) => $v !== null && $v !== '');
    echo renderPagination((int) $customersTotal, (int) $perPage, (int) $page, '/musteriler/depodan-ayrilanlar', $paginationParams);
endif;

$content = ob_get_clean();
require __DIR__ . '/../layout.php';
