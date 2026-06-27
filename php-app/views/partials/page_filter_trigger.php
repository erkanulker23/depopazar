<?php
/**
 * Filtre modal tetikleyici butonu + aktif filtre etiketleri.
 *
 * @var string $filterModalId
 * @var bool   $hasActiveFilters
 * @var array  $activeFilterTags  string[]
 * @var string|null $filterClearUrl
 * @var string $filterTriggerClass  ek sınıflar (örn. screen-only)
 */
$filterModalId = $filterModalId ?? 'pageFilterModal';
$hasActiveFilters = $hasActiveFilters ?? false;
$activeFilterTags = $activeFilterTags ?? [];
$filterClearUrl = $filterClearUrl ?? null;
$filterTriggerClass = $filterTriggerClass ?? '';
$activeFilterCount = count(array_filter($activeFilterTags, static fn($t) => $t !== null && $t !== ''));
?>
<div class="flex flex-wrap items-center gap-2 <?= htmlspecialchars($filterTriggerClass) ?>">
    <button type="button" onclick="openFilterModal('<?= htmlspecialchars($filterModalId, ENT_QUOTES) ?>')" class="btn-touch btn-filter inline-flex items-center gap-1.5">
        <i class="bi bi-funnel-fill text-sm opacity-90" aria-hidden="true"></i>
        <span>Filtrele</span>
        <?php if ($activeFilterCount > 0): ?>
            <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-white/25 text-[11px] font-bold leading-none"><?= $activeFilterCount ?></span>
        <?php endif; ?>
    </button>
    <?php if ($hasActiveFilters && $filterClearUrl !== null && $filterClearUrl !== ''): ?>
        <a href="<?= htmlspecialchars($filterClearUrl) ?>" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">Temizle</a>
    <?php endif; ?>
</div>
<?php if ($hasActiveFilters && !empty($activeFilterTags)): ?>
<div class="flex flex-wrap items-center gap-1.5 mb-3 text-xs <?= htmlspecialchars($filterTriggerClass) ?>">
    <?php foreach ($activeFilterTags as $tag): ?>
        <?php if ($tag === null || $tag === '') continue; ?>
        <span class="px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/50"><?= htmlspecialchars($tag) ?></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>
