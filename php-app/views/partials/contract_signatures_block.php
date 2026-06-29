<?php
/** @var array $contract */
/** @var array|null $company */
/** @var string $customerName */
/** @var string|null $customerSignatureHref */
/** @var string|null $companySignatureHref */
/** @var bool $signatureBeforePrint */
$contract = $contract ?? [];
$company = $company ?? null;
$customerName = $customerName ?? '';
$customerSignatureHref = $customerSignatureHref ?? null;
$companySignatureHref = $companySignatureHref ?? null;
$signatureBeforePrint = !empty($signatureBeforePrint);
$signaturePadHeight = $signaturePadHeight ?? 'h-36';
?>
<div id="contractSignatures" class="contract-signatures">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Müşteri</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2"><?= htmlspecialchars($customerName ?: '-') ?></p>
            <div id="customerSigPrint" class="signature-print min-h-[5.5rem] border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 flex items-center justify-center p-2">
                <?php if (!empty($customerSignatureHref)): ?>
                    <img src="<?= htmlspecialchars($customerSignatureHref) ?>" alt="Müşteri imzası" class="max-h-24 object-contain">
                <?php else: ?>
                    <span class="text-xs text-gray-400 signature-placeholder">İmza bekleniyor</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($contract['customer_signed_at'])): ?>
                <p id="customerSigDate" class="text-[10px] text-gray-500 dark:text-gray-400 mt-1"><?= fmtDateTime($contract['customer_signed_at']) ?></p>
            <?php else: ?>
                <p id="customerSigDate" class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 hidden"></p>
            <?php endif; ?>
            <div class="no-print mt-3 space-y-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">Parmağınız veya kalemle imza atın:</p>
                <canvas id="customerSigPad" class="w-full <?= htmlspecialchars($signaturePadHeight) ?> border-2 border-dashed border-emerald-300 dark:border-emerald-700 rounded-xl bg-white touch-none cursor-crosshair" aria-label="Müşteri imza alanı"></canvas>
                <button type="button" id="customerSigClear" class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline">Temizle</button>
            </div>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Firma</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2"><?= htmlspecialchars($company['name'] ?? 'Firma') ?></p>
            <div id="companySigPrint" class="signature-print min-h-[5.5rem] border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 flex items-center justify-center p-2">
                <?php if (!empty($companySignatureHref)): ?>
                    <img src="<?= htmlspecialchars($companySignatureHref) ?>" alt="Firma imzası" class="max-h-24 object-contain">
                <?php else: ?>
                    <span class="text-xs text-gray-400 signature-placeholder">İmza bekleniyor</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($contract['company_signed_at'])): ?>
                <p id="companySigDate" class="text-[10px] text-gray-500 dark:text-gray-400 mt-1"><?= fmtDateTime($contract['company_signed_at']) ?></p>
            <?php else: ?>
                <p id="companySigDate" class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 hidden"></p>
            <?php endif; ?>
            <div class="no-print mt-3 space-y-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">Firma yetkilisi imzası:</p>
                <canvas id="companySigPad" class="w-full <?= htmlspecialchars($signaturePadHeight) ?> border-2 border-dashed border-emerald-300 dark:border-emerald-700 rounded-xl bg-white touch-none cursor-crosshair" aria-label="Firma imza alanı"></canvas>
                <button type="button" id="companySigClear" class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline">Temizle</button>
            </div>
        </div>
    </div>
    <div class="no-print mt-5 flex flex-wrap items-center gap-3">
        <button type="button" id="saveSignaturesBtn" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700">
            <i class="bi bi-pen"></i> İmzaları Kaydet
        </button>
        <p id="signatureSaveStatus" class="text-sm text-gray-500 dark:text-gray-400"></p>
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
            ? '<img src="' + url.replace(/"/g, '&quot;') + '" alt="İmza" class="max-h-24 object-contain">'
            : '<span class="text-xs text-gray-400 signature-placeholder">İmza bekleniyor</span>';
        var dateEl = document.getElementById(dateId);
        if (dateEl && signedAt) {
            dateEl.textContent = signedAt;
            dateEl.classList.remove('hidden');
        }
    }

    document.getElementById('saveSignaturesBtn').addEventListener('click', function() {
        var customerData = !customerPad.isEmpty() ? customerPad.toDataURL() : '';
        var companyData = !companyPad.isEmpty() ? companyPad.toDataURL() : '';
        if (!customerData && !companyData) {
            document.getElementById('signatureSaveStatus').textContent = 'Kaydetmek için en az bir imza çizin.';
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
        status.className = 'text-sm text-gray-500 dark:text-gray-400';
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

    <?php if ($signatureBeforePrint): ?>
    window.addEventListener('beforeprint', function() {
        if (!customerPad.isEmpty()) {
            setPrintSig('customerSigPrint', customerPad.toDataURL(), 'customerSigDate', null);
        }
        if (!companyPad.isEmpty()) {
            setPrintSig('companySigPrint', companyPad.toDataURL(), 'companySigDate', null);
        }
    });
    <?php endif; ?>
})();
</script>
