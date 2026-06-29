<?php
/** @var array<int, array<string, mixed>> $personnelList */
/** @var array<int, string> $selectedPersonnelIds */
/** @var string $pickerId */
/** @var string|null $emptyMessage */
$personnelList = $personnelList ?? [];
$selectedPersonnelIds = $selectedPersonnelIds ?? [];
$pickerId = $pickerId ?? 'personnel_picker';
$emptyMessage = $emptyMessage ?? 'Aktif saha personeli bulunamadı.';
$personnelGroups = groupPersonnelByJobType($personnelList);
$totalCount = count($personnelList);
?>
<div id="<?= htmlspecialchars($pickerId) ?>" class="personnel-grouped-picker space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Görevlerine göre gruplandı. Birden fazla personel seçebilirsiniz.
            <a href="/personel" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Personel yönetimi</a>
        </p>
        <span class="personnel-pick-count inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <i class="bi bi-check2-circle"></i>
            <span class="personnel-pick-count-num">0</span> / <?= (int) $totalCount ?> seçili
        </span>
    </div>
    <?php if ($personnelGroups === []): ?>
        <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center rounded-xl border border-dashed border-gray-300 dark:border-gray-600">
            <?= htmlspecialchars($emptyMessage) ?>
            <a href="/personel" class="text-emerald-600 dark:text-emerald-400 hover:underline">Personel ekleyin</a>.
        </p>
    <?php else: ?>
        <div class="space-y-4 max-h-[22rem] overflow-y-auto pr-1">
            <?php foreach ($personnelGroups as $group): ?>
                <?php
                $badgeClass = personnelJobTypeBadgeClass($group['job_type']);
                $memberCount = count($group['members']);
                ?>
                <section class="rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800/80 overflow-hidden">
                    <header class="flex items-center justify-between gap-2 px-3 py-2.5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-700/40">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg <?= htmlspecialchars($badgeClass) ?>">
                                <i class="bi <?= htmlspecialchars($group['icon']) ?>"></i>
                            </span>
                            <div class="min-w-0">
                                <h5 class="text-sm font-bold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($group['label']) ?></h5>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wide"><?= (int) $memberCount ?> personel</p>
                            </div>
                        </div>
                    </header>
                    <div class="p-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                        <?php foreach ($group['members'] as $person):
                            $checked = in_array($person['id'] ?? '', $selectedPersonnelIds, true);
                            $style = 'card';
                            require __DIR__ . '/personnel_checkbox_row.php';
                        endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>
(function() {
    var root = document.getElementById(<?= json_encode($pickerId) ?>);
    if (!root) return;
    function updatePersonnelPickCount() {
        var checked = root.querySelectorAll('.personnel-pick-checkbox:checked').length;
        var numEl = root.querySelector('.personnel-pick-count-num');
        if (numEl) numEl.textContent = String(checked);
        var badge = root.querySelector('.personnel-pick-count');
        if (badge) {
            badge.classList.toggle('bg-emerald-100', checked > 0);
            badge.classList.toggle('text-emerald-800', checked > 0);
            badge.classList.toggle('dark:bg-emerald-900/30', checked > 0);
            badge.classList.toggle('dark:text-emerald-300', checked > 0);
            badge.classList.toggle('bg-gray-100', checked === 0);
            badge.classList.toggle('text-gray-600', checked === 0);
            badge.classList.toggle('dark:bg-gray-700', checked === 0);
            badge.classList.toggle('dark:text-gray-300', checked === 0);
        }
    }
    root.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('personnel-pick-checkbox')) {
            updatePersonnelPickCount();
        }
    });
    updatePersonnelPickCount();
})();
</script>
