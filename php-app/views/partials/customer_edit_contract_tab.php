<?php
/** @var array $customer */
/** @var array $warehouses */
/** @var array $contractRoomsJson */
$customer = $customer ?? [];
$warehouses = $warehouses ?? [];
$contractRoomsJson = $contractRoomsJson ?? [];
$customerId = $customer['id'] ?? '';
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
?>
<form method="post" action="/girisler/ekle" id="custContractForm" enctype="multipart/form-data" class="space-y-4">
    <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customerId) ?>">
    <input type="hidden" name="redirect" value="/musteriler/<?= htmlspecialchars($customerId) ?>">
    <p class="text-sm text-gray-600 dark:text-gray-300 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 px-3 py-2">
        <i class="bi bi-person-check text-emerald-600 mr-1"></i>
        Müşteri: <strong><?= htmlspecialchars($customerName) ?></strong>
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo <span class="text-red-500">*</span></label>
            <select name="warehouse_id" id="custContract_warehouse" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <option value="">Depo seçin</option>
                <?php foreach ($warehouses as $w): ?>
                    <?php $whFee = isset($w['monthly_base_fee']) && $w['monthly_base_fee'] !== null && $w['monthly_base_fee'] !== '' ? (float) $w['monthly_base_fee'] : null; ?>
                    <option value="<?= htmlspecialchars($w['id']) ?>" data-monthly-base-fee="<?= $whFee !== null ? htmlspecialchars(number_format((float) $whFee, 2, '.', '')) : '' ?>"><?= htmlspecialchars($w['name'] ?? '') ?><?= $whFee !== null ? ' (' . fmtPrice($whFee) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="custContract_room_search">Oda <span class="text-red-500">*</span></label>
            <input type="hidden" name="room_id" id="custContract_room_id" value="">
            <div class="relative">
                <input type="search" id="custContract_room_search" placeholder="Önce depo seçin" autocomplete="off" disabled class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white opacity-60 cursor-not-allowed">
                <div id="custContract_room_results" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-52 overflow-y-auto"></div>
            </div>
            <p id="custContract_room_hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Önce depo seçin</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç <span class="text-red-500">*</span></label>
            <input type="date" name="start_date" id="custContract_start_date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş <span class="text-red-500">*</span></label>
            <input type="date" name="end_date" id="custContract_end_date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aylık ücret (₺) <span class="text-red-500">*</span></label>
            <input type="text" name="monthly_price" id="custContract_monthly_price" required placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
    </div>
    <div>
        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Giriş yapılan ürün durumu <span class="text-red-500">*</span></h4>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2">
            <?php foreach (storedItemsConditionOptions() as $code => $label): ?>
                <label class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20">
                    <input type="radio" name="stored_items_condition" value="<?= htmlspecialchars($code) ?>" required class="rounded-full border-gray-300 text-emerald-600 focus:ring-emerald-500" onchange="toggleCustContractConditionNote(this.value)">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-200"><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div id="custContract_condition_note_block" class="hidden">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasar notu <span class="text-red-500">*</span></label>
            <textarea name="stored_items_condition_note" id="custContract_condition_note" rows="2" placeholder="Hasarın açıklamasını yazın..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
        </div>
    </div>
    <div>
        <?php $items = []; $storedItemsFormCompact = true; require __DIR__ . '/../contracts/_stored_items_form.php'; ?>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
    </div>
    <div class="form-submit-bar flex justify-end gap-2 pt-2 border-t border-gray-200 dark:border-gray-600">
        <button type="button" onclick="switchEditCustomerTab('info')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">Geri</button>
        <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-cyan-600 text-white font-medium hover:bg-cyan-700">Sözleşme Oluştur</button>
    </div>
</form>
