<?php
/** @var array $contract */
/** @var float|int|string $grossAmount Tahsil tutarı (payments.amount) */
/** @var array|null $company */
/** @var bool $compact Tek satır gösterim */
$grossAmount = (float) ($grossAmount ?? 0);
$company = $company ?? null;
$compact = $compact ?? false;
$breakdown = contractVatBreakdown($grossAmount, $contract, $company);
$includesVat = contractPriceIncludesVat($contract);
$rate = contractVatRate($contract, $company);
?>
<span class="font-medium"><?= fmtPrice($grossAmount) ?></span>
<span class="text-xs text-gray-500 dark:text-gray-400">(Tahsil · KDV Dahil)</span>
<?php if ($grossAmount > 0 && $rate > 0): ?>
    <?php if ($compact): ?>
        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">· Net <?= fmtPrice($breakdown['net']) ?></span>
    <?php else: ?>
        <span class="block text-xs text-gray-600 dark:text-gray-400 mt-0.5">
            <?php if (!$includesVat): ?>
                Girilen fiyat KDV hariç · Net: <?= fmtPrice($breakdown['net']) ?> · KDV: <?= fmtPrice($breakdown['vat']) ?>
            <?php else: ?>
                Net: <?= fmtPrice($breakdown['net']) ?> · KDV (%<?= htmlspecialchars(rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',')) ?>): <?= fmtPrice($breakdown['vat']) ?>
            <?php endif; ?>
        </span>
    <?php endif; ?>
<?php endif; ?>
