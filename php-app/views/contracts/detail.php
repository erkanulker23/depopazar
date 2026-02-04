<?php
$currentPage = 'girisler';
$customerName = trim(($contract['customer_first_name'] ?? '') . ' ' . ($contract['customer_last_name'] ?? ''));
$company = $company ?? null;
ob_start();
?>
<style>
@media print {
    .no-print { display: none !important; }
    .print-fatura { display: block !important; border: none; box-shadow: none; }
    body * { visibility: hidden; }
    .print-fatura, .print-fatura * { visibility: visible; }
    .print-fatura { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="/girisler" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium">Tüm Girişler</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-gray-700 dark:text-gray-300 font-medium"><?= htmlspecialchars($contract['contract_number'] ?? '') ?></span>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Sözleşme Detayı</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($contract['contract_number'] ?? '') ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>/duzenle" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-pencil mr-2"></i> Düzenle
        </a>
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>/yazdir" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-printer mr-2"></i> Yazdır
        </a>
        <?php if (!empty($contract['contract_pdf_url'])): ?>
            <a href="<?= htmlspecialchars($contract['contract_pdf_url']) ?>" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
                <i class="bi bi-file-pdf mr-2"></i> PDF İndir
            </a>
        <?php endif; ?>
        <a href="mailto:<?= htmlspecialchars($contract['customer_email'] ?? '') ?>?subject=Sözleşme%20<?= urlencode($contract['contract_number'] ?? '') ?>" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-envelope mr-2"></i> E-posta Gönder
        </a>
    </div>
</div>

<!-- Çıktı: barkod sayfası tasarımına uyumlu -->
<div class="print-fatura hidden bg-white p-6 max-w-4xl mx-auto border-2 border-gray-200 rounded-xl mb-8 print:border-gray-400">
    <h1 class="text-xl font-bold text-center text-gray-900 mb-6">Sözleşme Detayı</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Firma</h2>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? 'Firma Adı') ?></p>
            <?php if (!empty($company['address'])): ?><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
            <?php if (!empty($company['phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
            <?php if (!empty($company['email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($company['email']) ?></p><?php endif; ?>
            <?php if (!empty($company['tax_office'])): ?><p class="text-sm text-gray-600">Vergi Dairesi: <?= htmlspecialchars($company['tax_office']) ?></p><?php endif; ?>
        </div>
        <div>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri</h2>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName ?: '-') ?></p>
            <?php if (!empty($contract['customer_email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($contract['customer_email']) ?></p><?php endif; ?>
            <?php if (!empty($contract['customer_phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($contract['customer_phone']) ?></p><?php endif; ?>
            <?php if (!empty($contract['customer_address'])): ?><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($contract['customer_address'])) ?></p><?php endif; ?>
        </div>
    </div>
    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Sözleşme Bilgileri</h2>
    <table class="min-w-full border border-gray-300 text-sm mb-6">
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 w-48">Sözleşme No</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($contract['contract_number'] ?? '-') ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Depo / Oda</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($contract['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($contract['room_number'] ?? '') ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Başlangıç – Bitiş</td><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($contract['start_date'] ?? '')) ?> – <?= date('d.m.Y', strtotime($contract['end_date'] ?? '')) ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Aylık Ücret</td><td class="border border-gray-300 px-3 py-2"><?= fmtPrice($contract['monthly_price'] ?? 0) ?></td></tr>
    </table>
    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Ödeme Takvimi</h2>
    <table class="min-w-full border border-gray-300 text-sm">
        <thead class="bg-gray-100"><tr><th class="border border-gray-300 px-3 py-2 text-left font-bold">Vade</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Tutar</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Durum</th></tr></thead>
        <tbody>
            <?php foreach ($payments as $p): $s = $p['status'] ?? 'pending'; $l = $s === 'paid' ? 'Ödendi' : ($s === 'overdue' ? 'Gecikmiş' : 'Bekliyor'); ?>
            <tr><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td><td class="border border-gray-300 px-3 py-2"><?= fmtPrice($p['amount'] ?? 0) ?></td><td class="border border-gray-300 px-3 py-2"><?= $l ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= date('d.m.Y H:i') ?></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Özet kart -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-file-text text-emerald-600"></i> Sözleşme Bilgileri
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme No</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($contract['contract_number'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</dt>
                    <dd class="mt-1">
                        <a href="/musteriler/<?= htmlspecialchars($contract['customer_id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium"><?= htmlspecialchars($customerName ?: '-') ?></a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo / Oda</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white"><?= htmlspecialchars($contract['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($contract['room_number'] ?? '') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Başlangıç – Bitiş</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white"><?= date('d.m.Y', strtotime($contract['start_date'] ?? '')) ?> – <?= date('d.m.Y', strtotime($contract['end_date'] ?? '')) ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık Fiyat</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-white"><?= fmtPrice($contract['monthly_price'] ?? 0) ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşmeyi Yapan</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white"><?= htmlspecialchars(trim(($contract['sold_by_first_name'] ?? '') . ' ' . ($contract['sold_by_last_name'] ?? '')) ?: '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</dt>
                    <dd class="mt-1">
                        <?php if (!empty($contract['is_active'])): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200">Pasif</span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Ödemeler -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-credit-card text-emerald-600"></i> Ödeme Takvimi
            </h2>
            <?php if (empty($payments)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Bu sözleşmeye ait ödeme kaydı yok.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Vade</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödenme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= fmtPrice($p['amount'] ?? 0) ?></td>
                                    <td class="px-4 py-3">
                                        <?php
                                        $status = $p['status'] ?? 'pending';
                                        $badge = ['pending' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300', 'paid' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300', 'overdue' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300', 'cancelled' => 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200'][$status] ?? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
                                        $label = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal'][$status] ?? $status;
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badge ?>"><?= $label ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= !empty($p['paid_at']) ? date('d.m.Y', strtotime($p['paid_at'])) : '–' ?></td>
                                    <td class="px-4 py-3">
                                        <?php if (($p['status'] ?? '') === 'pending' || ($p['status'] ?? '') === 'overdue'): ?>
                                            <a href="/odemeler?payment=<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 text-sm font-medium">Ödeme al</a>
                                        <?php else: ?>
                                            <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm">Detay</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sağ sütun: Aylık fiyatlar -->
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-calendar-month text-emerald-600"></i> Aylık Fiyatlar
            </h2>
            <?php if (empty($monthlyPrices)): ?>
                <div class="p-4 text-sm text-gray-500 dark:text-gray-400">Kayıt yok.</div>
            <?php else: ?>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 max-h-64 overflow-y-auto">
                    <?php foreach ($monthlyPrices as $mp): ?>
                        <li class="px-4 py-3 flex justify-between items-center text-sm">
                            <span class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($mp['month'] ?? '') ?></span>
                            <span class="font-medium text-gray-900 dark:text-white"><?= fmtPrice($mp['price'] ?? 0) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
