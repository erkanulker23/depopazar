<?php
$currentPage = 'teklifler';
$customers = $customers ?? [];
$services = $services ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
$statusLabels = ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul', 'rejected' => 'Red'];
ob_start();
?>
<div class="mb-6">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/teklifler" class="text-emerald-600 dark:text-emerald-400 hover:underline">Teklifler</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Yeni Teklif Oluştur</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Yeni Teklif Oluştur</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Tüm detayları doldurarak teklif oluşturun</p>
</div>

<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <form method="post" action="/teklifler/ekle" id="proposalForm" class="p-6">
        <div class="space-y-6">
            <!-- Teklif türü -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teklif türü <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="proposal_type" value="depo" class="rounded-full border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-gray-700 dark:text-gray-300">Depo Teklifi</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="proposal_type" value="nakliye" checked class="rounded-full border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-gray-700 dark:text-gray-300">Nakliye Teklifi</span>
                    </label>
                </div>
            </div>
            <!-- Başlık + Müşteri + Durum -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlık <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="Teklif" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Müşteri</label>
                    <select name="customer_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Müşteri seçin (opsiyonel)</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ($c['email'] ? ' – ' . $c['email'] : '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <?php foreach ($statusLabels as $val => $l): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $val === 'draft' ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Eşya / Hizmet Alınacak Yer -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-geo-alt text-emerald-600"></i> Eşya / Hizmet Alınacak Adres</h4>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres</label>
                <textarea name="pickup_address" rows="3" placeholder="İl, İlçe, Mahalle, Sokak, Bina No vb." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
            </div>

            <!-- Eşya / Hizmet Teslim Adresi -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-geo-alt text-green-600"></i> Eşya / Hizmet Teslim Adresi</h4>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres</label>
                <textarea name="delivery_address" rows="3" placeholder="İl, İlçe, Mahalle, Sokak, Bina No vb." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
            </div>

            <!-- Kalemler (Hizmet / Ürün satırları) -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-list-ul text-emerald-600"></i> Teklif Kalemleri</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Her satır için hizmet adı, miktar ve birim fiyat girin. Toplam otomatik hesaplanır.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 dark:border-gray-600 rounded-xl" id="itemsTable">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Hizmet / Açıklama</th>
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Miktar</th>
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Birim Fiyat (₺)</th>
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php for ($i = 0; $i < 3; $i++): ?>
                            <tr class="item-row">
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400"><?= $i + 1 ?></td>
                                <td class="px-3 py-2">
                                    <input type="text" name="item_name[]" placeholder="Hizmet adı" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                                    <input type="hidden" name="item_service_id[]" value="">
                                </td>
                                <td class="px-3 py-2"><input type="text" name="item_quantity[]" value="1" placeholder="1" class="w-20 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>
                                <td class="px-3 py-2"><input type="text" name="item_unit_price[]" value="0" placeholder="0,00" class="w-24 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>
                                <td class="px-3 py-2"><input type="text" name="item_description[]" placeholder="Not" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>
                                <td class="px-3 py-2"><button type="button" class="remove-item text-red-600 hover:text-red-700 p-1" title="Satır sil"><i class="bi bi-trash"></i></button></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="addItemRow" class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="bi bi-plus-lg"></i> Satır Ekle
                </button>
                <?php if (!empty($services)): ?>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Hızlı ekle: <select id="quickAddService" class="inline-block px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white">
                    <option value="">Hizmet seçip ekle...</option>
                    <?php foreach ($services as $sv): ?>
                        <option value="<?= htmlspecialchars($sv['id']) ?>" data-name="<?= htmlspecialchars($sv['name'] ?? '') ?>" data-price="<?= htmlspecialchars($sv['unit_price'] ?? '0') ?>"><?= htmlspecialchars($sv['name'] ?? '') ?> (<?= number_format((float)($sv['unit_price'] ?? 0), 2, ',', '.') ?> ₺)</option>
                    <?php endforeach; ?>
                </select></p>
                <?php endif; ?>
            </div>

            <!-- Toplam + Geçerlilik + Notlar -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Özet</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Toplam Tutar (₺)</label>
                        <input type="text" name="total_amount" id="total_amount" value="0" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">Boş bırakırsanız kalemlerden hesaplanır.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Geçerlilik Tarihi</label>
                        <input type="date" name="valid_until" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                    <textarea name="notes" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="/teklifler" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">İptal</a>
            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Teklif Oluştur</button>
        </div>
    </form>
</div>

<script>
(function() {
    var tbody = document.getElementById('itemsBody');
    var addBtn = document.getElementById('addItemRow');
    var totalInput = document.getElementById('total_amount');
    function rowNumber() {
        var rows = tbody.querySelectorAll('.item-row');
        rows.forEach(function(r, i) { r.querySelector('td:first-child').textContent = i + 1; });
    }
    function addRow() {
        var n = tbody.querySelectorAll('.item-row').length + 1;
        var tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = '<td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">' + n + '</td>' +
            '<td class="px-3 py-2"><input type="text" name="item_name[]" placeholder="Hizmet adı" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"><input type="hidden" name="item_service_id[]" value=""></td>' +
            '<td class="px-3 py-2"><input type="text" name="item_quantity[]" value="1" class="w-20 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>' +
            '<td class="px-3 py-2"><input type="text" name="item_unit_price[]" value="0" class="w-24 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>' +
            '<td class="px-3 py-2"><input type="text" name="item_description[]" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>' +
            '<td class="px-3 py-2"><button type="button" class="remove-item text-red-600 hover:text-red-700 p-1" title="Satır sil"><i class="bi bi-trash"></i></button></td>';
        tbody.appendChild(tr);
        tr.querySelector('.remove-item').addEventListener('click', function() { tr.remove(); rowNumber(); });
        rowNumber();
    }
    addBtn && addBtn.addEventListener('click', addRow);
    tbody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.item-row').remove();
            rowNumber();
        }
    });
    var quickAdd = document.getElementById('quickAddService');
    if (quickAdd) quickAdd.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;
        var name = opt.getAttribute('data-name') || '';
        var price = opt.getAttribute('data-price') || '0';
        addRow();
        var rows = tbody.querySelectorAll('.item-row');
        var last = rows[rows.length - 1];
        last.querySelector('input[name="item_name[]"]').value = name;
        last.querySelector('input[name="item_unit_price[]"]').value = price.replace('.', ',');
        last.querySelector('input[name="item_service_id[]"]').value = opt.value;
        this.selectedIndex = 0;
    });
    rowNumber();
})();
</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
