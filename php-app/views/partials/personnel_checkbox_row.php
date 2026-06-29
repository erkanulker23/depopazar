<?php
/** @var array $person */
/** @var array|null $jobTypeLabels */
/** @var bool $checked */
/** @var string $style pill|row */
/** @var bool $showSelectedBadge */
/** @var string $inputName */
$person = $person ?? [];
$jobTypeLabels = $jobTypeLabels ?? Personnel::jobTypeLabels();
$checked = !empty($checked);
$style = $style ?? 'row';
$showSelectedBadge = !empty($showSelectedBadge);
$inputName = $inputName ?? 'personnel_ids[]';
$jobType = $jobTypeLabels[$person['job_type'] ?? 'diger'] ?? 'Diğer';
$name = trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''));
$id = $person['id'] ?? '';
$personnel = $person;
$size = $style === 'card' ? 'md' : 'sm';
?>
<?php if ($style === 'pill'): ?>
<label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600">
    <input type="checkbox" name="<?= htmlspecialchars($inputName) ?>" value="<?= htmlspecialchars($id) ?>" <?= $checked ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 shrink-0">
    <?php require __DIR__ . '/personnel_avatar.php'; ?>
    <span class="text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($name) ?></span>
    <span class="text-xs text-gray-500 dark:text-gray-400">(<?= htmlspecialchars($jobType) ?>)</span>
</label>
<?php elseif ($style === 'card'): ?>
<label class="personnel-pick-card group relative flex flex-col items-center gap-2 p-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-sm has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 has-[:checked]:shadow-md transition-all text-center min-h-[6.5rem]">
    <input type="checkbox" name="<?= htmlspecialchars($inputName) ?>" value="<?= htmlspecialchars($id) ?>" <?= $checked ? 'checked' : '' ?> class="personnel-pick-checkbox absolute top-2 right-2 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
    <?php require __DIR__ . '/personnel_avatar.php'; ?>
    <span class="text-sm font-medium text-gray-900 dark:text-white leading-tight px-1"><?= htmlspecialchars($name) ?></span>
    <?php if (!empty($person['phone'])): ?>
        <span class="text-[10px] text-gray-500 dark:text-gray-400"><?= htmlspecialchars(formatPhoneDisplay($person['phone'])) ?></span>
    <?php endif; ?>
</label>
<?php else: ?>
<label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer">
    <input type="checkbox" name="<?= htmlspecialchars($inputName) ?>" value="<?= htmlspecialchars($id) ?>" <?= $checked ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 shrink-0">
    <?php require __DIR__ . '/personnel_avatar.php'; ?>
    <span class="text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($name) ?></span>
    <span class="text-xs px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"><?= htmlspecialchars($jobType) ?></span>
    <?php if ($showSelectedBadge && $checked): ?><span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">(seçili)</span><?php endif; ?>
</label>
<?php endif; ?>
