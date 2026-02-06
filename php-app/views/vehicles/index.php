<?php
$currentPage = 'araclar';
$reportRows = $reportRows ?? [];
$upcomingKasko = $upcomingKasko ?? [];
$upcomingInspection = $upcomingInspection ?? [];
$tableExists = $tableExists ?? false;
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
$pageTitle = $pageTitle ?? 'Araçlar';
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Araçlar</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Plaka bazlı araç listesi ve kasko / muayene uyarıları</p>
</div>

<?php if (!$tableExists): ?>
    <div class="mb-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 text-sm">
        <strong>Araç kaydı eklemek için</strong> veritabanında <code class="px-1 py-0.5 rounded bg-amber-100 dark:bg-amber-800">vehicles</code> tablosu oluşturulmalı. Deploy sırasında migrations otomatik çalışır; manuel için:<br>
        <code class="block mt-2 p-2 rounded bg-gray-100 dark:bg-gray-800 text-xs">mysql -u kullanici -p veritabani &lt; php-app/sql/migrations/01_add_vehicles_table.sql</code>
    </div>
<?php endif; ?>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<?php if (!empty($upcomingKasko) || !empty($upcomingInspection)): ?>
    <div class="mb-6 space-y-3">
        <?php foreach ($upcomingKasko as $row): ?>
            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 text-sm flex items-center gap-2">
                <i class="bi bi-shield-check"></i>
                <strong>Kasko:</strong> <?= htmlspecialchars($row['plate']) ?> – <?= htmlspecialchars($row['kasko_date']) ?> (önceki 30 gün)
            </div>
        <?php endforeach; ?>
        <?php foreach ($upcomingInspection as $row): ?>
            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 text-sm flex items-center gap-2">
                <i class="bi bi-clipboard-check"></i>
                <strong>Muayene:</strong> <?= htmlspecialchars($row['plate']) ?> – <?= htmlspecialchars($row['inspection_date']) ?> (önceki 30 gün)
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($tableExists): ?>
<div class="flex flex-wrap items-center gap-3 mb-4">
    <button type="button" onclick="document.getElementById('addVehicleModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
        <i class="bi bi-plus-lg mr-2"></i> Araç Ekle
    </button>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="overflow-x-auto table-responsive">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Plaka</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Model Yılı</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Kasko</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Muayene</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Kasa m³</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Nakliye / Sözleşme</th>
                    <?php if ($tableExists): ?><th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th><?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                <?php if (empty($reportRows)): ?>
                    <tr><td colspan="<?= $tableExists ? 7 : 6 ?>" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Henüz araç veya plaka kaydı yok. Sözleşme/nakliye girişlerinde kullanılan plakalar burada listelenir.<?= $tableExists ? ' "Araç Ekle" ile kayıt ekleyebilirsiniz.' : '' ?></td></tr>
                <?php else: ?>
                    <?php foreach ($reportRows as $row): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($row['plate']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($row['model_year'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($row['kasko_date'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($row['inspection_date'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($row['cargo_volume_m3'] !== null && $row['cargo_volume_m3'] !== '' ? $row['cargo_volume_m3'] : '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= (int)($row['transport_job_count'] ?? 0) ?> / <?= (int)($row['contract_count'] ?? 0) ?></td>
                            <?php if ($tableExists): ?>
                            <td class="px-4 py-3 text-right">
                                <?php if (!empty($row['id'])): ?>
                                    <a href="/araclar/<?= htmlspecialchars($row['id']) ?>" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 mr-1">Detay</a>
                                    <button type="button" onclick='openEditVehicle(<?= json_encode($row) ?>)' class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 mr-1">Düzenle</button>
                                    <form method="post" action="/araclar/sil" class="inline" onsubmit="return confirm('Bu aracı silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Sil</button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" onclick='openAddVehicleWithPlate("<?= htmlspecialchars(addslashes($row['plate'])) ?>")' class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20">Kayda al</button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Araç Ekle -->
<div id="addVehicleModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="addVehicleModalTitle">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('addVehicleModal').classList.add('hidden')" aria-hidden="true"></div>
        <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col border border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between flex-shrink-0 px-6 pt-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                <h3 id="addVehicleModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">Araç Ekle</h3>
                <button type="button" onclick="document.getElementById('addVehicleModal').classList.add('hidden')" class="p-2 -m-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Kapat">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>
            <form method="post" action="/araclar/ekle" class="flex flex-col min-h-0">
                <div class="space-y-4 px-6 py-4 overflow-y-auto flex-1">
                    <div>
                        <label for="add_plate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plaka <span class="text-red-500">*</span></label>
                        <input type="text" name="plate" id="add_plate" required class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="34 ABC 123">
                    </div>
                    <div>
                        <label for="add_model_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model Yılı</label>
                        <input type="number" name="model_year" id="add_model_year" min="1990" max="2030" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="2022">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="add_kasko_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kasko Tarihi</label>
                            <input type="date" name="kasko_date" id="add_kasko_date" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="add_inspection_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Muayene Tarihi</label>
                            <input type="date" name="inspection_date" id="add_inspection_date" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label for="add_cargo_volume_m3" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kasa (m³)</label>
                        <input type="number" step="0.01" min="0" name="cargo_volume_m3" id="add_cargo_volume_m3" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500" placeholder="12">
                    </div>
                    <div>
                        <label for="add_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" id="add_notes" rows="2" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 resize-none" placeholder="Opsiyonel"></textarea>
                    </div>
                </div>
                <div class="flex-shrink-0 flex gap-3 justify-end px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 rounded-b-2xl">
                    <button type="button" onclick="document.getElementById('addVehicleModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">İptal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors shadow-sm">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Araç Düzenle -->
<div id="editVehicleModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="editVehicleModalTitle">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('editVehicleModal').classList.add('hidden')" aria-hidden="true"></div>
        <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col border border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between flex-shrink-0 px-6 pt-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                <h3 id="editVehicleModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">Araç Düzenle</h3>
                <button type="button" onclick="document.getElementById('editVehicleModal').classList.add('hidden')" class="p-2 -m-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Kapat">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>
            <form method="post" action="/araclar/guncelle" class="flex flex-col min-h-0">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4 px-6 py-4 overflow-y-auto flex-1">
                    <div>
                        <label for="edit_plate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plaka <span class="text-red-500">*</span></label>
                        <input type="text" name="plate" id="edit_plate" required class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label for="edit_model_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model Yılı</label>
                        <input type="number" name="model_year" id="edit_model_year" min="1990" max="2030" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_kasko_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kasko Tarihi</label>
                            <input type="date" name="kasko_date" id="edit_kasko_date" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="edit_inspection_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Muayene Tarihi</label>
                            <input type="date" name="inspection_date" id="edit_inspection_date" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label for="edit_cargo_volume_m3" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kasa (m³)</label>
                        <input type="number" step="0.01" min="0" name="cargo_volume_m3" id="edit_cargo_volume_m3" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="edit_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" id="edit_notes" rows="2" class="w-full px-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 resize-none"></textarea>
                    </div>
                </div>
                <div class="flex-shrink-0 flex gap-3 justify-end px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 rounded-b-2xl">
                    <button type="button" onclick="document.getElementById('editVehicleModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">İptal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors shadow-sm">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddVehicleWithPlate(plate) {
    document.getElementById('add_plate').value = plate || '';
    document.getElementById('addVehicleModal').classList.remove('hidden');
}
function openEditVehicle(row) {
    document.getElementById('edit_id').value = row.id || '';
    document.getElementById('edit_plate').value = row.plate || '';
    document.getElementById('edit_model_year').value = row.model_year || '';
    document.getElementById('edit_kasko_date').value = row.kasko_date || '';
    document.getElementById('edit_inspection_date').value = row.inspection_date || '';
    document.getElementById('edit_cargo_volume_m3').value = row.cargo_volume_m3 ?? '';
    document.getElementById('edit_notes').value = row.notes || '';
    document.getElementById('editVehicleModal').classList.remove('hidden');
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
