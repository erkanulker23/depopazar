<?php
$currentPage = 'musteriler';
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$customerCreatedByName = trim(($customer['created_by_first_name'] ?? '') . ' ' . ($customer['created_by_last_name'] ?? ''));
$debtOverdue = $debtOverdue ?? 0;
$debtDueThisMonth = $debtDueThisMonth ?? 0;
$debtAmount = (float) ($debt ?? 0);
$hasOverdueDebt = $debtOverdue > 0.009;
$hasAnyDebt = $debtAmount > 0.009;
if ($hasOverdueDebt) {
    $balanceHeaderClass = 'bg-rose-500/10 dark:bg-rose-500/20 border-b border-rose-200/50 dark:border-rose-800/50';
    $balanceIconClass = 'text-rose-600 dark:text-rose-400';
    $balanceAmountClass = 'text-red-700 dark:text-red-300';
    $warehouseBadgeClass = 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
    $debtSummaryBoxClass = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
    $debtSummaryLabelClass = 'text-red-700 dark:text-red-400';
    $debtSummaryAmountClass = 'text-red-800 dark:text-red-300';
} elseif ($hasAnyDebt) {
    $balanceHeaderClass = 'bg-amber-500/10 dark:bg-amber-500/20 border-b border-amber-200/50 dark:border-amber-800/50';
    $balanceIconClass = 'text-amber-600 dark:text-amber-400';
    $balanceAmountClass = 'text-amber-800 dark:text-amber-300';
    $warehouseBadgeClass = 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300';
    $debtSummaryBoxClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800';
    $debtSummaryLabelClass = 'text-amber-700 dark:text-amber-400';
    $debtSummaryAmountClass = 'text-amber-800 dark:text-amber-300';
} else {
    $balanceHeaderClass = 'bg-emerald-500/10 dark:bg-emerald-500/20 border-b border-emerald-200/50 dark:border-emerald-800/50';
    $balanceIconClass = 'text-emerald-600 dark:text-emerald-400';
    $balanceAmountClass = 'text-emerald-700 dark:text-emerald-300';
    $warehouseBadgeClass = 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300';
    $debtSummaryBoxClass = 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800';
    $debtSummaryLabelClass = 'text-emerald-700 dark:text-emerald-400';
    $debtSummaryAmountClass = 'text-emerald-800 dark:text-emerald-300';
}
ob_start();
?>
<div class="page-header mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="/musteriler" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium">Müşteriler</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-gray-700 dark:text-gray-300 font-medium"><?= htmlspecialchars($customerName) ?></span>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Müşteri Detayı</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($customerName) ?></p>
        <?php if (!empty($customer['created_at'])): ?>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Eklenme: <?= fmtDateTime($customer['created_at']) ?>
            <?php if ($customerCreatedByName !== ''): ?>
                · Ekleyen: <span class="font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($customerCreatedByName) ?></span>
            <?php endif; ?>
        </p>
        <?php endif; ?>
        <?php if ($hasOverdueDebt): ?>
        <p class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800">
            <i class="bi bi-exclamation-triangle-fill"></i> Gecikmede olan borçları var
        </p>
        <?php elseif (!$hasAnyDebt): ?>
        <p class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
            <i class="bi bi-check-circle-fill"></i> Borcu yok
        </p>
        <?php endif; ?>
    </div>
    <div class="page-header-actions flex flex-nowrap md:flex-wrap gap-2">
        <button type="button" onclick="openEditCustomerModal()" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <i class="bi bi-pencil mr-2"></i> Müşteri Düzenle
        </button>
        <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/yazdir" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-printer mr-2"></i> Sayfayı Yazdır
        </a>
        <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/barkod" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-700 text-white font-medium hover:bg-gray-800 transition-colors">
            <i class="bi bi-qr-code mr-2"></i> QR Etiket Yazdır
        </a>
    </div>
</div>
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
<?php
$bulkPaidIssues = $bulkPaidIssues ?? [];
$bulkPaidExtraCount = $bulkPaidExtraCount ?? 0;
if ($bulkPaidExtraCount > 0):
?>
<div class="mb-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
    <p class="text-sm font-semibold text-amber-900 dark:text-amber-200 flex items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i> Toplu yanlış tahsilat tespit edildi
    </p>
    <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">
        Aynı anda <?= (int) $bulkPaidExtraCount ?> taksit daha ödendi işaretlenmiş görünüyor (toplam <?= array_sum(array_map(fn($g) => (int)($g['count'] ?? 0), $bulkPaidIssues)) ?> kayıt, aynı tahsilat saati).
        Muhtemelen yalnızca 1 taksit tahsil edilmiştir.
    </p>
    <div class="mt-3 flex flex-wrap gap-2">
        <form method="post" action="/musteriler/<?= htmlspecialchars($customer['id']) ?>/toplu-tahsilat-duzelt" onsubmit="return confirm('<?= (int) $bulkPaidExtraCount ?> taksit geri alınacak; yalnızca en erken vadeli taksit ödendi kalacak. Devam edilsin mi?');">
            <input type="hidden" name="keep_count" value="1">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">
                <i class="bi bi-arrow-counterclockwise mr-2"></i> Düzelt — yalnızca 1. vade ödendi kalsın
            </button>
        </form>
        <form method="post" action="/musteriler/<?= htmlspecialchars($customer['id']) ?>/toplu-tahsilat-onayla">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl border border-amber-300 dark:border-amber-700 bg-white dark:bg-gray-800 text-amber-900 dark:text-amber-200 text-sm font-medium hover:bg-amber-100 dark:hover:bg-amber-900/30">
                <i class="bi bi-check-circle mr-2"></i> Evet, eminim doğru
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- BAKİYE DURUM kartı -->
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden mb-6">
    <div class="<?= $balanceHeaderClass ?> px-4 py-3">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-bar-chart-fill <?= $balanceIconClass ?>"></i> BAKİYE DURUM
        </h2>
    </div>
    <div class="p-4 space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($customerName) ?></span>
            <button type="button" onclick="openEditCustomerModal()" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400" title="Düzenle"><i class="bi bi-pencil"></i></button>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                <span class="font-semibold text-xl <?= $balanceAmountClass ?>"><?= number_format($debtAmount, 2, ',', '.') ?> ₺</span>
                <?php if ($hasOverdueDebt): ?>
                    <span class="text-red-600 dark:text-red-400 font-medium">(Gecikmede olan borçları var)</span>
                <?php elseif ($hasAnyDebt): ?>
                    <span class="text-amber-700 dark:text-amber-300 font-medium">(Müşteri borçlu)</span>
                <?php else: ?>
                    <span class="text-emerald-700 dark:text-emerald-300 font-medium">(Borcu yok)</span>
                <?php endif; ?>
            </p>
            <?php if ($hasOverdueDebt): ?>
            <p class="text-xs mt-1 text-red-700 dark:text-red-400 font-semibold flex items-center gap-1">
                <i class="bi bi-exclamation-circle"></i> Vadesi geçmiş borç: <?= number_format((float)$debtOverdue, 2, ',', '.') ?> ₺
            </p>
            <?php endif; ?>
            <?php $debtDueThisMonth = $debtDueThisMonth ?? 0; if ($debtDueThisMonth > 0 && $hasAnyDebt): ?>
            <p class="text-xs mt-1 text-amber-700 dark:text-amber-400 font-medium">Vadesi gelmiş borç (bu ay): <?= number_format((float)$debtDueThisMonth, 2, ',', '.') ?> ₺</p>
            <?php endif; ?>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Son Ödeme</p>
            <?php if ($lastPayment): ?>
            <p class="text-sm <?= (time() - strtotime($lastPayment['paid_at'])) > 90 * 86400 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' ?>">
                <?= htmlspecialchars(timeAgoTr($lastPayment['paid_at'])) ?>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Tarih: <?= fmtDateTime($lastPayment['paid_at'] ?? null) ?> · Tutar: <?= fmtPrice($lastPayment['amount'] ?? 0) ?></p>
            <?php else: ?>
            <p class="text-sm text-red-600 dark:text-red-400">Ödeme kaydı yok.</p>
            <?php endif; ?>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Toplam Tahsilat</p>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300"><?= fmtPrice($totalCollected ?? 0) ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Müşteriden toplam alınan tutar</p>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık Kira Tutarı</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= number_format((float)$monthlyRent, 2, ',', '.') ?> ₺</p>
        </div>
        <?php if ($primaryWarehouse !== null): ?>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium <?= $warehouseBadgeClass ?>"><?= htmlspecialchars($primaryWarehouse) ?></span>
            <?php if ($exitDone): ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">Çıkış İşlemi Yapıldı</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/borclandir" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                <i class="bi bi-currency-dollar"></i> Borçlandır
            </a>
            <button type="button" onclick="document.getElementById('paymentModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
                <i class="bi bi-currency-dollar"></i> Ödeme Gir
            </button>
            <a href="/girisler?newSale=1&newCustomerId=<?= htmlspecialchars($customer['id']) ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition-colors">
                <i class="bi bi-bag-plus"></i> Yeni Depo Sözleşmesi Ekle
            </a>
            <button type="button" onclick="openEditCustomerModal('contract')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-cyan-600 text-white hover:bg-cyan-700 transition-colors">
                <i class="bi bi-file-earmark-plus"></i> Sözleşme Ekle
            </button>
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/belge-ekle" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                <i class="bi bi-file-earmark-plus"></i> Belge Ekle
            </a>
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/cikis-belgesi" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                <i class="bi bi-download"></i> Çıkış Belgesi Oluştur
            </a>
            <button type="button" onclick="document.getElementById('noteModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                <i class="bi bi-chat-left-text"></i> Bilgi Notu Ekle
            </button>
            <button type="button" onclick="document.getElementById('smsModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                <i class="bi bi-chat-dots"></i> SMS Gönder
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Sol: Profil -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-person text-emerald-600"></i> Müşteri Bilgileri
                <button type="button" onclick="openEditCustomerModal()" class="ml-2 p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20" title="Düzenle"><i class="bi bi-pencil"></i></button>
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Eklenme Tarihi</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= !empty($customer['created_at']) ? fmtDateTime($customer['created_at']) : '-' ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ekleyen</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= $customerCreatedByName !== '' ? htmlspecialchars($customerCreatedByName) : '-' ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ad Soyad</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($customerName ?: '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">E-posta</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($customer['email'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Telefon</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars(!empty($customer['phone']) ? formatPhoneDisplay($customer['phone']) : '-') ?></dd>
                </div>
                <?php if (!empty($customer['phone_2'])): ?>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Telefon 2</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars(formatPhoneDisplay($customer['phone_2'])) ?></dd>
                </div>
                <?php endif; ?>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">TC Kimlik No</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($customer['identity_number'] ?? '-') ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Adres</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($customer['address'] ?? '-') ?></dd>
                </div>
                <?php if (!empty($customer['notes'])): ?>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Not</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= nl2br(htmlspecialchars($customer['notes'])) ?></dd>
                </div>
                <?php endif; ?>
                <?php if (!empty($customer['invoice_info'])): ?>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Fatura bilgisi</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300 whitespace-pre-line"><?= nl2br(htmlspecialchars($customer['invoice_info'])) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Sözleşmeler -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-file-text text-emerald-600"></i> Sözleşmeler
                </h2>
                <button type="button" onclick="openEditCustomerModal('contract')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-cyan-600 text-white hover:bg-cyan-700 transition-colors">
                    <i class="bi bi-plus-lg"></i> Sözleşme Ekle
                </button>
            </div>
            <?php if (empty($contracts)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Depo girişi yok.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo / Oda</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Başlangıç</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Bitiş</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($contracts as $c): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>?fromCustomer=1" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></a></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDate($c['start_date'] ?? null) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDate($c['end_date'] ?? null) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= number_format((float)($c['monthly_price'] ?? 0), 2, ',', '.') ?> ₺</td>
                                    <td class="px-4 py-3"><a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>?fromCustomer=1" class="text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">Detay</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Aylar takvimi: sözleşme başına (birden fazla sözleşmede karışıklığı önler) -->
        <?php
        $monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
        $hasMultipleContracts = count($contracts) > 1;
        $paymentGroups = groupPaymentsByContract($payments, $contracts);
        ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-calendar-month text-emerald-600"></i> Aylar Takvimi – Ödendi / Ödenmedi
            </h2>
            <div class="p-4 overflow-x-auto space-y-6">
                <?php if ($hasMultipleContracts): ?>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Bu müşterinin <?= count($contracts) ?> sözleşmesi var; her sözleşme için ayrı takvim gösterilir.</p>
                    <?php foreach ($contracts as $c):
                        $cid = $c['id'] ?? '';
                        if ($cid === '') continue;
                        $contractCalendar = buildPaymentMonthsCalendar($payments, [$c]);
                    ?>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden">
                        <div class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600 flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0">
                                <a href="/girisler/<?= htmlspecialchars($cid) ?>?fromCustomer=1" class="text-sm font-bold text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></a>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars(contractLocationLabel($c)) ?> · <?= fmtDate($c['start_date'] ?? null) ?> – <?= fmtDate($c['end_date'] ?? null) ?></p>
                            </div>
                            <?php if (!empty($c['terminated_at'])): ?>
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Sonlandı</span>
                            <?php elseif (!empty($c['is_active'])): ?>
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">Aktif</span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 overflow-x-auto">
                            <?php $monthsCalendar = $contractCalendar; require __DIR__ . '/_months_calendar_table.php'; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Ocak, Şubat, Mart… hangi ay ödendi, hangi ay ödenmedi</p>
                    <?php
                    $monthsCalendar = buildPaymentMonthsCalendar($payments, $contracts);
                    require __DIR__ . '/_months_calendar_table.php';
                    ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Manuel borçlar (Borçlandır) -->
        <?php $charges = $charges ?? []; ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-receipt text-emerald-600"></i> Ek Borçlar
                </h2>
                <a href="/musteriler/<?= htmlspecialchars($customer['id'] ?? '') ?>/borclandir" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline font-medium">+ Borçlandır</a>
            </div>
            <?php if (empty($charges)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Manuel borç kaydı yok.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Açıklama</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Vade</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($charges as $ch): $cs = chargeStatusDisplay($ch); ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($ch['description'] ?? 'Ek borç') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= !empty($ch['due_date']) ? date('d.m.Y', strtotime($ch['due_date'])) : '–' ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= number_format((float)($ch['amount'] ?? 0), 2, ',', '.') ?> ₺</td>
                                    <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $cs['badge'] ?>"><?= htmlspecialchars($cs['label']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ödeme takvimi (sözleşmeye göre gruplu) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-credit-card text-emerald-600"></i> Ödeme Takvimi / Ödenenler
            </h2>
            <?php if (empty($payments)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Ödeme kaydı yok.</div>
            <?php elseif (!$hasMultipleContracts): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Vade</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödenme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşleyen</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDate($p['due_date'] ?? null) ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= number_format((float)($p['amount'] ?? 0), 2, ',', '.') ?> ₺</td>
                                    <td class="px-4 py-3">
                                        <?php $ps = paymentStatusDisplay($p); ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $ps['badge'] ?>"><?= htmlspecialchars($ps['label']) ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDateTime($p['paid_at'] ?? null) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <?php if (($p['status'] ?? '') === 'paid'): ?>
                                            <?php $collectorName = paymentCollectorName($p); ?>
                                            <?= $collectorName !== '' ? htmlspecialchars($collectorName) : '–' ?>
                                        <?php else: ?>–<?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (paymentIsCollectible($p)): ?>
                                            <a href="/odemeler?payment=<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 text-sm font-medium">Ödeme al</a>
                                        <?php elseif (($p['status'] ?? '') === 'paid'): ?>
                                            <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm mr-2">Detay</a>
                                            <form method="post" action="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>/iptal" class="inline" onsubmit="return confirm('Bu tahsilat iptal edilsin mi? Taksit tekrar borç olarak görünür.');">
                                                <input type="hidden" name="redirect" value="/musteriler/<?= htmlspecialchars($customer['id']) ?>">
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium">Geri al</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm">Detay</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="px-4 pt-4 text-xs text-gray-500 dark:text-gray-400">Taksitler sözleşmeye göre gruplandı. Her blokta depo/oda ve özet görünür.</p>
                <div class="p-4 pt-2 space-y-6">
                    <?php
                    $contractsById = $paymentGroups['contracts_by_id'];
                    $paymentsByContract = $paymentGroups['by_contract'];
                    foreach ($paymentGroups['order'] as $groupContractId):
                        $groupPayments = $paymentsByContract[$groupContractId] ?? [];
                        if ($groupPayments === []) {
                            continue;
                        }
                        $groupContract = $contractsById[$groupContractId] ?? [];
                        $paidCount = count(array_filter($groupPayments, fn($p) => ($p['status'] ?? '') === 'paid'));
                        $pendingCount = count(array_filter($groupPayments, fn($p) => in_array($p['status'] ?? '', ['pending', 'overdue'], true)));
                        $groupDebt = array_sum(array_map(fn($p) => in_array($p['status'] ?? '', ['pending', 'overdue'], true) ? (float) ($p['amount'] ?? 0) : 0.0, $groupPayments));
                    ?>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600 flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="/girisler/<?= htmlspecialchars($groupContractId) ?>?fromCustomer=1" class="text-sm font-bold text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($groupContract['contract_number'] ?? ($groupPayments[0]['contract_number'] ?? '-')) ?></a>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= htmlspecialchars(contractLocationLabel($groupContract)) ?></p>
                            </div>
                            <div class="flex flex-wrap gap-2 text-[11px] font-semibold">
                                <span class="px-2 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300"><?= $paidCount ?> ödendi</span>
                                <?php if ($pendingCount > 0): ?>
                                    <span class="px-2 py-1 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300"><?= $pendingCount ?> bekliyor</span>
                                    <span class="px-2 py-1 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300"><?= fmtPrice($groupDebt) ?> borç</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-600/40 text-gray-600 dark:text-gray-300">Borç yok</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                <thead class="bg-white dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Vade</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödenme</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşleyen</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <?php foreach ($groupPayments as $p): ?>
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDate($p['due_date'] ?? null) ?></td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= number_format((float)($p['amount'] ?? 0), 2, ',', '.') ?> ₺</td>
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
                                                    <a href="/odemeler?payment=<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 text-sm font-medium">Ödeme al</a>
                                                <?php elseif (($p['status'] ?? '') === 'paid'): ?>
                                                    <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm mr-2">Detay</a>
                                                    <form method="post" action="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>/iptal" class="inline" onsubmit="return confirm('Bu tahsilat iptal edilsin mi? Taksit tekrar borç olarak görünür.');">
                                                        <input type="hidden" name="redirect" value="/musteriler/<?= htmlspecialchars($customer['id']) ?>">
                                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium">Geri al</button>
                                                    </form>
                                                <?php else: ?>
                                                    <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm">Detay</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sağ: Borç Özeti -->
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-cash-stack text-emerald-600"></i> Borç Özeti
            </h3>
            <div class="rounded-xl <?= $debtSummaryBoxClass ?> px-4 py-4 mb-4">
                <p class="text-xs font-bold <?= $debtSummaryLabelClass ?> uppercase tracking-widest">Toplam Borç</p>
                <p class="text-2xl font-bold <?= $debtSummaryAmountClass ?>"><?= number_format($debtAmount, 2, ',', '.') ?> ₺</p>
                <?php if (!$hasAnyDebt): ?>
                <p class="text-xs mt-1 text-emerald-700 dark:text-emerald-400 font-medium">Tüm ödemeler güncel</p>
                <?php endif; ?>
            </div>
            <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-4 mb-4">
                <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-widest">Toplam Tahsilat</p>
                <p class="text-2xl font-bold text-emerald-800 dark:text-emerald-300"><?= fmtPrice($totalCollected ?? 0) ?></p>
                <p class="text-xs mt-1 text-emerald-700 dark:text-emerald-400">Müşteriden toplam alınan</p>
            </div>
            <?php if ($hasOverdueDebt): ?>
            <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 mb-4">
                <p class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-widest flex items-center gap-1">
                    <i class="bi bi-exclamation-triangle-fill"></i> Gecikmede olan borç
                </p>
                <p class="text-lg font-bold text-red-800 dark:text-red-300"><?= number_format((float)$debtOverdue, 2, ',', '.') ?> ₺</p>
            </div>
            <?php endif; ?>
            <p class="text-sm text-gray-600 dark:text-gray-400">Sözleşme sayısı: <strong><?= count($contracts) ?></strong></p>
            <?php $collectiblePayments = $collectiblePayments ?? []; $hasCollectible = !empty($collectiblePayments) || !empty(array_filter($charges ?? [], fn($c) => ($c['status'] ?? '') === 'pending')); ?>
            <?php if ($hasCollectible): ?>
            <button type="button" onclick="document.getElementById('paymentModal').classList.remove('hidden')" class="mt-4 w-full text-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
                <i class="bi bi-bank mr-2"></i> Ödeme Al
            </button>
            <?php endif; ?>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <i class="bi bi-file-earmark text-emerald-600"></i> Belgeler
            </h3>
            <?php $documents = $documents ?? []; if (!empty($documents)): ?>
            <ul class="space-y-2">
                <?php foreach ($documents as $doc): ?>
                <?php $docHref = publicUploadHref($doc['file_path'] ?? null); ?>
                <li class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <?php if ($docHref): ?>
                    <a href="<?= htmlspecialchars($docHref) ?>" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium"><?= htmlspecialchars($doc['name'] ?? 'Belge') ?></a>
                    <?php else: ?>
                    <span class="text-amber-700 dark:text-amber-300 text-sm"><?= htmlspecialchars($doc['name'] ?? 'Belge') ?> — dosya sunucuda bulunamadı</span>
                    <?php endif; ?>
                    <form method="post" action="/musteriler/belge-sil" class="inline" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('belge')) ?>);">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($doc['id']) ?>">
                        <input type="hidden" name="redirect" value="/musteriler/<?= htmlspecialchars($customer['id']) ?>">
                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Sil</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Henüz belge yüklenmemiş.</p>
            <?php endif; ?>
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/belge-ekle" class="inline-flex items-center gap-1.5 mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                <i class="bi bi-file-earmark-plus"></i> Belge ekle
            </a>
        </div>
    </div>
</div>

<!-- Modal: Bilgi Notu -->
<div id="noteModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('noteModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><i class="bi bi-chat-left-text text-emerald-600 mr-2"></i> Bilgi Notu</h3>
                <button type="button" onclick="document.getElementById('noteModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/musteriler/<?= htmlspecialchars($customer['id']) ?>/not-guncelle" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                    <textarea name="notes" rows="5" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="Müşteri hakkında not..."><?= htmlspecialchars($customer['notes'] ?? '') ?></textarea>
                </div>
                <div class="form-submit-bar flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('noteModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Müşteri Düzenle / Sözleşme Ekle -->
<div id="editCustomerModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeEditCustomerModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><i class="bi bi-pencil text-emerald-600 mr-2"></i> Müşteri Düzenle</h3>
                <button type="button" onclick="closeEditCustomerModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="flex border-b border-gray-200 dark:border-gray-600 mb-4 -mt-1">
                <button type="button" id="editTabBtn_info" onclick="switchEditCustomerTab('info')" class="edit-customer-tab px-4 py-2 text-sm font-medium border-b-2 border-emerald-600 text-emerald-600 dark:text-emerald-400">Müşteri Bilgileri</button>
                <button type="button" id="editTabBtn_contract" onclick="switchEditCustomerTab('contract')" class="edit-customer-tab px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Sözleşme Ekle</button>
            </div>
            <div id="editTab_info">
            <form method="post" action="/musteriler/guncelle" class="space-y-3">
                <input type="hidden" name="id" value="<?= htmlspecialchars($customer['id']) ?>">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required value="<?= htmlspecialchars($customer['first_name'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soyad <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" required value="<?= htmlspecialchars($customer['last_name'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                    <input type="tel" name="phone" inputmode="numeric" autocomplete="tel" value="<?= htmlspecialchars(formatPhoneDisplay($customer['phone'] ?? '')) ?>" placeholder="0555 123 45 67" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" data-phone-mask title="11 hane: 05xx xxx xx xx">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon 2</label>
                    <input type="tel" name="phone_2" inputmode="numeric" autocomplete="tel" value="<?= htmlspecialchars(formatPhoneDisplay($customer['phone_2'] ?? '')) ?>" placeholder="0555 123 45 67" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" data-phone-mask title="11 hane: 05xx xxx xx xx">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">TC Kimlik No</label>
                    <input type="text" name="identity_number" maxlength="11" inputmode="numeric" value="<?= htmlspecialchars($customer['identity_number'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adres</label>
                    <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fatura bilgisi <span class="text-gray-400 font-normal">(opsiyonel)</span></label>
                    <textarea name="invoice_info" rows="3" placeholder="Fatura unvanı, vergi no, vergi dairesi vb." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($customer['invoice_info'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($customer['notes'] ?? '') ?></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" <?= !empty($customer['is_active']) ? 'checked' : '' ?> class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500">
                    <label for="edit_is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Aktif müşteri</label>
                </div>
                <div class="form-submit-bar flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeEditCustomerModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
            </div>
            <div id="editTab_contract" class="hidden">
                <?php require __DIR__ . '/../partials/customer_edit_contract_tab.php'; ?>
            </div>
        </div>
    </div>
</div>

<?php
$charges = $charges ?? [];
$collectiblePayments = $collectiblePayments ?? array_filter($payments ?? [], fn($p) => paymentIsCollectible($p));
$unpaidPayments = $collectiblePayments;
$unpaidCharges = array_filter($charges, fn($c) => ($c['status'] ?? '') === 'pending');
$bankAccounts = $bankAccounts ?? [];
?>
<!-- Modal: Ödeme Gir -->
<div id="paymentModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('paymentModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><i class="bi bi-currency-dollar text-emerald-600 mr-2"></i> Ödeme Gir – <?= htmlspecialchars($customerName) ?></h3>
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <?php if (empty($unpaidPayments) && empty($unpaidCharges)): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4">Bu müşteriye ait tahsil edilecek ödeme veya borç kaydı yok. Sözleşme varsa ödeme takvimi otomatik oluşturulur; sayfayı yenileyin veya sözleşme detayından kontrol edin.</p>
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">Kapat</button>
                </div>
            <?php else: ?>
                <form method="post" action="/odemeler/odeme-al" id="paymentModalForm">
                    <input type="hidden" name="redirect" value="/musteriler/<?= htmlspecialchars($customer['id']) ?>">
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Ödeme alınacak kalemleri işaretleyin. Vadesi gelmemiş aylar için erken tahsilat yapılabilir.</p>
                    <div class="max-h-48 overflow-y-auto space-y-3 mb-4">
                        <?php
                        $unpaidGroups = groupPaymentsByContract($unpaidPayments, $contracts);
                        $showUnpaidContractHeaders = count($contracts) > 1;
                        foreach ($unpaidGroups['order'] as $modalContractId):
                            $modalContractPayments = $unpaidGroups['by_contract'][$modalContractId] ?? [];
                            if ($modalContractPayments === []) {
                                continue;
                            }
                            $modalContract = $unpaidGroups['contracts_by_id'][$modalContractId] ?? [];
                        ?>
                        <?php if ($showUnpaidContractHeaders): ?>
                            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1 pt-1">
                                <?= htmlspecialchars($modalContract['contract_number'] ?? ($modalContractPayments[0]['contract_number'] ?? '-')) ?>
                                · <?= htmlspecialchars(contractLocationLabel($modalContract)) ?>
                            </p>
                        <?php endif; ?>
                        <?php foreach ($modalContractPayments as $p): ?>
                            <?php $psModal = paymentStatusDisplay($p); ?>
                            <label class="flex items-center justify-between gap-2 p-3 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <span class="flex items-center gap-2 min-w-0 flex-wrap">
                                    <input type="checkbox" name="payment_ids[]" value="<?= htmlspecialchars($p['id']) ?>" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 shrink-0">
                                    <?php if (!$showUnpaidContractHeaders): ?>
                                        <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($p['contract_number'] ?? '-') ?></span>
                                    <?php endif; ?>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Vade: <?= !empty($p['due_date']) ? date('d.m.Y', strtotime($p['due_date'])) : '-' ?></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $psModal['badge'] ?>"><?= htmlspecialchars($psModal['label']) ?></span>
                                </span>
                                <span class="font-semibold text-gray-900 dark:text-white shrink-0"><?= number_format((float)($p['amount'] ?? 0), 2, ',', '.') ?> ₺</span>
                            </label>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php foreach ($unpaidCharges as $ch): ?>
                            <label class="flex items-center justify-between gap-2 p-3 border border-amber-200 dark:border-amber-800 rounded-xl hover:bg-amber-50/50 dark:hover:bg-amber-900/10 cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" name="charge_ids[]" value="<?= htmlspecialchars($ch['id']) ?>" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($ch['description'] ?? 'Ek borç') ?></span>
                                    <span class="text-xs text-amber-600 dark:text-amber-400">Manuel borç</span>
                                </span>
                                <span class="font-semibold text-gray-900 dark:text-white"><?= number_format((float)($ch['amount'] ?? 0), 2, ',', '.') ?> ₺</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="space-y-3 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ödeme yöntemi <span class="text-red-500">*</span></label>
                            <select name="payment_method" required class="payment-method-select w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                <option value="bank_transfer" selected>Havale / EFT</option>
                                <option value="credit_card">Kredi Kartı</option>
                            </select>
                        </div>
                        <div class="payment-bank-field hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Banka hesabı <span class="text-red-500">*</span></label>
                            <?php if (empty($bankAccounts)): ?>
                                <p class="text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 p-2 rounded-lg">Aktif banka hesabı yok. Ayarlar → Banka Hesaplarından ekleyin.</p>
                            <?php else: ?>
                                <select name="bank_account_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Seçin</option>
                                    <?php foreach ($bankAccounts as $ba): ?>
                                        <option value="<?= htmlspecialchars($ba['id']) ?>"><?= htmlspecialchars($ba['bank_name']) ?> – <?= htmlspecialchars($ba['account_number']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ödeme tarihi</label>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahsilat Tarihi</label>
                            <input type="datetime-local" name="paid_at" value="<?= fmtDateTimeLocalInput() ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İşlem no (opsiyonel)</label>
                            <input type="text" name="transaction_id" placeholder="Havale işlem no" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahsil eden personel (opsiyonel)</label>
                            <?php if (!empty($activePersonnel)): ?>
                                <select name="paid_by_personnel_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Seçin</option>
                                    <?php foreach ($activePersonnel as $ap): ?>
                                        <option value="<?= htmlspecialchars($ap['id']) ?>"><?= htmlspecialchars(trim(($ap['first_name'] ?? '') . ' ' . ($ap['last_name'] ?? ''))) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Aktif personel yok.</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not (opsiyonel)</label>
                            <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                        <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Ödemeyi Kaydet</button>
                    </div>
                </form>
                <script>
                (function() {
                    var form = document.getElementById('paymentModalForm');
                    var methodSelect = form && form.querySelector('.payment-method-select');
                    var bankField = form && form.querySelector('.payment-bank-field');
                    var bankSelect = form && form.querySelector('select[name="bank_account_id"]');
                    if (methodSelect && bankField) {
                        function toggleBank() {
                            var isTransfer = methodSelect.value === 'bank_transfer';
                            bankField.classList.toggle('hidden', !isTransfer);
                            if (bankSelect) bankSelect.required = isTransfer;
                        }
                        methodSelect.addEventListener('change', toggleBank);
                        toggleBank();
                    }
                    if (form) {
                        var paymentCbs = form.querySelectorAll('input[name="payment_ids[]"]');
                        if (paymentCbs.length > 0 && !form.querySelector('input[name="payment_ids[]"]:checked')) {
                            paymentCbs[0].checked = true;
                        }
                        form.addEventListener('submit', function(e) {
                            var checked = form.querySelectorAll('input[name="payment_ids[]"]:checked');
                            if (checked.length === 0) {
                                e.preventDefault();
                                alert('En az bir ödeme kalemi seçin.');
                                return;
                            }
                            form.querySelectorAll('input[name="confirm_multi_period"]').forEach(function(el) { el.remove(); });
                            if (checked.length > 1) {
                                if (!confirm(checked.length + ' taksit aynı anda tahsil edilecek. Devam edilsin mi?')) {
                                    e.preventDefault();
                                    return;
                                }
                                var confirmInput = document.createElement('input');
                                confirmInput.type = 'hidden';
                                confirmInput.name = 'confirm_multi_period';
                                confirmInput.value = '1';
                                form.appendChild(confirmInput);
                            }
                            var futureCount = 0;
                            checked.forEach(function(cb) {
                                var label = cb.closest('label');
                                var badge = label && label.querySelector('.inline-flex');
                                if (badge && badge.textContent.indexOf('Vadesi gelmemiş') >= 0) futureCount++;
                            });
                            if (futureCount > 0 && !confirm(futureCount + ' adet vadesi gelmemiş taksit de ödendi olarak işaretlenecek. Devam edilsin mi?')) {
                                e.preventDefault();
                            }
                        });
                    }
                })();
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: SMS Gönder -->
<div id="smsModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('smsModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><i class="bi bi-chat-dots text-emerald-600 mr-2"></i> SMS Gönder</h3>
                <button type="button" onclick="document.getElementById('smsModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">SMS, Ayarlar &rarr; SMS (Netgsm) bölümündeki ayarlara göre gönderilir. Alıcı: <strong><?= htmlspecialchars($customer['phone'] ?? '') ?></strong></p>
            <form method="post" action="/musteriler/<?= htmlspecialchars($customer['id']) ?>/sms-gonder" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mesaj <span class="text-red-500">*</span></label>
                    <textarea name="message" required rows="4" maxlength="160" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="SMS metnini yazın (tek mesaj 160 karakter)"></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tek SMS en fazla 160 karakter. Uzun metinler birden fazla SMS olarak gönderilir.</p>
                </div>
                <div class="form-submit-bar flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('smsModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/room-picker.js"></script>
<script src="/contract-billing.js"></script>
<script>
window.closeEditCustomerModal = function() {
    var modal = document.getElementById('editCustomerModal');
    if (modal) modal.classList.add('hidden');
};
window.switchEditCustomerTab = function(tab) {
    ['info', 'contract'].forEach(function(t) {
        var panel = document.getElementById('editTab_' + t);
        var btn = document.getElementById('editTabBtn_' + t);
        if (panel) panel.classList.toggle('hidden', t !== tab);
        if (btn) {
            var active = t === tab;
            btn.classList.toggle('border-emerald-600', active);
            btn.classList.toggle('text-emerald-600', active);
            btn.classList.toggle('dark:text-emerald-400', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-gray-500', !active);
            btn.classList.toggle('dark:text-gray-400', !active);
        }
    });
    if (tab === 'contract' && typeof window.initCustContractRoomPicker === 'function') {
        window.initCustContractRoomPicker();
    }
};
window.openEditCustomerModal = function(tab) {
    tab = tab || 'info';
    var modal = document.getElementById('editCustomerModal');
    if (modal) {
        if (window.initPhoneMasks) window.initPhoneMasks();
        switchEditCustomerTab(tab);
        modal.classList.remove('hidden');
    }
};
function toggleCustContractConditionNote(value) {
    var block = document.getElementById('custContract_condition_note_block');
    var note = document.getElementById('custContract_condition_note');
    var show = value === 'hasarli';
    if (block) block.classList.toggle('hidden', !show);
    if (note) {
        note.required = show;
        if (!show) note.value = '';
    }
}
(function() {
    var custContractRoomsData = <?= json_encode($contractRoomsJson ?? [], JSON_UNESCAPED_UNICODE) ?>;
    var whSelect = document.getElementById('custContract_warehouse');
    function applyCustWarehouseBaseFee() {
        var priceEl = document.getElementById('custContract_monthly_price');
        if (!priceEl || !whSelect) return;
        var opt = whSelect.options[whSelect.selectedIndex];
        var baseFee = opt && opt.getAttribute('data-monthly-base-fee');
        priceEl.value = baseFee ? baseFee.replace('.', ',') : '';
    }
    function applyCustRoomMonthlyPrice(room) {
        var priceEl = document.getElementById('custContract_monthly_price');
        if (!priceEl || !room) return;
        if (room.monthly_price !== null && room.monthly_price !== undefined && room.monthly_price !== '') {
            priceEl.value = String(room.monthly_price).replace('.', ',');
        }
    }
    window.initCustContractRoomPicker = function() {
        if (typeof initRoomPicker !== 'function') return;
        if (window.custContractRoomPicker) return;
        window.custContractRoomPicker = initRoomPicker({
            hiddenInputId: 'custContract_room_id',
            searchInputId: 'custContract_room_search',
            resultsId: 'custContract_room_results',
            warehouseSelectId: 'custContract_warehouse',
            hintId: 'custContract_room_hint',
            rooms: custContractRoomsData,
            onWarehouseChange: function() {
                applyCustWarehouseBaseFee();
            },
            onSelect: function(room) {
                applyCustRoomMonthlyPrice(room);
            },
            onClear: function() {
                applyCustWarehouseBaseFee();
            }
        });
    };
    var custUseCampaign = document.getElementById('custContract_use_campaign');
    var custCampaignBlock = document.getElementById('custContract_campaign_block');
    var custCampaignSelect = document.getElementById('custContract_campaign_code');
    var custStartDate = document.getElementById('custContract_start_date');
    var custEndDate = document.getElementById('custContract_end_date');
    function applyCustContractCampaignDates() {
        if (!custStartDate || !custEndDate || typeof ContractBilling === 'undefined') return;
        if (!custUseCampaign || !custUseCampaign.checked || !custCampaignSelect || !custCampaignSelect.value) return;
        if (!custStartDate.value) return;
        var end = ContractBilling.campaignEndDate(custStartDate.value, custCampaignSelect.value);
        if (end) custEndDate.value = end;
    }
    if (custUseCampaign && custCampaignBlock) {
        custUseCampaign.addEventListener('change', function() {
            custCampaignBlock.classList.toggle('hidden', !custUseCampaign.checked);
            if (!custUseCampaign.checked && custCampaignSelect) custCampaignSelect.value = '';
            applyCustContractCampaignDates();
        });
    }
    if (custCampaignSelect) custCampaignSelect.addEventListener('change', applyCustContractCampaignDates);
    if (custStartDate) custStartDate.addEventListener('change', applyCustContractCampaignDates);
    var form = document.getElementById('custContractForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            var roomId = document.getElementById('custContract_room_id');
            if (!roomId || !roomId.value) {
                e.preventDefault();
                alert('Lütfen oda seçin.');
                return;
            }
            var selected = form.querySelector('input[name="stored_items_condition"]:checked');
            if (!selected) {
                e.preventDefault();
                alert('Giriş yapılan ürün durumu seçilmelidir.');
                return;
            }
            if (selected.value === 'hasarli') {
                var note = document.getElementById('custContract_condition_note');
                if (!note || !note.value.trim()) {
                    e.preventDefault();
                    alert('Hasarlı ürünler için hasar notu zorunludur.');
                    if (note) note.focus();
                }
            }
        });
    }
    <?php if (!empty($openAddContract)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        openEditCustomerModal('contract');
    });
    <?php endif; ?>
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
