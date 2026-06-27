<?php
$currentPage = 'raporlar';
$rows = $rows ?? [];
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$search = $search ?? null;
$status = $status ?? null;
$totalCount = $totalCount ?? 0;
$totalSum = $totalSum ?? 0;
$pendingCount = $pendingCount ?? 0;
$overdueCount = $overdueCount ?? 0;
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
?>
<div class="mb-6">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/raporlar" class="text-emerald-600 dark:text-emerald-400 hover:underline">Raporlar</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Vadesi Gelen Ödemeler</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Vadesi Gelen Ödemeler</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Seçilen dönemde vadesi gelen ve henüz tahsil edilmemiş ödemeler</p>
</div>

<div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 text-sm text-amber-900 dark:text-amber-200">
    <strong>Vade aralığı:</strong> Ödemelerin <code class="px-1 rounded bg-amber-100 dark:bg-amber-800">due_date</code> alanı seçilen tarih aralığına göre filtrelenir.
    <strong class="ml-1">Bekleyen:</strong> Vadesi gelmiş ama henüz gecikmemiş. <strong>Gecikmiş:</strong> Vadesi geçmiş ve tahsil edilmemiş.
</div>

<form method="get" action="/raporlar/vadesi-gelen" class="page-toolbar mb-6 p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yıl / Ay (hızlı)</label>
        <div class="flex flex-wrap gap-2">
            <select id="due_year" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= (int) date('Y', strtotime($startDate)) === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select id="due_month" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
                <?php $monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']; foreach ($monthNames as $i => $mn): ?>
                    <option value="<?= $i + 1 ?>" <?= (int) date('n', strtotime($startDate)) === $i + 1 && date('Y-m', strtotime($startDate)) === date('Y-m', strtotime($endDate)) ? 'selected' : '' ?>><?= $mn ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="applyDueMonthPreset()" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Ay uygula</button>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vade başlangıç</label>
        <input type="date" name="start_date" id="due_start_date" value="<?= htmlspecialchars($startDate) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vade bitiş</label>
        <input type="date" name="end_date" id="due_end_date" value="<?= htmlspecialchars($endDate) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
            <option value="">Tümü</option>
            <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
            <option value="overdue" <?= ($status ?? '') === 'overdue' ? 'selected' : '' ?>>Gecikmiş</option>
        </select>
    </div>
    <div class="flex-1 min-w-[200px]">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ara</label>
        <input type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Müşteri, sözleşme, ödeme no…" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <button type="submit" class="btn-touch btn-filter"><i class="bi bi-funnel-fill text-sm opacity-90" aria-hidden="true"></i> Göster</button>
    <a href="/raporlar/vadesi-gelen" class="btn-touch px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm">Temizle</a>
    <a href="/odemeler?status=pending" class="btn-touch px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm">Ödemeler listesinde filtrele</a>
</form>
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
?>
<div id="report-content">
<?php require __DIR__ . '/../partials/report_export_toolbar.php'; ?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Toplam kayıt</p>
        <p class="text-2xl font-bold text-amber-700 dark:text-amber-300"><?= (int) $totalCount ?> adet</p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= fmtMoney($totalSum) ?> ₺</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Bekleyen</p>
        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300"><?= (int) $pendingCount ?></p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Gecikmiş</p>
        <p class="text-2xl font-bold text-red-700 dark:text-red-300"><?= (int) $overdueCount ?></p>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
    <div class="px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800 flex justify-between items-center flex-wrap gap-2">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-calendar-event text-amber-600 dark:text-amber-400"></i>
            Vadesi gelen ödemeler
        </h2>
        <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($rows) ?> kayıt · <?= fmtMoney($totalSum) ?> ₺</span>
    </div>
    <?php if (empty($rows)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Seçilen tarih aralığında vadesi gelen ödenmemiş kayıt yok.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ödeme No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Telefon</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sözleşme</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Depo / Oda</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Vade</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($rows as $r):
                        $name = trim(($r['customer_first_name'] ?? '') . ' ' . ($r['customer_last_name'] ?? ''));
                        $dStatus = paymentStatusDisplay($r);
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <a href="/odemeler/<?= htmlspecialchars($r['id'] ?? '') ?>" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($r['payment_number'] ?? '-') ?></a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="/musteriler/<?= htmlspecialchars($r['customer_id'] ?? '') ?>" class="text-gray-700 dark:text-gray-300 hover:text-emerald-600"><?= htmlspecialchars($name) ?></a>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($r['customer_phone'] ?? '–') ?></td>
                        <td class="px-4 py-3">
                            <a href="/girisler/<?= htmlspecialchars($r['contract_id'] ?? '') ?>" class="text-gray-700 dark:text-gray-300 hover:text-emerald-600"><?= htmlspecialchars($r['contract_number'] ?? '-') ?></a>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars(($r['warehouse_name'] ?? '') . ' / ' . ($r['room_number'] ?? '')) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= !empty($r['due_date']) ? date('d.m.Y', strtotime($r['due_date'])) : '-' ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $dStatus['badge'] ?? 'bg-gray-100 text-gray-800' ?>"><?= htmlspecialchars($dStatus['label'] ?? '') ?></span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white"><?= fmtMoney($r['amount'] ?? 0) ?> ₺</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
