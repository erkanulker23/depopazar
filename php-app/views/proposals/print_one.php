<?php
$p = $proposal ?? [];
$items = $p['items'] ?? [];
$customerName = trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? ''));
$statusLabels = $statusLabels ?? ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul', 'rejected' => 'Red'];
$statusLabel = $statusLabels[$p['status'] ?? 'draft'] ?? ($p['status'] ?? '');
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
    <title>Teklif - <?= htmlspecialchars($p['title'] ?? 'Teklif') ?> - <?= htmlspecialchars($customerName ?: 'Müşteri') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-6 max-w-4xl mx-auto">
    <div class="no-print mb-4 flex flex-wrap justify-between items-center gap-2">
        <a href="/teklifler/<?= htmlspecialchars($p['id'] ?? '') ?>/duzenle" class="text-emerald-600 hover:underline">&larr; Teklife dön</a>
        <div class="flex gap-2">
            <a href="/teklifler/<?= htmlspecialchars($p['id'] ?? '') ?>/duzenle" class="px-4 py-2 border border-gray-300 rounded-xl font-medium hover:bg-gray-50">Detay / Düzenle</a>
            <a href="mailto:<?= htmlspecialchars($p['customer_email'] ?? '') ?>?subject=<?= rawurlencode('Teklif: ' . ($p['title'] ?? '')) ?>&body=<?= rawurlencode('Sayın Müşterimiz,\n\n' . ($p['title'] ?? 'Teklif') . ' teklifiniz ekteki linkten görüntüleyebilirsiniz.\n\n' . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '') . '/teklifler/' . ($p['id'] ?? '') . '/yazdir') ?>" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700">Teklifi E-posta Gönder</a>
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
                Yazdır / PDF
            </button>
        </div>
    </div>

    <div class="border-2 border-gray-200 rounded-xl p-6 print:border-gray-400">
        <?php $company = $company ?? null; if ($company): ?>
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
            <?php if (!empty($company['logo_url'])): ?><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-12 object-contain"><?php endif; ?>
            <div class="text-right text-sm text-gray-600">
                <p class="font-bold text-gray-900"><?= htmlspecialchars($company['name'] ?? '') ?></p>
                <?php if (!empty($company['address'])): ?><p><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><p><?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
                <?php if (!empty($company['email'])): ?><p><?= htmlspecialchars($company['email']) ?></p><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <h1 class="text-xl font-bold text-center text-gray-900 mb-1"><?= htmlspecialchars($p['title'] ?? 'Teklif') ?></h1>
        <?php
        $typeLabel = ($p['proposal_type'] ?? 'nakliye') === 'depo' ? 'Depo Teklifi' : 'Nakliye Teklifi';
        ?>
        <p class="text-center text-sm text-gray-500 mb-4"><?= htmlspecialchars($typeLabel) ?> · Teklif No: <?= htmlspecialchars(substr($p['id'] ?? '', 0, 8)) ?> · Oluşturulma: <?= !empty($p['created_at']) ? date('d.m.Y H:i', strtotime($p['created_at'])) : date('d.m.Y H:i') ?></p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri Bilgileri</h2>
                <p class="text-sm"><strong>Ad Soyad:</strong> <?= htmlspecialchars($customerName ?: '-') ?></p>
                <p class="text-sm"><strong>E-posta:</strong> <?= htmlspecialchars($p['customer_email'] ?? '-') ?></p>
                <p class="text-sm"><strong>Telefon:</strong> <?= htmlspecialchars($p['customer_phone'] ?? '-') ?></p>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Teklif Özeti</h2>
                <p class="text-sm"><strong>Durum:</strong> <?= htmlspecialchars($statusLabel) ?></p>
                <p class="text-sm"><strong>Toplam:</strong> <span class="font-bold text-lg"><?= fmtPrice($p['total_amount'] ?? 0) ?></span></p>
                <?php if (!empty($p['valid_until'])): ?>
                    <p class="text-sm"><strong>Geçerlilik:</strong> <?= date('d.m.Y', strtotime($p['valid_until'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($p['pickup_address']) || !empty($p['delivery_address'])): ?>
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if (!empty($p['pickup_address'])): ?>
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Alınacak Adres</h2>
                <p class="text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($p['pickup_address'])) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($p['delivery_address'])): ?>
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Teslim Adresi</h2>
                <p class="text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($p['delivery_address'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="mb-6">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Teklif Kalemleri</h2>
            <?php if (empty($items)): ?>
                <p class="text-sm text-gray-500">Kalem eklenmemiş.</p>
            <?php else: ?>
                <table class="min-w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">#</th>
                            <th class="border border-gray-300 px-3 py-2 text-left font-bold">Hizmet / Açıklama</th>
                            <th class="border border-gray-300 px-3 py-2 text-right font-bold">Miktar</th>
                            <th class="border border-gray-300 px-3 py-2 text-right font-bold">Birim Fiyat</th>
                            <th class="border border-gray-300 px-3 py-2 text-right font-bold">Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td class="border border-gray-300 px-3 py-2"><?= $i + 1 ?></td>
                                <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['name'] ?? '') ?><?= !empty($item['description']) ? ' – ' . htmlspecialchars($item['description']) : '' ?></td>
                                <td class="border border-gray-300 px-3 py-2 text-right"><?= number_format((float)($item['quantity'] ?? 0), 2, ',', '.') ?></td>
                                <td class="border border-gray-300 px-3 py-2 text-right"><?= fmtPrice($item['unit_price'] ?? 0) ?></td>
                                <td class="border border-gray-300 px-3 py-2 text-right font-medium"><?= fmtPrice($item['total_price'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="border border-gray-300 px-3 py-2 text-right font-bold">Toplam</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-bold"><?= fmtPrice($p['total_amount'] ?? 0) ?></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($p['notes'])): ?>
        <div class="mb-4">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Notlar</h2>
            <p class="text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($p['notes'])) ?></p>
        </div>
        <?php endif; ?>

        <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= !empty($p['created_at']) ? date('d.m.Y H:i', strtotime($p['created_at'])) : date('d.m.Y H:i') ?></p>
    </div>
</body>
</html>
