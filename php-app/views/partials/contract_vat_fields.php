<?php
/** @var string $prefix Form alanı id öneki (newSale, edit, custContract) */
/** @var array|null $company */
/** @var array|null $contract Düzenleme modunda mevcut sözleşme */
$prefix = $prefix ?? 'contract';
$company = $company ?? null;
$contract = $contract ?? null;
$defaultRate = companyDefaultVatRate($company);
$vatRate = $contract !== null ? contractVatRate($contract, $company) : $defaultRate;
$includesVat = $contract !== null ? contractPriceIncludesVat($contract) : true;
?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:col-span-2">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">KDV Oranı (%)</label>
        <input type="number" name="vat_rate" id="<?= htmlspecialchars($prefix) ?>_vat_rate" value="<?= htmlspecialchars(rtrim(rtrim(number_format($vatRate, 2, '.', ''), '0'), '.')) ?>" step="0.01" min="0" max="100" class="contract-vat-rate w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
    </div>
    <div class="flex flex-col justify-end">
        <label class="inline-flex items-center gap-2 cursor-pointer mb-2">
            <input type="checkbox" name="price_includes_vat" id="<?= htmlspecialchars($prefix) ?>_price_includes_vat" value="1" <?= $includesVat ? 'checked' : '' ?> class="contract-price-includes-vat rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">KDV Dahil</span>
        </label>
        <p class="text-xs text-gray-500 dark:text-gray-400">İşaretli değilse girilen tutar KDV hariç kabul edilir; ödeme tutarına KDV eklenir.</p>
    </div>
    <div class="sm:col-span-2">
        <p id="<?= htmlspecialchars($prefix) ?>_vat_preview" class="text-xs text-emerald-700 dark:text-emerald-300 hidden rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 px-3 py-2"></p>
    </div>
</div>
