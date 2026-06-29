<?php
$currentPage = 'genel-bakis';
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
function daysOverdue(?string $dueDate): int {
    if (!$dueDate) return 0;
    $due = strtotime(explode(' ', $dueDate)[0]);
    $today = strtotime(date('Y-m-d'));
    return $due === false ? 0 : max(0, (int) (($today - $due) / 86400));
}
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
        'staff'     => ['done' => false, 'label' => 'Saha personeli ekleyin', 'href' => '/personel', 'icon' => 'bi-person-badge'],
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
$authUser = Auth::user();
$userDisplayName = trim(($authUser['first_name'] ?? '') . ' ' . ($authUser['last_name'] ?? ''));
?>
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-600 bg-clip-text text-transparent">Genel Bakış</h1>
            <p class="text-base md:text-lg text-gray-700 dark:text-gray-200 mt-2 font-medium">
                Merhaba<?= $userDisplayName !== '' ? ' ' . htmlspecialchars($userDisplayName) : '' ?>, hoş geldin
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sistem özeti ve hızlı erişim</p>
        </div>
        <div class="inline-flex items-center gap-3 px-4 py-3 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm text-sm shrink-0" aria-live="polite">
            <span class="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                <i class="bi bi-calendar3 text-emerald-600 dark:text-emerald-400 text-base"></i>
                <span id="dashboardToday" class="font-medium tabular-nums">—</span>
            </span>
            <span class="w-px h-5 bg-gray-200 dark:bg-gray-600" aria-hidden="true"></span>
            <span class="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                <i class="bi bi-clock text-emerald-600 dark:text-emerald-400 text-base"></i>
                <span id="dashboardClock" class="font-semibold tabular-nums text-emerald-700 dark:text-emerald-300">—</span>
            </span>
        </div>
    </div>
</div>
<script>
(function() {
    var days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    var months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    var dateEl = document.getElementById('dashboardToday');
    var clockEl = document.getElementById('dashboardClock');
    if (!dateEl || !clockEl) return;
    function tick() {
        var now = new Date();
        dateEl.textContent = 'Bugün ' + days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
        clockEl.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

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
                <?php
                $otherRooms = (int) ($reservedRooms ?? 0) + (int) ($lockedRooms ?? 0);
                $roomStatusLine = (int) $occupiedRooms . ' Dolu / ' . (int) $emptyRooms . ' Boş';
                if ($otherRooms > 0) {
                    $roomStatusLine .= ' / ' . $otherRooms . ' Diğer';
                }
                ?>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 font-bold"><?= $roomStatusLine ?></p>
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

<?php if ($companyId || ($user['role'] ?? '') === 'super_admin'): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
    <a href="/odemeler?collect=1" class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-teal-500/10 transition-all duration-300 group block">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Bugün Alınan</p>
                <p class="text-2xl font-bold text-teal-600 dark:text-teal-400 tabular-nums"><?= fmtMoney($paidTodaySum ?? 0) ?> ₺</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 font-bold">Bugün kasaya giren tahsilatlar (erken ödemeler dahil)</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl flex-shrink-0 shadow-lg shadow-teal-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-cash-coin text-white text-xl"></i>
            </div>
        </div>
    </a>
    <a href="/odemeler" class="stat-card min-h-[110px] hover:shadow-lg hover:shadow-cyan-500/10 transition-all duration-300 group block">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Bu Hafta Alınan</p>
                <p class="text-2xl font-bold text-cyan-600 dark:text-cyan-400 tabular-nums"><?= fmtMoney($paidThisWeekSum ?? 0) ?> ₺</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 font-bold"><?= htmlspecialchars($weekRange['label'] ?? 'Bu hafta') ?> · Pazartesi–Pazar</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl flex-shrink-0 shadow-lg shadow-cyan-500/20 group-hover:scale-105 transition-transform">
                <i class="bi bi-calendar-week text-white text-xl"></i>
            </div>
        </div>
    </a>
</div>
<?php endif; ?>

<?php
$topSellers = $topSellers ?? [];
$topPersonnelByJobs = $topPersonnelByJobs ?? [];
$monthRange = $monthRange ?? Payment::currentMonthRange();
$showPerformancePanel = !empty($showMonthPanel);
?>
<?php if ($showPerformancePanel): ?>
<section class="mb-8 rounded-2xl border border-violet-200 dark:border-violet-800 bg-white dark:bg-gray-800 shadow-sm overflow-hidden" aria-label="Satış ve saha performansı">
    <div class="px-5 py-4 border-b border-violet-100 dark:border-violet-900/50 bg-gradient-to-r from-violet-50 to-fuchsia-50 dark:from-violet-900/20 dark:to-fuchsia-900/20 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400">
                <i class="bi bi-trophy text-xl"></i>
            </span>
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Performans</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($monthRange['label'] ?? 'Bu ay') ?> · satış ve saha özeti</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 text-xs">
            <a href="/girisler" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/30 transition-colors">Girişler</a>
            <a href="/nakliye-isler" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/30 transition-colors">Nakliye işleri</a>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 dark:divide-gray-700">
        <div class="p-4 lg:p-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <i class="bi bi-person-check text-emerald-600 dark:text-emerald-400"></i>
                En çok satış yapan kullanıcılar
            </h3>
            <?php if (empty($topSellers)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center rounded-xl bg-slate-50 dark:bg-gray-700/30">Bu ay satış kaydı yok veya satış yapan atanmamış</p>
            <?php else: ?>
                <ol class="space-y-2">
                    <?php foreach ($topSellers as $i => $seller):
                        $name = trim(($seller['first_name'] ?? '') . ' ' . ($seller['last_name'] ?? ''));
                        $rank = $i + 1;
                    ?>
                    <li class="flex items-center gap-3 py-2.5 px-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100/80 dark:border-emerald-900/30">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-bold <?= $rank === 1 ? 'bg-amber-400 text-amber-950' : ($rank === 2 ? 'bg-slate-300 text-slate-800 dark:bg-slate-500 dark:text-white' : ($rank === 3 ? 'bg-orange-300 text-orange-950' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300')) ?>">
                            <?= $rank ?>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($name ?: '-') ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= (int) ($seller['sale_count'] ?? 0) ?> satış · <?= fmtMoney($seller['sale_total'] ?? 0) ?> ₺</p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
        <div class="p-4 lg:p-5 border-t lg:border-t-0 border-slate-100 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <i class="bi bi-truck text-blue-600 dark:text-blue-400"></i>
                En çok işe giden personel
            </h3>
            <?php if (empty($topPersonnelByJobs)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center rounded-xl bg-slate-50 dark:bg-gray-700/30">Bu ay personel atanan nakliye işi yok</p>
            <?php else: ?>
                <ol class="space-y-2">
                    <?php foreach ($topPersonnelByJobs as $i => $person):
                        $name = trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''));
                        $jobType = Personnel::jobTypeLabel($person['job_type'] ?? '');
                        $rank = $i + 1;
                    ?>
                    <li class="flex items-center gap-3 py-2.5 px-3 rounded-xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100/80 dark:border-blue-900/30">
                        <?php
                        $personnel = [
                            'first_name' => $person['first_name'] ?? '',
                            'last_name' => $person['last_name'] ?? '',
                            'photo_url' => $person['photo_url'] ?? null,
                        ];
                        $size = 'sm';
                        require __DIR__ . '/../partials/personnel_avatar.php';
                        ?>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($name ?: '-') ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= (int) ($person['job_count'] ?? 0) ?> iş · <?= htmlspecialchars($jobType) ?><?= $rank <= 3 ? ' · #' . $rank : '' ?></p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$monthRange = $monthRange ?? Payment::currentMonthRange();
$monthOverdueList = $monthOverdueList ?? [];
$monthDueList = $monthDueList ?? [];
$monthPaidList = $monthPaidList ?? [];
$hasMonthData = !empty($showMonthPanel);
?>
<?php if ($hasMonthData): ?>
<section class="mb-8 rounded-2xl border border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-sm overflow-hidden" aria-label="Bu ay özeti">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-gray-700 bg-gradient-to-r from-slate-50 to-white dark:from-gray-800 dark:to-gray-800/80 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <i class="bi bi-calendar-month text-xl"></i>
            </span>
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Bu Ay</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($monthRange['label'] ?? '') ?></p>
            </div>
        </div>
        <a href="/odemeler?collect=1" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 shadow-sm transition-colors">
            <i class="bi bi-bank"></i> Ödeme Al
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-px bg-slate-100 dark:bg-gray-700">
        <div class="bg-white dark:bg-gray-800 p-4">
            <p class="text-[10px] font-bold text-orange-500 dark:text-orange-400 uppercase tracking-wider mb-1">Bu ay geciken</p>
            <p class="text-xl font-bold text-orange-700 dark:text-orange-300 tabular-nums"><?= fmtMoney($monthNewOverdueSum ?? 0) ?> ₺</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= (int) ($monthNewOverdueCount ?? 0) ?> ödeme · vadesi bu ay doldu</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4">
            <p class="text-[10px] font-bold text-amber-500 dark:text-amber-400 uppercase tracking-wider mb-1">Bu ay vadesi gelen</p>
            <p class="text-xl font-bold text-amber-700 dark:text-amber-300 tabular-nums"><?= fmtMoney($monthDueSum ?? 0) ?> ₺</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= (int) ($monthDueCount ?? 0) ?> ödeme · bugünden itibaren</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4">
            <p class="text-[10px] font-bold text-emerald-500 dark:text-emerald-400 uppercase tracking-wider mb-1">Bu ay tahsil edilen</p>
            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300 tabular-nums"><?= fmtMoney($monthPaidSum ?? 0) ?> ₺</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= count($monthPaidList) ?> son işlem gösteriliyor</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-0 lg:divide-x divide-slate-100 dark:divide-gray-700">
        <!-- Bu ay geciken ödemeler -->
        <div class="p-4 lg:p-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                Bu ay geciken
            </h3>
            <?php if (empty($monthOverdueList)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center rounded-xl bg-slate-50 dark:bg-gray-700/30">Bu ay geciken ödeme yok</p>
            <?php else: ?>
                <ul class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    <?php foreach ($monthOverdueList as $p):
                        $name = trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? ''));
                        $late = daysOverdue($p['due_date'] ?? '');
                    ?>
                    <li class="flex items-start justify-between gap-2 py-2.5 px-3 rounded-xl bg-red-50/60 dark:bg-red-900/10 border border-red-100/80 dark:border-red-900/30">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($name ?: '-') ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($p['payment_number'] ?? '') ?> · Vade <?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></p>
                            <?php if ($late > 0): ?><p class="text-xs font-medium text-red-600 dark:text-red-400 mt-0.5"><?= $late ?> gün gecikmiş</p><?php endif; ?>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-red-700 dark:text-red-300 tabular-nums"><?= fmtMoney($p['amount'] ?? 0) ?> ₺</p>
                            <a href="/odemeler?collect=1&customer=<?= htmlspecialchars($p['customer_id'] ?? '') ?>" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Tahsil et</a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Bu ay vadesi gelen -->
        <div class="p-4 lg:p-5 border-t lg:border-t-0 border-slate-100 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                Bu ay vadesi gelen
            </h3>
            <?php if (empty($monthDueList)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center rounded-xl bg-slate-50 dark:bg-gray-700/30">Bu ay kalan vade yok</p>
            <?php else: ?>
                <ul class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    <?php foreach ($monthDueList as $p):
                        $name = trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? ''));
                        $dueTs = strtotime(explode(' ', $p['due_date'] ?? '')[0] ?? '');
                        $isToday = $dueTs === strtotime(date('Y-m-d'));
                    ?>
                    <li class="flex items-start justify-between gap-2 py-2.5 px-3 rounded-xl bg-amber-50/60 dark:bg-amber-900/10 border border-amber-100/80 dark:border-amber-900/30">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($name ?: '-') ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($p['contract_number'] ?? '') ?></p>
                            <p class="text-xs font-medium <?= $isToday ? 'text-amber-700 dark:text-amber-300' : 'text-gray-600 dark:text-gray-400' ?> mt-0.5">
                                <?= $isToday ? 'Bugün vadesi doluyor' : ('Vade: ' . date('d.m.Y', strtotime($p['due_date'] ?? ''))) ?>
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white tabular-nums"><?= fmtMoney($p['amount'] ?? 0) ?> ₺</p>
                            <a href="/odemeler?collect=1&customer=<?= htmlspecialchars($p['customer_id'] ?? '') ?>" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Tahsil et</a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Bu ay tahsil edilen -->
        <div class="p-4 lg:p-5 border-t lg:border-t-0 border-slate-100 dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Bu ay tahsil edilen
            </h3>
            <?php if (empty($monthPaidList)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center rounded-xl bg-slate-50 dark:bg-gray-700/30">Bu ay henüz tahsilat yok</p>
            <?php else: ?>
                <ul class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    <?php foreach ($monthPaidList as $p):
                        $name = trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? ''));
                    ?>
                    <li class="flex items-start justify-between gap-2 py-2.5 px-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-900/10 border border-emerald-100/80 dark:border-emerald-900/30">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($name ?: '-') ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($p['payment_number'] ?? '') ?></p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5"><?= fmtDateTime($p['paid_at'] ?? null) ?></p>
                        </div>
                        <p class="text-sm font-bold text-emerald-700 dark:text-emerald-300 tabular-nums shrink-0"><?= fmtMoney($p['amount'] ?? 0) ?> ₺</p>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($showMonthPanel)): ?>
<section class="mb-8 rounded-2xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-gray-800 shadow-sm overflow-hidden" aria-label="Erken tahsilatlar">
    <div class="px-5 py-4 border-b border-blue-100 dark:border-blue-900/50 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                <i class="bi bi-lightning-charge text-xl"></i>
            </span>
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Erken tahsilatlar</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Vadesinden önce alınan ödemeler · <?= (int) ($earlyPaymentsCount ?? 0) ?> kayıt · <?= fmtMoney($earlyPaymentsSum ?? 0) ?> ₺
                    <?php if (!empty($prepaidContracts)): ?>
                        · <?= count($prepaidContracts) ?> peşin sözleşme
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <a href="/raporlar/erken-odemeler" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors">
            Detaylı rapor <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="p-4 lg:p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 rounded-lg bg-slate-50 dark:bg-gray-700/40 px-3 py-2">
            <strong class="text-gray-700 dark:text-gray-300">Erken ödeme:</strong> tek bir taksit vadesinden önce tahsil edilmiş.
            <strong class="text-gray-700 dark:text-gray-300 ml-1">Peşin sözleşme:</strong> tüm taksitleri ödenmiş ve en az biri erken tahsil edilmiş — ayrıntılar raporda.
        </p>
        <?php if (empty($earlyPaymentsList)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center rounded-xl bg-slate-50 dark:bg-gray-700/30">Henüz erken tahsilat kaydı yok</p>
        <?php else: ?>
            <ul class="space-y-2 max-h-64 overflow-y-auto pr-1">
                <?php foreach ($earlyPaymentsList as $p):
                    $name = trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? ''));
                    $daysEarly = (int) ($p['days_early'] ?? paymentDaysEarly($p));
                ?>
                <li class="flex items-start justify-between gap-2 py-2.5 px-3 rounded-xl bg-blue-50/60 dark:bg-blue-900/10 border border-blue-100/80 dark:border-blue-900/30">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($name ?: '-') ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Tahsilat <?= fmtDateTime($p['paid_at'] ?? null) ?>
                            · Vade <?= !empty($p['due_date']) ? date('d.m.Y', strtotime($p['due_date'])) : '-' ?>
                            <?php if (!empty($p['contract_number'])): ?>
                                · <?= htmlspecialchars($p['contract_number']) ?>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mt-0.5"><?= $daysEarly ?> gün erken</p>
                    </div>
                    <p class="text-sm font-bold text-blue-700 dark:text-blue-300 tabular-nums shrink-0"><?= fmtMoney($p['amount'] ?? 0) ?> ₺</p>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<a href="/odemeler" class="block mb-8 group">
    <div class="stat-card min-h-[120px] hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 border border-slate-200/80 dark:border-gray-600/80 overflow-hidden">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <i class="bi bi-cash-stack text-lg"></i>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Borç özeti</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= (int) $pendingPayments ?> bekleyen · <?= (int) $overduePayments ?> gecikmiş</p>
                </div>
            </div>
            <i class="bi bi-chevron-right text-gray-300 dark:text-gray-500 group-hover:text-emerald-500 transition-colors"></i>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="flex flex-col py-3 px-4 rounded-xl bg-red-50/80 dark:bg-red-900/20 border border-red-100 dark:border-red-800/40">
                <span class="text-[10px] font-bold text-red-600 dark:text-red-400 uppercase tracking-wider mb-0.5">Vadesi geçmiş</span>
                <span class="text-lg font-bold text-red-700 dark:text-red-300 tabular-nums"><?= fmtMoney($debtOverdue ?? 0) ?> ₺</span>
            </div>
            <div class="flex flex-col py-3 px-4 rounded-xl bg-amber-50/80 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40">
                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-0.5">Bu ay</span>
                <span class="text-lg font-bold text-amber-700 dark:text-amber-300 tabular-nums"><?= fmtMoney($debtDueThisMonth ?? 0) ?> ₺</span>
            </div>
            <div class="flex flex-col py-3 px-4 rounded-xl bg-slate-50 dark:bg-gray-700/50 border border-slate-100 dark:border-gray-600">
                <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-0.5">Toplam borç</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white tabular-nums"><?= fmtMoney($totalDebt ?? 0) ?> ₺</span>
            </div>
        </div>
    </div>
</a>

<?php
$upcomingPayments = $upcomingPayments ?? [];
$expiringContracts = $expiringContracts ?? [];
$customersWithUnpaid = $customersWithUnpaid ?? [];
?>
<?php if (!empty($customersWithUnpaid)): ?>
<div class="rounded-2xl border border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-sm overflow-hidden mb-8">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-800/80">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500/10 text-red-600 dark:text-red-400">
                <i class="bi bi-exclamation-circle text-lg"></i>
            </span>
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Vadesi gelmiş / ödemesi alınmayan müşteriler</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Bu ay veya önceki aylardan tahsil edilmemiş ödemeler. Ödeme almak için satırdaki butonu kullanın.</p>
            </div>
        </div>
    </div>
    <div class="p-4">
        <ul class="space-y-2 max-h-64 overflow-y-auto pr-1">
            <?php foreach ($customersWithUnpaid as $row):
                $name = trim(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? ''));
                $debt = (float)($row['total_debt'] ?? 0);
                $count = (int)($row['payment_count'] ?? 0);
            ?>
                <li class="flex items-center justify-between gap-4 py-3 px-4 rounded-xl bg-slate-50/80 dark:bg-gray-700/40 hover:bg-slate-100/80 dark:hover:bg-gray-700/60 border border-transparent hover:border-slate-200 dark:hover:border-gray-600 transition-colors">
                    <div class="min-w-0 flex-1">
                        <span class="font-semibold text-gray-900 dark:text-white block truncate"><?= htmlspecialchars($name ?: '-') ?></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400"><?= $count ?> ödeme</span>
                    </div>
                    <span class="text-base font-bold text-red-600 dark:text-red-400 tabular-nums shrink-0"><?= fmtMoney($debt) ?> ₺</span>
                    <a href="/odemeler?collect=1&customer=<?= htmlspecialchars($row['customer_id'] ?? '') ?>" class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 shadow-sm hover:shadow-md transition-all">
                        <i class="bi bi-bank text-sm"></i> Ödeme Al
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="/odemeler?status=unpaid" class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
            Tüm ödemeler <i class="bi bi-arrow-right text-xs"></i>
        </a>
    </div>
</div>
<?php endif; ?>

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
