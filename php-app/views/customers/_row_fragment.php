<?php
$contracts = $contracts ?? [];
$payments = $payments ?? [];
$debt = $debt ?? 0;
?>
<div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <h4 class="font-bold text-gray-900 dark:text-white mb-2">Depo / Sözleşmeler</h4>
            <?php if (empty($contracts)): ?>
                <p class="text-gray-500 dark:text-gray-400">Sözleşme yok.</p>
            <?php else: ?>
                <ul class="space-y-1">
                    <?php foreach ($contracts as $c): ?>
                        <li>
                            <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($c['contract_number'] ?? '') ?></a>
                            – <?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?>
                            (<?= number_format((float)($c['monthly_price'] ?? 0), 2, ',', '.') ?> ₺/ay)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div>
            <h4 class="font-bold text-gray-900 dark:text-white mb-2">Ödemeler / Borç</h4>
            <p class="text-amber-700 dark:text-amber-400 font-semibold">Toplam borç: <?= number_format($debt, 2, ',', '.') ?> ₺</p>
            <?php if (empty($payments)): ?>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Ödeme kaydı yok.</p>
            <?php else: ?>
                <ul class="mt-2 space-y-0.5 max-h-32 overflow-y-auto">
                    <?php foreach (array_slice($payments, 0, 10) as $p): ?>
                        <li class="flex justify-between gap-2">
                            <span><?= date('m.Y', strtotime($p['due_date'] ?? '')) ?></span>
                            <span><?= number_format((float)($p['amount'] ?? 0), 2, ',', '.') ?> ₺</span>
                            <span class="px-1.5 py-0.5 rounded text-xs <?= ($p['status'] ?? '') === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : (($p['status'] ?? '') === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300') ?>"><?= ($p['status'] ?? '') === 'paid' ? 'Ödendi' : (($p['status'] ?? '') === 'overdue' ? 'Gecikmiş' : 'Bekliyor') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($payments) > 10): ?><p class="text-gray-500 text-xs mt-1">+<?= count($payments) - 10 ?> kayıt daha</p><?php endif; ?>
            <?php endif; ?>
            <?php if ($debt > 0): ?>
                <a href="/odemeler?collect=1&customer=<?= htmlspecialchars($customer['id']) ?>" class="inline-block mt-2 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">Ödeme Al</a>
            <?php endif; ?>
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/barkod" target="_blank" class="inline-block mt-2 ml-2 px-3 py-1.5 rounded-lg bg-gray-600 text-white text-xs font-medium hover:bg-gray-700">Barkod Yazdır</a>
        </div>
    </div>
</div>
