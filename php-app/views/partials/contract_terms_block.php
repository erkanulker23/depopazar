<?php
/** @var array $contract */
/** @var array|null $company */
/** @var string $customerName */
$contract = $contract ?? [];
$company = $company ?? null;
$customerName = $customerName ?? '';
$terms = contractStorageTerms($contract, $company, $customerName);
$customNotes = trim((string) ($contract['notes'] ?? ''));
?>
<div class="contract-terms mt-8 pt-6 border-t border-gray-300 print:border-gray-400" id="contractTerms">
    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-3">Özel Şartlar</h2>
    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-800 leading-relaxed pl-1">
        <?php foreach ($terms as $term): ?>
            <li class="pl-1"><?= htmlspecialchars($term) ?></li>
        <?php endforeach; ?>
    </ol>
    <?php if ($customNotes !== ''): ?>
        <div class="mt-5">
            <h3 class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-2">Özel İstek ve Şartlar</h3>
            <p class="text-sm text-gray-800 whitespace-pre-wrap border border-gray-200 rounded-lg p-3 bg-gray-50 print:bg-white"><?= htmlspecialchars($customNotes) ?></p>
        </div>
    <?php endif; ?>
</div>
