<?php
$currentPage = 'raporlar';
$rows = $rows ?? [];
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$search = $search ?? null;
$status = $status ?? null;
$statusLabel = $statusLabel ?? duePaymentStatusFilterLabel($status);
$companyName = $companyName ?? null;
$totalCount = $totalCount ?? 0;
$totalSum = $totalSum ?? 0;
$pendingCount = $pendingCount ?? 0;
$overdueCount = $overdueCount ?? 0;
$paidCount = $paidCount ?? 0;
$paidSum = $paidSum ?? 0;
$unpaidSum = $unpaidSum ?? 0;
$overdueSum = $overdueSum ?? 0;
$periodLabel = date('d.m.Y', strtotime($startDate)) . ' – ' . date('d.m.Y', strtotime($endDate));
$defaultStart = date('Y-m-01');
$defaultEnd = date('Y-m-t');
$hasActiveFilters = ($status ?? '') !== '' || ($search ?? '') !== '' || $startDate !== $defaultStart || $endDate !== $defaultEnd;
$activeFilterTags = ['Dönem: ' . $periodLabel];
if (($status ?? '') !== '') {
    $activeFilterTags[] = 'Durum: ' . $statusLabel;
}
if (($search ?? '') !== '') {
    $activeFilterTags[] = 'Arama: ' . $search;
}
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
?>
<div class="mb-6 screen-only">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/raporlar" class="text-emerald-600 dark:text-emerald-400 hover:underline">Raporlar</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Vadesi Gelen Ödemeler</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Vadesi Gelen Ödemeler</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Vade tarihine göre bekleyen, vadesi geçmiş ve ödenmiş ödemeler</p>
</div>

<div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 text-sm text-amber-900 dark:text-amber-200 screen-only">
    <strong>Vade aralığı:</strong> Ödemeler <code class="px-1 rounded bg-amber-100 dark:bg-amber-800">due_date</code> alanına göre filtrelenir.
    <strong class="ml-1">Bekleyen:</strong> Vadesi gelmiş, henüz geçmemiş.
    <strong class="ml-1">Vadesi geçmiş:</strong> Tahsil edilmemiş gecikmiş.
    <strong class="ml-1">Ödenmiş:</strong> Vadesi bu dönemde olan ve tahsil edilmiş kayıtlar.
</div>

<div class="page-toolbar flex flex-wrap items-center justify-between gap-3 mb-4 screen-only">
    <?php
    $filterModalId = 'duePaymentsFilterModal';
    $filterClearUrl = '/raporlar/vadesi-gelen';
    $filterTriggerClass = 'screen-only';
    require __DIR__ . '/../partials/page_filter_trigger.php';
    ?>
</div>

<?php
ob_start();
?>
    <div class="filter-field">
        <label class="filter-label">Yıl / Ay (hızlı)</label>
        <div class="flex flex-wrap gap-2">
            <select id="due_year" class="filter-input flex-1 min-w-[5rem]">
                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= (int) date('Y', strtotime($startDate)) === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select id="due_month" class="filter-input flex-1 min-w-[5rem]">
                <?php $monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']; foreach ($monthNames as $i => $mn): ?>
                    <option value="<?= $i + 1 ?>" <?= (int) date('n', strtotime($startDate)) === $i + 1 && date('Y-m', strtotime($startDate)) === date('Y-m', strtotime($endDate)) ? 'selected' : '' ?>><?= $mn ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="applyDueMonthPreset()" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 whitespace-nowrap">Ay uygula</button>
        </div>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="due_start_date">Vade başlangıç</label>
        <input type="date" name="start_date" id="due_start_date" value="<?= htmlspecialchars($startDate) ?>" class="filter-input">
    </div>
    <div class="filter-field">
        <label class="filter-label" for="due_end_date">Vade bitiş</label>
        <input type="date" name="end_date" id="due_end_date" value="<?= htmlspecialchars($endDate) ?>" class="filter-input">
    </div>
    <div class="filter-field">
        <label class="filter-label" for="due_status">Durum</label>
        <select name="status" id="due_status" class="filter-input">
            <option value="" <?= ($status ?? '') === '' ? 'selected' : '' ?>>Tümü</option>
            <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
            <option value="overdue" <?= ($status ?? '') === 'overdue' ? 'selected' : '' ?>>Vadesi geçmiş</option>
            <option value="paid" <?= ($status ?? '') === 'paid' ? 'selected' : '' ?>>Ödenmiş</option>
            <option value="unpaid" <?= ($status ?? '') === 'unpaid' ? 'selected' : '' ?>>Ödenmemiş</option>
        </select>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="due_search">Ara</label>
        <input type="search" name="q" id="due_search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Müşteri, sözleşme, ödeme no…" class="filter-input">
    </div>
<?php
$filterModalBody = ob_get_clean();
$filterFormId = 'duePaymentsFilterForm';
$filterFormAction = '/raporlar/vadesi-gelen';
$filterSubmitLabel = 'Göster';
$filterModalTitle = 'Vadesi Gelen — Filtreler';
$filterModalClass = 'screen-only';
require __DIR__ . '/../partials/page_filter_modal.php';
?>

<script>
function applyDueMonthPreset() {
    var y = parseInt(document.getElementById('due_year').value, 10);
    var m = parseInt(document.getElementById('due_month').value, 10);
    var start = new Date(y, m - 1, 1);
    var end = new Date(y, m, 0);
    document.getElementById('due_start_date').value = start.toISOString().slice(0, 10);
    document.getElementById('due_end_date').value = end.toISOString().slice(0, 10);
}
</script>

<?php
$csvUrl = reportExportUrl('/raporlar/vadesi-gelen', array_filter([
    'start_date' => $startDate,
    'end_date' => $endDate,
    'status' => $status,
    'q' => $search,
], static fn($v) => $v !== null && $v !== ''));
$csvLabel = 'Excel Raporu İndir';
?>
<div id="report-content">
<?php require __DIR__ . '/../partials/report_export_toolbar.php'; ?>

<?php
$printTitle = 'Vadesi Gelen Ödemeler Raporu';
$printMeta = [
    ['label' => 'Dönem', 'value' => $periodLabel],
    ['label' => 'Durum', 'value' => $statusLabel],
];
if ($search) {
    $printMeta[] = ['label' => 'Arama', 'value' => $search];
}
$printSummary = [
    'headers' => ['Toplam kayıt', 'Toplam tutar', 'Bekleyen', 'Vadesi geçmiş', 'Ödenmiş'],
    'values' => [(int) $totalCount, fmtMoney($totalSum) . ' ₺', (int) $pendingCount, (int) $overdueCount, (int) $paidCount],
];
require __DIR__ . '/../partials/report_print_header.php';
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8 screen-only">
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Toplam kayıt</p>
        <p class="text-2xl font-bold text-amber-700 dark:text-amber-300"><?= (int) $totalCount ?></p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= fmtMoney($totalSum) ?> ₺</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Bekleyen</p>
        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300"><?= (int) $pendingCount ?></p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Vadesi geçmiş</p>
        <p class="text-2xl font-bold text-red-700 dark:text-red-300"><?= (int) $overdueCount ?></p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= fmtMoney($overdueSum) ?> ₺</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Ödenmiş</p>
        <p class="text-2xl font-bold text-green-700 dark:text-green-300"><?= (int) $paidCount ?></p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= fmtMoney($paidSum) ?> ₺</p>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden report-print-table-wrap">
    <div class="px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800 flex justify-between items-center flex-wrap gap-2 screen-only">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-calendar-event text-amber-600 dark:text-amber-400"></i>
            Vadesi gelen ödemeler
        </h2>
        <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($rows) ?> kayıt · <?= fmtMoney($totalSum) ?> ₺</span>
    </div>
    <?php if (empty($rows)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Seçilen kriterlere uygun kayıt yok.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm report-data-table">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ödeme No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Telefon</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sözleşme</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Depo / Oda</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Vade</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tahsilat</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($rows as $r):
                        $name = trim(($r['customer_first_name'] ?? '') . ' ' . ($r['customer_last_name'] ?? ''));
                        $dStatus = paymentStatusDisplay($r);
                        $isPaid = ($r['status'] ?? '') === 'paid';
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <a href="/odemeler/<?= htmlspecialchars($r['id'] ?? '') ?>" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline screen-only"><?= htmlspecialchars($r['payment_number'] ?? '-') ?></a>
                            <span class="print-only"><?= htmlspecialchars($r['payment_number'] ?? '-') ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="/musteriler/<?= htmlspecialchars($r['customer_id'] ?? '') ?>" class="text-gray-700 dark:text-gray-300 hover:text-emerald-600 screen-only"><?= htmlspecialchars($name) ?></a>
                            <span class="print-only"><?= htmlspecialchars($name) ?></span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($r['customer_phone'] ?? '–') ?></td>
                        <td class="px-4 py-3">
                            <a href="/girisler/<?= htmlspecialchars($r['contract_id'] ?? '') ?>" class="text-gray-700 dark:text-gray-300 hover:text-emerald-600 screen-only"><?= htmlspecialchars($r['contract_number'] ?? '-') ?></a>
                            <span class="print-only"><?= htmlspecialchars($r['contract_number'] ?? '-') ?></span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars(($r['warehouse_name'] ?? '') . ' / ' . ($r['room_number'] ?? '')) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= !empty($r['due_date']) ? date('d.m.Y', strtotime($r['due_date'])) : '-' ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $dStatus['badge'] ?? 'bg-gray-100 text-gray-800' ?>"><?= htmlspecialchars($dStatus['label'] ?? '') ?></span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            <?= $isPaid ? fmtDateTime($r['paid_at'] ?? null) : '–' ?>
                            <?php if ($isPaid && !empty($r['payment_method'])): ?>
                                <span class="block text-xs text-gray-400 screen-only"><?= htmlspecialchars($r['payment_method']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold <?= $isPaid ? 'text-green-700 dark:text-green-400' : 'text-gray-900 dark:text-white' ?>"><?= fmtMoney($r['amount'] ?? 0) ?> ₺</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="print-only">
                    <tr>
                        <td colspan="8" class="px-4 py-3 text-right font-bold">Toplam</td>
                        <td class="px-4 py-3 text-right font-bold"><?= fmtMoney($totalSum) ?> ₺</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
