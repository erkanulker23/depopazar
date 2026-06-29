<?php
$customerName = trim(($contract['customer_first_name'] ?? '') . ' ' . ($contract['customer_last_name'] ?? ''));
$company = $company ?? null;
$warehouse = $warehouse ?? null;
$soldByName = $soldByName ?? '-';
$payments = $payments ?? [];
$items = $items ?? [];
$logoSrc = '';
if ($warehouse && !empty($warehouse['logo_url'])) {
    $logoSrc = publicFileDataUri($warehouse['logo_url']) ?? '';
}
if ($logoSrc === '' && $company && !empty($company['logo_url'])) {
    $logoPath = publicFilePath($company['logo_url']);
    if ($logoPath && is_file($logoPath)) {
        $mime = mime_content_type($logoPath) ?: 'image/png';
        $logoSrc = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
    }
}
$depotName = trim((string) ($warehouse['name'] ?? $contract['warehouse_name'] ?? ''));
$roomNumber = trim((string) ($contract['room_number'] ?? ''));
$depotAddress = trim((string) ($warehouse['address'] ?? ''));
$location = trim(implode(' / ', array_filter([
    trim((string) ($warehouse['district'] ?? '')),
    trim((string) ($warehouse['city'] ?? '')),
], fn($p) => $p !== '')));
$depotDescription = trim((string) ($warehouse['description'] ?? ''));
$hasDepot = $depotName !== '' || $warehouse !== null;
$depotPhone = warehouseContact($warehouse, $company, 'phone');
$depotWhatsapp = warehouseContact($warehouse, $company, 'whatsapp_number');
$depotEmail = warehouseContact($warehouse, $company, 'email');
$depotWebsite = trim((string) ($warehouse['website'] ?? ''));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { text-align: center; font-size: 18px; margin: 0 0 18px; }
        h2 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #444; margin: 0 0 6px; }
        .logo { height: 48px; margin-bottom: 12px; }
        .grid { width: 100%; margin-bottom: 16px; }
        .grid td { vertical-align: top; width: 50%; padding-right: 12px; }
        .box { font-weight: 600; margin: 0 0 4px; }
        .muted { color: #555; margin: 0 0 3px; line-height: 1.4; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data th, table.data td { border: 1px solid #bbb; padding: 6px 8px; text-align: left; }
        table.data th { background: #f3f4f6; font-weight: 700; }
        .label { background: #f3f4f6; font-weight: 600; width: 34%; }
        .footer { font-size: 9px; color: #666; margin-top: 12px; }
    </style>
</head>
<body>
    <?php if ($logoSrc !== ''): ?>
        <img src="<?= $logoSrc ?>" alt="" class="logo">
    <?php endif; ?>
    <h1>Depolama Sözleşmesi</h1>
    <table class="grid"><tr>
        <td>
            <h2><?= $hasDepot ? 'Depo' : 'Firma' ?></h2>
            <?php if ($hasDepot): ?>
                <p class="box"><?= htmlspecialchars($depotName !== '' ? $depotName : '-') ?></p>
                <?php if ($roomNumber !== ''): ?><p class="muted">Oda: <?= htmlspecialchars($roomNumber) ?></p><?php endif; ?>
                <?php if ($depotAddress !== ''): ?><p class="muted"><?= nl2br(htmlspecialchars($depotAddress)) ?></p><?php endif; ?>
                <?php if ($location !== ''): ?><p class="muted"><?= htmlspecialchars($location) ?></p><?php endif; ?>
                <?php if ($depotDescription !== ''): ?><p class="muted"><?= nl2br(htmlspecialchars($depotDescription)) ?></p><?php endif; ?>
            <?php else: ?>
                <p class="box"><?= htmlspecialchars($company['name'] ?? 'Firma Adı') ?></p>
                <?php if (!empty($company['address'])): ?><p class="muted"><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
            <?php endif; ?>
            <?php if ($depotPhone !== ''): ?><p class="muted">Tel: <?= htmlspecialchars($depotPhone) ?></p><?php endif; ?>
            <?php if ($depotWhatsapp !== ''): ?><p class="muted">WhatsApp: <?= htmlspecialchars($depotWhatsapp) ?></p><?php endif; ?>
            <?php if ($depotEmail !== ''): ?><p class="muted"><?= htmlspecialchars($depotEmail) ?></p><?php endif; ?>
            <?php if ($depotWebsite !== ''): ?><p class="muted"><?= htmlspecialchars(websiteDisplayUrl($depotWebsite)) ?></p><?php endif; ?>
        </td>
        <td>
            <h2>Müşteri</h2>
            <p class="box"><?= htmlspecialchars($customerName ?: '-') ?></p>
            <?php if (!empty($contract['customer_email'])): ?><p class="muted"><?= htmlspecialchars($contract['customer_email']) ?></p><?php endif; ?>
            <?php if (!empty($contract['customer_phone'])): ?><p class="muted">Tel: <?= htmlspecialchars($contract['customer_phone']) ?></p><?php endif; ?>
        </td>
    </tr></table>

    <h2>Sözleşme Bilgileri</h2>
    <table class="data">
        <tr><td class="label">Sözleşme No</td><td><?= htmlspecialchars($contract['contract_number'] ?? '-') ?></td></tr>
        <tr><td class="label">Depo / Oda</td><td><?= htmlspecialchars($contract['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($contract['room_number'] ?? '') ?></td></tr>
        <tr><td class="label">Başlangıç – Bitiş</td><td><?= date('d.m.Y', strtotime($contract['start_date'] ?? '')) ?> – <?= date('d.m.Y', strtotime($contract['end_date'] ?? '')) ?></td></tr>
        <tr><td class="label">Aylık Ücret</td><td><?= fmtPrice($contract['monthly_price'] ?? 0) ?></td></tr>
        <tr><td class="label">Sözleşmeyi Yapan</td><td><?= htmlspecialchars($soldByName) ?></td></tr>
        <?php if (!empty($contract['stored_items_condition'])): ?>
        <tr><td class="label">Ürün Durumu</td><td><?= htmlspecialchars(storedItemsConditionLabel($contract['stored_items_condition'] ?? null)) ?><?php if (($contract['stored_items_condition'] ?? '') === 'hasarli' && !empty($contract['stored_items_condition_note'])): ?><br><?= htmlspecialchars($contract['stored_items_condition_note']) ?><?php endif; ?></td></tr>
        <?php endif; ?>
    </table>

    <h2>Depo Eşya Listesi</h2>
    <?php if (empty($items)): ?>
        <p class="muted">Eşya listesi girilmemiş.</p>
    <?php else: ?>
        <table class="data">
            <thead>
                <tr><th>#</th><th>Eşya Adı</th><th>Durum</th><th>Adet</th><th>Birim</th><th>Açıklama</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($item['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars(itemConditionLabel($item['condition'] ?? null)) ?></td>
                    <td><?= (int) ($item['quantity'] ?? 1) ?></td>
                    <td><?= htmlspecialchars($item['unit'] ?? 'adet') ?></td>
                    <td><?= htmlspecialchars($item['description'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Ödeme Takvimi</h2>
    <table class="data">
        <thead><tr><th>Vade</th><th>Tutar</th><th>Durum</th></tr></thead>
        <tbody>
            <?php foreach ($payments as $p): $ps = paymentStatusDisplay($p); ?>
            <tr>
                <td><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td>
                <td><?= fmtPrice($p['amount'] ?? 0) ?></td>
                <td><?= htmlspecialchars($ps['label']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    $terms = contractStorageTerms($contract, $company, $customerName, $warehouse);
    $customNotes = trim((string) ($contract['notes'] ?? ''));
    ?>
    <h2 style="margin-top: 18px;">Özel Şartlar</h2>
    <ol style="margin: 8px 0 14px 18px; padding: 0; font-size: 10px; line-height: 1.45;">
        <?php foreach ($terms as $term): ?>
            <li style="margin-bottom: 4px;"><?= htmlspecialchars($term) ?></li>
        <?php endforeach; ?>
    </ol>
    <?php if ($customNotes !== ''): ?>
        <h2>Özel İstek ve Şartlar</h2>
        <p class="muted" style="white-space: pre-wrap; border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($customNotes) ?></p>
    <?php endif; ?>
    <?php
    $customerSigSrc = publicFileDataUri($contract['customer_signature_url'] ?? null);
    $companySigSrc = publicFileDataUri($contract['company_signature_url'] ?? null);
    ?>
    <h2 style="margin-top: 18px;">İmzalar</h2>
    <table class="grid" style="margin-top: 8px;">
        <tr>
            <td>
                <h2>Müşteri</h2>
                <p class="box"><?= htmlspecialchars($customerName ?: '-') ?></p>
                <?php if ($customerSigSrc): ?>
                    <img src="<?= $customerSigSrc ?>" alt="" style="max-height: 64px; margin-top: 6px;">
                    <?php if (!empty($contract['customer_signed_at'])): ?>
                        <p class="muted"><?= fmtDateTime($contract['customer_signed_at']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="muted">—</p>
                <?php endif; ?>
            </td>
            <td>
                <h2>Firma</h2>
                <p class="box"><?= htmlspecialchars($company['name'] ?? 'Firma') ?></p>
                <?php if ($companySigSrc): ?>
                    <img src="<?= $companySigSrc ?>" alt="" style="max-height: 64px; margin-top: 6px;">
                    <?php if (!empty($contract['company_signed_at'])): ?>
                        <p class="muted"><?= fmtDateTime($contract['company_signed_at']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="muted">—</p>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <p class="footer">Oluşturulma: <?= fmtDateTime($contract['created_at'] ?? null) ?></p>
</body>
</html>
