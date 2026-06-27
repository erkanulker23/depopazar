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
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
?>
<div class="mb-6">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/raporlar" class="text-emerald-600 dark:text-emerald-400 hover:underline">Raporlar</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Banka Hesaplarına Göre Ödemeler</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Banka Hesaplarına Göre Ödemeler</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Tarih aralığına ve banka hesabına göre tahsilat listesi</p>
</div>

<form method="get" action="/raporlar/banka-hesaplari" class="page-toolbar mb-6 p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yıl / Ay (hızlı)</label>
        <div class="flex flex-wrap gap-2">
            <select id="bank_year" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= (int) date('Y', strtotime($startDate)) === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select id="bank_month" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
                <?php $monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']; foreach ($monthNames as $i => $mn): ?>
                    <option value="<?= $i + 1 ?>"><?= $mn ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="applyBankMonthPreset()" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-sm">Ay uygula</button>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Banka Hesabı</label>
        <select name="bank_account_id" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white min-w-[200px]">
            <option value="">Tümü</option>
            <?php foreach ($bankAccounts as $ba): ?>
                <option value="<?= htmlspecialchars($ba['id']) ?>" <?= $bankAccountId === $ba['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ba['bank_name'] . ' - ' . ($ba['account_holder_name'] ?? '')) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç</label>
        <input type="date" name="start_date" id="bank_start_date" value="<?= htmlspecialchars($startDate) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
        <input type="date" name="end_date" id="bank_end_date" value="<?= htmlspecialchars($endDate) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ödeme Yöntemi</label>
        <select name="payment_method" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white min-w-[140px]">
            <option value="">Tümü</option>
            <option value="havale" <?= $paymentMethodGet === 'havale' ? 'selected' : '' ?>>Havale</option>
            <option value="kredi_karti" <?= $paymentMethodGet === 'kredi_karti' ? 'selected' : '' ?>>Kredi Kartı</option>
        </select>
    </div>
    <div class="w-full sm:w-auto flex-1 min-w-[200px]">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arama</label>
        <input type="search" name="q" value="<?= htmlspecialchars($qGet) ?>" placeholder="Ödeme no, sözleşme, müşteri, işlem no..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <button type="submit" class="btn-touch btn-filter"><i class="bi bi-funnel-fill text-sm opacity-90" aria-hidden="true"></i> Göster</button>
    <?php if ($qGet !== '' || $paymentMethodGet !== '' || $bankAccountId !== ''): ?>
        <a href="/raporlar/banka-hesaplari?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm">Temizle</a>
    <?php endif; ?>
</form>

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
<?php if (!empty($bankAccounts)): ?>
<div class="mb-6 card-modern p-6">
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

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
    <?php if (empty($rows)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Seçilen kriterlere uygun ödeme kaydı yok.</div>
    <?php else: ?>
        <?php
        $total = array_sum(array_map(fn($r) => (float)($r['amount'] ?? 0), $rows));
        ?>
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Toplam <?= count($rows) ?> ödeme</span>
            <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400"><?= fmtMoney($total) ?> ₺</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ödeme No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Banka</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sözleşme / Müşteri</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php
                    $reportUrl = '/raporlar/banka-hesaplari?' . http_build_query(array_filter(['bank_account_id' => $bankAccountId ?: null, 'start_date' => $startDate, 'end_date' => $endDate]));
                    foreach ($rows as $r):
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <a href="/odemeler/<?= htmlspecialchars($r['id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium"><?= htmlspecialchars($r['payment_number'] ?? '-') ?></a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDateTime($r['paid_at'] ?? null) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($r['bank_name'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($r['contract_number'] ?? '') ?> / <?= htmlspecialchars(trim(($r['customer_first_name'] ?? '') . ' ' . ($r['customer_last_name'] ?? ''))) ?></td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white"><?= fmtMoney($r['amount'] ?? 0) ?> ₺</td>
                            <td class="px-4 py-3 text-right">
                                <form method="post" action="/odemeler/<?= htmlspecialchars($r['id']) ?>/iptal" class="inline" onsubmit="return confirm('Bu ödemeyi iptal etmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($reportUrl) ?>">
                                    <button type="submit" class="btn-touch text-red-600 dark:text-red-400 hover:underline text-sm font-medium">Ödemeyi iptal et</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($expenseRows)): ?>
<div class="mt-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
    <?php $expTotal = array_sum(array_map(fn($r) => (float)($r['amount'] ?? 0), $expenseRows)); ?>
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Banka hesabından yapılan masraflar (<?= count($expenseRows) ?> adet)</span>
        <span class="text-lg font-bold text-red-600 dark:text-red-400"><?= fmtMoney($expTotal) ?> ₺</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
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
