<?php
$currentPage = 'genel-bakis';
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
?>
<div class="mb-6">
    <h1 class="page-title gradient-title">Dashboard</h1>
    <p class="page-subtitle uppercase tracking-widest font-bold">Sistem özeti</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card min-h-[100px]">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Toplam Depo</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $warehousesCount ?></p>
            </div>
            <div class="p-2.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex-shrink-0">
                <i class="bi bi-building text-emerald-600 dark:text-emerald-400 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="stat-card min-h-[100px]">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Toplam Oda</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $roomsCount ?></p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 font-bold"><?= (int) $occupiedRooms ?> Dolu / <?= (int) $emptyRooms ?> Boş</p>
            </div>
            <div class="p-2.5 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex-shrink-0">
                <i class="bi bi-grid-3x3 text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="stat-card min-h-[100px]">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Toplam Müşteri</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $customersCount ?></p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 font-bold"><?= (int) $activeContracts ?> Aktif Sözleşme</p>
            </div>
            <div class="p-2.5 bg-purple-50 dark:bg-purple-900/30 rounded-xl flex-shrink-0">
                <i class="bi bi-people text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="stat-card min-h-[100px]">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Bu Ay Gelir</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= fmtMoney($monthlyRevenue) ?> ₺</p>
            </div>
            <div class="p-2.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex-shrink-0">
                <i class="bi bi-bank text-emerald-600 dark:text-emerald-400 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <a href="/odemeler" class="block stat-card border-l-4 border-yellow-500 min-h-[100px] hover:scale-[1.01] active:scale-[0.99] transition-transform">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Bekleyen Ödeme</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $pendingPayments ?></p>
            </div>
            <div class="p-2.5 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl flex-shrink-0">
                <i class="bi bi-credit-card text-yellow-600 dark:text-yellow-400 text-xl"></i>
            </div>
        </div>
    </a>
    <a href="/odemeler" class="block stat-card border-l-4 border-red-500 min-h-[100px] hover:scale-[1.01] active:scale-[0.99] transition-transform">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Geciken Ödeme</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= (int) $overduePayments ?></p>
            </div>
            <div class="p-2.5 bg-red-50 dark:bg-red-900/30 rounded-xl flex-shrink-0">
                <i class="bi bi-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
            </div>
        </div>
    </a>
    <a href="/odemeler" class="block stat-card border-l-4 border-gray-900 dark:border-gray-500 min-h-[100px] hover:scale-[1.01] active:scale-[0.99] transition-transform">
        <div class="flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Toplam Borç</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= fmtMoney($totalDebt) ?> ₺</p>
            </div>
            <div class="p-2.5 bg-gray-100 dark:bg-gray-700 rounded-xl flex-shrink-0">
                <i class="bi bi-bar-chart text-gray-600 dark:text-gray-400 text-xl"></i>
            </div>
        </div>
    </a>
</div>

<?php $upcomingPayments = $upcomingPayments ?? []; $expiringContracts = $expiringContracts ?? []; ?>
<?php if (!empty($upcomingPayments) || !empty($expiringContracts)): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
    <?php if (!empty($upcomingPayments)): ?>
    <div class="card-modern p-5">
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
    <div class="card-modern p-5">
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
