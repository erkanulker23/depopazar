<?php
/** @var array $contract */
/** @var array|null $company */
/** @var string $customerName */
/** @var string|null $customerSignatureHref */
/** @var string|null $companySignatureHref */
/** @var bool $signatureBeforePrint */
/** @var bool $enableSignatureReset */
$contract = $contract ?? [];
$company = $company ?? null;
$customerName = $customerName ?? '';
$customerSignatureHref = $customerSignatureHref ?? null;
$companySignatureHref = $companySignatureHref ?? null;
$signatureBeforePrint = !empty($signatureBeforePrint);
$enableSignatureReset = !empty($enableSignatureReset);
$signaturePadHeight = $signaturePadHeight ?? 'h-36';
$hasCustomerSig = !empty($customerSignatureHref);
$hasCompanySig = !empty($companySignatureHref);
?>
<div class="contract-signatures">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Müşteri</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2"><?= htmlspecialchars($customerName ?: '-') ?></p>
            <div id="customerSigPrint" class="signature-print min-h-[5.5rem] border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 flex items-center justify-center p-2">
                <?php if ($hasCustomerSig): ?>
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
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="customerSigClear" class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline">Temizle</button>
                    <?php if ($enableSignatureReset): ?>
                        <button type="button" id="customerSigResign" class="text-xs text-amber-700 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300 underline<?= $hasCustomerSig ? '' : ' hidden' ?>">Yeniden imzala</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Firma</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2"><?= htmlspecialchars($company['name'] ?? 'Firma') ?></p>
            <div id="companySigPrint" class="signature-print min-h-[5.5rem] border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 flex items-center justify-center p-2">
                <?php if ($hasCompanySig): ?>
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
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="companySigClear" class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline">Temizle</button>
                    <?php if ($enableSignatureReset): ?>
                        <button type="button" id="companySigResign" class="text-xs text-amber-700 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300 underline<?= $hasCompanySig ? '' : ' hidden' ?>">Yeniden imzala</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="no-print mt-5 flex flex-wrap items-center gap-3">
        <button type="button" id="saveSignaturesBtn" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700">
            <i class="bi bi-pen"></i> İmzaları Kaydet
        </button>
        <?php if ($enableSignatureReset && ($hasCustomerSig || $hasCompanySig)): ?>
            <button type="button" id="clearAllSignaturesBtn" class="px-4 py-2.5 border border-red-300 text-red-700 rounded-xl text-sm font-medium hover:bg-red-50">
                <i class="bi bi-eraser"></i> İmzaları Temizle
            </button>
        <?php elseif ($enableSignatureReset): ?>
            <button type="button" id="clearAllSignaturesBtn" class="hidden px-4 py-2.5 border border-red-300 text-red-700 rounded-xl text-sm font-medium hover:bg-red-50">
                <i class="bi bi-eraser"></i> İmzaları Temizle
            </button>
        <?php endif; ?>
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
    var enableReset = <?= $enableSignatureReset ? 'true' : 'false' ?>;
    if (existingCustomer) customerPad.fromDataURL(existingCustomer);
    if (existingCompany) companyPad.fromDataURL(existingCompany);
    window.addEventListener('resize', function() {
        customerPad.resize();
        companyPad.resize();
    });

    function setPrintSig(containerId, url, dateId, signedAt) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = url
            ? '<img src="' + url.replace(/"/g, '&quot;') + '" alt="İmza" class="max-h-24 object-contain">'
            : '<span class="text-xs text-gray-400 signature-placeholder">İmza bekleniyor</span>';
        var dateEl = document.getElementById(dateId);
        if (dateEl) {
            if (signedAt) {
                dateEl.textContent = signedAt;
                dateEl.classList.remove('hidden');
            } else if (!url) {
                dateEl.textContent = '';
                dateEl.classList.add('hidden');
            }
        }
    }

    function toggleResignButtons() {
        var custBtn = document.getElementById('customerSigResign');
        var compBtn = document.getElementById('companySigResign');
        var allBtn = document.getElementById('clearAllSignaturesBtn');
        if (custBtn) custBtn.classList.toggle('hidden', !existingCustomer);
        if (compBtn) compBtn.classList.toggle('hidden', !existingCompany);
        if (allBtn) allBtn.classList.toggle('hidden', !(existingCustomer || existingCompany));
    }

    function clearSignatureOnServer(role, done) {
        var fd = new FormData();
        fd.append('contract_id', contractId);
        fd.append('role', role);
        var status = document.getElementById('signatureSaveStatus');
        if (status) {
            status.textContent = 'İmzalar temizleniyor…';
            status.className = 'text-sm text-gray-500 dark:text-gray-400';
        }
        fetch('/girisler/imza-temizle', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.ok) {
                    if (status) status.textContent = data.error || 'Temizleme başarısız.';
                    if (done) done(false);
                    return;
                }
                existingCustomer = data.customer_signature_url || '';
                existingCompany = data.company_signature_url || '';
                if (role === 'customer' || role === 'both') {
                    customerPad.clear();
                    setPrintSig('customerSigPrint', '', 'customerSigDate', null);
                }
                if (role === 'company' || role === 'both') {
                    companyPad.clear();
                    setPrintSig('companySigPrint', '', 'companySigDate', null);
                }
                toggleResignButtons();
                if (typeof window.updateContractSendPanel === 'function') {
                    window.updateContractSendPanel(existingCustomer, existingCompany);
                }
                if (status) {
                    status.textContent = 'İmza temizlendi. Yeniden imzalayabilirsiniz.';
                    status.className = 'text-sm text-emerald-600';
                }
                if (done) done(true);
            })
            .catch(function() {
                if (status) status.textContent = 'Bağlantı hatası.';
                if (done) done(false);
            });
    }

    document.getElementById('customerSigClear').addEventListener('click', function() { customerPad.clear(); });
    document.getElementById('companySigClear').addEventListener('click', function() { companyPad.clear(); });

    var customerResign = document.getElementById('customerSigResign');
    if (customerResign) {
        customerResign.addEventListener('click', function() {
            if (!confirm('Müşteri imzası silinsin ve yeniden imzalansın mı?')) return;
            clearSignatureOnServer('customer');
        });
    }
    var companyResign = document.getElementById('companySigResign');
    if (companyResign) {
        companyResign.addEventListener('click', function() {
            if (!confirm('Firma imzası silinsin ve yeniden imzalansın mı?')) return;
            clearSignatureOnServer('company');
        });
    }
    var clearAllBtn = document.getElementById('clearAllSignaturesBtn');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            if (!confirm('Tüm kayıtlı imzalar silinsin mi?')) return;
            clearSignatureOnServer('both');
        });
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
                toggleResignButtons();
                if (typeof window.updateContractSendPanel === 'function') {
                    window.updateContractSendPanel(existingCustomer, existingCompany);
                }
            })
            .catch(function() {
                btn.disabled = false;
                status.textContent = 'Bağlantı hatası.';
            });
    });

    if (typeof window.updateContractSendPanel === 'function') {
        window.updateContractSendPanel(existingCustomer, existingCompany);
    }

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
