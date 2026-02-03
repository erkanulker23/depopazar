<?php
$currentPage = 'odalar';
$statusLabels = ['empty' => 'Boş', 'occupied' => 'Dolu', 'reserved' => 'Rezerve', 'locked' => 'Kilitli'];
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Odalar</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Her oda bir depoya aittir – depo odaları</p>
</div>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <form method="get" action="/odalar" class="flex flex-wrap items-center gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Depo:</label>
        <select name="warehouse_id" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            <option value="">Tümü</option>
            <?php foreach ($warehouses as $w): ?>
                <option value="<?= htmlspecialchars($w['id']) ?>" <?= (isset($_GET['warehouse_id']) && $_GET['warehouse_id'] === $w['id']) ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600">Filtrele</button>
    </form>
    <button type="button" onclick="openModal('addRoomModal')" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
        <i class="bi bi-plus-lg mr-2"></i> Yeni Oda
    </button>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-800 text-sm flex items-center justify-between">
        <span><?= htmlspecialchars($flashSuccess) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-800 text-sm flex items-center justify-between">
        <span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($rooms)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400"><?= empty($warehouses) ? 'Önce Depolar sayfasından depo ekleyin. Her oda bir depoya aittir.' : 'Bu depoda oda yok veya filtreye uygun oda bulunamadı. Yeni Oda ile ekleyebilirsiniz.' ?></div>
    <?php else: ?>
        <div id="roomBulkBar" class="hidden flex items-center justify-between gap-3 px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><span id="roomBulkCount">0</span> oda seçildi</span>
            <form method="post" action="/odalar/sil" id="roomBulkDeleteForm">
                <div id="roomBulkIdsContainer"></div>
                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Toplu Sil</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" id="selectAllRooms" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" title="Tümünü seç"></label></th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Oda No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Depo</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Alan (m²)</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Aylık Fiyat</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($rooms as $r): ?>
                        <?php
                        $status = $r['status'] ?? 'empty';
                        $badgeClass = $status === 'empty' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : ($status === 'occupied' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : ($status === 'reserved' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' : 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200'));
                        ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="room-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($r['id']) ?>"></label></td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($r['room_number']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($r['warehouse_name'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?= number_format((float)$r['area_m2'], 2, ',', '.') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtPrice($r['monthly_price'] ?? 0) ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $badgeClass ?>"><?= $statusLabels[$status] ?? $status ?></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="/odalar/<?= htmlspecialchars($r['id']) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 mr-1">Detay</a>
                                <button type="button" onclick='openEditRoom(<?= json_encode([
                                    'id' => $r['id'],
                                    'room_number' => $r['room_number'],
                                    'warehouse_id' => $r['warehouse_id'],
                                    'area_m2' => $r['area_m2'],
                                    'monthly_price' => $r['monthly_price'],
                                    'status' => $r['status'] ?? 'empty',
                                    'floor' => $r['floor'] ?? '',
                                    'block' => $r['block'] ?? '',
                                    'corridor' => $r['corridor'] ?? '',
                                    'description' => $r['description'] ?? '',
                                    'notes' => $r['notes'] ?? '',
                                ]) ?>)' class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 mr-1">Düzenle</button>
                                <form method="post" action="/odalar/sil" class="inline" onsubmit="return confirm('Bu odayı silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="ids[]" value="<?= htmlspecialchars($r['id']) ?>">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Yeni Oda -->
<div id="addRoomModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeModal('addRoomModal')"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Yeni Oda</h3>
                <button type="button" onclick="closeModal('addRoomModal')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/odalar/ekle">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Depo <span class="text-red-500">*</span></label>
                        <select name="warehouse_id" required class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Seçin</option>
                            <?php foreach ($warehouses as $w): ?>
                                <option value="<?= htmlspecialchars($w['id']) ?>"><?= htmlspecialchars($w['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Oda Numarası <span class="text-red-500">*</span></label>
                        <input type="text" name="room_number" required placeholder="A-101" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alan (m²) <span class="text-red-500">*</span></label>
                            <input type="text" name="area_m2" required placeholder="25.5" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Aylık Fiyat (₺) <span class="text-red-500">*</span></label>
                            <input type="text" name="monthly_price" required placeholder="500" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durum</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <?php foreach ($statusLabels as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kat</label>
                            <input type="text" name="floor" placeholder="1" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Blok</label>
                            <input type="text" name="block" placeholder="A" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Koridor</label>
                            <input type="text" name="corridor" placeholder="Kuzey" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notlar</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeModal('addRoomModal')" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Düzenle -->
<div id="editRoomModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeModal('editRoomModal')"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Oda Düzenle</h3>
                <button type="button" onclick="closeModal('editRoomModal')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/odalar/guncelle">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Depo <span class="text-red-500">*</span></label>
                        <select name="warehouse_id" id="edit_warehouse_id" required class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Seçin</option>
                            <?php foreach ($warehouses as $w): ?>
                                <option value="<?= htmlspecialchars($w['id']) ?>"><?= htmlspecialchars($w['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Oda Numarası <span class="text-red-500">*</span></label>
                        <input type="text" name="room_number" id="edit_room_number" required class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alan (m²) <span class="text-red-500">*</span></label>
                            <input type="text" name="area_m2" id="edit_area_m2" required class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Aylık Fiyat (₺) <span class="text-red-500">*</span></label>
                            <input type="text" name="monthly_price" id="edit_monthly_price" required class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durum</label>
                        <select name="status" id="edit_status" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <?php foreach ($statusLabels as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kat</label>
                            <input type="text" name="floor" id="edit_floor" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Blok</label>
                            <input type="text" name="block" id="edit_block" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Koridor</label>
                            <input type="text" name="corridor" id="edit_corridor" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label>
                        <textarea name="description" id="edit_description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notlar</label>
                        <textarea name="notes" id="edit_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeModal('editRoomModal')" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
function openEditRoom(d) {
    document.getElementById('edit_id').value = d.id || '';
    document.getElementById('edit_warehouse_id').value = d.warehouse_id || '';
    document.getElementById('edit_room_number').value = d.room_number || '';
    document.getElementById('edit_area_m2').value = d.area_m2 || '';
    document.getElementById('edit_monthly_price').value = d.monthly_price || '';
    document.getElementById('edit_status').value = d.status || 'empty';
    document.getElementById('edit_floor').value = d.floor || '';
    document.getElementById('edit_block').value = d.block || '';
    document.getElementById('edit_corridor').value = d.corridor || '';
    document.getElementById('edit_description').value = d.description || '';
    document.getElementById('edit_notes').value = d.notes || '';
    openModal('editRoomModal');
}
document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(el.id); });
});
(function() {
    var bulkBar = document.getElementById('roomBulkBar');
    var bulkCountEl = document.getElementById('roomBulkCount');
    var selectAll = document.getElementById('selectAllRooms');
    var form = document.getElementById('roomBulkDeleteForm');
    var container = document.getElementById('roomBulkIdsContainer');
    function update() {
        var cbs = document.querySelectorAll('.room-cb:checked');
        var n = cbs.length;
        if (bulkCountEl) bulkCountEl.textContent = n;
        if (bulkBar) bulkBar.classList.toggle('hidden', n === 0);
        if (selectAll) selectAll.checked = n > 0 && document.querySelectorAll('.room-cb').length === n;
    }
    if (form) form.addEventListener('submit', function(e) {
        var cbs = document.querySelectorAll('.room-cb:checked');
        if (cbs.length === 0) { e.preventDefault(); return; }
        if (!confirm('Seçili ' + cbs.length + ' odayı silmek istediğinize emin misiniz?')) { e.preventDefault(); return; }
        if (container) { container.innerHTML = ''; cbs.forEach(function(cb) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = cb.value; container.appendChild(i); }); }
    });
    document.querySelectorAll('.room-cb').forEach(function(cb) { cb.addEventListener('change', update); });
    if (selectAll) selectAll.addEventListener('change', function() { document.querySelectorAll('.room-cb').forEach(function(cb) { cb.checked = selectAll.checked; }); update(); });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
