<?php
$currentPage = 'raporlar';
$rows = $rows ?? [];
$categories = $categories ?? [];
$bankAccounts = $bankAccounts ?? [];
$creditCards = $creditCards ?? [];
$totalAmount = $totalAmount ?? 0;
$byCategory = $byCategory ?? [];
$categoryId = $categoryId ?? '';
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$paymentSourceType = $paymentSourceType ?? '';
$paymentSourceId = $paymentSourceId ?? '';
$companyName = $companyName ?? null;
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
function getPaymentSourceDisplay($e, $bankAccounts, $creditCards) {
    $type = $e['payment_source_type'] ?? 'bank_account';
    $id = $e['payment_source_id'] ?? '';
    if ($type === 'bank_account') {
        foreach ($bankAccounts as $ba) {
            if (($ba['id'] ?? '') === $id) return ($ba['bank_name'] ?? '') . ' - ' . ($ba['account_holder_name'] ?? '');
        }
        return 'Banka Hesabı';
    }
    foreach ($creditCards as $cc) {
        if (($cc['id'] ?? '') === $id) return CreditCard::getDisplayName($cc);
    }
    return 'Kredi Kartı';
}
$periodLabel = date('d.m.Y', strtotime($startDate)) . ' – ' . date('d.m.Y', strtotime($endDate));
$hasActiveFilters = $categoryId !== '' || $paymentSourceType !== '' || $paymentSourceId !== '' || $startDate !== date('Y-m-01') || $endDate !== date('Y-m-t');
$activeFilterTags = ['Dönem: ' . $periodLabel];
if ($categoryId !== '') {
    foreach ($categories as $c) {
        if (($c['id'] ?? '') === $categoryId) { $activeFilterTags[] = 'Kategori: ' . ($c['name'] ?? ''); break; }
    }
}
if ($paymentSourceType === 'bank_account') $activeFilterTags[] = 'Kaynak: Banka';
elseif ($paymentSourceType === 'credit_card') $activeFilterTags[] = 'Kaynak: Kredi kartı';
?>
<div class="mb-6 screen-only">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/raporlar" class="text-emerald-600 dark:text-emerald-400 hover:underline">Raporlar</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Masraf Raporu</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Masraf Raporu</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Tarih aralığına ve kategoriye göre masraf listesi</p>
</div>

<div class="page-toolbar mb-6 screen-only">
    <?php
    $filterModalId = 'expenseReportFilterModal';
    $filterClearUrl = '/raporlar/masraflar';
    require __DIR__ . '/../partials/page_filter_trigger.php';
    ?>
</div>

<?php
ob_start();
?>
    <div class="filter-field">
        <label class="filter-label">Yıl / Ay (hızlı)</label>
        <div class="flex flex-wrap gap-2">
            <select id="expense_year" class="filter-input flex-1 min-w-[5rem]">
                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= (int) date('Y', strtotime($startDate)) === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select id="expense_month" class="filter-input flex-1 min-w-[5rem]">
                <?php $monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']; foreach ($monthNames as $i => $mn): ?>
                    <option value="<?= $i + 1 ?>"><?= $mn ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="applyExpenseMonthPreset()" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-sm whitespace-nowrap">Ay uygula</button>
        </div>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="expense_report_category">Kategori</label>
        <select name="category_id" id="expense_report_category" class="filter-input">
            <option value="">Tümü</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>" <?= $categoryId === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="expense_report_source_type">Ödeme Kaynağı</label>
        <select name="payment_source_type" id="expense_report_source_type" class="filter-input">
            <option value="">Tümü</option>
            <option value="bank_account" <?= $paymentSourceType === 'bank_account' ? 'selected' : '' ?>>Banka Hesabı</option>
            <option value="credit_card" <?= $paymentSourceType === 'credit_card' ? 'selected' : '' ?>>Kredi Kartı</option>
        </select>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="expense_start_date">Başlangıç</label>
        <input type="date" name="start_date" id="expense_start_date" value="<?= htmlspecialchars($startDate) ?>" class="filter-input">
    </div>
    <div class="filter-field">
        <label class="filter-label" for="expense_end_date">Bitiş</label>
        <input type="date" name="end_date" id="expense_end_date" value="<?= htmlspecialchars($endDate) ?>" class="filter-input">
    </div>
<?php
$filterModalBody = ob_get_clean();
$filterFormId = 'expenseReportFilterForm';
$filterFormAction = '/raporlar/masraflar';
$filterSubmitLabel = 'Göster';
$filterModalTitle = 'Masraf Raporu — Filtreler';
$filterModalClass = 'screen-only';
require __DIR__ . '/../partials/page_filter_modal.php';
?>

<?php
$csvUrl = reportExportUrl('/raporlar/masraflar', array_filter([
    'category_id' => $categoryId ?: null,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'payment_source_type' => $paymentSourceType ?: null,
    'payment_source_id' => $paymentSourceId ?: null,
]));
?>
<div id="report-content">
<?php require __DIR__ . '/../partials/report_export_toolbar.php'; ?>

<?php
$printTitle = 'Masraf Raporu';
$printMeta = [
    ['label' => 'Dönem', 'value' => $periodLabel],
];
if ($categoryId !== '') {
    foreach ($categories as $c) {
        if (($c['id'] ?? '') === $categoryId) {
            $printMeta[] = ['label' => 'Kategori', 'value' => $c['name'] ?? ''];
            break;
        }
    }
}
if ($paymentSourceType === 'bank_account') {
    $printMeta[] = ['label' => 'Kaynak', 'value' => 'Banka Hesabı'];
} elseif ($paymentSourceType === 'credit_card') {
    $printMeta[] = ['label' => 'Kaynak', 'value' => 'Kredi Kartı'];
}
$printSummary = [
    'headers' => ['Toplam masraf', 'Toplam tutar', 'Kategori sayısı'],
    'values' => [count($rows), fmtMoney($totalAmount) . ' ₺', count($byCategory)],
];
require __DIR__ . '/../partials/report_print_header.php';
?>

<?php if (!empty($byCategory)): ?>
<div class="print-only report-print-section">
    <h2>Kategoriye Göre Özet</h2>
    <table class="report-data-table">
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Adet</th>
                <th>Tutar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($byCategory as $catName => $data): ?>
            <tr>
                <td><?= htmlspecialchars($catName) ?></td>
                <td><?= (int) $data['count'] ?></td>
                <td><?= fmtMoney($data['total']) ?> ₺</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mb-6 card-modern p-6 screen-only">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kategoriye Göre Özet</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($byCategory as $catName => $data): ?>
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-600">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($catName) ?></p>
                <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-1"><?= fmtMoney($data['total']) ?> ₺</p>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= (int) $data['count'] ?> masraf</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden report-print-table-wrap">
    <?php if (empty($rows)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400 screen-only">Seçilen kriterlere uygun masraf kaydı yok.</div>
        <div class="print-only p-4 text-gray-600">Seçilen kriterlere uygun masraf kaydı yok.</div>
    <?php else: ?>
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center screen-only">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Toplam <?= count($rows) ?> masraf</span>
            <span class="text-lg font-bold text-red-600 dark:text-red-400"><?= fmtMoney($totalAmount) ?> ₺</span>
        </div>
        <div class="print-only report-print-section">
            <h2>Masraf Listesi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 report-data-table">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ödeme Kaynağı</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= $r['expense_date'] ? date('d.m.Y', strtotime($r['expense_date'])) : '-' ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($r['category_name'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($r['description'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars(getPaymentSourceDisplay($r, $bankAccounts, $creditCards)) ?></td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white"><?= fmtMoney($r['amount'] ?? 0) ?> ₺</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="print-only">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-bold">Toplam</td>
                        <td class="px-4 py-3 text-right font-bold"><?= fmtMoney($totalAmount) ?> ₺</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>
<script>
function applyExpenseMonthPreset() {
    var y = parseInt(document.getElementById('expense_year').value, 10);
    var m = parseInt(document.getElementById('expense_month').value, 10);
    var start = new Date(y, m - 1, 1);
    var end = new Date(y, m, 0);
    document.getElementById('expense_start_date').value = start.toISOString().slice(0, 10);
    document.getElementById('expense_end_date').value = end.toISOString().slice(0, 10);
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
