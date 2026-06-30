<?php
$currentPage = 'depolar';
require __DIR__ . '/../partials/warehouse_list_helpers.php';
$whTotal = count($warehouses ?? []);
$whActive = count(array_filter($warehouses ?? [], fn($w) => !empty($w['is_active'])));
$whRooms = array_sum(array_map(fn($w) => (int) ($w['room_count'] ?? 0), $warehouses ?? []));
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Depolar</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Depo yönetimi, iletişim ve oda takibi</p>
</div>

<div class="page-toolbar flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <?php
    $qWh = isset($_GET['q']) ? trim($_GET['q']) : '';
    $hasActiveFilters = $qWh !== '';
    $activeFilterTags = $qWh !== '' ? ['Arama: ' . $qWh] : [];
    $filterModalId = 'warehouseFilterModal';
    $filterClearUrl = '/depolar';
    require __DIR__ . '/../partials/page_filter_trigger.php';
    ?>
    <div class="page-toolbar-actions">
        <a href="/depolar/excel-disari-aktar" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <i class="bi bi-file-earmark-excel"></i><span class="hidden sm:inline">Excel Dışa Aktar</span><span class="sm:hidden">Dışa Aktar</span>
        </a>
        <a href="/depolar/excel-ice-aktar" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <i class="bi bi-file-earmark-arrow-down"></i><span class="hidden sm:inline">Excel İçe Aktar</span><span class="sm:hidden">İçe Aktar</span>
        </a>
        <button type="button" onclick="openModal('addWarehouseModal')" class="col-span-2 inline-flex items-center justify-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <i class="bi bi-plus-lg mr-2"></i> Yeni Depo
        </button>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm flex items-center justify-between">
        <span><?= htmlspecialchars($flashSuccess) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm flex items-center justify-between">
        <span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>

<?php if ($whTotal > 0): ?>
<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
    <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Toplam Depo</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= $whTotal ?></p>
    </div>
    <div class="rounded-xl border border-emerald-100 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/15 p-4 shadow-sm">
        <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-widest">Aktif</p>
        <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-300"><?= $whActive ?></p>
    </div>
    <div class="col-span-2 lg:col-span-1 rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Toplam Oda</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= $whRooms ?></p>
    </div>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
    <?php if (empty($warehouses)): ?>
        <div class="p-8 md:p-12 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 mb-4">
                <i class="bi bi-building text-2xl"></i>
            </div>
            <p class="text-gray-600 dark:text-gray-300 font-medium">Henüz depo eklenmemiş</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Yeni depo ekleyerek oda ve sözleşme yönetimine başlayın.</p>
            <button type="button" onclick="openModal('addWarehouseModal')" class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 btn-touch">
                <i class="bi bi-plus-lg mr-2"></i> İlk Depoyu Ekle
            </button>
        </div>
    <?php else: ?>
        <div id="whBulkBar" class="hidden flex items-center justify-between gap-3 px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><span id="whBulkCount">0</span> depo seçildi</span>
            <form method="post" action="/depolar/sil" id="whBulkDeleteForm">
                <div id="whBulkIdsContainer"></div>
                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 btn-touch">Toplu Sil</button>
            </form>
        </div>

        <!-- Mobil: kart listesi -->
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($warehouses as $w): ?>
                <div class="mobile-data-card">
                    <div class="flex items-start gap-3">
                        <label class="flex-shrink-0 mt-1 cursor-pointer">
                            <input type="checkbox" class="wh-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($w['id']) ?>">
                        </label>
                        <?php $warehouse = $w; $size = 'md'; ?>
                        <div class="shrink-0"><?php require __DIR__ . '/../partials/warehouse_logo.php'; ?></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <a href="/depolar/<?= htmlspecialchars($w['id']) ?>" class="font-semibold text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 truncate block"><?= htmlspecialchars($w['name']) ?></a>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2"><?= htmlspecialchars(warehouseLocationLine($w)) ?></p>
                                </div>
                                <?php if (!empty($w['is_active'])): ?>
                                    <span class="shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                <?php else: ?>
                                    <span class="shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                                <i class="bi bi-grid-3x3-gap mr-1 opacity-70"></i><?= (int) ($w['room_count'] ?? 0) ?> oda
                                <?php if ($w['monthly_base_fee'] !== null && $w['monthly_base_fee'] !== ''): ?>
                                    <span class="text-gray-400 dark:text-gray-500 mx-1">·</span><?= fmtPrice($w['monthly_base_fee']) ?>/ay
                                <?php endif; ?>
                            </p>
                            <?php if (warehouseHasContact($w)): ?>
                                <div class="mt-2">
                                    <?php $layout = 'chips'; require __DIR__ . '/../partials/warehouse_contact_display.php'; ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex flex-wrap gap-2 mt-3">
                                <a href="/depolar/<?= htmlspecialchars($w['id']) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 btn-touch">Detay</a>
                                <a href="/odalar?warehouse_id=<?= urlencode($w['id']) ?>&add=1" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 btn-touch">Oda Ekle</a>
                                <button type="button" onclick='openEditWarehouse(<?= json_encode(warehouseEditPayload($w), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-600 btn-touch">Düzenle</button>
                                <form method="post" action="/depolar/sil" class="inline" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('depo')) ?>);">
                                    <input type="hidden" name="ids[]" value="<?= htmlspecialchars($w['id']) ?>">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/25 btn-touch">Sil</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Masaüstü: tablo -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left w-10"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" id="selectAllWh" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" title="Tümünü seç"></label></th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest hidden lg:table-cell">Konum</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İletişim</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Oda</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($warehouses as $w): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 align-top"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="wh-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($w['id']) ?>"></label></td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-3 min-w-[12rem]">
                                    <?php $warehouse = $w; $size = 'sm'; require __DIR__ . '/../partials/warehouse_logo.php'; ?>
                                    <div class="min-w-0">
                                        <a href="/depolar/<?= htmlspecialchars($w['id']) ?>" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline block truncate"><?= htmlspecialchars($w['name']) ?></a>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 lg:hidden truncate"><?= htmlspecialchars(warehouseLocationLine($w)) ?></p>
                                        <?php if ($w['monthly_base_fee'] !== null && $w['monthly_base_fee'] !== ''): ?>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= fmtPrice($w['monthly_base_fee']) ?> / ay</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 align-top hidden lg:table-cell max-w-xs">
                                <?= htmlspecialchars(warehouseLocationLine($w)) ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 align-top max-w-[220px]">
                                <?php if (warehouseHasContact($w)): ?>
                                    <?php $compact = true; require __DIR__ . '/../partials/warehouse_contact_display.php'; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white align-top font-medium"><?= (int) ($w['room_count'] ?? 0) ?></td>
                            <td class="px-4 py-3 align-top">
                                <?php if (!empty($w['is_active'])): ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right align-top whitespace-nowrap">
                                <div class="inline-flex flex-wrap justify-end gap-1">
                                    <a href="/depolar/<?= htmlspecialchars($w['id']) ?>" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100" title="Detay"><i class="bi bi-eye"></i></a>
                                    <a href="/odalar?warehouse_id=<?= urlencode($w['id']) ?>&add=1" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20" title="Oda Ekle"><i class="bi bi-plus-circle"></i></a>
                                    <a href="/odalar?warehouse_id=<?= urlencode($w['id']) ?>" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200" title="Odalar"><i class="bi bi-grid-3x3-gap"></i></a>
                                    <button type="button" onclick='openEditWarehouse(<?= json_encode(warehouseEditPayload($w), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200" title="Düzenle"><i class="bi bi-pencil"></i></button>
                                    <form method="post" action="/depolar/sil" class="inline" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('depo')) ?>);">
                                        <input type="hidden" name="ids[]" value="<?= htmlspecialchars($w['id']) ?>">
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-red-700 bg-red-50 dark:bg-red-900/20 hover:bg-red-100" title="Sil"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
ob_start();
?>
    <div class="filter-field">
        <label class="filter-label" for="warehouse_filter_q">Ara</label>
        <input type="search" name="q" id="warehouse_filter_q" value="<?= htmlspecialchars($qWh) ?>" placeholder="Depo adı, şehir, adres..." class="filter-input">
    </div>
<?php
$filterModalBody = ob_get_clean();
$filterFormId = 'warehouseFilterForm';
$filterFormAction = '/depolar';
$filterSubmitLabel = 'Filtrele';
$filterModalTitle = 'Depo Filtreleri';
require __DIR__ . '/../partials/page_filter_modal.php';
?>

<!-- Modal: Yeni Depo -->
<div id="addWarehouseModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-end md:items-center justify-center p-0 md:p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeModal('addWarehouseModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-t-2xl md:rounded-xl shadow-xl w-full max-w-lg md:max-w-2xl p-5 md:p-6 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 sticky top-0 bg-white dark:bg-gray-800 z-10 pb-2 border-b border-gray-100 dark:border-gray-700 md:border-0 md:static md:pb-0">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Depo</h3>
                <button type="button" onclick="closeModal('addWarehouseModal')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 btn-touch"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/depolar/ekle" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Depo Adı <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Örn: Ana Depo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo Logosu</label>
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">JPG, PNG, GIF veya WebP</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adres</label>
                        <input type="text" name="address" placeholder="Tam adres" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">İl</label>
                            <input type="text" name="city" placeholder="İstanbul" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İlçe</label>
                            <input type="text" name="district" placeholder="Şişli" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kat Sayısı</label>
                        <input type="number" name="total_floors" min="1" placeholder="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Varsayılan Aylık Depo Ücreti (₺)</label>
                        <input type="text" name="monthly_base_fee" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <?php $prefix = ''; require __DIR__ . '/../partials/warehouse_contact_form_fields.php'; ?>
                </div>
                <div class="form-submit-bar mt-6 flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <button type="button" onclick="closeModal('addWarehouseModal')" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 btn-touch">İptal</button>
                    <button type="submit" class="w-full sm:w-auto btn-touch px-4 py-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_edit_modal.php'; ?>

<?php require __DIR__ . '/_warehouse_form_scripts.php'; ?>
<script>
(function() {
    var bulkBar = document.getElementById('whBulkBar');
    var bulkCountEl = document.getElementById('whBulkCount');
    var selectAll = document.getElementById('selectAllWh');
    var form = document.getElementById('whBulkDeleteForm');
    var container = document.getElementById('whBulkIdsContainer');
    function update() {
        var cbs = document.querySelectorAll('.wh-cb:checked');
        var n = cbs.length;
        if (bulkCountEl) bulkCountEl.textContent = n;
        if (bulkBar) bulkBar.classList.toggle('hidden', n === 0);
        if (selectAll) selectAll.checked = n > 0 && document.querySelectorAll('.wh-cb').length === n;
    }
    if (form) form.addEventListener('submit', function(e) {
        var cbs = document.querySelectorAll('.wh-cb:checked');
        if (cbs.length === 0) { e.preventDefault(); return; }
        if (!confirm(deleteConfirmMsg('depo', cbs.length))) { e.preventDefault(); return; }
        if (container) { container.innerHTML = ''; cbs.forEach(function(cb) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = cb.value; container.appendChild(i); }); }
    });
    document.querySelectorAll('.wh-cb').forEach(function(cb) { cb.addEventListener('change', update); });
    if (selectAll) selectAll.addEventListener('change', function() { document.querySelectorAll('.wh-cb').forEach(function(cb) { cb.checked = selectAll.checked; }); update(); });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
