<?php
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$barcodeCode = strtoupper(substr($customer['id'], 0, 8));
$items = $items ?? [];
$monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$currentYear = (int)date('Y');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Depo Etiketi - <?= htmlspecialchars($customerName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-6 max-w-4xl mx-auto">
    <div class="no-print mb-4 flex justify-between items-center">
        <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>" class="text-emerald-600 hover:underline">&larr; Müşteriye dön</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
            <i class="bi bi-printer inline-block mr-2"></i>Yazdır / PDF olarak kaydet
        </button>
    </div>

    <div class="border-2 border-gray-200 rounded-xl p-6 print:border-gray-400">
        <h1 class="text-xl font-bold text-center text-gray-900 mb-2">Müşteri Depo Etiketi</h1>
        <div class="flex justify-center mb-4">
            <svg id="barcode" class="block"></svg>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri Bilgileri</h2>
                <p class="text-sm"><strong>Depo kiralayan:</strong> <?= htmlspecialchars($customerName) ?></p>
                <p class="text-sm"><strong>İletişim:</strong> <?= htmlspecialchars($customer['phone'] ?? 'Girilmedi') ?> / <?= htmlspecialchars($customer['email'] ?? 'Girilmedi') ?></p>
            </div>
        </div>

        <div class="mb-6">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Eşya Listesi</h2>
            <?php if (empty($items)): ?>
                <p class="text-sm text-gray-500">Henüz eşya listesi girilmemiştir.</p>
            <?php else: ?>
                <table class="min-w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">#</th>
                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">Eşya Adı</th>
                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">Adet</th>
                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">Birim</th>
                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td class="border border-gray-300 px-3 py-2"><?= $i + 1 ?></td>
                                <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['name'] ?? '') ?></td>
                                <td class="border border-gray-300 px-3 py-2"><?= (int)($item['quantity'] ?? 1) ?></td>
                                <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['unit'] ?? 'adet') ?></td>
                                <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['description'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Aylık Ödeme Takip Çizelgesi</h2>
            <table class="min-w-full border border-gray-300 text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border border-gray-300 px-3 py-2 text-left font-bold">Dönem</th>
                        <th class="border border-gray-300 px-3 py-2 text-center font-bold">Ödendi</th>
                        <th class="border border-gray-300 px-3 py-2 text-center font-bold">Ödenmedi</th>
                        <th class="border border-gray-300 px-3 py-2 text-left font-bold">Not</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < 12; $i++): ?>
                        <tr>
                            <td class="border border-gray-300 px-3 py-2"><?= $monthNames[$i] ?> <?= $currentYear ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-center">[  ]</td>
                            <td class="border border-gray-300 px-3 py-2 text-center">[  ]</td>
                            <td class="border border-gray-300 px-3 py-2"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= date('d.m.Y H:i') ?></p>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script>
        JsBarcode("#barcode", "<?= htmlspecialchars($barcodeCode) ?>", {
            format: "CODE128",
            width: 2,
            height: 40,
            displayValue: true
        });
    </script>
</body>
</html>
