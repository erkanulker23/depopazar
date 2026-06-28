<?php
$currentPage = 'girisler';
$customerName = trim(($contract['customer_first_name'] ?? '') . ' ' . ($contract['customer_last_name'] ?? ''));
$company = $company ?? null;
$contractId = $contract['id'] ?? '';
$renderPaymentAmountCell = function (array $p) use ($contractId): void {
    $status = $p['status'] ?? 'pending';
    $editable = !in_array($status, ['paid', 'cancelled'], true);
    $monthKey = ContractBilling::periodKeyFromDueDate($p['due_date'] ?? null);
    $amount = (float) ($p['amount'] ?? 0);
    if ($editable): ?>
        <button type="button"
                class="payment-amount-editable inline-flex items-center gap-1 text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap rounded-lg px-2 py-1 -mx-2 border border-transparent hover:border-emerald-300 dark:hover:border-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                data-payment-id="<?= htmlspecialchars($p['id'] ?? '') ?>"
                data-contract-id="<?= htmlspecialchars($contractId) ?>"
                data-month-key="<?= htmlspecialchars($monthKey) ?>"
                data-amount="<?= htmlspecialchars((string) $amount) ?>"
                title="Tutarı değiştirmek için tıklayın">
            <span class="payment-amount-display"><?= fmtPrice($amount) ?></span>
            <i class="bi bi-pencil-square text-xs text-emerald-600 dark:text-emerald-400 opacity-70" aria-hidden="true"></i>
        </button>
    <?php else: ?>
        <span class="text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap"><?= fmtPrice($amount) ?></span>
    <?php endif;
};
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
<div class="page-header mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="/girisler" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium">Tüm Girişler</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-gray-700 dark:text-gray-300 font-medium"><?= htmlspecialchars($contract['contract_number'] ?? '') ?></span>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Sözleşme Detayı</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($contract['contract_number'] ?? '') ?></p>
    </div>
    <div class="page-header-actions flex flex-nowrap md:flex-wrap gap-2 overflow-x-auto">
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>/duzenle" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-pencil mr-2"></i> Düzenle
        </a>
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>/yazdir" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-printer mr-2"></i> Yazdır
        </a>
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>/pdf-indir" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
            <i class="bi bi-file-pdf mr-2"></i> PDF İndir
        </a>
        <a href="mailto:<?= htmlspecialchars($contract['customer_email'] ?? '') ?>?subject=Sözleşme%20<?= urlencode($contract['contract_number'] ?? '') ?>" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-envelope mr-2"></i> E-posta Gönder
        </a>
        <?php
        $pdfDownloadUrl = '/girisler/' . ($contract['id'] ?? '') . '/pdf-indir';
        $pdfFilename = ContractPdf::filename($contract);
        $waPhone = preg_replace('/[^0-9]/', '', $contract['customer_phone'] ?? '');
        if (substr($waPhone, 0, 1) === '0') {
            $waPhone = '90' . substr($waPhone, 1);
        } elseif (strlen($waPhone) === 10) {
            $waPhone = '90' . $waPhone;
        }
        $waMessage = 'Merhaba, ' . ($contract['contract_number'] ?? 'sözleşme') . ' numaralı sözleşme belgeniz ektedir.';
        $waBase = $waPhone !== '' ? ('https://wa.me/' . $waPhone) : 'https://wa.me/';
        $waUrl = $waBase . '?text=' . rawurlencode($waMessage);
        ?>
        <button type="button"
                id="contractWhatsAppBtn"
                class="inline-flex items-center px-4 py-2 rounded-xl bg-green-600 text-white font-medium hover:bg-green-700 disabled:opacity-60"
                title="PDF indir ve WhatsApp ile gönder"
                data-pdf-url="<?= htmlspecialchars($pdfDownloadUrl) ?>"
                data-wa-url="<?= htmlspecialchars($waUrl) ?>"
                data-filename="<?= htmlspecialchars($pdfFilename) ?>">
            <i class="bi bi-whatsapp mr-2"></i> WhatsApp Gönder
        </button>
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
<div class="print-fatura hidden bg-white p-6 max-w-4xl mx-auto border-2 border-gray-200 rounded-xl print:border-gray-400">
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
    <div class="table-scroll overflow-x-auto -mx-1 px-1 md:mx-0 md:px-0">
    <table class="min-w-full border border-gray-300 text-sm mb-6">
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 w-48">Sözleşme No</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($contract['contract_number'] ?? '-') ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Depo / Oda</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($contract['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($contract['room_number'] ?? '') ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Başlangıç – Bitiş</td><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($contract['start_date'] ?? '')) ?> – <?= date('d.m.Y', strtotime($contract['end_date'] ?? '')) ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Aylık Ücret</td><td class="border border-gray-300 px-3 py-2"><?= fmtPrice($contract['monthly_price'] ?? 0) ?></td></tr>
        <?php if (!empty($contract['stored_items_condition'])): ?>
        <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Ürün Durumu</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars(storedItemsConditionLabel($contract['stored_items_condition'] ?? null)) ?><?php if (($contract['stored_items_condition'] ?? '') === 'hasarli' && !empty($contract['stored_items_condition_note'])): ?><br><span class="text-xs text-gray-600 mt-1 block">Hasar notu: <?= nl2br(htmlspecialchars($contract['stored_items_condition_note'])) ?></span><?php endif; ?></td></tr>
        <?php endif; ?>
    </table>
    </div>
    <?php $items = $items ?? []; ?>
    <?php if (!empty($items)): ?>
    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Depo Eşya Listesi</h2>
    <div class="table-scroll overflow-x-auto -mx-1 px-1 md:mx-0 md:px-0">
    <table class="min-w-full border border-gray-300 text-sm mb-6">
        <thead class="bg-gray-100"><tr><th class="border border-gray-300 px-3 py-2 text-left font-bold">#</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Eşya Adı</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Durum</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Adet</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Birim</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Açıklama</th></tr></thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr><td class="border border-gray-300 px-3 py-2"><?= $i + 1 ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['name'] ?? '') ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars(itemConditionLabel($item['condition'] ?? null)) ?></td><td class="border border-gray-300 px-3 py-2"><?= (int) ($item['quantity'] ?? 1) ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['unit'] ?? 'adet') ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['description'] ?? '-') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Ödeme Takvimi</h2>
    <div class="table-scroll overflow-x-auto -mx-1 px-1 md:mx-0 md:px-0">
    <table class="min-w-full border border-gray-300 text-sm">
        <thead class="bg-gray-100"><tr><th class="border border-gray-300 px-3 py-2 text-left font-bold">Vade</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Tutar</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Durum</th></tr></thead>
        <tbody>
            <?php foreach ($payments as $p): $ps = paymentStatusDisplay($p); ?>
            <tr><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td><td class="border border-gray-300 px-3 py-2"><?= fmtPrice($p['amount'] ?? 0) ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($ps['label']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
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

        <?php
        $contractPdfHref = publicUploadHref($contract['contract_pdf_url'] ?? null);
        $hasUploadedContractPdf = $contractPdfHref !== null;
        $uploadMaxLabel = uploadMaxBytesLabel();
        ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1 flex items-center gap-2">
                <i class="bi bi-file-earmark-pdf text-emerald-600"></i> İmzalı Sözleşme Belgesi
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Şirket ile müşteri arasında imzalanan sözleşme PDF’ini yükleyin. Yüklü belge varsa PDF indir ve WhatsApp gönder işlemleri bu dosyayı kullanır.</p>
            <?php if ($hasUploadedContractPdf): ?>
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <a href="<?= htmlspecialchars($contractPdfHref) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50">
                        <i class="bi bi-eye"></i> Görüntüle
                    </a>
                    <a href="/girisler/<?= htmlspecialchars($contractId) ?>/pdf-indir" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <i class="bi bi-download"></i> İndir
                    </a>
                    <form method="post" action="/girisler/sozlesme-pdf-sil" class="inline" onsubmit="return confirm('Yüklenen sözleşme PDF silinsin mi? Sistem otomatik PDF kullanılır.');">
                        <input type="hidden" name="contract_id" value="<?= htmlspecialchars($contractId) ?>">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40">
                            <i class="bi bi-trash"></i> Kaldır
                        </button>
                    </form>
                </div>
                <p class="text-xs text-emerald-700 dark:text-emerald-400 mb-3"><i class="bi bi-check-circle mr-1"></i> İmzalı sözleşme yüklü.</p>
            <?php else: ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Henüz imzalı sözleşme yüklenmemiş. PDF İndir ile sistem belgesi alınabilir; imzalı nüshayı aşağıdan ekleyin.</p>
            <?php endif; ?>
            <form method="post" action="/girisler/sozlesme-pdf-yukle" enctype="multipart/form-data" class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                <input type="hidden" name="contract_id" value="<?= htmlspecialchars($contractId) ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= $hasUploadedContractPdf ? 'Sözleşmeyi değiştir' : 'Sözleşme PDF yükle' ?> <span class="text-red-500">*</span></label>
                    <input type="file" name="contract_pdf" required accept=".pdf,application/pdf" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PDF · En fazla <?= htmlspecialchars($uploadMaxLabel) ?></p>
                </div>
                <button type="submit" class="btn-touch inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                    <i class="bi bi-upload"></i> <?= $hasUploadedContractPdf ? 'PDF Değiştir' : 'PDF Yükle' ?>
                </button>
            </form>
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
                <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($items as $i => $item): ?>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['name'] ?? '') ?></p>
                            <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">#<?= $i + 1 ?></span>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                            <div><span class="text-gray-500 dark:text-gray-400">Durum:</span> <span class="text-gray-900 dark:text-white"><?= htmlspecialchars(itemConditionLabel($item['condition'] ?? null)) ?></span></div>
                            <div><span class="text-gray-500 dark:text-gray-400">Adet:</span> <span class="text-gray-900 dark:text-white"><?= (int) ($item['quantity'] ?? 1) ?> <?= htmlspecialchars($item['unit'] ?? 'adet') ?></span></div>
                        </div>
                        <?php if (!empty($item['description']) && ($item['description'] ?? '') !== '-'): ?>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Eşya Adı</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Durum</th>
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
                                <td class="px-4 py-2 text-gray-900 dark:text-white"><?= htmlspecialchars(itemConditionLabel($item['condition'] ?? null)) ?></td>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-credit-card text-emerald-600"></i> Ödeme Takvimi
            </h2>
            <p class="px-4 pt-3 pb-0 text-xs text-gray-500 dark:text-gray-400 no-print">Ödenmemiş tutarlara dokunarak ay bazında düzenleyebilirsiniz.</p>
            <?php if (empty($payments)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Bu sözleşmeye ait ödeme kaydı yok.</div>
            <?php else: ?>
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($payments as $p): ?>
                        <?php $ps = paymentStatusDisplay($p); ?>
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></p>
                                    <?php if (paymentIsPaid($p) && !empty($p['paid_at'])): ?>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ödendi: <?= fmtDateTime($p['paid_at']) ?></p>
                                    <?php endif; ?>
                                    <?php if (($p['status'] ?? '') === 'paid' && ($cn = paymentCollectorName($p)) !== ''): ?>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">İşleyen: <?= htmlspecialchars($cn) ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php $renderPaymentAmountCell($p); ?>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $ps['badge'] ?>"><?= htmlspecialchars($ps['label']) ?></span>
                                <?php if (paymentIsCollectible($p)): ?>
                                    <button type="button"
                                            class="contract-collect-pay-btn inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700"
                                            data-payments="<?= htmlspecialchars(json_encode([['id' => $p['id'], 'payment_number' => $p['payment_number'] ?? '', 'amount' => $p['amount'] ?? 0, 'due_date' => $p['due_date'] ?? '']]), ENT_QUOTES, 'UTF-8') ?>">Ödeme al</button>
                                <?php else: ?>
                                    <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600">Detay</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Vade</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödenme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşleyen</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td>
                                    <td class="px-4 py-3"><?php $renderPaymentAmountCell($p); ?></td>
                                    <td class="px-4 py-3">
                                        <?php $ps = paymentStatusDisplay($p); ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $ps['badge'] ?>"><?= htmlspecialchars($ps['label']) ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDateTime($p['paid_at'] ?? null) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <?php if (($p['status'] ?? '') === 'paid'): ?>
                                            <?php $collectorName = paymentCollectorName($p); ?>
                                            <?= $collectorName !== '' ? htmlspecialchars($collectorName) : '–' ?>
                                        <?php else: ?>
                                            –
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (paymentIsCollectible($p)): ?>
                                            <button type="button"
                                                    class="contract-collect-pay-btn text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 text-sm font-medium"
                                                    data-payments="<?= htmlspecialchars(json_encode([['id' => $p['id'], 'payment_number' => $p['payment_number'] ?? '', 'amount' => $p['amount'] ?? 0, 'due_date' => $p['due_date'] ?? '']]), ENT_QUOTES, 'UTF-8') ?>">Ödeme al</button>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-calendar-month text-emerald-600"></i> Aylık Fiyatlar
            </h2>
            <?php if (empty($monthlyPrices)): ?>
                <div class="p-4 text-sm text-gray-500 dark:text-gray-400">Kayıt yok.</div>
            <?php else: ?>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 max-h-64 overflow-y-auto">
                    <?php foreach ($monthlyPrices as $mp): ?>
                        <li class="px-4 py-3 flex justify-between items-center text-sm"<?= !empty($mp['month_key']) ? ' id="monthly-price-' . htmlspecialchars($mp['month_key']) . '"' : '' ?>>
                            <span class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($mp['month'] ?? '') ?></span>
                            <span class="font-medium text-gray-900 dark:text-white monthly-price-value"><?= fmtPrice($mp['price'] ?? 0) ?></span>
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
                <div class="form-submit-bar mt-6 flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-600">
                    <button type="button" onclick="closeContractItemsModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
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
            <div id="collectStepSelect">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Tahsil edilecek ayları seçin. Varsayılan olarak yalnızca ilk vade işaretlidir.</p>
                <div id="contractCollectPaymentList" class="max-h-52 overflow-y-auto space-y-2 mb-4"></div>
                <p id="contractCollectSelectedTotal" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4"></p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCollectModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="button" id="contractCollectProceedBtn" onclick="proceedContractCollect()" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Devam et</button>
                </div>
            </div>
            <div id="collectStepBank" class="hidden">
                <form method="post" action="/odemeler/odeme-al" id="contractCollectForm">
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
                        <button type="button" onclick="backContractCollectSelect()" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700">← Geri</button>
                        <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white">Ödemeyi Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
var allCollectPayments = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'payment_number' => $p['payment_number'] ?? '', 'amount' => $p['amount'] ?? 0, 'due_date' => $p['due_date'] ?? ''], $collectPayments)) ?>;
var contractCollectPayments = allCollectPayments.slice();

function formatCollectDueDate(due) {
    if (!due) return '-';
    var d = due.split(' ')[0].split('-');
    if (d.length !== 3) return due.split(' ')[0];
    return d[2] + '.' + d[1] + '.' + d[0];
}

function renderContractCollectList() {
    var list = document.getElementById('contractCollectPaymentList');
    if (!list) return;
    list.innerHTML = '';
    contractCollectPayments.forEach(function(p) {
        var label = document.createElement('label');
        label.className = 'flex items-center justify-between gap-2 p-3 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer';
        label.innerHTML =
            '<span class="flex items-center gap-2 min-w-0">' +
                '<input type="checkbox" class="contract-collect-cb rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 shrink-0" value="' + (p.id || '') + '">' +
                '<span class="text-sm text-gray-900 dark:text-white">Vade: ' + formatCollectDueDate(p.due_date) + '</span>' +
            '</span>' +
            '<span class="font-semibold text-gray-900 dark:text-white shrink-0">' + parseFloat(p.amount || 0).toFixed(2).replace('.', ',') + ' ₺</span>';
        var cb = label.querySelector('.contract-collect-cb');
        if (cb) cb.addEventListener('change', updateContractCollectUi);
        list.appendChild(label);
    });
    updateContractCollectUi();
}

function getCheckedContractCollectIds() {
    return Array.from(document.querySelectorAll('.contract-collect-cb:checked')).map(function(cb) { return cb.value; });
}

function updateContractCollectUi() {
    var ids = getCheckedContractCollectIds();
    var selected = contractCollectPayments.filter(function(p) { return ids.indexOf(String(p.id)) >= 0; });
    var total = selected.reduce(function(s, p) { return s + parseFloat(p.amount || 0); }, 0);
    var totalEl = document.getElementById('contractCollectSelectedTotal');
    var btn = document.getElementById('contractCollectProceedBtn');
    if (totalEl) {
        totalEl.textContent = selected.length > 0
            ? selected.length + ' taksit · ' + total.toFixed(2).replace('.', ',') + ' ₺'
            : 'En az bir taksit seçin';
    }
    if (btn) btn.disabled = selected.length === 0;
}

function syncContractCollectFormIds() {
    var container = document.getElementById('collectFormIds');
    if (!container) return;
    container.innerHTML = '';
    getCheckedContractCollectIds().forEach(function(id) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'payment_ids[]';
        inp.value = id;
        container.appendChild(inp);
    });
}

function openCollectModal(payments) {
    contractCollectPayments = Array.isArray(payments) && payments.length ? payments.slice() : allCollectPayments.slice();
    contractCollectPayments.sort(function(a, b) {
        var da = (a.due_date || '').split(' ')[0];
        var db = (b.due_date || '').split(' ')[0];
        return da.localeCompare(db);
    });
    renderContractCollectList();
    var preselect = Array.isArray(payments) && payments.length ? payments.map(function(p) { return String(p.id); }) : (contractCollectPayments[0] ? [String(contractCollectPayments[0].id)] : []);
    var preSet = {};
    preselect.forEach(function(id) { preSet[id] = true; });
    document.querySelectorAll('.contract-collect-cb').forEach(function(cb) {
        cb.checked = !!preSet[String(cb.value)];
    });
    updateContractCollectUi();
    document.getElementById('collectStepSelect').classList.remove('hidden');
    document.getElementById('collectStepBank').classList.add('hidden');
    document.getElementById('collectModal').classList.remove('hidden');
}
function closeCollectModal() { document.getElementById('collectModal').classList.add('hidden'); }
function proceedContractCollect() {
    if (getCheckedContractCollectIds().length === 0) {
        alert('En az bir taksit seçin.');
        return;
    }
    syncContractCollectFormIds();
    document.getElementById('collectStepSelect').classList.add('hidden');
    document.getElementById('collectStepBank').classList.remove('hidden');
}
function backContractCollectSelect() {
    document.getElementById('collectStepSelect').classList.remove('hidden');
    document.getElementById('collectStepBank').classList.add('hidden');
}
document.getElementById('collectModal').addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCollectModal(); });
document.getElementById('contractCollectForm')?.addEventListener('submit', function(e) {
    var ids = getCheckedContractCollectIds();
    if (ids.length === 0) {
        e.preventDefault();
        alert('En az bir taksit seçin.');
        return;
    }
    var form = e.target;
    form.querySelectorAll('input[name="confirm_multi_period"]').forEach(function(el) { el.remove(); });
    if (ids.length > 1) {
        if (!confirm(ids.length + ' taksit aynı anda tahsil edilecek. Devam edilsin mi?')) {
            e.preventDefault();
            return;
        }
        var confirmInput = document.createElement('input');
        confirmInput.type = 'hidden';
        confirmInput.name = 'confirm_multi_period';
        confirmInput.value = '1';
        form.appendChild(confirmInput);
    }
});
document.querySelectorAll('.contract-collect-pay-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var raw = btn.getAttribute('data-payments');
        var payments = null;
        if (raw) {
            try { payments = JSON.parse(raw); } catch (e) { payments = null; }
        }
        openCollectModal(payments);
    });
});
<?php if (!empty($_GET['collectPay'])): ?>document.addEventListener('DOMContentLoaded', function() { openCollectModal(); });<?php endif; ?>
</script>
<?php endif; ?>

<script>
(function() {
    var btn = document.getElementById('contractWhatsAppBtn');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var pdfUrl = btn.getAttribute('data-pdf-url');
        var waUrl = btn.getAttribute('data-wa-url');
        var filename = btn.getAttribute('data-filename') || 'Sozlesme.pdf';
        if (!pdfUrl) return;
        btn.disabled = true;
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i> PDF hazırlanıyor…';
        fetch(pdfUrl, { credentials: 'same-origin' })
            .then(function(res) {
                if (!res.ok) throw new Error('PDF indirilemedi');
                return res.blob();
            })
            .then(function(blob) {
                var file = new File([blob], filename, { type: 'application/pdf' });
                if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                    return navigator.share({
                        files: [file],
                        title: filename.replace(/\.pdf$/i, ''),
                        text: 'Sözleşme belgeniz ektedir.'
                    });
                }
                var objectUrl = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = objectUrl;
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(function() {
                    URL.revokeObjectURL(objectUrl);
                    if (waUrl) window.open(waUrl, '_blank', 'noopener');
                }, 700);
            })
            .catch(function() {
                if (waUrl) window.open(waUrl, '_blank', 'noopener');
                else alert('PDF indirilemedi. Lütfen PDF İndir butonunu deneyin.');
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
    });
})();
</script>

<script>
(function() {
    var activeEditor = null;

    function parseAmountInput(val) {
        val = (val || '').trim().replace(/\s/g, '').replace(/₺/g, '');
        if (val.indexOf(',') >= 0) {
            val = val.replace(/\./g, '').replace(',', '.');
        }
        return val;
    }

    function showAmountError(btn, message) {
        var existing = btn.parentElement && btn.parentElement.querySelector('.payment-amount-error');
        if (existing) existing.remove();
        if (!message) return;
        var err = document.createElement('p');
        err.className = 'payment-amount-error text-xs text-red-600 dark:text-red-400 mt-1';
        err.textContent = message;
        if (btn.parentElement) btn.parentElement.appendChild(err);
        setTimeout(function() { if (err.parentElement) err.remove(); }, 4000);
    }

    function updateCollectButtonsForPayment(paymentId, newAmount) {
        document.querySelectorAll('.contract-collect-pay-btn').forEach(function(collectBtn) {
            var raw = collectBtn.getAttribute('data-payments');
            if (!raw) return;
            try {
                var arr = JSON.parse(raw);
                var changed = false;
                arr.forEach(function(p) {
                    if (String(p.id) === String(paymentId)) {
                        p.amount = newAmount;
                        changed = true;
                    }
                });
                if (changed) collectBtn.setAttribute('data-payments', JSON.stringify(arr));
            } catch (e) {}
        });
    }

    function syncMonthlyPriceSidebar(monthKey, formatted) {
        if (!monthKey) return;
        var row = document.getElementById('monthly-price-' + monthKey);
        if (row) {
            var val = row.querySelector('.monthly-price-value');
            if (val) val.textContent = formatted;
        }
    }

    function finishEditor(save) {
        if (!activeEditor) return;
        var wrap = activeEditor.wrap;
        var btn = activeEditor.btn;
        var input = activeEditor.input;
        var paymentId = btn.getAttribute('data-payment-id');
        var contractId = btn.getAttribute('data-contract-id');
        var monthKey = btn.getAttribute('data-month-key') || '';
        var previous = parseFloat(btn.getAttribute('data-amount') || '0');

        if (!save) {
            wrap.replaceChild(btn, input);
            activeEditor = null;
            return;
        }

        var parsed = parseAmountInput(input.value);
        if (parsed === '' || isNaN(parseFloat(parsed)) || parseFloat(parsed) <= 0) {
            showAmountError(btn, 'Geçerli bir tutar girin.');
            input.focus();
            return;
        }
        var newAmount = parseFloat(parsed);
        if (Math.abs(newAmount - previous) < 0.009) {
            wrap.replaceChild(btn, input);
            activeEditor = null;
            return;
        }

        input.disabled = true;
        var fd = new FormData();
        fd.append('payment_id', paymentId);
        fd.append('contract_id', contractId);
        fd.append('amount', input.value);

        fetch('/girisler/odeme-tutar-guncelle', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data.ok) {
                    showAmountError(btn, data.error || 'Kaydedilemedi.');
                    input.disabled = false;
                    input.focus();
                    return;
                }
                btn.setAttribute('data-amount', String(data.amount));
                var display = btn.querySelector('.payment-amount-display');
                if (display) display.textContent = data.formatted;
                document.querySelectorAll('.payment-amount-editable[data-payment-id="' + paymentId + '"]').forEach(function(other) {
                    other.setAttribute('data-amount', String(data.amount));
                    var d = other.querySelector('.payment-amount-display');
                    if (d) d.textContent = data.formatted;
                });
                updateCollectButtonsForPayment(paymentId, data.amount);
                syncMonthlyPriceSidebar(data.month_key || monthKey, data.formatted);
                wrap.replaceChild(btn, input);
                activeEditor = null;
            })
            .catch(function() {
                showAmountError(btn, 'Bağlantı hatası. Tekrar deneyin.');
                input.disabled = false;
                input.focus();
            });
    }

    document.querySelectorAll('.payment-amount-editable').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (activeEditor) return;

            var input = document.createElement('input');
            input.type = 'text';
            input.inputMode = 'decimal';
            input.autocomplete = 'off';
            input.className = 'w-28 min-w-[6rem] px-2 py-1 text-sm font-semibold border-2 border-emerald-500 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-400';
            var raw = parseFloat(btn.getAttribute('data-amount') || '0');
            input.value = raw === Math.floor(raw)
                ? String(Math.floor(raw))
                : String(raw).replace('.', ',');

            var wrap = btn.parentElement;
            if (!wrap) return;
            wrap.replaceChild(input, btn);
            input.focus();
            input.select();
            activeEditor = { btn: btn, input: input, wrap: wrap };

            input.addEventListener('keydown', function(ev) {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    finishEditor(true);
                } else if (ev.key === 'Escape') {
                    ev.preventDefault();
                    finishEditor(false);
                }
            });
            input.addEventListener('blur', function() {
                setTimeout(function() {
                    if (activeEditor && activeEditor.input === input) finishEditor(true);
                }, 120);
            });
        });
    });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
