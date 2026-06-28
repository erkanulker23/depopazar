<?php
/** @var array $personnelList */
/** @var string $size sm|md */
/** @var bool $showNames */
$personnelList = $personnelList ?? [];
$size = $size ?? 'sm';
$showNames = $showNames ?? true;
if ($personnelList === []) {
    echo '–';
    return;
}
?>
<div class="flex flex-wrap items-center gap-2">
    <?php foreach ($personnelList as $person): ?>
        <?php
        $name = trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''));
        $personnel = $person;
        ?>
        <span class="inline-flex items-center gap-1.5" title="<?= htmlspecialchars($name) ?>">
            <?php require __DIR__ . '/personnel_avatar.php'; ?>
            <?php if ($showNames): ?>
                <span class="text-sm text-gray-700 dark:text-gray-200"><?= htmlspecialchars($name) ?></span>
            <?php endif; ?>
        </span>
    <?php endforeach; ?>
</div>
