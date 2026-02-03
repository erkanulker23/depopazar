<?php
$currentPage = 'hizmetler';
$categories = $categories ?? [];
$services = $services ?? [];
$kategoriGet = isset($_GET['kategori']) ? $_GET['kategori'] : '';
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Hizmetler</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Hizmet kategorileri ve hizmetler</p>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<!-- Filtre + Kategori ekle -->
<div class="flex flex-wrap items-center gap-3 mb-4">
    <form method="get" action="/hizmetler" class="flex flex-wrap items-center gap-2">
        <select name="kategori" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
            <option value="">Tüm kategoriler</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['id']) ?>" <?= $kategoriGet === $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium">Filtrele</button>
    </form>
    <button type="button" onclick="document.getElementById('addCategoryModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
        <i class="bi bi-plus-lg mr-2"></i> Kategori Ekle
    </button>
    <button type="button" onclick="document.getElementById('addServiceModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 rounded-xl border border-emerald-600 text-emerald-600 dark:text-emerald-400 text-sm font-medium hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
        <i class="bi bi-plus-lg mr-2"></i> Hizmet Ekle
    </button>
</div>

<!-- Kategoriler -->
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-200 dark:border-gray-700">Hizmet Kategorileri</h2>
    <?php if (empty($categories)): ?>
        <div class="p-6 text-center text-gray-500 dark:text-gray-400">Henüz kategori yok. "Kategori Ekle" ile ekleyin.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($cat['name']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($cat['description'] ?? '') ?></td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" onclick='openEditCat(<?= json_encode($cat) ?>)' class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 mr-1">Düzenle</button>
                                <form method="post" action="/hizmetler/kategori/sil" class="inline" onsubmit="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($cat['id']) ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Hizmetler -->
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-200 dark:border-gray-700">Hizmetler</h2>
    <?php if (empty($services)): ?>
        <div class="p-6 text-center text-gray-500 dark:text-gray-400">Henüz hizmet yok veya filtreye uygun hizmet bulunamadı. "Hizmet Ekle" ile ekleyin.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Hizmet</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Birim Fiyat</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Birim</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($s['name'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($s['category_name'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= number_format((float)($s['unit_price'] ?? 0), 2, ',', '.') ?> ₺</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($s['unit'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" onclick='openEditSvc(<?= json_encode($s) ?>)' class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 mr-1">Düzenle</button>
                                <form method="post" action="/hizmetler/hizmet/sil" class="inline" onsubmit="return confirm('Bu hizmeti silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($s['id']) ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Kategori Ekle -->
<div id="addCategoryModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addCategoryModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kategori Ekle</h3>
            <form method="post" action="/hizmetler/kategori/ekle">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori Adı *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kategori Düzenle -->
<div id="editCategoryModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('editCategoryModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kategori Düzenle</h3>
            <form method="post" action="/hizmetler/kategori/guncelle">
                <input type="hidden" name="id" id="editCat_id">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori Adı *</label>
                        <input type="text" name="name" id="editCat_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" id="editCat_description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('editCategoryModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Hizmet Ekle -->
<div id="addServiceModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addServiceModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Hizmet Ekle</h3>
            <form method="post" action="/hizmetler/hizmet/ekle">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori *</label>
                        <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                            <option value="">Seçin</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hizmet Adı *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Birim Fiyat (₺)</label>
                        <input type="text" name="unit_price" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Birim</label>
                        <input type="text" name="unit" placeholder="adet, m², vb." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addServiceModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Hizmet Düzenle -->
<div id="editServiceModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('editServiceModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Hizmet Düzenle</h3>
            <form method="post" action="/hizmetler/hizmet/guncelle">
                <input type="hidden" name="id" id="editSvc_id">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                        <select name="category_id" id="editSvc_category_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hizmet Adı *</label>
                        <input type="text" name="name" id="editSvc_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Birim Fiyat (₺)</label>
                        <input type="text" name="unit_price" id="editSvc_unit_price" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Birim</label>
                        <input type="text" name="unit" id="editSvc_unit" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" id="editSvc_description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('editServiceModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditCat(d) {
    document.getElementById('editCat_id').value = d.id || '';
    document.getElementById('editCat_name').value = d.name || '';
    document.getElementById('editCat_description').value = d.description || '';
    document.getElementById('editCategoryModal').classList.remove('hidden');
}
function openEditSvc(d) {
    document.getElementById('editSvc_id').value = d.id || '';
    document.getElementById('editSvc_category_id').value = d.category_id || '';
    document.getElementById('editSvc_name').value = d.name || '';
    document.getElementById('editSvc_unit_price').value = d.unit_price ?? '';
    document.getElementById('editSvc_unit').value = d.unit || '';
    document.getElementById('editSvc_description').value = d.description || '';
    document.getElementById('editServiceModal').classList.remove('hidden');
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
