<?php
/** @var array $contract */
/** @var float|int|string $grossAmount Tahsil tutarı (payments.amount) */
/** @var array|null $company */
/** @var bool $compact Kısa metin (yine blok; satır taşmasını önler) */
$grossAmount = (float) ($grossAmount ?? 0);
$company = $company ?? null;
$compact = $compact ?? false;
$breakdown = contractVatBreakdown($grossAmount, $contract, $company);
$includesVat = contractPriceIncludesVat($contract);
$rate = contractVatRate($contract, $company);
?>
<span class="contract-amount-display block min-w-0 max-w-full">
    <span class="block font-medium leading-snug break-words"><?= fmtPrice($grossAmount) ?></span>
    <span class="block text-xs text-gray-500 dark:text-gray-400 leading-snug">(Tahsil · KDV Dahil)</span>
    <?php if ($grossAmount > 0 && $rate > 0): ?>
        <span class="block text-xs text-gray-600 dark:text-gray-400 mt-0.5 leading-snug break-words">
            <?php if ($compact): ?>
                Net <?= fmtPrice($breakdown['net']) ?>
            <?php elseif (!$includesVat): ?>
                Girilen fiyat KDV hariç · Net: <?= fmtPrice($breakdown['net']) ?> · KDV: <?= fmtPrice($breakdown['vat']) ?>
            <?php else: ?>
                Net: <?= fmtPrice($breakdown['net']) ?> · KDV (%<?= htmlspecialchars(rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',')) ?>): <?= fmtPrice($breakdown['vat']) ?>
            <?php endif; ?>
        </span>
    <?php endif; ?>
</span>
