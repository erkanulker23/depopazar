<?php
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$company = $company ?? null;
$items = $items ?? [];
$contracts = $contracts ?? [];
$isStaff = !empty($isStaff);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($customerName) ?> — Depo Bilgisi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    <div class="no-print bg-white border-b border-gray-200 px-4 py-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm font-medium text-gray-700"><i class="bi bi-qr-code-scan text-emerald-600 mr-1"></i> Depo etiketi detayı</p>
        <?php if ($isStaff): ?>
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>" class="text-sm text-emerald-600 hover:underline">Panele git &rarr;</a>
        <?php endif; ?>
    </div>

    <div class="max-w-3xl mx-auto p-4 sm:p-6">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6">
            <?php if ($company && !empty($company['logo_url'])): ?>
            <div class="mb-4"><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-14 object-contain"></div>
            <?php endif; ?>

            <h1 class="text-xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($customerName) ?></h1>
            <p class="text-sm text-gray-500 mb-6">Müşteri depo kaydı</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <?php if ($company): ?>
                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Firma</h2>
                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? 'Firma Adı') ?></p>
                    <?php if (!empty($company['project_name']) && ($company['project_name'] ?? '') !== ($company['name'] ?? '')): ?>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($company['project_name']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($company['address'])): ?><p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
                    <?php if (!empty($company['phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
                    <?php if (!empty($company['whatsapp_number'])): ?><p class="text-sm text-gray-600">WhatsApp: <?= htmlspecialchars($company['whatsapp_number']) ?></p><?php endif; ?>
                    <?php if (!empty($company['email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($company['email']) ?></p><?php endif; ?>
                    <?php if (!empty($company['tax_office']) || !empty($company['mersis_number'])): ?>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php if (!empty($company['tax_office'])): ?>V.D.: <?= htmlspecialchars($company['tax_office']) ?><?php endif; ?>
                            <?php if (!empty($company['tax_office']) && !empty($company['mersis_number'])): ?> · <?php endif; ?>
                            <?php if (!empty($company['mersis_number'])): ?>MERSİS: <?= htmlspecialchars($company['mersis_number']) ?><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="rounded-xl bg-emerald-50/50 border border-emerald-100 p-4">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Müşteri</h2>
                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName) ?></p>
                    <?php if (!empty($customer['phone'])): ?><p class="text-sm text-gray-600 mt-1">Tel: <?= htmlspecialchars(formatPhoneDisplay($customer['phone'])) ?></p><?php endif; ?>
                    <?php if (!empty($customer['phone_2'])): ?><p class="text-sm text-gray-600">Tel 2: <?= htmlspecialchars(formatPhoneDisplay($customer['phone_2'])) ?></p><?php endif; ?>
                    <?php if (!empty($customer['email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($customer['email']) ?></p><?php endif; ?>
                    <?php if (!empty($customer['identity_number'])): ?><p class="text-sm text-gray-600">TC: <?= htmlspecialchars($customer['identity_number']) ?></p><?php endif; ?>
                    <?php if (!empty($customer['address'])): ?><p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($customer['address'])) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest mb-3 flex items-center gap-2">
                    <i class="bi bi-box-seam text-emerald-600"></i> Eşya Listesi
                </h2>
                <?php if ($items === []): ?>
                    <p class="text-sm text-gray-500 rounded-xl border border-dashed border-gray-300 p-4 text-center">Henüz eşya listesi girilmemiş.</p>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left font-bold">#</th>
                                    <th class="px-3 py-2 text-left font-bold">Eşya</th>
                                    <th class="px-3 py-2 text-left font-bold">Durum</th>
                                    <th class="px-3 py-2 text-left font-bold">Adet</th>
                                    <th class="px-3 py-2 text-left font-bold">Birim</th>
                                    <th class="px-3 py-2 text-left font-bold">Açıklama</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($items as $i => $item): ?>
                                    <tr class="bg-white">
                                        <td class="px-3 py-2"><?= $i + 1 ?></td>
                                        <td class="px-3 py-2 font-medium"><?= htmlspecialchars($item['name'] ?? '') ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars(itemConditionLabel($item['condition'] ?? null)) ?></td>
                                        <td class="px-3 py-2"><?= (int) ($item['quantity'] ?? 1) ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($item['unit'] ?? 'adet') ?></td>
                                        <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($item['description'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest mb-3 flex items-center gap-2">
                    <i class="bi bi-door-open text-emerald-600"></i> Oda / Sözleşme
                </h2>
                <?php if ($contracts === []): ?>
                    <p class="text-sm text-gray-500 rounded-xl border border-dashed border-gray-300 p-4 text-center">Aktif depo girişi / oda kaydı yok.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($contracts as $c): ?>
                            <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/80">
                                <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($c['warehouse_name'] ?? '-') ?> — Oda <?= htmlspecialchars($c['room_number'] ?? '-') ?></p>
                                    <span class="text-xs font-mono text-gray-500"><?= htmlspecialchars($c['contract_number'] ?? '') ?></span>
                                </div>
                                <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                    <div><dt class="text-gray-500 text-xs">Başlangıç</dt><dd><?= !empty($c['start_date']) ? date('d.m.Y', strtotime($c['start_date'])) : '-' ?></dd></div>
                                    <div><dt class="text-gray-500 text-xs">Bitiş</dt><dd><?= !empty($c['end_date']) ? date('d.m.Y', strtotime($c['end_date'])) : '-' ?></dd></div>
                                    <div><dt class="text-gray-500 text-xs">Aylık ücret</dt><dd class="font-medium"><?= number_format((float) ($c['monthly_price'] ?? 0), 2, ',', '.') ?> ₺</dd></div>
                                    <div><dt class="text-gray-500 text-xs">Durum</dt><dd><?= !empty($c['is_active']) ? 'Aktif' : 'Sonlandırılmış' ?></dd></div>
                                </dl>
                                <?php if (!empty($c['notes'])): ?>
                                    <p class="text-xs text-gray-600 mt-2 border-t border-gray-200 pt-2"><?= nl2br(htmlspecialchars($c['notes'])) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <p class="text-xs text-gray-400 mt-6 text-center"><?= date('d.m.Y H:i') ?></p>
        </div>
    </div>
</body>
</html>
