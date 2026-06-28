<?php
/**
 * @var string      $printTitle
 * @var string|null $companyName
 * @var array       $printMeta   [['label' => 'Dönem', 'value' => '...'], ...]
 * @var array|null  $printSummary ['headers' => [...], 'values' => [...]]
 */
$printTitle = $printTitle ?? 'Rapor';
$companyName = $companyName ?? null;
$printMeta = $printMeta ?? [];
$printSummary = $printSummary ?? null;
?>
<div class="print-only report-print-header">
    <div class="report-print-brand">
        <h1><?= htmlspecialchars($printTitle) ?></h1>
        <?php if ($companyName): ?><p class="report-print-company"><?= htmlspecialchars($companyName) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($printMeta)): ?>
    <div class="report-print-meta">
        <?php foreach ($printMeta as $line): ?>
            <?php if (is_array($line)): ?>
                <p><strong><?= htmlspecialchars($line['label'] ?? '') ?>:</strong> <?= htmlspecialchars($line['value'] ?? '') ?></p>
            <?php else: ?>
                <p><?= htmlspecialchars((string) $line) ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
        <p><strong>Oluşturulma:</strong> <?= date('d.m.Y H:i') ?></p>
    </div>
    <?php endif; ?>
    <?php if (!empty($printSummary['headers']) && !empty($printSummary['values'])): ?>
    <table class="report-print-summary">
        <tr>
            <?php foreach ($printSummary['headers'] as $h): ?>
                <th><?= htmlspecialchars($h) ?></th>
            <?php endforeach; ?>
        </tr>
        <tr>
            <?php foreach ($printSummary['values'] as $v): ?>
                <td><?= htmlspecialchars((string) $v) ?></td>
            <?php endforeach; ?>
        </tr>
    </table>
    <?php endif; ?>
</div>
