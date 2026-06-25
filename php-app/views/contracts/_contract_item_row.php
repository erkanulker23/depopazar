<?php
/** @var array $item */
/** @var int $rowNum */
$item = $item ?? [];
$rowNum = $rowNum ?? 1;
?>
<tr class="contract-item-row">
    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400"><?= (int) $rowNum ?></td>
    <td class="px-3 py-2">
        <input type="text" name="item_name[]" value="<?= htmlspecialchars($item['name'] ?? '') ?>" placeholder="Örn: Koltuk takımı" class="w-full min-w-[120px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
    </td>
    <td class="px-3 py-2">
        <?= itemConditionSelectHtml($item['condition'] ?? 'sifir') ?>
    </td>
    <td class="px-3 py-2">
        <input type="number" name="item_quantity[]" value="<?= (int) ($item['quantity'] ?? 1) ?>" min="1" class="w-20 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
    </td>
    <td class="px-3 py-2">
        <input type="text" name="item_unit[]" value="<?= htmlspecialchars($item['unit'] ?? 'adet') ?>" placeholder="adet" class="w-24 px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
    </td>
    <td class="px-3 py-2">
        <input type="text" name="item_description[]" value="<?= htmlspecialchars($item['description'] ?? '') ?>" placeholder="Renk, boyut vb." class="w-full min-w-[100px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
    </td>
    <td class="px-3 py-2">
        <button type="button" class="remove-contract-item text-red-600 hover:text-red-700 p-1" title="Satır sil"><i class="bi bi-trash"></i></button>
    </td>
</tr>
