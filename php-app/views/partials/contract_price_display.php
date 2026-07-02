<?php
/** @var array $contract */
/** @var float|int|string $enteredPrice Girilen tutar (KDV dahil veya hariç) */
/** @var array|null $company */
/** @var bool $showCharge Tahsil edilecek brüt tutarı göster */
$enteredPrice = (float) ($enteredPrice ?? 0);
$company = $company ?? null;
$showCharge = $showCharge ?? true;
$includesVat = contractPriceIncludesVat($contract);
$rate = contractVatRate($contract, $company);
$gross = contractGrossFromEntered($enteredPrice, $contract, $company);
$breakdown = contractVatBreakdown($gross, $contract, $company);
?>
<span class="font-medium"><?= fmtPrice($enteredPrice) ?></span>
<span class="text-xs text-gray-500 dark:text-gray-400">(<?= htmlspecialchars(contractVatStatusLabel($contract)) ?>, %<?= htmlspecialchars(rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',')) ?>)</span>
<?php if ($showCharge && !$includesVat && $enteredPrice > 0): ?>
    <span class="block text-xs text-gray-600 dark:text-gray-400 mt-0.5">Tahsil: <?= fmtPrice($gross) ?> (KDV: <?= fmtPrice($breakdown['vat']) ?>)</span>
<?php elseif ($showCharge && $includesVat && $enteredPrice > 0 && $rate > 0): ?>
    <span class="block text-xs text-gray-600 dark:text-gray-400 mt-0.5">Net: <?= fmtPrice($breakdown['net']) ?> · KDV: <?= fmtPrice($breakdown['vat']) ?></span>
<?php endif; ?>
