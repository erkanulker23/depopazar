<?php
/** @var array $warehouses */
/** @var string|null $selectedWarehouseId */
/** @var string $fieldPrefix edit|add */
/** @var bool $isSuperAdminCompanySelect */
$warehouses = $warehouses ?? [];
$selectedWarehouseId = $selectedWarehouseId ?? null;
$fieldPrefix = $fieldPrefix ?? 'user';
$isSuperAdminCompanySelect = $isSuperAdminCompanySelect ?? false;
$selectId = $fieldPrefix . '_managed_warehouse_id';
$wrapId = $fieldPrefix . '_managed_warehouse_wrap';
?>
<div id="<?= htmlspecialchars($wrapId) ?>" class="hidden">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="<?= htmlspecialchars($selectId) ?>">Sorumlu depo <span class="text-red-500">*</span></label>
    <select name="managed_warehouse_id" id="<?= htmlspecialchars($selectId) ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        <option value="">Depo seçin</option>
        <?php foreach ($warehouses as $wh): ?>
            <option value="<?= htmlspecialchars($wh['id']) ?>" data-company-id="<?= htmlspecialchars($wh['company_id'] ?? '') ?>" <?= ($selectedWarehouseId ?? '') === ($wh['id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($wh['name'] ?? '') ?></option>
        <?php endforeach; ?>
    </select>
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Depo sorumlusu yalnızca seçilen depo ile ilgili bildirim ve e-posta alır.</p>
</div>
