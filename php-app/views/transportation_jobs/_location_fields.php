<?php
/** @var string $fieldPrefix pickup|delivery */
/** @var string $idPrefix newJob|edit */
/** @var list<array<string, mixed>> $warehouses */
/** @var array{source_type?: string, warehouse_id?: string, address_detail?: string, preview?: string} $parsed */
$sourceType = $parsed['source_type'] ?? 'evden';
$warehouseId = $parsed['warehouse_id'] ?? '';
$addressDetail = $parsed['address_detail'] ?? '';
$depoPreview = $parsed['preview'] ?? '';
$hasWarehouses = !empty($warehouses);
?>
<div class="transport-location-fields" data-field-prefix="<?= htmlspecialchars($fieldPrefix) ?>" data-id-prefix="<?= htmlspecialchars($idPrefix) ?>">
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Konum türünü seçin. <strong>Depo</strong> seçerseniz sistemde kayıtlı depolardan birini seçmelisiniz.</p>
    <div class="flex flex-wrap gap-2 mb-4" id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_type_group">
        <?php foreach (['evden' => 'Evden', 'ofisten' => 'Ofisten', 'depo' => 'Depo'] as $typeVal => $typeLabel): ?>
            <?php $active = $sourceType === $typeVal; ?>
            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transport-location-type-label <?= $active ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-gray-300 dark:border-gray-600' ?>">
                <input type="radio" name="<?= htmlspecialchars($fieldPrefix) ?>_source_type" value="<?= htmlspecialchars($typeVal) ?>" class="text-emerald-600 focus:ring-emerald-500 transport-location-type-radio" <?= $active ? 'checked' : '' ?>>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($typeLabel) ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_address_block" class="space-y-3 <?= $sourceType === 'depo' ? 'hidden' : '' ?>">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İl / İlçe</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <select id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_il" class="transport-location-il w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    <option value="">İl seçin</option>
                </select>
                <select name="<?= htmlspecialchars($fieldPrefix) ?>_ilce" id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_ilce" class="transport-location-ilce w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Önce il seçin</option>
                </select>
            </div>
            <input type="hidden" name="<?= htmlspecialchars($fieldPrefix) ?>_il_name" id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_il_name" value="">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres <span class="text-red-500 transport-location-detail-required">*</span></label>
            <textarea name="<?= htmlspecialchars($fieldPrefix) ?>_address_detail" id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_address_detail" rows="2" placeholder="Mahalle, sokak, bina no..." class="transport-location-detail w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($addressDetail) ?></textarea>
        </div>
    </div>

    <div id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_depo_block" class="space-y-2 <?= ($sourceType !== 'depo' || !$hasWarehouses) ? 'hidden' : '' ?>">
        <?php if ($hasWarehouses): ?>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo seçin <span class="text-red-500">*</span></label>
            <select name="<?= htmlspecialchars($fieldPrefix) ?>_warehouse_id" id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_warehouse_id" class="transport-location-warehouse w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <option value="">Depo seçin</option>
                <?php foreach ($warehouses as $wh): ?>
                    <?php $whAddr = formatWarehouseAddress($wh); ?>
                    <option value="<?= htmlspecialchars($wh['id']) ?>" data-address="<?= htmlspecialchars($whAddr) ?>" <?= $warehouseId === (string) ($wh['id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($wh['name'] ?? '') ?><?= $whAddr !== ($wh['name'] ?? '') ? ' — ' . htmlspecialchars($whAddr) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800">
                <p class="text-xs text-emerald-700 dark:text-emerald-400 mb-1">Seçilen depo adresi</p>
                <p id="<?= htmlspecialchars($idPrefix) ?>_<?= htmlspecialchars($fieldPrefix) ?>_depo_preview" class="text-sm font-medium text-emerald-900 dark:text-emerald-200"><?= htmlspecialchars($depoPreview !== '' ? $depoPreview : 'Depo seçin') ?></p>
            </div>
        <?php else: ?>
            <p class="text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-3 py-2">Depo seçimi için önce <a href="/depolar" class="underline font-medium">Depolar</a> sayfasından depo ekleyin.</p>
        <?php endif; ?>
    </div>
</div>
