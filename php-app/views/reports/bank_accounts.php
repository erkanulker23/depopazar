<?php
$currentPage = 'raporlar';
$bankAccounts = $bankAccounts ?? [];
$rows = $rows ?? [];
$bankAccountId = $bankAccountId ?? '';
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
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

<form method="get" action="/raporlar/banka-hesaplari" class="mb-6 p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-4">
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
        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Göster</button>
</form>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <a href="/odemeler/<?= htmlspecialchars($r['id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium"><?= htmlspecialchars($r['payment_number'] ?? '-') ?></a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= $r['paid_at'] ? date('d.m.Y H:i', strtotime($r['paid_at'])) : '-' ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($r['bank_name'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($r['contract_number'] ?? '') ?> / <?= htmlspecialchars(trim(($r['customer_first_name'] ?? '') . ' ' . ($r['customer_last_name'] ?? ''))) ?></td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white"><?= fmtMoney($r['amount'] ?? 0) ?> ₺</td>
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
