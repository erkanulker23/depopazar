<?php
$contracts = $contracts ?? [];
$payments = $payments ?? [];
$debt = $debt ?? 0;
$statusStyles = [
    'paid' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
    'overdue' => 'bg-red-500/15 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800',
    'pending' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
];
$statusLabels = ['paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'pending' => 'Bekliyor'];
?>
<div class="row-fragment-modern bg-gradient-to-b from-slate-50/80 to-white dark:from-gray-800/80 dark:to-gray-800 border-t border-slate-200 dark:border-gray-700">
    <div class="px-4 py-4 md:px-6 md:py-5">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Depo / Sözleşmeler -->
            <div class="rounded-2xl bg-white dark:bg-gray-800/60 border border-slate-200/80 dark:border-gray-600/80 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-gray-700 flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <i class="bi bi-file-earmark-text text-sm"></i>
                    </span>
                    <h4 class="text-sm font-semibold text-slate-800 dark:text-white tracking-tight">Depo / Sözleşmeler</h4>
                </div>
                <div class="p-4">
                    <?php if (empty($contracts)): ?>
                        <p class="text-sm text-slate-500 dark:text-gray-400">Sözleşme yok.</p>
                    <?php else: ?>
                        <ul class="space-y-2">
                            <?php foreach ($contracts as $c): ?>
                                <li class="flex items-center gap-2 text-sm">
                                    <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:underline"><?= htmlspecialchars($c['contract_number'] ?? '') ?></a>
                                    <span class="text-slate-400 dark:text-gray-500">·</span>
                                    <span class="text-slate-600 dark:text-gray-300"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?></span>
                                    <span class="ml-auto shrink-0 font-medium text-slate-700 dark:text-gray-200"><?= number_format((float)($c['monthly_price'] ?? 0), 2, ',', '.') ?> ₺/ay</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ödemeler / Borç -->
            <div class="rounded-2xl bg-white dark:bg-gray-800/60 border border-slate-200/80 dark:border-gray-600/80 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <i class="bi bi-credit-card-2-front text-sm"></i>
                        </span>
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-white tracking-tight">Ödemeler / Borç</h4>
                    </div>
                    <?php if ($debt > 0): ?>
                        <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200/80 dark:border-amber-800/50 px-3 py-1.5">
                            <span class="text-xs font-medium text-amber-800 dark:text-amber-200 uppercase tracking-wider">Toplam borç</span>
                            <span class="block text-lg font-bold text-amber-700 dark:text-amber-300 tabular-nums"><?= number_format($debt, 2, ',', '.') ?> ₺</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <?php if (empty($payments)): ?>
                        <p class="text-sm text-slate-500 dark:text-gray-400">Ödeme kaydı yok.</p>
                    <?php else: ?>
                        <ul class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                            <?php foreach (array_slice($payments, 0, 10) as $p):
                                $status = $p['status'] ?? 'pending';
                                $style = $statusStyles[$status] ?? $statusStyles['pending'];
                                $label = $statusLabels[$status] ?? $status;
                            ?>
                                <li class="flex items-center justify-between gap-3 py-2 px-3 rounded-xl bg-slate-50/80 dark:bg-gray-700/40 hover:bg-slate-100/80 dark:hover:bg-gray-700/60 transition-colors">
                                    <span class="text-sm font-medium text-slate-700 dark:text-gray-200 tabular-nums"><?= date('m.Y', strtotime($p['due_date'] ?? '')) ?></span>
                                    <span class="text-sm font-semibold text-slate-800 dark:text-white tabular-nums"><?= number_format((float)($p['amount'] ?? 0), 2, ',', '.') ?> ₺</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold <?= $style ?>"><?= $label ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($payments) > 10): ?>
                            <p class="text-xs text-slate-500 dark:text-gray-400 mt-2">+<?= count($payments) - 10 ?> kayıt daha</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <?php if ($debt > 0): ?>
                            <a href="/odemeler?collect=1&customer=<?= htmlspecialchars($customer['id']) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors shadow-sm">
                                <i class="bi bi-bank"></i> Ödeme Al
                            </a>
                        <?php endif; ?>
                        <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/barkod" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 dark:bg-gray-600 text-slate-700 dark:text-gray-200 text-sm font-medium hover:bg-slate-200 dark:hover:bg-gray-500 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            <i class="bi bi-upc-scan"></i> Barkod Yazdır
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
