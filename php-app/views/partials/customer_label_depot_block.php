<?php
/** @var array $depot */
/** @var bool $compact */
$depot = $depot ?? [];
$compact = !empty($compact);
$name = trim((string) ($depot['name'] ?? ''));
$location = trim(implode(' / ', array_filter([
    trim((string) ($depot['district'] ?? '')),
    trim((string) ($depot['city'] ?? '')),
], fn($p) => $p !== '')));
$rooms = $depot['room_numbers'] ?? [];
?>
<div>
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
