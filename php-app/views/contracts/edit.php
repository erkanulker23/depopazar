<?php
$currentPage = 'girisler';
$contract = $contract ?? [];
$monthlyPricesByKey = $monthlyPricesByKey ?? [];
$paidMonths = $paidMonths ?? [];
$monthlyPriceDisplay = number_format((float) ($contract['monthly_price'] ?? 0), 2, ',', '.');
ob_start();
?>
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium">Sözleşme detayı</a>
        <i class="bi bi-chevron-right"></i>
        <span class="text-gray-700 dark:text-gray-300 font-medium">Düzenle</span>
    </div>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Sözleşme Düzenle</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($contract['contract_number'] ?? '') ?></p>
</div>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form method="post" action="/girisler/guncelle" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-4xl">
    <input type="hidden" name="contract_id" value="<?= htmlspecialchars($contract['id'] ?? '') ?>">
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç Tarihi</label>
                <input type="date" name="start_date" id="edit_start_date" value="<?= !empty($contract['start_date']) ? date('Y-m-d', strtotime($contract['start_date'])) : '' ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş Tarihi</label>
                <input type="date" name="end_date" id="edit_end_date" value="<?= !empty($contract['end_date']) ? date('Y-m-d', strtotime($contract['end_date'])) : '' ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 space-y-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Depo / Oda</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars(trim(($contract['warehouse_name'] ?? '') . ' — Oda ' . ($contract['room_number'] ?? ''))) ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Varsayılan Aylık Ücret (₺)</label>
                <input type="text" name="monthly_price" id="edit_monthly_price" value="<?= htmlspecialchars($monthlyPriceDisplay) ?>" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sözleşme dönemindeki aylar için temel tutar. Aşağıdan ay bazında özelleştirebilirsiniz.</p>
            </div>
            <div id="edit_monthly_prices_section">
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2"><i class="bi bi-calendar-month text-emerald-600"></i> Aylık Fiyatlar</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Başlangıç–bitiş tarihlerine göre her ay için kira tutarını düzenleyin. Ödemesi alınmış ayların fiyatı değiştirilemez.</p>
                <div id="edit_monthly_prices_list" class="space-y-2 max-h-56 overflow-y-auto pr-1"></div>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nakliye Ücreti (₺)</label>
                <input type="text" name="transportation_fee" value="<?= htmlspecialchars($contract['transportation_fee'] ?? '0') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İndirim (₺)</label>
                <input type="text" name="discount" value="<?= htmlspecialchars($contract['discount'] ?? '0') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Eşyanın Alındığı Yer</label>
            <input type="text" name="pickup_location" value="<?= htmlspecialchars($contract['pickup_location'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şoför Adı</label>
                <input type="text" name="driver_name" value="<?= htmlspecialchars($contract['driver_name'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şoför Telefon</label>
                <input type="text" name="driver_phone" value="<?= htmlspecialchars($contract['driver_phone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Araç Plakası</label>
                <input type="text" name="vehicle_plate" value="<?= htmlspecialchars($contract['vehicle_plate'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Giriş Yapılan Ürün Durumu <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                <?php $currentCondition = $contract['stored_items_condition'] ?? ''; ?>
                <?php foreach (storedItemsConditionOptions() as $code => $label): ?>
                    <label class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/30 dark:has-[:checked]:border-emerald-500">
                        <input type="radio" name="stored_items_condition" value="<?= htmlspecialchars($code) ?>" required <?= $currentCondition === $code ? 'checked' : '' ?> class="rounded-full border-gray-300 dark:border-gray-500 text-emerald-600 focus:ring-emerald-500" onchange="toggleEditStoredItemsConditionNote(this.value)">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-200"><?= htmlspecialchars($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div id="edit_stored_items_condition_note_block" class="<?= ($currentCondition === 'hasarli') ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasar Notu <span class="text-red-500">*</span></label>
                <textarea name="stored_items_condition_note" id="edit_stored_items_condition_note" rows="2" placeholder="Hasarın açıklamasını yazın..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white" <?= ($currentCondition === 'hasarli') ? 'required' : '' ?>><?= htmlspecialchars($contract['stored_items_condition_note'] ?? '') ?></textarea>
            </div>
        </div>
        <?php require __DIR__ . '/_stored_items_form.php'; ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($contract['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="form-submit-bar mt-6 flex flex-wrap gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>" class="btn-touch px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">İptal</a>
        <button type="submit" class="btn-touch px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
    </div>
</form>

<script>
function toggleEditStoredItemsConditionNote(value) {
    var block = document.getElementById('edit_stored_items_condition_note_block');
    var note = document.getElementById('edit_stored_items_condition_note');
    var show = value === 'hasarli';
    if (block) block.classList.toggle('hidden', !show);
    if (note) {
        note.required = show;
        if (!show) note.value = '';
    }
}

(function() {
    var monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    var existingMonthlyPrices = <?= json_encode($monthlyPricesByKey, JSON_UNESCAPED_UNICODE) ?>;
    var paidMonthSet = new Set(<?= json_encode(array_values($paidMonths), JSON_UNESCAPED_UNICODE) ?>);

    function formatPriceInput(num) {
        if (num === null || num === undefined || num === '') return '';
        var n = parseFloat(String(num).replace(',', '.'));
        if (isNaN(n)) return '';
        return n.toFixed(2).replace('.', ',');
    }

    function buildEditMonthlyPricesList() {
        var startEl = document.getElementById('edit_start_date');
        var endEl = document.getElementById('edit_end_date');
        var list = document.getElementById('edit_monthly_prices_list');
        var defaultPriceEl = document.getElementById('edit_monthly_price');
        if (!startEl || !endEl || !list) return;

        var startStr = startEl.value;
        var endStr = endEl.value;
        if (!startStr || !endStr) {
            list.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">Başlangıç ve bitiş tarihlerini seçin.</p>';
            return;
        }

        var start = new Date(startStr + 'T00:00:00');
        var end = new Date(endStr + 'T00:00:00');
        if (end < start) {
            list.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400">Bitiş tarihi başlangıçtan önce olamaz.</p>';
            return;
        }

        var defaultVal = defaultPriceEl && defaultPriceEl.value ? defaultPriceEl.value.replace(',', '.') : '';
        var currentValues = {};
        list.querySelectorAll('input[name^="monthly_prices"]').forEach(function(inp) {
            var match = inp.name.match(/monthly_prices\[(\d{4}-\d{2})\]/);
            if (match) currentValues[match[1]] = inp.value;
        });
        var months = [];
        var d = new Date(start.getFullYear(), start.getMonth(), 1);
        var endFirst = new Date(end.getFullYear(), end.getMonth(), 1);
        while (d <= endFirst) {
            months.push({
                y: d.getFullYear(),
                m: d.getMonth(),
                key: d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0')
            });
            d.setMonth(d.getMonth() + 1);
        }

        list.innerHTML = '';
        months.forEach(function(item) {
            var existing = currentValues[item.key] !== undefined
                ? currentValues[item.key]
                : (existingMonthlyPrices[item.key] !== undefined && existingMonthlyPrices[item.key] !== null
                    ? formatPriceInput(existingMonthlyPrices[item.key])
                    : (defaultVal ? formatPriceInput(defaultVal) : ''));
            var isPaid = paidMonthSet.has(item.key);
            var row = document.createElement('div');
            row.className = 'flex items-center gap-3' + (isPaid ? ' opacity-90' : '');
            var inputClass = 'flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm ' +
                (isPaid
                    ? 'bg-gray-100 dark:bg-gray-600/50 text-gray-600 dark:text-gray-300 cursor-not-allowed'
                    : 'focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white');
            row.innerHTML = '<label class="w-28 text-sm text-gray-700 dark:text-gray-300 shrink-0">' + monthNames[item.m] + ' ' + item.y + '</label>' +
                '<input type="text" name="monthly_prices[' + item.key + ']" value="' + existing + '" placeholder="0,00"' +
                (isPaid ? ' readonly' : '') + ' class="' + inputClass + '">' +
                '<span class="text-gray-500 dark:text-gray-400 text-sm shrink-0">₺</span>' +
                (isPaid ? '<span class="text-xs font-medium text-green-700 dark:text-green-400 shrink-0">Ödeme alındı</span>' : '');
            list.appendChild(row);
        });
    }

    var startEl = document.getElementById('edit_start_date');
    var endEl = document.getElementById('edit_end_date');
    var defaultPriceEl = document.getElementById('edit_monthly_price');
    if (startEl) startEl.addEventListener('change', buildEditMonthlyPricesList);
    if (endEl) endEl.addEventListener('change', buildEditMonthlyPricesList);
    if (defaultPriceEl) {
        defaultPriceEl.addEventListener('blur', function() {
            var list = document.getElementById('edit_monthly_prices_list');
            if (!list) return;
            var val = this.value.replace(',', '.');
            list.querySelectorAll('input[name^="monthly_prices"]').forEach(function(inp) {
                if (inp.readOnly) return;
                if (!inp.value || inp.value === '0' || inp.value === '0,00') {
                    inp.value = val ? formatPriceInput(val) : '';
                }
            });
        });
    }
    buildEditMonthlyPricesList();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
