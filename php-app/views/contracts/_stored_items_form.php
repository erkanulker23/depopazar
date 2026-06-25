<?php
$items = $items ?? [];
$storedItemsFormCompact = !empty($storedItemsFormCompact);
?>
<div class="<?= $storedItemsFormCompact ? '' : 'border-t border-gray-200 dark:border-gray-600 pt-4 mt-2' ?>">
    <?php if (!$storedItemsFormCompact): ?>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1 flex items-center gap-2">
        <i class="bi bi-box-seam text-emerald-600"></i> Depo Eşya Listesi
    </h3>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Depoya giren ürün ve eşyaları satır satır ekleyin.</p>
    <?php endif; ?>
    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200 dark:border-gray-600 rounded-xl" id="contractItemsTable">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">#</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Eşya Adı</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Adet</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Birim</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                    <th class="px-3 py-2 w-10"></th>
                </tr>
            </thead>
            <tbody id="contractItemsBody">
                <?php foreach ($items as $i => $item): ?>
                <tr class="contract-item-row">
                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400"><?= $i + 1 ?></td>
                    <td class="px-3 py-2">
                        <input type="text" name="item_name[]" value="<?= htmlspecialchars($item['name'] ?? '') ?>" placeholder="Örn: Koltuk takımı" class="w-full min-w-[140px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" name="item_quantity[]" value="<?= (int) ($item['quantity'] ?? 1) ?>" min="1" class="w-20 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" name="item_unit[]" value="<?= htmlspecialchars($item['unit'] ?? 'adet') ?>" placeholder="adet" class="w-24 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" name="item_description[]" value="<?= htmlspecialchars($item['description'] ?? '') ?>" placeholder="Renk, boyut vb." class="w-full min-w-[120px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </td>
                    <td class="px-3 py-2">
                        <button type="button" class="remove-contract-item text-red-600 hover:text-red-700 p-1" title="Satır sil"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr class="contract-item-row">
                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">1</td>
                    <td class="px-3 py-2">
                        <input type="text" name="item_name[]" placeholder="Örn: Koltuk takımı" class="w-full min-w-[140px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" name="item_quantity[]" value="1" min="1" class="w-20 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" name="item_unit[]" value="adet" placeholder="adet" class="w-24 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" name="item_description[]" placeholder="Renk, boyut vb." class="w-full min-w-[120px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </td>
                    <td class="px-3 py-2">
                        <button type="button" class="remove-contract-item text-red-600 hover:text-red-700 p-1" title="Satır sil"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <button type="button" id="addContractItemRow" class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
        <i class="bi bi-plus-lg"></i> Satır Ekle
    </button>
</div>
<?php if (empty($storedItemsFormSkipScript)): ?>
<script>
(function() {
    if (window.__contractItemsFormInit) return;
    window.__contractItemsFormInit = true;
    document.addEventListener('click', function(e) {
        if (e.target.closest('#addContractItemRow')) {
            var tbody = document.getElementById('contractItemsBody');
            if (!tbody) return;
            var n = tbody.querySelectorAll('.contract-item-row').length + 1;
            var tr = document.createElement('tr');
            tr.className = 'contract-item-row';
            tr.innerHTML =
                '<td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">' + n + '</td>' +
                '<td class="px-3 py-2"><input type="text" name="item_name[]" placeholder="Örn: Koltuk takımı" class="w-full min-w-[140px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>' +
                '<td class="px-3 py-2"><input type="number" name="item_quantity[]" value="1" min="1" class="w-20 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>' +
                '<td class="px-3 py-2"><input type="text" name="item_unit[]" value="adet" placeholder="adet" class="w-24 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>' +
                '<td class="px-3 py-2"><input type="text" name="item_description[]" placeholder="Renk, boyut vb." class="w-full min-w-[120px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"></td>' +
                '<td class="px-3 py-2"><button type="button" class="remove-contract-item text-red-600 hover:text-red-700 p-1" title="Satır sil"><i class="bi bi-trash"></i></button></td>';
            tbody.appendChild(tr);
            window.renumberContractItems && window.renumberContractItems();
        }
        if (e.target.closest('.remove-contract-item')) {
            var row = e.target.closest('.contract-item-row');
            var body = document.getElementById('contractItemsBody');
            if (row && body && body.querySelectorAll('.contract-item-row').length > 1) {
                row.remove();
                window.renumberContractItems && window.renumberContractItems();
            }
        }
    });
    window.renumberContractItems = function() {
        var tbody = document.getElementById('contractItemsBody');
        if (!tbody) return;
        tbody.querySelectorAll('.contract-item-row').forEach(function(r, i) {
            var cell = r.querySelector('td:first-child');
            if (cell) cell.textContent = i + 1;
        });
    };
})();
</script>
<?php endif; ?>
