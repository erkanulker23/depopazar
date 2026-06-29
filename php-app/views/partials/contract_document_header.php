<?php
/** @var array|null $warehouse */
/** @var array|null $company */
/** @var array $contract */
/** @var string $customerName */
$warehouse = $warehouse ?? null;
$company = $company ?? null;
$contract = $contract ?? [];
$customerName = $customerName ?? '';

$depotName = trim((string) ($warehouse['name'] ?? $contract['warehouse_name'] ?? ''));
$roomNumber = trim((string) ($contract['room_number'] ?? ''));
$depotAddress = trim((string) ($warehouse['address'] ?? ''));
$location = trim(implode(' / ', array_filter([
    trim((string) ($warehouse['district'] ?? '')),
    trim((string) ($warehouse['city'] ?? '')),
], fn($p) => $p !== '')));
$depotDescription = trim((string) ($warehouse['description'] ?? ''));
$hasDepot = $depotName !== '' || $warehouse !== null;
?>
<?php if ($warehouse): ?>
<div class="mb-4 flex items-center gap-3">
    <?php $size = 'lg'; require __DIR__ . '/warehouse_logo.php'; ?>
</div>
<?php elseif ($company && !empty($company['logo_url'])): ?>
<div class="mb-4"><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-14 object-contain"></div>
<?php endif; ?>
<h1 class="text-xl font-bold text-center text-gray-900 mb-6">Depolama Sözleşmesi</h1>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2"><?= $hasDepot ? 'Depo' : 'Firma' ?></h2>
        <?php if ($hasDepot): ?>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($depotName !== '' ? $depotName : '-') ?></p>
            <?php if ($roomNumber !== ''): ?>
                <p class="text-sm text-gray-600">Oda: <?= htmlspecialchars($roomNumber) ?></p>
            <?php endif; ?>
            <?php if ($depotAddress !== ''): ?>
                <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($depotAddress)) ?></p>
            <?php endif; ?>
            <?php if ($location !== ''): ?>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($location) ?></p>
            <?php endif; ?>
            <?php if ($depotDescription !== ''): ?>
                <p class="text-sm text-gray-500"><?= nl2br(htmlspecialchars($depotDescription)) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? 'Firma Adı') ?></p>
            <?php if (!empty($company['address'])): ?><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($company['phone'])): ?><p class="text-sm text-gray-600<?= $hasDepot ? ' mt-1' : '' ?>">Tel: <?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
        <?php if (!empty($company['email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($company['email']) ?></p><?php endif; ?>
    </div>
    <div>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri</h2>
        <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName ?: '-') ?></p>
        <?php if (!empty($contract['customer_email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($contract['customer_email']) ?></p><?php endif; ?>
        <?php if (!empty($contract['customer_phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($contract['customer_phone']) ?></p><?php endif; ?>
    </div>
</div>
