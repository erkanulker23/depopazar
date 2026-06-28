<?php
$currentPage = 'raporlar';
$bankAccounts = $bankAccounts ?? [];
$rows = $rows ?? [];
$expenseRows = $expenseRows ?? [];
$bankBalances = $bankBalances ?? [];
$bankAccountId = $bankAccountId ?? '';
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$qGet = isset($_GET['q']) ? trim($_GET['q']) : '';
$paymentMethodGet = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$periodLabel = date('d.m.Y', strtotime($startDate)) . ' – ' . date('d.m.Y', strtotime($endDate));
$hasActiveFilters = $qGet !== '' || $paymentMethodGet !== '' || $bankAccountId !== '' || $startDate !== date('Y-m-01') || $endDate !== date('Y-m-t');
$activeFilterTags = ['Dönem: ' . $periodLabel];
if ($bankAccountId !== '') {
    foreach ($bankAccounts as $ba) {
        if (($ba['id'] ?? '') === $bankAccountId) {
            $activeFilterTags[] = 'Banka: ' . ($ba['bank_name'] ?? '');
            break;
        }
    }
}
if ($paymentMethodGet === 'havale') $activeFilterTags[] = 'Yöntem: Havale';
elseif ($paymentMethodGet === 'kredi_karti') $activeFilterTags[] = 'Yöntem: Kredi Kartı';
if ($qGet !== '') $activeFilterTags[] = 'Arama: ' . $qGet;
$companyName = $companyName ?? null;
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
$paymentTotal = !empty($rows) ? array_sum(array_map(fn($r) => (float)($r['amount'] ?? 0), $rows)) : 0;
$expenseTotal = !empty($expenseRows) ? array_sum(array_map(fn($r) => (float)($r['amount'] ?? 0), $expenseRows)) : 0;
$selectedBankLabel = 'Tümü';
if ($bankAccountId !== '') {
    foreach ($bankAccounts as $ba) {
        if (($ba['id'] ?? '') === $bankAccountId) {
            $selectedBankLabel = ($ba['bank_name'] ?? '') . ' - ' . ($ba['account_holder_name'] ?? '');
            break;
        }
    }
}
$paymentMethodLabel = $paymentMethodGet === 'havale' ? 'Havale' : ($paymentMethodGet === 'kredi_karti' ? 'Kredi Kartı' : 'Tümü');
?>
<div class="mb-6 screen-only">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/raporlar" class="text-emerald-600 dark:text-emerald-400 hover:underline">Raporlar</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Banka Hesaplarına Göre Ödemeler</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Banka Hesaplarına Göre Ödemeler</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Tarih aralığına ve banka hesabına göre tahsilat listesi</p>
</div>

<div class="page-toolbar mb-6 screen-only">
    <?php
    $filterModalId = 'bankReportFilterModal';
    $filterClearUrl = '/raporlar/banka-hesaplari?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate);
    require __DIR__ . '/../partials/page_filter_trigger.php';
    ?>
</div>

<?php
ob_start();
?>
    <div class="filter-field">
        <label class="filter-label">Yıl / Ay (hızlı)</label>
        <div class="flex flex-wrap gap-2">
            <select id="bank_year" class="filter-input flex-1 min-w-[5rem]">
                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= (int) date('Y', strtotime($startDate)) === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select id="bank_month" class="filter-input flex-1 min-w-[5rem]">
                <?php $monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']; foreach ($monthNames as $i => $mn): ?>
                    <option value="<?= $i + 1 ?>"><?= $mn ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="applyBankMonthPreset()" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-sm whitespace-nowrap">Ay uygula</button>
        </div>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="bank_account_id">Banka Hesabı</label>
        <select name="bank_account_id" id="bank_account_id" class="filter-input">
            <option value="">Tümü</option>
            <?php foreach ($bankAccounts as $ba): ?>
                <option value="<?= htmlspecialchars($ba['id']) ?>" <?= $bankAccountId === $ba['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ba['bank_name'] . ' - ' . ($ba['account_holder_name'] ?? '')) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="bank_start_date">Başlangıç</label>
        <input type="date" name="start_date" id="bank_start_date" value="<?= htmlspecialchars($startDate) ?>" class="filter-input">
    </div>
    <div class="filter-field">
        <label class="filter-label" for="bank_end_date">Bitiş</label>
        <input type="date" name="end_date" id="bank_end_date" value="<?= htmlspecialchars($endDate) ?>" class="filter-input">
    </div>
    <div class="filter-field">
        <label class="filter-label" for="bank_payment_method">Ödeme Yöntemi</label>
        <select name="payment_method" id="bank_payment_method" class="filter-input">
            <option value="">Tümü</option>
            <option value="havale" <?= $paymentMethodGet === 'havale' ? 'selected' : '' ?>>Havale</option>
            <option value="kredi_karti" <?= $paymentMethodGet === 'kredi_karti' ? 'selected' : '' ?>>Kredi Kartı</option>
        </select>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="bank_search">Arama</label>
        <input type="search" name="q" id="bank_search" value="<?= htmlspecialchars($qGet) ?>" placeholder="Ödeme no, sözleşme, müşteri, işlem no..." class="filter-input">
    </div>
<?php
$filterModalBody = ob_get_clean();
$filterFormId = 'bankReportFilterForm';
$filterFormAction = '/raporlar/banka-hesaplari';
$filterSubmitLabel = 'Göster';
$filterModalTitle = 'Banka Raporu — Filtreler';
$filterModalClass = 'screen-only';
require __DIR__ . '/../partials/page_filter_modal.php';
?>

<?php
$csvUrl = reportExportUrl('/raporlar/banka-hesaplari', array_filter([
    'bank_account_id' => $bankAccountId ?: null,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'payment_method' => $paymentMethodGet ?: null,
    'q' => $qGet ?: null,
]));
?>
<div id="report-content">
<?php require __DIR__ . '/../partials/report_export_toolbar.php'; ?>

<?php
$printTitle = 'Banka Hesaplarına Göre Ödemeler Raporu';
$printMeta = [
    ['label' => 'Dönem', 'value' => $periodLabel],
    ['label' => 'Banka', 'value' => $selectedBankLabel],
    ['label' => 'Ödeme yöntemi', 'value' => $paymentMethodLabel],
];
if ($qGet !== '') {
    $printMeta[] = ['label' => 'Arama', 'value' => $qGet];
}
$printSummary = [
    'headers' => ['Tahsilat adedi', 'Tahsilat toplamı', 'Masraf adedi', 'Masraf toplamı'],
    'values' => [count($rows), fmtMoney($paymentTotal) . ' ₺', count($expenseRows), fmtMoney($expenseTotal) . ' ₺'],
];
require __DIR__ . '/../partials/report_print_header.php';
?>

<?php if (!empty($bankAccounts)): ?>
<div class="print-only report-print-section">
    <h2>Hesap Bakiyeleri (<?= date('d.m.Y', strtotime($endDate)) ?> itibarıyla)</h2>
    <table class="report-data-table">
        <thead>
            <tr>
                <th>Banka</th>
                <th>Hesap sahibi</th>
                <th>Bakiye</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bankAccounts as $ba):
                $bal = $bankBalances[$ba['id'] ?? ''] ?? 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($ba['bank_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($ba['account_holder_name'] ?? '') ?></td>
                <td><?= fmtMoney($bal) ?> ₺</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mb-6 card-modern p-6 screen-only">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Hesap Bakiyeleri (<?= date('d.m.Y', strtotime($endDate)) ?> itibarıyla)</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Açılış bakiyesi + Tahsilat - Masraflar</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($bankAccounts as $ba):
            $bal = $bankBalances[$ba['id'] ?? ''] ?? 0;
        ?>
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-600">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($ba['bank_name'] ?? '') ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($ba['account_holder_name'] ?? '') ?></p>
                <p class="text-xl font-bold mt-2 <?= $bal >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>"><?= fmtMoney($bal) ?> ₺</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden report-print-table-wrap">
    <?php if (empty($rows)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400 screen-only">Seçilen kriterlere uygun ödeme kaydı yok.</div>
        <div class="print-only p-4 text-gray-600">Seçilen kriterlere uygun ödeme kaydı yok.</div>
    <?php else: ?>
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center screen-only">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Toplam <?= count($rows) ?> ödeme</span>
            <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= fmtMoney($paymentTotal) ?> ₺</span>
        </div>
        <div class="print-only report-print-section">
            <h2>Tahsilatlar</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 report-data-table">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ödeme No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Banka</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sözleşme / Müşteri</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase screen-only">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php
                    $reportUrl = '/raporlar/banka-hesaplari?' . http_build_query(array_filter(['bank_account_id' => $bankAccountId ?: null, 'start_date' => $startDate, 'end_date' => $endDate]));
                    foreach ($rows as $r):
                        $customerName = trim(($r['customer_first_name'] ?? '') . ' ' . ($r['customer_last_name'] ?? ''));
                        $contractCustomer = ($r['contract_number'] ?? '') . ' / ' . $customerName;
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <a href="/odemeler/<?= htmlspecialchars($r['id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium screen-only"><?= htmlspecialchars($r['payment_number'] ?? '-') ?></a>
                                <span class="print-only"><?= htmlspecialchars($r['payment_number'] ?? '-') ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDateTime($r['paid_at'] ?? null) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($r['bank_name'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                <span class="screen-only"><?= htmlspecialchars($contractCustomer) ?></span>
                                <span class="print-only"><?= htmlspecialchars($contractCustomer) ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white"><?= fmtMoney($r['amount'] ?? 0) ?> ₺</td>
                            <td class="px-4 py-3 text-right screen-only">
                                <form method="post" action="/odemeler/<?= htmlspecialchars($r['id']) ?>/iptal" class="inline" onsubmit="return confirm('Bu ödemeyi iptal etmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($reportUrl) ?>">
                                    <button type="submit" class="btn-touch text-red-600 dark:text-red-400 hover:underline text-sm font-medium">Ödemeyi iptal et</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="print-only">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-bold">Toplam</td>
                        <td class="px-4 py-3 text-right font-bold"><?= fmtMoney($paymentTotal) ?> ₺</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($expenseRows)): ?>
<div class="mt-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden report-print-table-wrap">
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center screen-only">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Banka hesabından yapılan masraflar (<?= count($expenseRows) ?> adet)</span>
        <span class="text-lg font-bold text-red-600 dark:text-red-400"><?= fmtMoney($expenseTotal) ?> ₺</span>
    </div>
    <div class="print-only report-print-section">
        <h2>Banka Masrafları</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 report-data-table">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                <?php foreach ($expenseRows as $er): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= $er['expense_date'] ? date('d.m.Y', strtotime($er['expense_date'])) : '-' ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($er['category_name'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($er['description'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-red-600 dark:text-red-400"><?= fmtMoney($er['amount'] ?? 0) ?> ₺</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="print-only">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-right font-bold">Toplam</td>
                    <td class="px-4 py-3 text-right font-bold"><?= fmtMoney($expenseTotal) ?> ₺</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

</div>
<script>
function applyBankMonthPreset() {
    var y = parseInt(document.getElementById('bank_year').value, 10);
    var m = parseInt(document.getElementById('bank_month').value, 10);
    var start = new Date(y, m - 1, 1);
    var end = new Date(y, m, 0);
    document.getElementById('bank_start_date').value = start.toISOString().slice(0, 10);
    document.getElementById('bank_end_date').value = end.toISOString().slice(0, 10);
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
