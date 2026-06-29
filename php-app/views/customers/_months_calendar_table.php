<?php
/** @var array $monthsCalendar */
/** @var array $monthNames */
$monthsStatus = $monthsCalendar['months'] ?? [];
$minYear = (int) ($monthsCalendar['minYear'] ?? date('Y'));
$maxYear = (int) ($monthsCalendar['maxYear'] ?? date('Y'));
if ($maxYear < $minYear): ?>
    <p class="text-sm text-gray-500 dark:text-gray-400">Bu sözleşme için ödeme dönemi yok.</p>
<?php else: ?>
<table class="min-w-full border border-gray-200 dark:border-gray-600 text-sm">
    <thead class="bg-gray-50 dark:bg-gray-700/50">
        <tr>
            <th class="border border-gray-200 dark:border-gray-600 px-2 py-2 text-left font-bold text-gray-700 dark:text-gray-300">Yıl</th>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <th class="border border-gray-200 dark:border-gray-600 px-2 py-2 text-center font-bold text-gray-700 dark:text-gray-300"><?= $monthNames[$m - 1] ?></th>
            <?php endfor; ?>
        </tr>
    </thead>
    <tbody>
        <?php for ($year = $maxYear; $year >= $minYear; $year--): ?>
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
            <td class="border border-gray-200 dark:border-gray-600 px-2 py-2 font-medium text-gray-900 dark:text-white"><?= $year ?></td>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <?php
                $key = $year . '-' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                $info = $monthsStatus[$key] ?? null;
                $status = $info['status'] ?? null;
                $label = $info ? $info['label'] : '–';
                $bg = match ($status) {
                    'paid' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                    'partial' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                    'overdue' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
                    'pending' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300',
                    default => 'bg-gray-50 dark:bg-gray-700/30 text-gray-500 dark:text-gray-400',
                };
                $title = $info ? fmtPrice($info['amount'] ?? 0) : '';
                ?>
                <td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5 text-center <?= $bg ?>" title="<?= htmlspecialchars($title) ?>"><?= htmlspecialchars($label) ?></td>
            <?php endfor; ?>
        </tr>
        <?php endfor; ?>
    </tbody>
</table>
<?php endif; ?>
