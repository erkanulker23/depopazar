<?php
$proposals = $proposals ?? [];
$statusLabels = $statusLabels ?? ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul', 'rejected' => 'Red'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklifler - Yazdır</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@media print { .no-print { display: none !important; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }</style>
</head>
<body class="bg-white text-gray-900 p-6">
    <div class="no-print mb-4 flex justify-between items-center">
        <a href="/teklifler" class="text-emerald-600 hover:underline">&larr; Teklifler</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">Yazdır / PDF</button>
    </div>
    <div class="max-w-4xl mx-auto space-y-8">
        <?php foreach ($proposals as $p):
            $items = $p['items'] ?? [];
        ?>
            <div class="border-2 border-gray-200 rounded-xl p-6 break-inside-avoid">
                <h2 class="text-lg font-bold text-gray-900 border-b border-gray-300 pb-2 mb-4"><?= htmlspecialchars($p['title'] ?? 'Teklif') ?></h2>
                <table class="min-w-full text-sm">
                    <tr><td class="py-1 font-medium text-gray-600 w-40">Müşteri</td><td><?= htmlspecialchars(trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? '')) ?: '-') ?></td></tr>
                    <tr><td class="py-1 font-medium text-gray-600">Tutar</td><td class="font-semibold"><?= fmtPrice($p['total_amount'] ?? 0) ?> <?= htmlspecialchars($p['currency'] ?? 'TRY') ?></td></tr>
                    <tr><td class="py-1 font-medium text-gray-600">Durum</td><td><?= htmlspecialchars($statusLabels[$p['status'] ?? 'draft'] ?? $p['status']) ?></td></tr>
                    <?php if (!empty($p['valid_until'])): ?><tr><td class="py-1 font-medium text-gray-600">Geçerlilik</td><td><?= date('d.m.Y', strtotime($p['valid_until'])) ?></td></tr><?php endif; ?>
                    <?php if (!empty($p['pickup_address'])): ?><tr><td class="py-1 font-medium text-gray-600">Alınacak Adres</td><td><?= nl2br(htmlspecialchars($p['pickup_address'])) ?></td></tr><?php endif; ?>
                    <?php if (!empty($p['delivery_address'])): ?><tr><td class="py-1 font-medium text-gray-600">Teslim Adresi</td><td><?= nl2br(htmlspecialchars($p['delivery_address'])) ?></td></tr><?php endif; ?>
                </table>
                <?php if (!empty($items)): ?>
                    <div class="mt-4">
                        <strong class="text-gray-700">Kalemler:</strong>
                        <table class="min-w-full border border-gray-300 text-sm mt-2">
                            <thead class="bg-gray-100"><tr><th class="border px-2 py-1 text-left">#</th><th class="border px-2 py-1 text-left">Açıklama</th><th class="border px-2 py-1 text-right">Miktar</th><th class="border px-2 py-1 text-right">Birim Fiyat</th><th class="border px-2 py-1 text-right">Tutar</th></tr></thead>
                            <tbody>
                                <?php foreach ($items as $i => $it): ?>
                                    <tr><td class="border px-2 py-1"><?= $i + 1 ?></td><td class="border px-2 py-1"><?= htmlspecialchars($it['name'] ?? '') ?></td><td class="border px-2 py-1 text-right"><?= number_format((float)($it['quantity'] ?? 0), 2, ',', '.') ?></td><td class="border px-2 py-1 text-right"><?= fmtPrice($it['unit_price'] ?? 0) ?></td><td class="border px-2 py-1 text-right"><?= fmtPrice($it['total_price'] ?? 0) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <?php if (!empty($p['notes'])): ?>
                    <p class="mt-4 text-sm text-gray-600"><strong>Not:</strong> <?= nl2br(htmlspecialchars($p['notes'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (empty($proposals)): ?>
        <p class="text-gray-500 text-center py-8">Yazdırılacak teklif bulunamadı.</p>
    <?php endif; ?>
</body>
</html>
