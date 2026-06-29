<?php
$customerName = trim(($contract['customer_first_name'] ?? '') . ' ' . ($contract['customer_last_name'] ?? ''));
$company = $company ?? null;
$soldByName = $soldByName ?? '-';
$payments = $payments ?? [];
$customerSignatureHref = $customerSignatureHref ?? null;
$companySignatureHref = $companySignatureHref ?? null;
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
    <title>Sözleşme Yazdır - <?= htmlspecialchars($contract['contract_number'] ?? '') ?></title>
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
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>" class="text-emerald-600 hover:underline">&larr; Sözleşmeye dön</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
            <i class="bi bi-printer inline-block mr-2"></i>Yazdır / PDF olarak kaydet
        </button>
    </div>

    <div class="border-2 border-gray-200 rounded-xl p-6 print:border-gray-400">
        <?php if ($company && !empty($company['logo_url'])): ?>
        <div class="mb-4"><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-14 object-contain"></div>
        <?php endif; ?>
        <h1 class="text-xl font-bold text-center text-gray-900 mb-6">Sözleşme</h1>
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
                <?php if (!empty($contract['customer_email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($contract['customer_email']) ?></p><?php endif; ?>
                <?php if (!empty($contract['customer_phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($contract['customer_phone']) ?></p><?php endif; ?>
            </div>
        </div>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Sözleşme Bilgileri</h2>
        <table class="min-w-full border border-gray-300 text-sm mb-6">
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 w-48">Sözleşme No</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($contract['contract_number'] ?? '-') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Depo / Oda</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($contract['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($contract['room_number'] ?? '') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Başlangıç – Bitiş</td><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($contract['start_date'] ?? '')) ?> – <?= date('d.m.Y', strtotime($contract['end_date'] ?? '')) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Aylık Ücret</td><td class="border border-gray-300 px-3 py-2"><?= fmtPrice($contract['monthly_price'] ?? 0) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Sözleşmeyi Yapan</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($soldByName) ?></td></tr>
            <?php if (!empty($contract['stored_items_condition'])): ?>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Ürün Durumu</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars(storedItemsConditionLabel($contract['stored_items_condition'] ?? null)) ?><?php if (($contract['stored_items_condition'] ?? '') === 'hasarli' && !empty($contract['stored_items_condition_note'])): ?><br><span class="text-xs text-gray-600 mt-1 block">Hasar notu: <?= nl2br(htmlspecialchars($contract['stored_items_condition_note'])) ?></span><?php endif; ?></td></tr>
            <?php endif; ?>
        </table>
        <?php $items = $items ?? []; ?>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2 mt-6">Depo Eşya Listesi</h2>
        <?php if (empty($items)): ?>
            <p class="text-sm text-gray-500 mb-6">Eşya listesi girilmemiş.</p>
        <?php else: ?>
            <table class="min-w-full border border-gray-300 text-sm mb-6">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border border-gray-300 px-3 py-2 text-left font-bold">#</th>
                        <th class="border border-gray-300 px-3 py-2 text-left font-bold">Eşya Adı</th>
                        <th class="border border-gray-300 px-3 py-2 text-left font-bold">Durum</th>
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
                        <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars(itemConditionLabel($item['condition'] ?? null)) ?></td>
                        <td class="border border-gray-300 px-3 py-2"><?= (int) ($item['quantity'] ?? 1) ?></td>
                        <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['unit'] ?? 'adet') ?></td>
                        <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($item['description'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Ödeme Takvimi</h2>
        <table class="min-w-full border border-gray-300 text-sm">
            <thead class="bg-gray-100"><tr><th class="border border-gray-300 px-3 py-2 text-left font-bold">Vade</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Tutar</th><th class="border border-gray-300 px-3 py-2 text-left font-bold">Durum</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $p): $ps = paymentStatusDisplay($p); ?>
                <tr><td class="border border-gray-300 px-3 py-2"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td><td class="border border-gray-300 px-3 py-2"><?= fmtPrice($p['amount'] ?? 0) ?></td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($ps['label']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= fmtDateTime($contract['created_at'] ?? null) ?></p>

        <div class="mt-8 pt-6 border-t-2 border-gray-300 print:border-gray-500">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-4">İmzalar</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Müşteri</p>
                    <p class="text-sm font-medium text-gray-900 mb-2"><?= htmlspecialchars($customerName ?: '-') ?></p>
                    <div id="customerSigPrint" class="signature-print min-h-[5rem] border border-gray-300 rounded-lg bg-white flex items-center justify-center p-2">
                        <?php if (!empty($customerSignatureHref)): ?>
                            <img src="<?= htmlspecialchars($customerSignatureHref) ?>" alt="Müşteri imzası" class="max-h-20 object-contain">
                        <?php else: ?>
                            <span class="text-xs text-gray-400 signature-placeholder">İmza bekleniyor</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($contract['customer_signed_at'])): ?>
                        <p id="customerSigDate" class="text-[10px] text-gray-500 mt-1"><?= fmtDateTime($contract['customer_signed_at']) ?></p>
                    <?php else: ?>
                        <p id="customerSigDate" class="text-[10px] text-gray-500 mt-1 hidden"></p>
                    <?php endif; ?>
                    <div class="no-print mt-3 space-y-2">
                        <p class="text-xs text-gray-500">Parmağınız veya kalemle imza atın:</p>
                        <canvas id="customerSigPad" class="w-full h-28 border-2 border-dashed border-emerald-300 rounded-xl bg-white touch-none cursor-crosshair" aria-label="Müşteri imza alanı"></canvas>
                        <button type="button" id="customerSigClear" class="text-xs text-gray-600 hover:text-gray-900 underline">Temizle</button>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Firma</p>
                    <p class="text-sm font-medium text-gray-900 mb-2"><?= htmlspecialchars($company['name'] ?? 'Firma') ?></p>
                    <div id="companySigPrint" class="signature-print min-h-[5rem] border border-gray-300 rounded-lg bg-white flex items-center justify-center p-2">
                        <?php if (!empty($companySignatureHref)): ?>
                            <img src="<?= htmlspecialchars($companySignatureHref) ?>" alt="Firma imzası" class="max-h-20 object-contain">
                        <?php else: ?>
                            <span class="text-xs text-gray-400 signature-placeholder">İmza bekleniyor</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($contract['company_signed_at'])): ?>
                        <p id="companySigDate" class="text-[10px] text-gray-500 mt-1"><?= fmtDateTime($contract['company_signed_at']) ?></p>
                    <?php else: ?>
                        <p id="companySigDate" class="text-[10px] text-gray-500 mt-1 hidden"></p>
                    <?php endif; ?>
                    <div class="no-print mt-3 space-y-2">
                        <p class="text-xs text-gray-500">Firma yetkilisi imzası:</p>
                        <canvas id="companySigPad" class="w-full h-28 border-2 border-dashed border-emerald-300 rounded-xl bg-white touch-none cursor-crosshair" aria-label="Firma imza alanı"></canvas>
                        <button type="button" id="companySigClear" class="text-xs text-gray-600 hover:text-gray-900 underline">Temizle</button>
                    </div>
                </div>
            </div>
            <div class="no-print mt-5 flex flex-wrap items-center gap-3">
                <button type="button" id="saveSignaturesBtn" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700">
                    <i class="bi bi-pen"></i> İmzaları Kaydet
                </button>
                <p id="signatureSaveStatus" class="text-sm text-gray-500"></p>
            </div>
        </div>
    </div>

    <script src="/js/signature-pad.js"></script>
    <script>
    (function() {
        var contractId = <?= json_encode($contract['id'] ?? '') ?>;
        var customerPad = new SignaturePad(document.getElementById('customerSigPad'));
        var companyPad = new SignaturePad(document.getElementById('companySigPad'));
        var existingCustomer = <?= json_encode($customerSignatureHref ?? '') ?>;
        var existingCompany = <?= json_encode($companySignatureHref ?? '') ?>;
        if (existingCustomer) customerPad.fromDataURL(existingCustomer);
        if (existingCompany) companyPad.fromDataURL(existingCompany);
        window.addEventListener('resize', function() {
            customerPad.resize();
            companyPad.resize();
        });
        document.getElementById('customerSigClear').addEventListener('click', function() { customerPad.clear(); });
        document.getElementById('companySigClear').addEventListener('click', function() { companyPad.clear(); });

        function setPrintSig(containerId, url, dateId, signedAt) {
            var el = document.getElementById(containerId);
            if (!el) return;
            el.innerHTML = url
                ? '<img src="' + url.replace(/"/g, '&quot;') + '" alt="İmza" class="max-h-20 object-contain">'
                : '<span class="text-xs text-gray-400 signature-placeholder">İmza bekleniyor</span>';
            var dateEl = document.getElementById(dateId);
            if (dateEl && signedAt) {
                dateEl.textContent = signedAt;
                dateEl.classList.remove('hidden');
            }
        }

        function padChanged(pad) {
            return !pad.isEmpty() ? pad.toDataURL() : '';
        }

        document.getElementById('saveSignaturesBtn').addEventListener('click', function() {
            var customerData = padChanged(customerPad);
            var companyData = padChanged(companyPad);
            if (!customerData && !companyData) {
                document.getElementById('signatureSaveStatus').textContent = 'Kaydetmek için en az bir imza çizin veya değiştirin.';
                return;
            }
            var fd = new FormData();
            fd.append('contract_id', contractId);
            if (customerData) fd.append('customer_signature', customerData);
            if (companyData) fd.append('company_signature', companyData);
            var btn = document.getElementById('saveSignaturesBtn');
            var status = document.getElementById('signatureSaveStatus');
            btn.disabled = true;
            status.textContent = 'Kaydediliyor…';
            fetch('/girisler/imza-kaydet', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    if (!data.ok) {
                        status.textContent = data.error || 'Kayıt başarısız.';
                        return;
                    }
                    status.textContent = 'İmzalar kaydedildi.';
                    status.className = 'text-sm text-emerald-600';
                    if (data.customer_signature_url) {
                        existingCustomer = data.customer_signature_url;
                        setPrintSig('customerSigPrint', data.customer_signature_url, 'customerSigDate', data.customer_signed_at);
                    }
                    if (data.company_signature_url) {
                        existingCompany = data.company_signature_url;
                        setPrintSig('companySigPrint', data.company_signature_url, 'companySigDate', data.company_signed_at);
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    status.textContent = 'Bağlantı hatası.';
                });
        });

        window.addEventListener('beforeprint', function() {
            if (!customerPad.isEmpty()) {
                var u = customerPad.toDataURL();
                setPrintSig('customerSigPrint', u, 'customerSigDate', null);
            }
            if (!companyPad.isEmpty()) {
                var v = companyPad.toDataURL();
                setPrintSig('companySigPrint', v, 'companySigDate', null);
            }
        });
    })();
    </script>
</body>
</html>
