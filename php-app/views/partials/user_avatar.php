<?php
/** @var array|null $userRow */
/** @var string $size sm|md|lg */
$userRow = $userRow ?? null;
$size = $size ?? 'md';
$sizeClasses = [
    'sm' => 'w-8 h-8 text-xs',
    'md' => 'w-10 h-10 text-sm',
    'lg' => 'w-16 h-16 text-lg',
];
$cls = $sizeClasses[$size] ?? $sizeClasses['md'];
$photo = userPhotoHref($userRow);
$initials = userInitials($userRow);
$name = trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? ''));
?>
<?php if ($photo): ?>
    <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($name) ?>" class="<?= htmlspecialchars($cls) ?> rounded-full object-cover ring-2 ring-white dark:ring-gray-800 shrink-0 bg-gray-100 dark:bg-gray-700">
<?php else: ?>
    <span class="<?= htmlspecialchars($cls) ?> rounded-full shrink-0 inline-flex items-center justify-center font-bold bg-emerald-600 text-white ring-2 ring-white dark:ring-gray-800" aria-hidden="true"><?= htmlspecialchars($initials) ?></span>
<?php endif; ?>
