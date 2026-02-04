<?php
$currentPage = 'depolar';
ob_start();
?>
<div class="mb-6">
    <h1 class="page-title gradient-title">Depolar</h1>
    <p class="page-subtitle uppercase tracking-widest font-bold">Depo yönetimi ve oda takibi</p>
</div>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div></div>
    <button type="button" onclick="openModal('addWarehouseModal')" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
        <i class="bi bi-plus-lg mr-2"></i> Yeni Depo
    </button>
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

<div class="card-modern overflow-hidden">
    <?php if (empty($warehouses)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz depo eklenmemiş. Yeni Depo ile ekleyebilirsiniz.</div>
    <?php else: ?>
        <div id="whBulkBar" class="hidden flex items-center justify-between gap-3 px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><span id="whBulkCount">0</span> depo seçildi</span>
            <form method="post" action="/depolar/sil" id="whBulkDeleteForm">
                <div id="whBulkIdsContainer"></div>
                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Toplu Sil</button>
            </form>
        </div>
        <div class="table-responsive overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" id="selectAllWh" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" title="Tümünü seç"></label></th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo Adı</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Adres / Şehir</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Oda Sayısı</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($warehouses as $w): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="wh-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($w['id']) ?>"></label></td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="/depolar/<?= htmlspecialchars($w['id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($w['name']) ?></a></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                <?= htmlspecialchars(trim($w['address'] ?? '') ?: '-') ?>
                                <?php if (!empty($w['city'])): ?>
                                    <span class="text-gray-400 dark:text-gray-500"> / <?= htmlspecialchars($w['city']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= (int) ($w['room_count'] ?? 0) ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($w['is_active'])): ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="/odalar?warehouse_id=<?= urlencode($w['id']) ?>&add=1" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 mr-1" title="Oda Ekle"><i class="bi bi-plus-circle mr-1"></i> Oda Ekle</a>
                                <a href="/odalar?warehouse_id=<?= urlencode($w['id']) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 mr-1">Odalar</a>
                                <button type="button" onclick='openEditWarehouse(<?= json_encode([
                                    'id' => $w['id'],
                                    'name' => $w['name'],
                                    'address' => $w['address'] ?? '',
                                    'city' => $w['city'] ?? '',
                                    'district' => $w['district'] ?? '',
                                    'total_floors' => (int)($w['total_floors'] ?? 0),
                                    'description' => $w['description'] ?? '',
                                    'is_active' => !empty($w['is_active']),
                                ]) ?>)' class="inline-flex items-center px-2 py-1 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 mr-1" title="Düzenle"><i class="bi bi-pencil"></i></button>
                                <form method="post" action="/depolar/sil" class="inline" onsubmit="return confirm('Bu depoyu silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="ids[]" value="<?= htmlspecialchars($w['id']) ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100" title="Sil"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Yeni Depo -->
<div id="addWarehouseModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeModal('addWarehouseModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Depo</h3>
                <button type="button" onclick="closeModal('addWarehouseModal')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/depolar/ekle">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Depo Adı <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Örn: Ana Depo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeModal('addWarehouseModal')" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Düzenle -->
<div id="editWarehouseModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeModal('editWarehouseModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Depo Düzenle</h3>
                <button type="button" onclick="closeModal('editWarehouseModal')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/depolar/guncelle">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Depo Adı <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adres</label>
                        <input type="text" name="address" id="edit_address" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">İl</label>
                            <input type="text" name="city" id="edit_city" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İlçe</label>
                            <input type="text" name="district" id="edit_district" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kat Sayısı</label>
                        <input type="number" name="total_floors" id="edit_floors" min="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" id="edit_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" id="edit_active" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="edit_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeModal('editWarehouseModal')" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
function openEditWarehouse(d) {
    document.getElementById('edit_id').value = d.id || '';
    document.getElementById('edit_name').value = d.name || '';
    document.getElementById('edit_address').value = d.address || '';
    document.getElementById('edit_city').value = d.city || '';
    document.getElementById('edit_district').value = d.district || '';
    document.getElementById('edit_floors').value = d.total_floors || '';
    document.getElementById('edit_desc').value = d.description || '';
    document.getElementById('edit_active').checked = !!d.is_active;
    openModal('editWarehouseModal');
}
document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(el.id); });
});
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
        if (!confirm('Seçili ' + cbs.length + ' depoyu silmek istediğinize emin misiniz?')) { e.preventDefault(); return; }
        if (container) { container.innerHTML = ''; cbs.forEach(function(cb) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = cb.value; container.appendChild(i); }); }
    });
    document.querySelectorAll('.wh-cb').forEach(function(cb) { cb.addEventListener('change', update); });
    if (selectAll) selectAll.addEventListener('change', function() { document.querySelectorAll('.wh-cb').forEach(function(cb) { cb.checked = selectAll.checked; }); update(); });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
