<?php
/** @var array|null $personnel */
/** @var string $size sm|md|lg */
$personnel = $personnel ?? null;
$size = $size ?? 'md';
$sizeClasses = [
    'sm' => 'w-8 h-8 text-xs',
    'md' => 'w-10 h-10 text-sm',
    'lg' => 'w-14 h-14 text-base',
];
$cls = $sizeClasses[$size] ?? $sizeClasses['md'];
$photo = personnelPhotoHref($personnel);
$initials = personnelInitials($personnel);
$name = trim(($personnel['first_name'] ?? '') . ' ' . ($personnel['last_name'] ?? ''));
?>
<?php if ($photo): ?>
    <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($name) ?>" class="<?= htmlspecialchars($cls) ?> rounded-full object-cover ring-2 ring-white dark:ring-gray-800 shrink-0 bg-gray-100 dark:bg-gray-700">
<?php else: ?>
    <span class="<?= htmlspecialchars($cls) ?> rounded-full shrink-0 inline-flex items-center justify-center font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 ring-2 ring-white dark:ring-gray-800" aria-hidden="true"><?= htmlspecialchars($initials) ?></span>
<?php endif; ?>
