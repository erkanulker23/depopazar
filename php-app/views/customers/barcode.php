<?php
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$company = $company ?? null;
$qrDetailUrl = $qrDetailUrl ?? '';
$qrCodeDataUri = $qrCodeDataUri ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Depo Etiketi - <?= htmlspecialchars($customerName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 p-4 sm:p-6">
    <div class="no-print max-w-md mx-auto mb-4 flex justify-between items-center gap-2">
        <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>" class="text-emerald-600 hover:underline text-sm">&larr; Müşteriye dön</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700">
            <i class="bi bi-printer inline-block mr-1"></i>Yazdır
        </button>
    </div>

    <div class="max-w-md mx-auto border-2 border-gray-300 rounded-2xl bg-white p-6 print:border-gray-500 print:shadow-none shadow-sm">
        <?php if ($company && !empty($company['logo_url'])): ?>
        <div class="mb-4 flex justify-center">
            <img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-12 object-contain">
        </div>
        <?php endif; ?>

        <h1 class="text-center text-base font-bold text-gray-900 uppercase tracking-wide mb-4">Depo QR Etiketi</h1>

        <div class="flex justify-center mb-5">
            <?php if ($qrCodeDataUri): ?>
                <img src="<?= htmlspecialchars($qrCodeDataUri) ?>" alt="QR Kod" width="168" height="168" class="block rounded-lg border border-gray-200">
            <?php else: ?>
                <div class="w-[168px] h-[168px] flex items-center justify-center rounded-xl border border-dashed border-red-300 bg-red-50 text-red-600 text-xs text-center p-3">
                    QR oluşturulamadı.<br>Sunucuda <code class="text-[10px]">composer install</code> çalıştırın.
                </div>
            <?php endif; ?>
        </div>
        <p class="text-center text-[10px] text-gray-400 mb-5 no-print">QR kodu okutunca eşya ve oda detayları açılır</p>

        <div class="space-y-4 text-sm border-t border-gray-200 pt-4">
            <?php if ($company): ?>
            <div>
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Firma</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? 'Firma Adı') ?></p>
                <?php if (!empty($company['phone'])): ?><p class="text-gray-600 text-xs mt-0.5">Tel: <?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
                <?php if (!empty($company['address'])): ?><p class="text-gray-600 text-xs mt-0.5 line-clamp-3"><?= htmlspecialchars($company['address']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div>
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Müşteri</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName) ?></p>
                <?php if (!empty($customer['phone'])): ?><p class="text-gray-600 text-xs mt-0.5">Tel: <?= htmlspecialchars(formatPhoneDisplay($customer['phone'])) ?></p><?php endif; ?>
                <?php if (!empty($customer['email'])): ?><p class="text-gray-600 text-xs mt-0.5"><?= htmlspecialchars($customer['email']) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
