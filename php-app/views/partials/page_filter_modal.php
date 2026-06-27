<?php
/**
 * Filtre modalı — form alanları $filterModalBody içinde gelir.
 *
 * @var string      $filterModalId
 * @var string      $filterFormId
 * @var string      $filterFormAction
 * @var string      $filterFormMethod
 * @var string|null $filterClearUrl
 * @var string      $filterSubmitLabel
 * @var string      $filterModalTitle
 * @var string      $filterModalBody   HTML
 * @var string      $filterModalClass  ek sınıflar (örn. screen-only)
 * @var string      $filterFormClass
 */
$filterModalId = $filterModalId ?? 'pageFilterModal';
$filterFormId = $filterFormId ?? 'pageFilterForm';
$filterFormAction = $filterFormAction ?? '';
$filterFormMethod = $filterFormMethod ?? 'get';
$filterClearUrl = $filterClearUrl ?? null;
$filterSubmitLabel = $filterSubmitLabel ?? 'Uygula';
$filterModalTitle = $filterModalTitle ?? 'Filtreler';
$filterModalBody = $filterModalBody ?? '';
$filterModalClass = $filterModalClass ?? '';
$filterFormClass = $filterFormClass ?? '';
?>
<div id="<?= htmlspecialchars($filterModalId) ?>" class="filter-modal-overlay modal-overlay page-filter-modal hidden fixed inset-0 z-50 overflow-y-auto <?= htmlspecialchars($filterModalClass) ?>" aria-hidden="true" role="dialog" aria-labelledby="<?= htmlspecialchars($filterModalId) ?>Title">
    <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
        <button type="button" class="filter-modal-backdrop fixed inset-0 bg-black/50 cursor-default" tabindex="-1" aria-hidden="true" onclick="closeFilterModal('<?= htmlspecialchars($filterModalId, ENT_QUOTES) ?>')"></button>
        <div class="filter-modal-panel relative w-full sm:max-w-lg bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 flex flex-col max-h-[min(92vh,640px)]">
            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <h2 id="<?= htmlspecialchars($filterModalId) ?>Title" class="text-base font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($filterModalTitle) ?></h2>
                <button type="button" onclick="closeFilterModal('<?= htmlspecialchars($filterModalId, ENT_QUOTES) ?>')" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" aria-label="Kapat">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form method="<?= htmlspecialchars($filterFormMethod) ?>" action="<?= htmlspecialchars($filterFormAction) ?>" id="<?= htmlspecialchars($filterFormId) ?>" class="filter-modal-form flex flex-col flex-1 min-h-0 <?= htmlspecialchars($filterFormClass) ?>">
                <div class="filter-modal-body p-4 space-y-4 overflow-y-auto flex-1">
                    <?= $filterModalBody ?>
                </div>
                <div class="filter-modal-footer flex flex-wrap items-center gap-2 px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 shrink-0 rounded-b-2xl">
                    <button type="submit" class="btn-touch btn-filter flex-1 sm:flex-none min-w-[120px]">
                        <i class="bi bi-check-lg text-sm opacity-90" aria-hidden="true"></i>
                        <?= htmlspecialchars($filterSubmitLabel) ?>
                    </button>
                    <button type="button" onclick="closeFilterModal('<?= htmlspecialchars($filterModalId, ENT_QUOTES) ?>')" class="btn-touch flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">İptal</button>
                    <?php if ($filterClearUrl !== null && $filterClearUrl !== ''): ?>
                        <a href="<?= htmlspecialchars($filterClearUrl) ?>" class="btn-touch flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm text-center hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Sıfırla</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
