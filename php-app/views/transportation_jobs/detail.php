<?php
$currentPage = 'nakliye-isler';
$job = $job ?? [];
$company = $company ?? null;
$staffNames = $staffNames ?? [];
$customerName = trim(($job['customer_first_name'] ?? '') . ' ' . ($job['customer_last_name'] ?? ''));
$statusLabels = ['pending' => 'Beklemede', 'in_progress' => 'Devam Ediyor', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal Edildi'];
$status = $job['status'] ?? 'pending';
$statusLabel = $statusLabels[$status] ?? $status;
if (!function_exists('fmtPrice')) {
    function fmtPrice($n) {
        if ($n === null || $n === '') return '-';
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
    <title>Nakliye İşi - <?= htmlspecialchars($customerName) ?></title>
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
    <div class="no-print mb-4 flex justify-between items-center flex-wrap gap-2">
        <a href="/nakliye-isler" class="text-emerald-600 hover:underline">&larr; Nakliye işlerine dön</a>
        <div class="flex gap-2">
            <a href="/nakliye-isler/<?= htmlspecialchars($job['id'] ?? '') ?>/duzenle" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                <i class="bi bi-pencil mr-2"></i> Düzenle
            </a>
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
                <i class="bi bi-printer inline-block mr-2"></i>Yazdır / PDF
            </button>
        </div>
    </div>

    <div class="border-2 border-gray-200 rounded-xl p-6 print:border-gray-400">
        <?php if ($company && !empty($company['logo_url'])): ?>
        <div class="mb-4"><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-14 object-contain"></div>
        <?php endif; ?>
        <h1 class="text-xl font-bold text-center text-gray-900 mb-6">Nakliye İşi</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Firma</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? 'Firma Adı') ?></p>
                <?php if (!empty($company['address'])): ?><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName ?: '-') ?></p>
                <?php if (!empty($job['customer_email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($job['customer_email']) ?></p><?php endif; ?>
                <?php if (!empty($job['customer_phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($job['customer_phone']) ?></p><?php endif; ?>
            </div>
        </div>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Nakliye Bilgileri</h2>
        <table class="min-w-full border border-gray-300 text-sm">
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 w-40">Tarih</td><td class="border border-gray-300 px-3 py-2"><?= !empty($job['job_date']) ? date('d.m.Y', strtotime($job['job_date'])) : '–' ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Alış Adresi</td><td class="border border-gray-300 px-3 py-2"><?= nl2br(htmlspecialchars($job['pickup_address'] ?? '–')) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Teslim Adresi</td><td class="border border-gray-300 px-3 py-2"><?= nl2br(htmlspecialchars($job['delivery_address'] ?? '–')) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Araç Plakası</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($job['vehicle_plate'] ?? '–') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">İşe Giden Personel</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars(implode(', ', $staffNames) ?: '–') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Tutar</td><td class="border border-gray-300 px-3 py-2 font-bold"><?= fmtPrice($job['price'] ?? null) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Durum</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($statusLabel) ?></td></tr>
            <?php if (!empty($job['notes'])): ?><tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Not</td><td class="border border-gray-300 px-3 py-2"><?= nl2br(htmlspecialchars($job['notes'])) ?></td></tr><?php endif; ?>
        </table>
        <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= date('d.m.Y H:i') ?></p>
    </div>
</body>
</html>
