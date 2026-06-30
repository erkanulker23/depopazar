<?php
$currentPage = 'personel';
$personnelRow = $personnelRow ?? [];
$contracts = $contracts ?? [];
$payments = $payments ?? [];
$contractsTotal = $contractsTotal ?? count($contracts);
$contractsPerPage = $contractsPerPage ?? 20;
$contractsPage = $contractsPage ?? max(1, (int) ($_GET['page'] ?? 1));
$stats = $stats ?? ['contract_count' => 0, 'active_contract_count' => 0, 'payment_count' => 0, 'total_collected' => 0];
$jobTypeLabels = $jobTypeLabels ?? Personnel::jobTypeLabels();
$canManage = $canManage ?? false;
$companyName = $companyName ?? null;
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
$fullName = trim(($personnelRow['first_name'] ?? '') . ' ' . ($personnelRow['last_name'] ?? ''));
$jobLabel = $jobTypeLabels[$personnelRow['job_type'] ?? 'diger'] ?? 'Diğer';
$personnelId = $personnelRow['id'] ?? '';
ob_start();
?>
<div class="page-header mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-3">
        <a href="/personel" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Personel</a>
        <i class="bi bi-chevron-right text-xs"></i>
        <span class="text-gray-700 dark:text-gray-300 font-medium truncate"><?= htmlspecialchars($fullName) ?></span>
    </div>
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="flex items-start gap-4 min-w-0">
            <?php $size = 'lg'; require __DIR__ . '/../partials/personnel_avatar.php'; ?>
            <div class="min-w-0">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1 truncate"><?= htmlspecialchars($fullName) ?></h1>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($jobLabel) ?></p>
                <?php if (!empty($personnelRow['phone'])): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $personnelRow['phone'])) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($personnelRow['phone']) ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($canManage): ?>
        <div class="page-header-actions flex flex-wrap gap-2 shrink-0">
            <button type="button" onclick='openEditPersonnelModal(<?= json_encode(array_merge($personnelRow, ['photo_href' => personnelPhotoHref($personnelRow) ?? '']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)' class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 btn-touch">
                <i class="bi bi-pencil mr-2"></i> Düzenle
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Satış / Sözleşme</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?= (int) ($stats['contract_count'] ?? 0) ?></p>
    </div>
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aktif Sözleşme</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?= (int) ($stats['active_contract_count'] ?? 0) ?></p>
    </div>
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tahsilat</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?= (int) ($stats['payment_count'] ?? 0) ?></p>
    </div>
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm col-span-2 md:col-span-1">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Toplam Tahsil</p>
        <p class="mt-1 text-lg font-bold text-emerald-700 dark:text-emerald-400"><?= fmtPrice($stats['total_collected'] ?? 0) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 md:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-person-badge text-emerald-600"></i> Personel Bilgileri
            </h2>
            <dl class="grid grid-cols-1 gap-4">
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ad Soyad</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($fullName ?: '–') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Görev</dt>
                    <dd class="mt-1">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"><?= htmlspecialchars($jobLabel) ?></span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Telefon</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($personnelRow['phone'] ?? '–') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</dt>
                    <dd class="mt-1">
                        <?php if (!empty($personnelRow['is_active'])): ?>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if ($companyName): ?>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Şirket</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($companyName) ?></dd>
                </div>
                <?php endif; ?>
                <?php if (!empty($personnelRow['notes'])): ?>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Notlar</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($personnelRow['notes']) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-file-earmark-text text-emerald-600"></i> Satışlar / Sözleşmeler
                <span class="ml-auto text-sm font-normal text-gray-500 dark:text-gray-400"><?= (int) $contractsTotal ?></span>
            </h2>
            <?php if ($contractsTotal === 0): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Bu personele atanmış sözleşme veya satış kaydı yok.</div>
            <?php else: ?>
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($contracts as $c):
                        $custName = trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''));
                        $soldBy = trim(($c['sold_by_first_name'] ?? '') . ' ' . ($c['sold_by_last_name'] ?? ''));
                    ?>
                    <div class="mobile-data-card">
                        <a href="/girisler/<?= htmlspecialchars($c['id']) ?>" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($c['contract_number'] ?? '–') ?></a>
                        <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <p><span class="text-gray-500 dark:text-gray-500">Müşteri:</span> <?php if (!empty($c['customer_id'])): ?><a href="/musteriler/<?= htmlspecialchars($c['customer_id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($custName) ?></a><?php else: ?><?= htmlspecialchars($custName ?: '–') ?><?php endif; ?></p>
                            <p><span class="text-gray-500">Depo / Oda:</span> <?= htmlspecialchars(trim(($c['warehouse_name'] ?? '') . ' · ' . ($c['room_number'] ?? ''), ' ·') ?: '–') ?></p>
                            <p><span class="text-gray-500">Aylık:</span> <?= fmtPrice($c['monthly_price'] ?? 0) ?></p>
                            <?php if ($soldBy !== ''): ?><p><span class="text-gray-500">Sözleşmeyi yapan:</span> <?= htmlspecialchars($soldBy) ?></p><?php endif; ?>
                        </div>
                        <div class="mt-2">
                            <?php if (!empty($c['is_active'])): ?>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo / Oda</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($contracts as $c):
                                $custName = trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''));
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <a href="/girisler/<?= htmlspecialchars($c['id']) ?>" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($c['contract_number'] ?? '–') ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    <?php if (!empty($c['customer_id'])): ?>
                                        <a href="/musteriler/<?= htmlspecialchars($c['customer_id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($custName) ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($custName ?: '–') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars(trim(($c['warehouse_name'] ?? '') . ' · ' . ($c['room_number'] ?? ''), ' ·') ?: '–') ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white tabular-nums"><?= fmtPrice($c['monthly_price'] ?? 0) ?></td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($c['is_active'])): ?>
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($contractsTotal > $contractsPerPage):
                    echo renderPagination($contractsTotal, $contractsPerPage, $contractsPage, '/personel/' . $personnelId);
                endif; ?>
            <?php endif; ?>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-cash-stack text-emerald-600"></i> Aldığı Ödemeler
                <span class="ml-auto text-sm font-normal text-gray-500 dark:text-gray-400"><?= count($payments) ?></span>
            </h2>
            <?php if (empty($payments)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Bu personelin tahsilat kaydı yok. Ödeme alırken &quot;Tahsil eden personel&quot; alanından seçim yapılabilir.</div>
            <?php else: ?>
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($payments as $p):
                        $custName = trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? ''));
                    ?>
                    <div class="mobile-data-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="/odemeler/<?= htmlspecialchars($p['id']) ?>" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"><?= fmtPrice($p['amount'] ?? 0) ?></a>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= htmlspecialchars($custName) ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1"><?= htmlspecialchars($p['contract_number'] ?? '–') ?> · <?= htmlspecialchars($p['warehouse_name'] ?? '') ?></p>
                            </div>
                            <div class="text-right shrink-0 text-sm">
                                <p class="text-gray-700 dark:text-gray-300"><?= !empty($p['paid_at']) ? date('d.m.Y', strtotime($p['paid_at'])) : '–' ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= htmlspecialchars(paymentMethodLabel($p['payment_method'] ?? '')) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tahsilat Tarihi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Yöntem</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($payments as $p):
                                $custName = trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? ''));
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap"><?= !empty($p['paid_at']) ? date('d.m.Y H:i', strtotime($p['paid_at'])) : '–' ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if (!empty($p['customer_id'])): ?>
                                        <a href="/musteriler/<?= htmlspecialchars($p['customer_id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($custName) ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($custName ?: '–') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if (!empty($p['contract_id'])): ?>
                                        <a href="/girisler/<?= htmlspecialchars($p['contract_id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($p['contract_number'] ?? '–') ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($p['contract_number'] ?? '–') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars(paymentMethodLabel($p['payment_method'] ?? '')) ?></td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right tabular-nums">
                                    <a href="/odemeler/<?= htmlspecialchars($p['id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= fmtPrice($p['amount'] ?? 0) ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<div id="editPersonnelModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeEditPersonnelModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Personel Düzenle</h3>
                <button type="button" onclick="closeEditPersonnelModal()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/personel/guncelle" id="editPersonnelForm" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="id" id="editPersonnel_id">
                <input type="hidden" name="redirect" value="/personel/<?= htmlspecialchars($personnelId) ?>">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" id="editPersonnel_first_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soyad <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" id="editPersonnel_last_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Görev</label>
                    <select name="job_type" id="editPersonnel_job_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <?php foreach ($jobTypeLabels as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                    <input type="text" name="phone" id="editPersonnel_phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                    <textarea name="notes" id="editPersonnel_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" id="editPersonnel_is_active" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                </label>
                <div class="form-submit-bar flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeEditPersonnelModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openEditPersonnelModal(p) {
    document.getElementById('editPersonnel_id').value = p.id || '';
    document.getElementById('editPersonnel_first_name').value = p.first_name || '';
    document.getElementById('editPersonnel_last_name').value = p.last_name || '';
    document.getElementById('editPersonnel_job_type').value = p.job_type || 'diger';
    document.getElementById('editPersonnel_phone').value = p.phone || '';
    document.getElementById('editPersonnel_notes').value = p.notes || '';
    document.getElementById('editPersonnel_is_active').checked = !!parseInt(p.is_active || '0', 10);
    document.getElementById('editPersonnelModal').classList.remove('hidden');
}
function closeEditPersonnelModal() {
    document.getElementById('editPersonnelModal').classList.add('hidden');
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = $fullName . ' – Personel';
require __DIR__ . '/../layout.php';
