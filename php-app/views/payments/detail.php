<?php
$currentPage = 'odemeler';
$statusLabels = $statusLabels ?? [];
$customerName = trim(($payment['customer_first_name'] ?? '') . ' ' . ($payment['customer_last_name'] ?? ''));
$status = $payment['status'] ?? 'pending';
$statusClass = ['pending' => 'bg-amber-100 text-amber-800', 'paid' => 'bg-green-100 text-green-800', 'overdue' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-gray-100 text-gray-800'][$status] ?? 'bg-gray-100 text-gray-800';
$statusLabel = $statusLabels[$status] ?? $status;
$company = $company ?? null;
ob_start();
?>
<style>@media print { .no-print { display: none !important; } }</style>
<div class="mb-6 no-print">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="/odemeler" class="text-emerald-600 hover:text-emerald-700 font-medium">Ödemeler</a>
        <i class="bi bi-chevron-right"></i>
        <span class="text-gray-700 font-medium"><?= htmlspecialchars($payment['payment_number'] ?? '') ?></span>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Ödeme Detayı</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($payment['payment_number'] ?? '') ?></p>
        </div>
        <button type="button" onclick="window.print()" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-printer mr-2"></i> Yazdır
        </button>
    </div>
</div>

<!-- Çıktı: barkod sayfası tasarımına uyumlu (yazdırma) -->
<div class="hidden print:block bg-white p-6 max-w-4xl mx-auto border-2 border-gray-200 rounded-xl mb-6 print:border-gray-400">
    <h1 class="text-xl font-bold text-center text-gray-900 mb-6">Ödeme Makbuzu</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Firma</h2>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? '') ?></p>
        </div>
        <div>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri</h2>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName ?: '-') ?></p>
            <?php if (!empty($payment['customer_email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($payment['customer_email']) ?></p><?php endif; ?>
            <?php if (!empty($payment['customer_phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($payment['customer_phone']) ?></p><?php endif; ?>
        </div>
    </div>
    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Ödeme Bilgileri</h2>
    <table class="min-w-full border border-gray-300 text-sm">
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 w-40">Ödeme No</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($payment['payment_number'] ?? '') ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Tutar</td><td class="border border-gray-300 px-3 py-2 font-bold"><?= fmtPrice($payment['amount'] ?? 0) ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Vade</td><td class="border border-gray-300 px-3 py-2"><?= !empty($payment['due_date']) ? date('d.m.Y', strtotime($payment['due_date'])) : '–' ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Ödenme</td><td class="border border-gray-300 px-3 py-2"><?= !empty($payment['paid_at']) ? date('d.m.Y', strtotime($payment['paid_at'])) : '–' ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Durum</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($statusLabel) ?></td></tr>
    </table>
    <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= date('d.m.Y H:i') ?></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 no-print">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-credit-card text-emerald-600"></i> Ödeme Bilgileri
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödeme No</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($payment['payment_number'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-white"><?= fmtPrice($payment['amount'] ?? 0) ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</dt>
                    <dd class="mt-1"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Vade Tarihi</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-300"><?= !empty($payment['due_date']) ? date('d.m.Y', strtotime($payment['due_date'])) : '–' ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödenme Tarihi</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-300"><?= !empty($payment['paid_at']) ? date('d.m.Y H:i', strtotime($payment['paid_at'])) : '–' ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödeme Yöntemi</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($payment['payment_method'] ?? '–') ?></dd>
                </div>
                <?php if (!empty($payment['transaction_id'])): ?>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem No</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($payment['transaction_id']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if (!empty($payment['notes'])): ?>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Not</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-400"><?= nl2br(htmlspecialchars($payment['notes'])) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-file-text text-emerald-600"></i> Sözleşme
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                <a href="/girisler/<?= htmlspecialchars($payment['contract_id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium"><?= htmlspecialchars($payment['contract_number'] ?? '-') ?></a>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Depo: <?= htmlspecialchars($payment['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($payment['room_number'] ?? '') ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-person text-emerald-600"></i> Müşteri
            </h2>
            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($customerName ?: '-') ?></p>
            <?php if (!empty($payment['customer_email'])): ?><p class="text-sm text-gray-600 dark:text-gray-300 mt-1"><?= htmlspecialchars($payment['customer_email']) ?></p><?php endif; ?>
            <?php if (!empty($payment['customer_phone'])): ?><p class="text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($payment['customer_phone']) ?></p><?php endif; ?>
            <a href="/musteriler/<?= htmlspecialchars($payment['customer_id'] ?? '') ?>" class="inline-block mt-3 text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium">Müşteri detayı →</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
