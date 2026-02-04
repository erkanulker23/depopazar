<?php
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$company = $company ?? null;
$contracts = $contracts ?? [];
$payments = $payments ?? [];
$debt = $debt ?? 0;
$monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$monthsStatus = [];
foreach ($payments as $p) {
    $due = $p['due_date'] ?? '';
    if ($due === '') continue;
    $key = date('Y-m', strtotime($due));
    $status = $p['status'] ?? 'pending';
    $label = $status === 'paid' ? 'Ödendi' : ($status === 'overdue' ? 'Gecikmede' : 'Ödenmedi');
    $monthsStatus[$key] = ['status' => $status, 'label' => $label, 'amount' => $p['amount'] ?? 0, 'contract_number' => $p['contract_number'] ?? ''];
}
$minYear = date('Y');
$maxYear = date('Y');
foreach (array_keys($monthsStatus) as $ym) {
    $y = (int) substr($ym, 0, 4);
    if ($y < $minYear) $minYear = $y;
    if ($y > $maxYear) $maxYear = $y;
}
foreach ($contracts as $c) {
    if (!empty($c['start_date'])) { $y = (int) date('Y', strtotime($c['start_date'])); if ($y < $minYear) $minYear = $y; }
    if (!empty($c['end_date'])) { $y = (int) date('Y', strtotime($c['end_date'])); if ($y > $maxYear) $maxYear = $y; }
}
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
    <title>Müşteri Detayı - <?= htmlspecialchars($customerName) ?></title>
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
        <a href="/musteriler/<?= htmlspecialchars($customer['id'] ?? '') ?>" class="text-emerald-600 hover:underline">&larr; Müşteriye dön</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
            <i class="bi bi-printer inline-block mr-2"></i>Yazdır / PDF olarak kaydet
        </button>
    </div>

    <div class="border-2 border-gray-200 rounded-xl p-6 print:border-gray-400">
        <?php if ($company && !empty($company['logo_url'])): ?>
        <div class="mb-4"><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-14 object-contain"></div>
        <?php endif; ?>
        <h1 class="text-xl font-bold text-center text-gray-900 mb-6">Müşteri Detayı</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <?php if ($company): ?>
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Firma</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? '') ?></p>
                <?php if (!empty($company['address'])): ?><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName ?: '-') ?></p>
                <?php if (!empty($customer['email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($customer['email']) ?></p><?php endif; ?>
                <?php if (!empty($customer['phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($customer['phone']) ?></p><?php endif; ?>
                <?php if (!empty($customer['identity_number'])): ?><p class="text-sm text-gray-600">TC: <?= htmlspecialchars($customer['identity_number']) ?></p><?php endif; ?>
                <?php if (!empty($customer['address'])): ?><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($customer['address'])) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3 mb-6">
            <p class="text-xs font-bold text-amber-700 uppercase tracking-widest">Toplam Borç</p>
            <p class="text-xl font-bold text-amber-800"><?= number_format($debt, 2, ',', '.') ?> ₺</p>
            <p class="text-xs text-gray-600 mt-1">Sözleşme sayısı: <?= count($contracts) ?></p>
        </div>

        <?php if (!empty($contracts)): ?>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Sözleşmeler</h2>
        <table class="min-w-full border border-gray-300 text-sm mb-6">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Sözleşme No</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Depo / Oda</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Başlangıç</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Bitiş</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Aylık</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contracts as $c): ?>
                <tr>
                    <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($c['start_date'] ?? '')) ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($c['end_date'] ?? '')) ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= fmtPrice($c['monthly_price'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Ödeme Takvimi</h2>
        <?php if (empty($payments)): ?>
        <p class="text-sm text-gray-600 mb-6">Ödeme kaydı yok.</p>
        <?php else: ?>
        <table class="min-w-full border border-gray-300 text-sm mb-6">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Sözleşme</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Vade</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Tutar</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Durum</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold">Ödenme</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): $s = $p['status'] ?? 'pending'; $l = $s === 'paid' ? 'Ödendi' : ($s === 'overdue' ? 'Gecikmiş' : 'Bekliyor'); ?>
                <tr>
                    <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($p['contract_number'] ?? '-') ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= fmtPrice($p['amount'] ?? 0) ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= $l ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= !empty($p['paid_at']) ? date('d.m.Y', strtotime($p['paid_at'])) : '–' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if ($maxYear >= $minYear): ?>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Aylar Takvimi – Ödendi / Ödenmedi</h2>
        <table class="min-w-full border border-gray-300 text-sm mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border border-gray-300 px-2 py-2 text-left font-bold">Yıl</th>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <th class="border border-gray-300 px-2 py-2 text-center font-bold text-xs"><?= $monthNames[$m - 1] ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($year = $maxYear; $year >= $minYear; $year--): ?>
                <tr>
                    <td class="border border-gray-300 px-2 py-1.5 font-medium"><?= $year ?></td>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <?php
                    $key = $year . '-' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
                    $info = $monthsStatus[$key] ?? null;
                    $label = $info ? $info['label'] : '–';
                    $bg = ($info['status'] ?? '') === 'paid' ? 'bg-green-100' : (($info['status'] ?? '') === 'overdue' ? 'bg-red-100' : (($info['status'] ?? '') === 'pending' ? 'bg-amber-100' : ''));
                    ?>
                    <td class="border border-gray-300 px-1 py-1 text-center text-xs <?= $bg ?>"><?= htmlspecialchars($label) ?></td>
                    <?php endfor; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($customer['notes'])): ?>
        <p class="text-sm text-gray-600 mt-4"><strong>Not:</strong> <?= nl2br(htmlspecialchars($customer['notes'])) ?></p>
        <?php endif; ?>
        <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= date('d.m.Y H:i') ?></p>
    </div>
</body>
</html>
