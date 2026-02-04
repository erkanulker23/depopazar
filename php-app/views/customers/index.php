<?php
$currentPage = 'musteriler';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$customers = $customers ?? [];
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Müşteriler</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Müşteri yönetimi</p>
</div>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <form method="get" action="/musteriler" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ad, e-posta veya telefon ara..." class="flex-1 min-w-0 sm:w-56 px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        <button type="submit" class="btn-touch px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600">Filtrele</button>
        <?php if ($q !== ''): ?><a href="/musteriler" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm">Temizle</a><?php endif; ?>
    </form>
    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <a href="/musteriler/excel-disari-aktar<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <i class="bi bi-file-earmark-excel"></i> Excel Dışa Aktar
        </a>
        <a href="/musteriler/excel-ice-aktar" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <i class="bi bi-file-earmark-arrow-up"></i> Excel İçe Aktar
        </a>
        <button type="button" onclick="document.getElementById('addCustomerModal').classList.remove('hidden')" class="btn-touch w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <i class="bi bi-plus-lg mr-2"></i> Yeni Müşteri
        </button>
    </div>
</div>

<?php if (isset($flashSuccess) && $flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (isset($flashError) && $flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($customers)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz müşteri kaydı yok<?= $q !== '' ? ' veya arama sonucu bulunamadı.' : '.' ?></div>
    <?php else: ?>
        <!-- Mobil: kart listesi -->
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($customers as $c): ?>
                <div class="p-4 active:bg-gray-50 dark:active:bg-gray-700/50" data-customer-id="<?= htmlspecialchars($c['id']) ?>">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-300 font-bold text-sm">
                            <?= strtoupper(mb_substr($c['first_name'] ?? '?', 0, 1) . mb_substr($c['last_name'] ?? '', 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>" class="font-semibold text-gray-900 dark:text-white block truncate"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></a>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate mt-0.5"><?= htmlspecialchars($c['email'] ?? '–') ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($c['phone'] ?? '–') ?></p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <?php if (!empty($c['is_active'])): ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                                <?php endif; ?>
                                <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Detay →</a>
                                <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>/barkod" target="_blank" class="text-sm font-medium text-gray-600 dark:text-gray-400">Barkod</a>
                            </div>
                        </div>
                        <button type="button" class="expand-row-mobile flex-shrink-0 p-2 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600" aria-label="Detayı aç"><i class="bi bi-chevron-down expand-icon-mobile text-lg"></i></button>
                    </div>
                    <div class="expandable-mobile hidden mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 fragment-cell-mobile"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Masaüstü: tablo -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest w-10"></th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ad Soyad</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">E-posta</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Telefon</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($customers as $c):
                        $detail = [];
                        if (!empty($c['phone'])) $detail[] = 'Tel: ' . $c['phone'];
                        if (!empty($c['email'])) $detail[] = 'E-posta: ' . $c['email'];
                        if (!empty($c['identity_number'])) $detail[] = 'TC: ' . $c['identity_number'];
                        if (!empty($c['address'])) $detail[] = 'Adres: ' . $c['address'];
                        if (!empty($c['notes'])) $detail[] = 'Not: ' . $c['notes'];
                        $detailStr = implode("\n", $detail);
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" data-customer-id="<?= htmlspecialchars($c['id']) ?>">
                            <td class="px-4 py-3">
                                <button type="button" class="expand-row p-1.5 rounded-lg text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white" title="Detayı göster" aria-expanded="false">
                                    <i class="bi bi-chevron-down expand-icon"></i>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($c['email'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($c['is_active'])): ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="/musteriler/<?= htmlspecialchars($c['id']) ?>" class="inline-flex items-center px-2 py-1 rounded-lg text-sm text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 mr-1">Detay</a>
                            </td>
                        </tr>
                        <tr class="expandable-row hidden" id="expand-<?= htmlspecialchars($c['id']) ?>"><td colspan="6" class="p-0 fragment-cell"></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Yeni Müşteri -->
<div id="addCustomerModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addCustomerModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Müşteri Ekle</h3>
                <button type="button" onclick="document.getElementById('addCustomerModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/musteriler/ekle">
                <div class="space-y-3">
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta</label>
                        <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                        <input type="text" name="phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">TC Kimlik No</label>
                        <input type="text" name="identity_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adres</label>
                        <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addCustomerModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    function loadFragment(id, cell, icon, isOpen) {
        if (cell.innerHTML === '') {
            fetch('/musteriler/' + id + '/satir-detay').then(function(r){ return r.text(); }).then(function(html){
                cell.innerHTML = html;
            });
        }
        if (icon) { icon.classList.toggle('bi-chevron-down', !isOpen); icon.classList.toggle('bi-chevron-up', isOpen); }
    }
    document.querySelectorAll('.expand-row').forEach(function(btn){
        btn.addEventListener('click', function(){
            var tr = this.closest('tr');
            var id = tr.getAttribute('data-customer-id');
            var expandRow = document.getElementById('expand-' + id);
            var cell = expandRow && expandRow.querySelector('.fragment-cell');
            var icon = this.querySelector('.expand-icon');
            if (!expandRow || !cell) return;
            var isOpen = !expandRow.classList.contains('hidden');
            if (!isOpen) {
                loadFragment(id, cell, icon, true);
                expandRow.classList.remove('hidden');
                btn.setAttribute('aria-expanded', 'true');
            } else {
                expandRow.classList.add('hidden');
                if (icon) { icon.classList.remove('bi-chevron-up'); icon.classList.add('bi-chevron-down'); }
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    });
    document.querySelectorAll('.expand-row-mobile').forEach(function(btn){
        btn.addEventListener('click', function(){
            var card = this.closest('[data-customer-id]');
            var id = card && card.getAttribute('data-customer-id');
            var panel = card && card.querySelector('.expandable-mobile');
            var cell = panel && panel.querySelector('.fragment-cell-mobile');
            var icon = this.querySelector('.expand-icon-mobile');
            if (!panel || !cell) return;
            var isOpen = !panel.classList.contains('hidden');
            if (isOpen) {
                panel.classList.add('hidden');
                if (icon) { icon.classList.remove('bi-chevron-up'); icon.classList.add('bi-chevron-down'); }
            } else {
                loadFragment(id, cell, icon, true);
                panel.classList.remove('hidden');
            }
        });
    });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
