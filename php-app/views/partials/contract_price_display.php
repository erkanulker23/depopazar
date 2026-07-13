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
?>
<span class="contract-price-display block min-w-0 max-w-full">
    <span class="block font-medium leading-snug break-words"><?= fmtPrice($enteredPrice) ?></span>
    <?php if (!$includesVat): ?>
        <span class="block text-xs text-gray-500 dark:text-gray-400 leading-snug break-words">(<?= htmlspecialchars(contractVatStatusLabel($contract)) ?>, %<?= htmlspecialchars(rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',')) ?>)</span>
        <?php if ($showCharge && $enteredPrice > 0):
            $gross = contractGrossFromEntered($enteredPrice, $contract, $company);
            $breakdown = contractVatBreakdown($gross, $contract, $company);
        ?>
            <span class="block text-xs text-gray-600 dark:text-gray-400 mt-0.5 leading-snug break-words">Tahsil: <?= fmtPrice($gross) ?> (KDV: <?= fmtPrice($breakdown['vat']) ?>)</span>
        <?php endif; ?>
    <?php endif; ?>
</span>
