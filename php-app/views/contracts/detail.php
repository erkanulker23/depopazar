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
        <?php
        $printUrl = (isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST']) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] : '') . '/girisler/' . ($contract['id'] ?? '') . '/yazdir';
        $waPhone = preg_replace('/[^0-9]/', '', $contract['customer_phone'] ?? '');
        if (substr($waPhone, 0, 1) === '0') $waPhone = '90' . substr($waPhone, 1);
        elseif (strlen($waPhone) === 10) $waPhone = '90' . $waPhone;
        $waText = rawurlencode('Sözleşme PDF: ' . $printUrl);
        if ($waPhone): ?>
        <a href="https://wa.me/<?= htmlspecialchars($waPhone) ?>?text=<?= $waText ?>" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-xl bg-green-600 text-white font-medium hover:bg-green-700" title="WhatsApp ile PDF gönder">
            <i class="bi bi-whatsapp mr-2"></i> WhatsApp Gönder
        </a>
        <?php else: ?>
        <a href="https://wa.me/?text=<?= $waText ?>" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-xl bg-green-600 text-white font-medium hover:bg-green-700" title="WhatsApp ile PDF paylaş (numara seçin)">
            <i class="bi bi-whatsapp mr-2"></i> WhatsApp Gönder
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm no-print"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm no-print"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

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
        <?php if (!empty($contract['stored_items_condition'])): ?>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Ürün Durumu</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars(storedItemsConditionLabel($contract['stored_items_condition'] ?? null)) ?><?php if (($contract['stored_items_condition'] ?? '') === 'hasarli' && !empty($contract['stored_items_condition_note'])): ?><br><span class="text-xs text-gray-600 mt-1 block">Hasar notu: <?= nl2br(htmlspecialchars($contract['stored_items_condition_note'])) ?></span><?php endif; ?></td></tr>
        <?php endif; ?>
    </table>
    <?php $items = $items ?? []; ?>
    <?php if (!empty($items)): ?>
    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Depo Eşya Listesi</h2>
    <table class="min-w-full border border-gray-300 text-sm mb-6">
        <thead class="bg-gray-100"><tr><th class="border border-gray-300 px-3 py-2 text-left font-bold">#</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Eşya Adı</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Adet</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Birim</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Açıklama</th></tr></thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr><td class="border border-gray-300 px-3 py-2"><?= $i + 1 ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['name'] ?? '') ?></td><td class="border border-gray-300 px-3 py-2"><?= (int) ($item['quantity'] ?? 1) ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['unit'] ?? 'adet') ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['description'] ?? '-') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Ödeme Takvimi</h2>
    <table class="min-w-full border border-gray-300 text-sm">
        <thead class="bg-gray-100"><tr><th class="border border-gray-300 px-3 py-2 text-left font-bold">Vade</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Tutar</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Durum</th></tr></thead>
        <tbody>
            <?php foreach ($payments as $p): $ps = paymentStatusDisplay($p); ?>
            <tr><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td><td class="border border-gray-300 px-3 py-2"><?= fmtPrice($p['amount'] ?? 0) ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($ps['label']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= fmtDateTime($contract['created_at'] ?? null) ?></p>
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
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Kayıt Tarihi</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white"><?= fmtDateTime($contract['created_at'] ?? null) ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Başlangıç – Bitiş</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white"><?= fmtDate($contract['start_date'] ?? null) ?> – <?= fmtDate($contract['end_date'] ?? null) ?></dd>
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
                <?php if (!empty($contract['stored_items_condition'])): ?>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Giriş Yapılan Ürün Durumu</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">
                        <?= htmlspecialchars(storedItemsConditionLabel($contract['stored_items_condition'] ?? null)) ?>
                        <?php if (($contract['stored_items_condition'] ?? '') === 'hasarli' && !empty($contract['stored_items_condition_note'])): ?>
                            <p class="mt-2 text-sm text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 border border-amber-200 dark:border-amber-800">
                                <span class="font-medium">Hasar notu:</span> <?= nl2br(htmlspecialchars($contract['stored_items_condition_note'])) ?>
                            </p>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-box-seam text-emerald-600"></i> Depo Eşya Listesi
                </h2>
                <button type="button" onclick="openContractItemsModal()" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm flex-shrink-0" title="Eşya listesi ekle / düzenle">
                    <i class="bi bi-plus-lg text-lg"></i>
                </button>
            </div>
            <?php $items = $items ?? []; ?>
            <?php if (empty($items)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">Henüz eşya listesi girilmemiş. Sağ üstteki <strong class="font-medium text-gray-700 dark:text-gray-300">+</strong> butonuna basarak ekleyebilirsiniz.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Eşya Adı</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Adet</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Birim</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300"><?= $i + 1 ?></td>
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['name'] ?? '') ?></td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white"><?= (int) ($item['quantity'] ?? 1) ?></td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($item['unit'] ?? 'adet') ?></td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($item['description'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
                                        <?php $ps = paymentStatusDisplay($p); ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $ps['badge'] ?>"><?= htmlspecialchars($ps['label']) ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDateTime($p['paid_at'] ?? null) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if (paymentIsCollectible($p)): ?>
                                            <button type="button" onclick="openCollectModal(<?= htmlspecialchars(json_encode([['id' => $p['id'], 'payment_number' => $p['payment_number'] ?? '', 'amount' => $p['amount'] ?? 0, 'due_date' => $p['due_date'] ?? '']])) ?>)" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 text-sm font-medium">Ödeme al</button>
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

<!-- Eşya Listesi Modal -->
<div id="contractItemsModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto no-print" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeContractItemsModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-box-seam text-emerald-600"></i> Depo Eşya Listesi
                </h3>
                <button type="button" onclick="closeContractItemsModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/girisler/esya-listesi-guncelle">
                <input type="hidden" name="contract_id" value="<?= htmlspecialchars($contract['id'] ?? '') ?>">
                <?php
                $storedItemsFormCompact = true;
                require __DIR__ . '/_stored_items_form.php';
                unset($storedItemsFormCompact);
                ?>
                <div class="mt-6 flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-600">
                    <button type="button" onclick="closeContractItemsModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openContractItemsModal() {
    var modal = document.getElementById('contractItemsModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (window.renumberContractItems) window.renumberContractItems();
    }
}
function closeContractItemsModal() {
    var modal = document.getElementById('contractItemsModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
}
document.getElementById('contractItemsModal').addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeContractItemsModal();
});
<?php if (!empty($_GET['esyaListesi'])): ?>
document.addEventListener('DOMContentLoaded', function() { openContractItemsModal(); });
<?php endif; ?>
</script>

<!-- Ödeme Al Modal (aynı sayfada) -->
<?php if (!empty($collectPayments)): ?>
<div id="collectModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeCollectModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ödeme Al</h3>
                <button type="button" onclick="closeCollectModal()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="collectError" class="hidden mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 text-sm"></div>
            <div id="collectStepMethod">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Ödeme banka havalesi / EFT ile alınır.</p>
                <button type="button" onclick="setCollectMethod('bank_transfer')" class="w-full p-4 border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center gap-3 text-left mb-4">
                    <i class="bi bi-bank text-2xl text-blue-600"></i>
                    <div><p class="font-semibold text-gray-900 dark:text-white">Havale / EFT</p><p class="text-xs text-gray-500">Banka havalesi ile ödeme al</p></div>
                </button>
            </div>
            <div id="collectStepBank" class="hidden">
                <form method="post" action="/odemeler/odeme-al">
                    <input type="hidden" name="payment_method" value="bank_transfer">
                    <input type="hidden" name="redirect" value="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>">
                    <div id="collectFormIds"></div>
                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Banka Hesabı <span class="text-red-500">*</span></label>
                            <?php if (empty($bankAccounts)): ?>
                                <p class="text-sm text-amber-700 bg-amber-50 p-3 rounded-xl">Aktif banka hesabı yok. Ayarlar → Banka Hesaplarından ekleyin.</p>
                            <?php else: ?>
                                <select name="bank_account_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                                    <option value="">Seçin</option>
                                    <?php foreach ($bankAccounts as $ba): ?>
                                        <option value="<?= htmlspecialchars($ba['id']) ?>"><?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_number']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahsilat Tarihi</label>
                            <input type="datetime-local" name="paid_at" value="<?= fmtDateTimeLocalInput() ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                        </div>
                        <input type="text" name="transaction_id" placeholder="İşlem no (opsiyonel)" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                        <textarea name="notes" rows="2" placeholder="Not (opsiyonel)" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="backCollectMethod()" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700">← Geri</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white">Ödemeyi Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
var collectPayments = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'payment_number' => $p['payment_number'] ?? '', 'amount' => $p['amount'] ?? 0, 'due_date' => $p['due_date'] ?? ''], $collectPayments)) ?>;
function openCollectModal(payments) {
    collectPayments = Array.isArray(payments) && payments.length ? payments : collectPayments;
    document.getElementById('collectModal').classList.remove('hidden');
    setCollectMethod('bank_transfer');
}
function closeCollectModal() { document.getElementById('collectModal').classList.add('hidden'); }
function setCollectMethod(method) {
    var ids = collectPayments.map(function(p) { return p.id; });
    document.getElementById('collectStepMethod').classList.add('hidden');
    document.getElementById('collectStepBank').classList.remove('hidden');
    var c = document.getElementById('collectFormIds'); c.innerHTML = '';
    ids.forEach(function(id) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'payment_ids[]'; i.value = id; c.appendChild(i); });
}
function backCollectMethod() {
    document.getElementById('collectStepMethod').classList.remove('hidden');
    document.getElementById('collectStepBank').classList.add('hidden');
}
document.getElementById('collectModal').addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCollectModal(); });
<?php if (!empty($_GET['collectPay'])): ?>document.addEventListener('DOMContentLoaded', function() { openCollectModal(); });<?php endif; ?>
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
