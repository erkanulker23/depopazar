<?php
$csvUrl = $csvUrl ?? null;
$csvLabel = $csvLabel ?? 'Excel Dışa Aktar';
require __DIR__ . '/report_print_styles.php';
?>
<div class="no-print flex flex-wrap items-center gap-2 mb-4">
    <?php if (!empty($csvUrl)): ?>
    <a href="<?= htmlspecialchars($csvUrl) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
        <i class="bi bi-file-earmark-excel"></i> <?= htmlspecialchars($csvLabel) ?>
    </a>
    <?php endif; ?>
    <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
        <i class="bi bi-printer"></i> Yazdır / PDF
    </button>
</div>
