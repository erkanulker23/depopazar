<?php
$contract = $contract ?? null;
$company = $company ?? null;
$customerName = $customerName ?? '';
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
    <title>Çıkış Belgesi - <?= htmlspecialchars($contract['contract_number'] ?? '') ?> - <?= htmlspecialchars($seoAppName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-white text-gray-900 p-6 max-w-4xl mx-auto">
    <div class="no-print mb-4 flex justify-between items-center">
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>" class="text-emerald-600 hover:underline">&larr; Sözleşmeye dön</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
            <i class="bi bi-printer inline-block mr-2"></i>Yazdır / PDF olarak kaydet
        </button>
    </div>

    <div class="border-2 border-gray-200 rounded-xl p-6 print:border-gray-400">
        <?php if ($company && !empty($company['logo_url'])): ?>
        <div class="mb-4"><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-14 object-contain"></div>
        <?php endif; ?>
        <h1 class="text-xl font-bold text-center text-gray-900 mb-2">DEPO ÇIKIŞ BELGESİ</h1>
        <p class="text-center text-sm text-gray-600 mb-6">Bu belge, depo sözleşmesinin sona erdiğini ve eşyaların teslim alındığını belirtir.</p>
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
                <?php if (!empty($contract['customer_email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($contract['customer_email']) ?></p><?php endif; ?>
                <?php if (!empty($contract['customer_phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($contract['customer_phone']) ?></p><?php endif; ?>
            </div>
        </div>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Sözleşme / Depo Bilgisi</h2>
        <table class="min-w-full border border-gray-300 text-sm mb-4">
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 w-48">Sözleşme No</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($contract['contract_number'] ?? '-') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Depo / Oda</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($contract['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($contract['room_number'] ?? '') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Giriş Tarihi</td><td class="border border-gray-300 px-3 py-2"><?= !empty($contract['start_date']) ? date('d.m.Y', strtotime($contract['start_date'])) : '-' ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Sözleşme Dönemi</td><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($contract['start_date'] ?? '')) ?> – <?= date('d.m.Y', strtotime($contract['end_date'] ?? '')) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Çıkış Tarihi</td><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y') ?></td></tr>
        </table>

        <?php $contractPayments = $contractPayments ?? []; if (!empty($contractPayments)): ?>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2 mt-6">Ödeme Geçmişi (Ne Zaman Girmiş / Ne Zaman Ödemiş)</h2>
        <table class="min-w-full border border-gray-300 text-sm mb-4">
            <thead><tr class="bg-gray-100"><th class="border border-gray-300 px-3 py-2 text-left font-bold">Vade</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Tutar</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Durum</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Ödenme Tarihi</th></tr></thead>
            <tbody>
            <?php foreach ($contractPayments as $p): ?>
                <tr>
                    <td class="border border-gray-300 px-3 py-2"><?= !empty($p['due_date']) ? date('d.m.Y', strtotime($p['due_date'])) : '-' ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= fmtPrice($p['amount'] ?? 0) ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= (($p['status'] ?? '') === 'paid') ? 'Ödendi' : (($p['status'] ?? '') === 'overdue' ? 'Gecikmiş' : 'Bekliyor') ?></td>
                    <td class="border border-gray-300 px-3 py-2"><?= !empty($p['paid_at']) ? date('d.m.Y H:i', strtotime($p['paid_at'])) : '–' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <p class="text-sm text-gray-600 mb-4">Yukarıda bilgileri yer alan depo sözleşmesi sona ermiş olup, müşteri eşyalarını teslim almıştır. Bu belge çıkış işleminin yapıldığını teyit eder.</p>
        <p class="text-xs text-gray-500 mt-4">Belge tarihi: <?= date('d.m.Y H:i') ?></p>
    </div>
</body>
</html>
