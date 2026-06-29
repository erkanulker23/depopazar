<?php
/** @var array|null $warehouse */
/** @var string $size sm|md|lg */
$warehouse = $warehouse ?? null;
$size = $size ?? 'md';
$sizeClasses = [
    'sm' => 'w-10 h-10 text-xs',
    'md' => 'w-12 h-12 text-sm',
    'lg' => 'w-16 h-16 text-base',
];
$cls = $sizeClasses[$size] ?? $sizeClasses['md'];
$logo = warehouseLogoHref($warehouse);
$initials = warehouseInitials($warehouse);
$name = trim((string) ($warehouse['name'] ?? ''));
?>
<?php if ($logo): ?>
    <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($name !== '' ? $name : 'Depo') ?>" class="<?= htmlspecialchars($cls) ?> rounded-xl object-contain bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 shrink-0 p-0.5">
<?php else: ?>
    <span class="<?= htmlspecialchars($cls) ?> rounded-xl shrink-0 inline-flex items-center justify-center font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" title="<?= htmlspecialchars($name) ?>" aria-hidden="true"><?= htmlspecialchars($initials) ?></span>
<?php endif; ?>
