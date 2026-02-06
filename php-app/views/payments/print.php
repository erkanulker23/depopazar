<?php
$customerName = trim(($payment['customer_first_name'] ?? '') . ' ' . ($payment['customer_last_name'] ?? ''));
$company = $company ?? null;
$statusLabels = $statusLabels ?? [];
$status = $payment['status'] ?? 'pending';
$statusLabel = $statusLabels[$status] ?? $status;
$seoAppName = trim($_SESSION['company_project_name'] ?? '') !== '' ? $_SESSION['company_project_name'] : 'Depo ve Nakliye Takip';
$seoCn = trim($_SESSION['company_name'] ?? '');
$seoDescription = ($seoCn !== '' && $seoAppName !== '') ? ($seoCn . ' - ' . $seoAppName . '. Depo ve nakliye yönetimi.') : ($seoAppName . '. Depo ve nakliye işlemlerinizi tek panelden yönetin.');
if (!function_exists('fmtPrice')) {
    function fmtPrice($n) {
        if ($n === null || $n === '') return '';
        $f = (float) $n;
        return ($f == (int)$f ? number_format((int)$f, 0, '', '.') : number_format($f, 2, ',', '.')) . ' ₺';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <title>Ödeme Makbuzu - <?= htmlspecialchars($payment['payment_number'] ?? '') ?> - <?= htmlspecialchars($seoAppName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-6 max-w-4xl mx-auto">
    <div class="no-print mb-4 flex justify-between items-center">
        <a href="/odemeler/<?= htmlspecialchars($payment['id'] ?? '') ?>" class="text-emerald-600 hover:underline">&larr; Ödemeye dön</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
            <i class="bi bi-printer inline-block mr-2"></i>Yazdır / PDF olarak kaydet
        </button>
    </div>

    <div class="border-2 border-gray-200 rounded-xl p-6 print:border-gray-400">
        <?php if ($company && !empty($company['logo_url'])): ?>
        <div class="mb-4"><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-14 object-contain"></div>
        <?php endif; ?>
        <h1 class="text-xl font-bold text-center text-gray-900 mb-6">Ödeme Makbuzu</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Firma</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? 'Firma Adı') ?></p>
                <?php if (!empty($company['address'])): ?><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
                <?php if (!empty($company['email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($company['email']) ?></p><?php endif; ?>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName ?: '-') ?></p>
                <?php if (!empty($payment['customer_email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($payment['customer_email']) ?></p><?php endif; ?>
                <?php if (!empty($payment['customer_phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($payment['customer_phone']) ?></p><?php endif; ?>
            </div>
        </div>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Ödeme Bilgileri</h2>
        <table class="min-w-full border border-gray-300 text-sm">
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 w-40">Ödeme No</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($payment['payment_number'] ?? '-') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Sözleşme</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($payment['contract_number'] ?? '-') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Depo / Oda</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($payment['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($payment['room_number'] ?? '') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Tutar</td><td class="border border-gray-300 px-3 py-2 font-bold"><?= fmtPrice($payment['amount'] ?? 0) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Vade</td><td class="border border-gray-300 px-3 py-2"><?= !empty($payment['due_date']) ? date('d.m.Y', strtotime($payment['due_date'])) : '–' ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Ödenme</td><td class="border border-gray-300 px-3 py-2"><?= !empty($payment['paid_at']) ? date('d.m.Y', strtotime($payment['paid_at'])) : '–' ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Durum</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($statusLabel) ?></td></tr>
            <?php if (!empty($payment['payment_method'])): ?><tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Ödeme Yöntemi</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($payment['payment_method']) ?></td></tr><?php endif; ?>
        </table>
        <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= date('d.m.Y H:i') ?></p>
    </div>
</body>
</html>
