<?php
$currentPage = 'raporlar';
$year = $year ?? (int) date('Y');
$month = $month ?? (int) date('n');
$occupancy = $occupancy ?? ['total_rooms' => 0, 'occupied_rooms' => 0, 'empty_rooms' => 0, 'occupancy_rate' => 0];
$revenueByMonth = $revenueByMonth ?? ['total_revenue' => 0, 'total_payments' => 0, 'payments' => []];
$monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
ob_start();
?>
<div class="mb-8">
    <h1 class="page-title gradient-title">Raporlar</h1>
    <p class="page-subtitle">Doluluk ve gelir raporları</p>
</div>

<form method="get" action="/raporlar" class="mb-6 flex flex-wrap items-center gap-4">
    <div>
        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Yıl</label>
        <select name="year" onchange="this.form.submit()" class="px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Ay</label>
        <select name="month" onchange="this.form.submit()" class="px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            <option value="0" <?= ($monthDisplay ?? 0) === 0 ? 'selected' : '' ?>>Tüm Aylar</option>
            <?php foreach ($monthNames as $i => $m): ?>
                <option value="<?= $i + 1 ?>" <?= ($monthDisplay ?? 0) === $i + 1 ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<!-- Banka hesabı raporu kartı -->
<div class="mb-8">
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
</div>

<?php $paymentBreakdown = $paymentBreakdown ?? ['cash' => 0, 'credit_card' => 0, 'bank' => 0]; $monthDisplay = $monthDisplay ?? (int)date('n'); ?>
<!-- Ödeme yöntemine göre: Nakit, Kredi kartı, Banka -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <i class="bi bi-cash-coin text-amber-600 dark:text-amber-400 text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Nakit Alınanlar (<?= ($monthDisplay ?? 0) === 0 ? 'Yıllık ' . $year : ($monthNames[($monthDisplay ?? 1) - 1] ?? '') . ' ' . $year ?>)</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= fmtMoney($paymentBreakdown['cash'] ?? 0) ?> ₺</p>
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
    <div class="stat-card">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <i class="bi bi-bank text-emerald-600 dark:text-emerald-400 text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Banka Hesabına Alınanlar</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= fmtMoney($paymentBreakdown['bank'] ?? 0) ?> ₺</p>
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
            <i class="bi bi-currency-exchange text-emerald-600 dark:text-emerald-400"></i> Gelir Raporu (<?= ($monthDisplay ?? 0) === 0 ? 'Tüm Yıl ' . $year : ($monthNames[($monthDisplay ?? 1) - 1] ?? '') . ' ' . $year ?>)
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
            <a href="/raporlar/banka-hesaplari" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 font-medium hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors">
                <i class="bi bi-bank mr-2"></i> Banka Raporu
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
