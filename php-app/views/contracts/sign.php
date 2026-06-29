<?php
$customerName = trim(($contract['customer_first_name'] ?? '') . ' ' . ($contract['customer_last_name'] ?? ''));
$company = $company ?? null;
$contractId = $contract['id'] ?? '';
$customerSignatureHref = $customerSignatureHref ?? null;
$companySignatureHref = $companySignatureHref ?? null;
$bothSigned = !empty($customerSignatureHref) && !empty($companySignatureHref);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sözleşme İmzala — <?= htmlspecialchars($contract['contract_number'] ?? '') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
    <div class="no-print bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex flex-wrap items-center justify-between gap-2">
        <a href="/girisler/<?= htmlspecialchars($contractId) ?>" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline font-medium">
            <i class="bi bi-arrow-left"></i> Sözleşmeye dön
        </a>
        <a href="/girisler/<?= htmlspecialchars($contractId) ?>/yazdir" target="_blank" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
            Tam belgeyi yazdır <i class="bi bi-box-arrow-up-right text-xs"></i>
        </a>
    </div>

    <div class="max-w-3xl mx-auto p-4 sm:p-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-5 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                <div>
                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1">E-İmza</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sözleşme İmzala</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?= htmlspecialchars($contract['contract_number'] ?? '') ?></p>
                </div>
                <?php if ($bothSigned): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                        <i class="bi bi-check-circle-fill"></i> İmzalar tamam
                    </span>
                <?php endif; ?>
            </div>

            <div class="rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700 p-4 mb-8 text-sm">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</dt>
                        <dd class="mt-0.5 font-medium"><?= htmlspecialchars($customerName ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo / Oda</dt>
                        <dd class="mt-0.5 font-medium"><?= htmlspecialchars($contract['warehouse_name'] ?? '-') ?> / <?= htmlspecialchars($contract['room_number'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Dönem</dt>
                        <dd class="mt-0.5"><?= date('d.m.Y', strtotime($contract['start_date'] ?? 'now')) ?> – <?= date('d.m.Y', strtotime($contract['end_date'] ?? 'now')) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık Ücret</dt>
                        <dd class="mt-0.5 font-semibold text-emerald-700 dark:text-emerald-400"><?= fmtPrice($contract['monthly_price'] ?? 0) ?></dd>
                    </div>
                </dl>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                Müşteri ve firma yetkilisi aşağıdaki alanlara imza atmalıdır. İmzalar kaydedildikten sonra yazdırılabilir belge ve PDF’de görünür.
            </p>

            <?php
            $signaturePadHeight = 'h-40';
            $signatureBeforePrint = false;
            require __DIR__ . '/../partials/contract_signatures_block.php';
            ?>
        </div>
    </div>
</body>
</html>
