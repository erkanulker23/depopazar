<?php
/** @var array $contract */
/** @var float|int|string $grossAmount Tahsil tutarı (payments.amount) */
/** @var array|null $company */
/** @var bool $compact Kısa metin (yine blok; satır taşmasını önler) */
$grossAmount = (float) ($grossAmount ?? 0);
$company = $company ?? null;
$compact = $compact ?? false;
$includesVat = contractPriceIncludesVat($contract);
$rate = contractVatRate($contract, $company);
?>
<span class="contract-amount-display block min-w-0 max-w-full">
    <span class="block font-medium leading-snug break-words"><?= fmtPrice($grossAmount) ?></span>
    <?php if (!$includesVat): ?>
        <span class="block text-xs text-gray-500 dark:text-gray-400 leading-snug">(Tahsil · KDV Dahil)</span>
        <?php if ($grossAmount > 0 && $rate > 0):
            $breakdown = contractVatBreakdown($grossAmount, $contract, $company);
        ?>
            <span class="block text-xs text-gray-600 dark:text-gray-400 mt-0.5 leading-snug break-words">
                <?php if ($compact): ?>
                    Net <?= fmtPrice($breakdown['net']) ?>
                <?php else: ?>
                    Girilen fiyat KDV hariç · Net: <?= fmtPrice($breakdown['net']) ?> · KDV: <?= fmtPrice($breakdown['vat']) ?>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    <?php endif; ?>
</span>
