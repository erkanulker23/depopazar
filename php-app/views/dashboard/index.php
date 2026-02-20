<?php
$currentPage = 'genel-bakis';
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
$setupSteps = $setupSteps ?? [];
if (!is_array($setupSteps)) {
    $setupSteps = [];
}
// Eski deployda controller steps göndermeyebilir; varsayılan 6 adım göster (hepsi yapılmadı)
if (empty($setupSteps)) {
    $setupSteps = [
        'company'   => ['done' => false, 'label' => 'Firma bilgilerini güncelleyiniz', 'href' => '/ayarlar?tab=firma', 'icon' => 'bi-building-gear'],
        'warehouses' => ['done' => false, 'label' => 'Depolarınızı ekleyin', 'href' => '/depolar', 'icon' => 'bi-building'],
        'rooms'     => ['done' => false, 'label' => 'Odalarınızı ekleyin', 'href' => '/odalar', 'icon' => 'bi-grid-3x3'],
        'staff'     => ['done' => false, 'label' => 'Personel ekleyin', 'href' => '/kullanicilar', 'icon' => 'bi-people'],
        'vehicles'  => ['done' => false, 'label' => 'Araçlarınızı ekleyin', 'href' => '/araclar', 'icon' => 'bi-truck'],
        'services'  => ['done' => false, 'label' => 'Hizmetlerinizi ekleyin', 'href' => '/hizmetler', 'icon' => 'bi-list-check'],
    ];
    $setupComplete = false;
} else {
    $setupComplete = $setupComplete ?? true;
    foreach ($setupSteps as $s) {
        if (empty($s['done'])) {
            $setupComplete = false;
            break;
        }
    }
}
?>
<div class="mb-8">
    <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-600 bg-clip-text text-transparent">Genel Bakış</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sistem özeti ve hızlı erişim</p>
</div>

<?php if (!empty($setupSteps) && !$setupComplete): ?>
<div class="setup-guide mb-8 rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 shadow-sm overflow-hidden" role="region" aria-label="Kurulum rehberi">
    <div class="p-5 md:p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-lg animate-pulse">
                <i class="bi bi-lightbulb text-2xl"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Kurulum rehberi</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Sistemi kullanmaya başlamak için aşağıdaki adımları tamamlayın.</p>
            </div>
        </div>
        <ul class="space-y-2" id="setupStepsList">
            <?php
            $order = ['company', 'warehouses', 'rooms', 'staff', 'vehicles', 'services'];
            $delay = 0;
            foreach ($order as $key):
                if (!isset($setupSteps[$key])) continue;
                $step = $setupSteps[$key];
                $done = $step['done'];
                $delay += 80;
            ?>
            <li class="setup-step flex items-center gap-3 py-2.5 px-3 rounded-xl transition-all duration-300 <?= $done ? 'bg-white/60 dark:bg-gray-800/40 opacity-90' : 'bg-white dark:bg-gray-800/60 shadow-sm' ?>" style="animation: setupFadeIn 0.4s ease-out <?= $delay ?>ms both;">
                <?php if ($done): ?>
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-white" aria-hidden="true">
                        <i class="bi bi-check-lg text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400 line-through"><?= htmlspecialchars($step['label']) ?></span>
                <?php else: ?>
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <i class="bi <?= htmlspecialchars($step['icon']) ?> text-lg"></i>
                    </span>
                    <a href="<?= htmlspecialchars($step['href']) ?>" class="flex-1 text-sm font-semibold text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"><?= htmlspecialchars($step['label']) ?></a>
                    <i class="bi bi-chevron-right text-gray-400 dark:text-gray-500 text-sm"></i>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<style>
@keyframes setupFadeIn {
    from { opacity: 0; transform: translateX(-8px); }
    to { opacity: 1; transform: translateX(0); }
}
.setup-step:not([class*="opacity-90"]):hover { background: rgba(255,255,255,0.9); }
.dark .setup-step:not([class*="opacity-90"]):hover { background: rgba(30,41,59,0.6); }
</style>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <a href="/depolar" class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-emerald-500/10 transition-all duration-300 group block">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Toplam Depo</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $warehousesCount ?></p>
            </div>
            <div class="p-3 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex-shrink-0 shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-building text-white text-xl"></i>
            </div>
        </div>
    </a>
    <a href="/odalar" class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-blue-500/10 transition-all duration-300 group block">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Toplam Oda</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $roomsCount ?></p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 font-bold"><?= (int) $occupiedRooms ?> Dolu / <?= (int) $emptyRooms ?> Boş</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex-shrink-0 shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-grid-3x3 text-white text-xl"></i>
            </div>
        </div>
    </a>
    <a href="/musteriler" class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-purple-500/10 transition-all duration-300 group block">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Toplam Müşteri</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $customersCount ?></p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 font-bold"><?= (int) $activeContracts ?> Aktif Sözleşme</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex-shrink-0 shadow-lg shadow-purple-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-people text-white text-xl"></i>
            </div>
        </div>
    </a>
    <div class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-emerald-500/10 transition-all duration-300 group">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Bu Ay Gelir</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= fmtMoney($monthlyRevenue) ?> ₺</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex-shrink-0 shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-currency-dollar text-white text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <a href="/odemeler?status=pending" class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-amber-500/10 transition-all duration-300 group block">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0 overflow-hidden">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Bekleyen Ödeme</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $pendingPayments ?></p>
            </div>
            <div class="p-3 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex-shrink-0 shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-credit-card text-white text-xl"></i>
            </div>
        </div>
    </a>
    <a href="/odemeler?status=overdue" class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-red-500/10 transition-all duration-300 group block">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0 overflow-hidden">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Geciken Ödeme</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $overduePayments ?></p>
            </div>
            <div class="p-3 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex-shrink-0 shadow-lg shadow-red-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-exclamation-triangle text-white text-xl"></i>
            </div>
        </div>
    </a>
    <a href="/odemeler" class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-rose-500/10 transition-all duration-300 group block">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0 overflow-hidden">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Toplam Borç</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 break-words"><?= fmtMoney($totalDebt) ?> ₺</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-rose-500 to-red-600 rounded-xl flex-shrink-0 shadow-lg shadow-rose-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-cash-stack text-white text-xl"></i>
            </div>
        </div>
    </a>
</div>

<?php $upcomingPayments = $upcomingPayments ?? []; $expiringContracts = $expiringContracts ?? []; ?>
<?php if (!empty($upcomingPayments) || !empty($expiringContracts)): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-6">
    <?php if (!empty($upcomingPayments)): ?>
    <div class="card-modern p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <h2 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
            <i class="bi bi-calendar-event text-amber-600 dark:text-amber-400"></i> Önümüzdeki 10 Gün Vadesi Gelen Ödemeler
        </h2>
        <ul class="space-y-2 max-h-48 overflow-y-auto">
            <?php foreach (array_slice($upcomingPayments, 0, 15) as $p): ?>
                <li class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700 text-sm gap-2">
                    <span class="min-w-0 text-gray-700 dark:text-gray-300"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?> – <?= htmlspecialchars($p['contract_number'] ?? '') ?> / <?= htmlspecialchars(trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? ''))) ?></span>
                    <span class="font-semibold text-gray-900 dark:text-white flex-shrink-0"><?= fmtMoney($p['amount'] ?? 0) ?> ₺</span>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="/odemeler" class="inline-block mt-3 text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Tüm ödemeler →</a>
    </div>
    <?php endif; ?>
    <?php if (!empty($expiringContracts)): ?>
    <div class="card-modern p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <h2 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
            <i class="bi bi-file-earmark-x text-blue-600 dark:text-blue-400"></i> 30 Gün İçinde Süresi Dolan Sözleşmeler
        </h2>
        <ul class="space-y-2 max-h-48 overflow-y-auto">
            <?php foreach (array_slice($expiringContracts, 0, 15) as $c): ?>
                <li class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700 text-sm gap-2">
                    <span class="min-w-0 text-gray-700 dark:text-gray-300"><?= date('d.m.Y', strtotime($c['end_date'] ?? '')) ?> – <?= htmlspecialchars($c['contract_number'] ?? '') ?> / <?= htmlspecialchars(trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''))) ?></span>
                    <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline flex-shrink-0">Detay</a>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="/girisler" class="inline-block mt-3 text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Tüm girişler →</a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
