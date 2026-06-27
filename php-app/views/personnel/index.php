<?php
$currentPage = 'personel';
$personnel = $personnel ?? [];
$jobTypeLabels = $jobTypeLabels ?? Personnel::jobTypeLabels();
$tableExists = $tableExists ?? false;
$canManage = $canManage ?? false;
$companies = $companies ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Personel</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Saha personeli (sisteme giriş yapmaz)</p>
</div>

<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div data-flash-error class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<?php if (!$tableExists): ?>
    <div class="mb-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 text-sm">
        Personel modülü için veritabanı migration'ı henüz çalıştırılmamış. Deploy sonrası <code class="text-xs">php artisan migrate --force</code> çalıştırın.
    </div>
<?php else: ?>

<?php
$qGet = isset($_GET['q']) ? trim($_GET['q']) : '';
$jobTypeGet = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$activeGet = isset($_GET['is_active']) ? $_GET['is_active'] : '';
?>
<form method="get" action="/personel" class="mb-4 flex flex-wrap items-center gap-2">
    <input type="search" name="q" value="<?= htmlspecialchars($qGet) ?>" placeholder="Ad, soyad, telefon..." class="flex-1 min-w-0 sm:w-56 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    <select name="job_type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        <option value="">Tüm Görevler</option>
        <?php foreach ($jobTypeLabels as $key => $label): ?>
            <option value="<?= htmlspecialchars($key) ?>" <?= $jobTypeGet === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="is_active" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        <option value="">Tüm Durumlar</option>
        <option value="1" <?= $activeGet === '1' ? 'selected' : '' ?>>Aktif</option>
        <option value="0" <?= $activeGet === '0' ? 'selected' : '' ?>>Pasif</option>
    </select>
    <button type="submit" class="btn-touch btn-filter"><i class="bi bi-funnel-fill text-sm opacity-90" aria-hidden="true"></i> Filtrele</button>
    <?php if ($qGet !== '' || $jobTypeGet !== '' || $activeGet !== ''): ?>
        <a href="/personel" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm">Temizle</a>
    <?php endif; ?>
</form>

<?php if ($canManage): ?>
<div class="mb-4">
    <button type="button" onclick="openAddPersonnelModal()" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
        <i class="bi bi-plus-lg mr-2"></i> Personel Ekle
    </button>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
    <?php if (empty($personnel)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz personel kaydı yok. Şoför, taşımacı vb. saha personelini buradan ekleyin.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ad Soyad</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Görev</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Telefon</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <?php if ($canManage): ?><th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($personnel as $p): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars(trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))) ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"><?= htmlspecialchars($jobTypeLabels[$p['job_type'] ?? 'diger'] ?? 'Diğer') ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($p['phone'] ?? '–') ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($p['is_active'])): ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManage): ?>
                            <td class="px-4 py-3 text-right">
                                <button type="button" onclick='openEditPersonnelModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)' class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 mr-1" title="Düzenle"><i class="bi bi-pencil"></i></button>
                                <form method="post" action="/personel/sil" class="inline" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('personel')) ?>);">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100" title="Sil"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($canManage): ?>
<!-- Modal: Personel Ekle -->
<div id="addPersonnelModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeAddPersonnelModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Personel Ekle</h3>
                <button type="button" onclick="closeAddPersonnelModal()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/personel/ekle" id="addPersonnelForm" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soyad <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Görev</label>
                    <select name="job_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <?php foreach ($jobTypeLabels as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                <?php if (!empty($companies)): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şirket <span class="text-red-500">*</span></label>
                    <select name="company_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Seçin</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                </label>
                <div class="form-submit-bar flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeAddPersonnelModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Personel Düzenle -->
<div id="editPersonnelModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeEditPersonnelModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Personel Düzenle</h3>
                <button type="button" onclick="closeEditPersonnelModal()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/personel/guncelle" id="editPersonnelForm" class="space-y-3">
                <input type="hidden" name="id" id="editPersonnel_id">
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
function openAddPersonnelModal() {
    var modal = document.getElementById('addPersonnelModal');
    if (!modal) return;
    var form = document.getElementById('addPersonnelForm');
    if (form) {
        if (window.resetSubmitForm) window.resetSubmitForm(form);
        form.reset();
        var active = form.querySelector('input[name="is_active"]');
        if (active) active.checked = true;
    }
    modal.classList.remove('hidden');
}
function closeAddPersonnelModal() {
    var modal = document.getElementById('addPersonnelModal');
    if (modal) modal.classList.add('hidden');
}
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

<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = 'Personel';
require __DIR__ . '/../layout.php';
