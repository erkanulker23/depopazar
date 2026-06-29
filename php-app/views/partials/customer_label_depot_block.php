<?php
/** @var array $depot */
/** @var bool $compact */
$depot = $depot ?? [];
$compact = !empty($compact);
$hideLogo = !empty($hideLogo);
$name = trim((string) ($depot['name'] ?? ''));
$location = trim(implode(' / ', array_filter([
    trim((string) ($depot['district'] ?? '')),
    trim((string) ($depot['city'] ?? '')),
], fn($p) => $p !== '')));
$rooms = $depot['room_numbers'] ?? [];
$logo = warehouseLogoHref($depot);
?>
<div class="flex items-start gap-3">
    <?php if ($logo && !$hideLogo): ?>
        <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($name !== '' ? $name : 'Depo') ?>" class="<?= $compact ? 'w-8 h-8' : 'w-10 h-10' ?> rounded-lg object-contain bg-white border border-gray-200 shrink-0 p-0.5">
    <?php endif; ?>
    <div class="min-w-0 flex-1">
    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1"><?= $compact ? 'Depo' : 'Depo bilgileri' ?></h2>
    <p class="font-semibold text-gray-900"><?= htmlspecialchars($name !== '' ? $name : 'Depo') ?></p>
    <?php if ($rooms !== []): ?>
        <p class="text-gray-600 <?= $compact ? 'text-xs' : 'text-sm' ?> mt-0.5">
            Oda: <?= htmlspecialchars(implode(', ', $rooms)) ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($depot['address'])): ?>
        <p class="text-gray-600 <?= $compact ? 'text-xs' : 'text-sm' ?> mt-0.5"><?= nl2br(htmlspecialchars((string) $depot['address'])) ?></p>
    <?php endif; ?>
    <?php if ($location !== ''): ?>
        <p class="text-gray-600 <?= $compact ? 'text-xs' : 'text-sm' ?>"><?= htmlspecialchars($location) ?></p>
    <?php endif; ?>
    <?php if (!$compact && !empty($depot['description'])): ?>
        <p class="text-gray-500 text-xs mt-1"><?= nl2br(htmlspecialchars((string) $depot['description'])) ?></p>
    <?php endif; ?>
    </div>
</div>
