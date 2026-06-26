<?php
$currentPage = 'raporlar';
$rows = $rows ?? [];
$prepaidContracts = $prepaidContracts ?? [];
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$totalCount = $totalCount ?? 0;
$totalSum = $totalSum ?? 0;
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
?>
<div class="mb-6">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/raporlar" class="text-emerald-600 dark:text-emerald-400 hover:underline">Raporlar</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Erken ve Peşin Ödemeler</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Erken ve Peşin Ödemeler</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Vadesi gelmeden tahsil edilen ödemeler ve tüm taksitlerini peşin ödeyen müşteriler</p>
</div>

<div class="mb-6 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 text-sm text-blue-900 dark:text-blue-200">
    <strong>Erken ödeme:</strong> Tahsilat tarihi (<code class="px-1 rounded bg-blue-100 dark:bg-blue-800">paid_at</code>) vade tarihinden (<code class="px-1 rounded bg-blue-100 dark:bg-blue-800">due_date</code>) önce olan ödemeler.
    <strong class="ml-1">Peşin sözleşme:</strong> Aktif sözleşmenin tüm taksitleri ödenmiş ve en az bir taksit vadesinden önce tahsil edilmiş.
</div>

<form method="get" action="/raporlar/erken-odemeler" class="page-toolbar mb-6 p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahsilat başlangıç</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahsilat bitiş</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Göster</button>
    <a href="/odemeler?status=early" class="btn-touch px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm">Ödemeler listesinde filtrele</a>
</form>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Seçilen dönemde erken ödeme</p>
        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300"><?= (int) $totalCount ?> adet</p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= fmtMoney($totalSum) ?> ₺</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Peşin ödemiş aktif sözleşme</p>
        <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-300"><?= count($prepaidContracts) ?></p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Tüm taksitleri tahsil edilmiş</p>
    </div>
</div>

<?php if (!empty($prepaidContracts)): ?>
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden mb-8">
    <div class="px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-100 dark:border-indigo-800">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-lightning-charge text-indigo-600 dark:text-indigo-400"></i>
            Peşin ödeyen müşteriler (aktif sözleşmeler)
        </h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">6 aylık kiralama gibi tüm vadeleri önceden kapatmış sözleşmeler</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Müşteri</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sözleşme</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Depo / Oda</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Taksit</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Toplam</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İlk tahsilat</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Son vade</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Erken taksit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                <?php foreach ($prepaidContracts as $c):
                    $name = trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''));
                ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3">
                        <a href="/musteriler/<?= htmlspecialchars($c['customer_id'] ?? '') ?>" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($name) ?></a>
                    </td>
                    <td class="px-4 py-3">
                        <a href="/girisler/<?= htmlspecialchars($c['contract_id'] ?? '') ?>" class="text-gray-700 dark:text-gray-300 hover:text-emerald-600"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></a>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars(($c['warehouse_name'] ?? '') . ' / ' . ($c['room_number'] ?? '')) ?></td>
                    <td class="px-4 py-3"><?= (int) ($c['payment_count'] ?? 0) ?></td>
                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white"><?= fmtMoney($c['total_paid'] ?? 0) ?> ₺</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= fmtDateTime($c['first_paid_at'] ?? null) ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= !empty($c['last_due_date']) ? date('d.m.Y', strtotime($c['last_due_date'])) : '-' ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                            <?= (int) ($c['early_payment_count'] ?? 0) ?> taksit
                            <?php if (!empty($c['max_days_early'])): ?> · max <?= (int) $c['max_days_early'] ?> gün erken<?php endif; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center flex-wrap gap-2">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Erken tahsil edilen ödemeler</h2>
        <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($rows) ?> kayıt gösteriliyor</span>
    </div>
    <?php if (empty($rows)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Seçilen tarih aralığında erken ödeme kaydı yok.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ödeme No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sözleşme</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tahsilat</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Vade</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Erken</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($rows as $r):
                        $name = trim(($r['customer_first_name'] ?? '') . ' ' . ($r['customer_last_name'] ?? ''));
                        $daysEarly = (int) ($r['days_early'] ?? paymentDaysEarly($r));
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <a href="/odemeler/<?= htmlspecialchars($r['id'] ?? '') ?>" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($r['payment_number'] ?? '-') ?></a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="/musteriler/<?= htmlspecialchars($r['customer_id'] ?? '') ?>" class="text-gray-700 dark:text-gray-300 hover:text-emerald-600"><?= htmlspecialchars($name) ?></a>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($r['contract_number'] ?? '-') ?></td>
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white"><?= fmtMoney($r['amount'] ?? 0) ?> ₺</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= fmtDateTime($r['paid_at'] ?? null) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= !empty($r['due_date']) ? date('d.m.Y', strtotime($r['due_date'])) : '-' ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"><?= $daysEarly ?> gün erken</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
