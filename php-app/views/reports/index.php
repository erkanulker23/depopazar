<?php
$currentPage = 'raporlar';
$year = $year ?? (int) date('Y');
$month = $month ?? (int) date('n');
$periodMode = $periodMode ?? 'month';
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$paidPayments = $paidPayments ?? [];
$paidPeriodTotal = $paidPeriodTotal ?? 0;
$csvUrl = $csvUrl ?? null;
$occupancy = $occupancy ?? ['total_rooms' => 0, 'occupied_rooms' => 0, 'empty_rooms' => 0, 'occupancy_rate' => 0];
$revenueByMonth = $revenueByMonth ?? ['total_revenue' => 0, 'total_payments' => 0, 'payments' => []];
$monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
$periodLabel = ($periodMode ?? 'month') === 'custom'
    ? (date('d.m.Y', strtotime($startDate)) . ' – ' . date('d.m.Y', strtotime($endDate)))
    : (($monthDisplay ?? 0) === 0 ? 'Tüm Yıl ' . $year : (($monthNames[($monthDisplay ?? 1) - 1] ?? '') . ' ' . $year));
$defaultStart = date('Y-m-01');
$defaultEnd = date('Y-m-t');
$hasActiveFilters = ($periodMode ?? 'month') === 'custom' || $year !== (int) date('Y') || ($monthDisplay ?? 0) !== (int) date('n');
$activeFilterTags = ['Dönem: ' . $periodLabel];
ob_start();
?>
<div class="mb-8">
    <h1 class="page-title gradient-title">Raporlar</h1>
    <p class="page-subtitle">Doluluk, gelir ve müşteri ödeme raporları</p>
</div>

<div class="page-toolbar mb-6">
    <?php
    $filterModalId = 'reportPeriodFilterModal';
    $filterClearUrl = '/raporlar';
    require __DIR__ . '/../partials/page_filter_trigger.php';
    ?>
</div>

<?php
ob_start();
?>
    <div class="filter-field">
        <label class="filter-label" for="report_period_mode">Dönem</label>
        <select name="period_mode" id="report_period_mode" onchange="toggleReportPeriodMode()" class="filter-input">
            <option value="month" <?= ($periodMode ?? 'month') === 'month' ? 'selected' : '' ?>>Ay bazlı</option>
            <option value="custom" <?= ($periodMode ?? '') === 'custom' ? 'selected' : '' ?>>Özel tarih aralığı</option>
        </select>
    </div>
    <div id="report_month_fields" class="space-y-4 <?= ($periodMode ?? 'month') === 'custom' ? 'hidden' : '' ?>">
        <div class="filter-field">
            <label class="filter-label" for="report_year">Yıl</label>
            <select name="year" id="report_year" class="filter-input">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-field">
            <label class="filter-label" for="report_month">Ay</label>
            <select name="month" id="report_month" class="filter-input">
                <option value="0" <?= ($monthDisplay ?? 0) === 0 ? 'selected' : '' ?>>Tüm Aylar</option>
                <?php foreach ($monthNames as $i => $m): ?>
                    <option value="<?= $i + 1 ?>" <?= ($monthDisplay ?? 0) === $i + 1 ? 'selected' : '' ?>><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div id="report_custom_fields" class="space-y-4 <?= ($periodMode ?? 'month') === 'custom' ? '' : 'hidden' ?>">
        <div class="filter-field">
            <label class="filter-label" for="report_start_date">Başlangıç</label>
            <input type="date" name="start_date" id="report_start_date" value="<?= htmlspecialchars($startDate) ?>" class="filter-input">
        </div>
        <div class="filter-field">
            <label class="filter-label" for="report_end_date">Bitiş</label>
            <input type="date" name="end_date" id="report_end_date" value="<?= htmlspecialchars($endDate) ?>" class="filter-input">
        </div>
    </div>
<?php
$filterModalBody = ob_get_clean();
$filterFormId = 'reportPeriodForm';
$filterFormAction = '/raporlar';
$filterSubmitLabel = 'Göster';
$filterModalTitle = 'Rapor Dönemi';
require __DIR__ . '/../partials/page_filter_modal.php';
?>

<script>
function toggleReportPeriodMode() {
    var mode = document.getElementById('report_period_mode').value;
    document.getElementById('report_month_fields').classList.toggle('hidden', mode !== 'month');
    document.getElementById('report_custom_fields').classList.toggle('hidden', mode !== 'custom');
}
</script>

<div id="report-content">
<?php require __DIR__ . '/../partials/report_export_toolbar.php'; ?>

<!-- Rapor kartları -->
<div class="mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <a href="/raporlar/banka-hesaplari" class="block p-6 card-modern hover:border-emerald-500/50 dark:hover:border-emerald-500/50 group">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-bank text-2xl text-white"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Banka Hesap Raporu</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Hangi banka hesabına ne kadar para girmiş – tüm detaylar</p>
            </div>
            <span class="text-emerald-600 dark:text-emerald-400 text-xl group-hover:translate-x-1 transition-transform">→</span>
        </div>
    </a>
    <a href="/raporlar/masraflar" class="block p-6 card-modern hover:border-emerald-500/50 dark:hover:border-emerald-500/50 group">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-amber-600 flex items-center justify-center shadow-lg shadow-red-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-wallet2 text-2xl text-white"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Masraf Raporu</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kategoriye ve ödeme kaynağına göre harcamalar</p>
            </div>
            <span class="text-emerald-600 dark:text-emerald-400 text-xl group-hover:translate-x-1 transition-transform">→</span>
        </div>
    </a>
    <a href="/raporlar/vadesi-gelen" class="block p-6 card-modern hover:border-emerald-500/50 dark:hover:border-emerald-500/50 group">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-calendar-event text-2xl text-white"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Vadesi Gelen Ödemeler</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Seçilen dönemde vadesi gelen bekleyen ve gecikmiş ödemeler — filtrele, yazdır, Excel</p>
            </div>
            <span class="text-emerald-600 dark:text-emerald-400 text-xl group-hover:translate-x-1 transition-transform">→</span>
        </div>
    </a>
    <a href="/raporlar/erken-odemeler" class="block p-6 card-modern hover:border-emerald-500/50 dark:hover:border-emerald-500/50 group">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-lightning-charge text-2xl text-white"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Erken / Peşin Ödemeler</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Vadesinden önce tahsil edilen ödemeler ve tüm taksitlerini peşin kapatan müşteriler</p>
            </div>
            <span class="text-emerald-600 dark:text-emerald-400 text-xl group-hover:translate-x-1 transition-transform">→</span>
        </div>
    </a>
</div>

<?php $paymentBreakdown = $paymentBreakdown ?? ['credit_card' => 0, 'bank' => 0]; $monthDisplay = $monthDisplay ?? (int)date('n'); ?>
<!-- Ödeme yöntemine göre: Havale, Kredi kartı -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-card">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <i class="bi bi-bank text-emerald-600 dark:text-emerald-400 text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Havale / EFT (<?= htmlspecialchars($periodLabel) ?>)</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= fmtMoney($paymentBreakdown['bank'] ?? 0) ?> ₺</p>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="bi bi-credit-card text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Kredi Kartı Alınanlar</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= fmtMoney($paymentBreakdown['credit_card'] ?? 0) ?> ₺</p>
            </div>
        </div>
    </div>
</div>
<!-- Özet kartlar -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <i class="bi bi-cash-stack text-amber-600 dark:text-amber-400 text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Toplam Bekleyen Borç</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= fmtMoney($totalUnpaid ?? 0) ?> ₺</p>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <i class="bi bi-bank text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Bu Ay Tahsilat</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= fmtMoney($paidThisMonth ?? 0) ?> ₺</p>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <i class="bi bi-file-text text-emerald-600 dark:text-emerald-400 text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aktif Sözleşme</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= (int) ($activeContracts ?? 0) ?></p>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="bi bi-calendar-check text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"><?= $year ?> Yılı Tahsilat</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= fmtMoney($paidInYear ?? 0) ?> ₺</p>
            </div>
        </div>
    </div>
</div>

<!-- Doluluk + Gelir raporu -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="card-modern p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="bi bi-building text-emerald-600 dark:text-emerald-400"></i> Doluluk Raporu
        </h2>
        <div class="space-y-3">
            <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Toplam oda</span>
                <span class="font-semibold text-gray-900 dark:text-white"><?= (int) $occupancy['total_rooms'] ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Dolu</span>
                <span class="font-semibold text-green-600 dark:text-green-400"><?= (int) $occupancy['occupied_rooms'] ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Boş</span>
                <span class="font-semibold text-gray-600 dark:text-gray-300"><?= (int) $occupancy['empty_rooms'] ?></span>
            </div>
            <div class="flex justify-between items-center pt-3">
                <span class="text-gray-600 dark:text-gray-400">Doluluk oranı</span>
                <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">%<?= number_format($occupancy['occupancy_rate'], 1, ',', '') ?></span>
            </div>
        </div>
    </div>

    <div class="card-modern p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="bi bi-currency-exchange text-emerald-600 dark:text-emerald-400"></i> Gelir Raporu (<?= htmlspecialchars($periodLabel) ?>)
        </h2>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-400">Toplam gelir</span>
                <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400"><?= fmtMoney($revenueByMonth['total_revenue']) ?> ₺</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Ödeme sayısı</span>
                <span class="font-semibold text-gray-900 dark:text-white"><?= (int) $revenueByMonth['total_payments'] ?></span>
            </div>
            <?php if (!empty($revenueByMonth['payments'])): ?>
                <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Ödemeler</p>
                    <div class="max-h-48 overflow-y-auto space-y-1.5">
                        <?php foreach ($revenueByMonth['payments'] as $p): ?>
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span><?= htmlspecialchars($p['contract_number'] ?? '-') ?></span>
                                <span><?= fmtMoney($p['amount'] ?? 0) ?> ₺</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tahsil edilen müşteriler -->
<div class="grid grid-cols-1 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
        <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 border-b border-green-100 dark:border-green-800 flex flex-wrap justify-between items-center gap-2">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-check-circle text-green-600"></i> Ödemesi yapılan müşteriler
            </h2>
            <span class="text-sm font-semibold text-green-800 dark:text-green-300"><?= count($paidPayments) ?> kayıt · <?= fmtMoney($paidPeriodTotal) ?> ₺</span>
        </div>
        <?php if (empty($paidPayments)): ?>
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">Seçilen dönemde tahsilat kaydı yok.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Sözleşme</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Depo / Oda</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tahsilat</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Yöntem</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($paidPayments as $pp):
                        $pName = trim(($pp['customer_first_name'] ?? '') . ' ' . ($pp['customer_last_name'] ?? ''));
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3"><a href="/musteriler/<?= htmlspecialchars($pp['customer_id'] ?? '') ?>" class="font-medium text-emerald-600 hover:underline"><?= htmlspecialchars($pName) ?></a></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($pp['contract_number'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars(($pp['warehouse_name'] ?? '') . ' / ' . ($pp['room_number'] ?? '')) ?></td>
                        <td class="px-4 py-3"><?= fmtDateTime($pp['paid_at'] ?? null) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($pp['payment_method'] ?? '–') ?></td>
                        <td class="px-4 py-3 text-right font-semibold text-green-700 dark:text-green-400"><?= fmtMoney($pp['amount'] ?? 0) ?> ₺</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Özet + Hızlı erişim -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card-modern p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="bi bi-hourglass-split text-emerald-600 dark:text-emerald-400"></i> Ödeme Durumu
        </h2>
        <ul class="space-y-3">
            <li class="py-2 border-b border-gray-100 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('pendingCustomers').classList.toggle('hidden')" class="w-full flex justify-between items-center text-left">
                    <span class="text-gray-600 dark:text-gray-400">Bekleyen ödeme</span>
                    <span class="font-semibold text-gray-900 dark:text-white"><?= (int) ($pendingCount ?? 0) ?></span>
                </button>
                <div id="pendingCustomers" class="hidden mt-2 pl-4 border-l-2 border-amber-200 dark:border-amber-800 space-y-1 max-h-40 overflow-y-auto">
                    <?php $pendingCustomers = $pendingCustomers ?? []; foreach ($pendingCustomers as $pc): ?>
                    <a href="/musteriler/<?= htmlspecialchars($pc['id']) ?>" class="block text-sm text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400"><?= htmlspecialchars(trim(($pc['first_name'] ?? '') . ' ' . ($pc['last_name'] ?? ''))) ?> – <?= number_format((float)($pc['total_debt'] ?? 0), 2, ',', '.') ?> ₺</a>
                    <?php endforeach; ?>
                    <?php if (empty($pendingCustomers)): ?><p class="text-sm text-gray-500 dark:text-gray-400">Müşteri yok</p><?php endif; ?>
                </div>
            </li>
            <li class="py-2">
                <button type="button" onclick="document.getElementById('overdueCustomers').classList.toggle('hidden')" class="w-full flex justify-between items-center text-left">
                    <span class="text-gray-600 dark:text-gray-400">Gecikmiş ödeme</span>
                    <span class="font-semibold text-red-600 dark:text-red-400"><?= (int) ($overdueCount ?? 0) ?></span>
                </button>
                <div id="overdueCustomers" class="hidden mt-2 pl-4 border-l-2 border-red-200 dark:border-red-800 space-y-1 max-h-40 overflow-y-auto">
                    <?php $overdueCustomers = $overdueCustomers ?? []; foreach ($overdueCustomers as $oc): ?>
                    <a href="/musteriler/<?= htmlspecialchars($oc['id']) ?>" class="block text-sm text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400"><?= htmlspecialchars(trim(($oc['first_name'] ?? '') . ' ' . ($oc['last_name'] ?? ''))) ?> – <?= number_format((float)($oc['total_debt'] ?? 0), 2, ',', '.') ?> ₺</a>
                    <?php endforeach; ?>
                    <?php if (empty($overdueCustomers)): ?><p class="text-sm text-gray-500 dark:text-gray-400">Müşteri yok</p><?php endif; ?>
                </div>
            </li>
        </ul>
    </div>
    <div class="card-modern p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="bi bi-link-45deg text-emerald-600 dark:text-emerald-400"></i> Hızlı Erişim
        </h2>
        <div class="flex flex-wrap gap-2">
            <a href="/raporlar/vadesi-gelen" class="inline-flex items-center px-4 py-2 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 font-medium hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors">
                <i class="bi bi-calendar-event mr-2"></i> Vadesi Gelen
            </a>
            <a href="/raporlar/banka-hesaplari" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 font-medium hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors">
                <i class="bi bi-bank mr-2"></i> Banka Raporu
            </a>
            <a href="/raporlar/masraflar" class="inline-flex items-center px-4 py-2 rounded-xl bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 font-medium hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                <i class="bi bi-wallet2 mr-2"></i> Masraf Raporu
            </a>
            <a href="/odemeler" class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="bi bi-credit-card mr-2"></i> Ödemeler
            </a>
            <a href="/girisler" class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="bi bi-file-text mr-2"></i> Girişler
            </a>
            <a href="/musteriler" class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="bi bi-people mr-2"></i> Müşteriler
            </a>
        </div>
    </div>
</div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
