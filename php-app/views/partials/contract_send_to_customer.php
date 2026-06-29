<?php
/** @var array $contract */
/** @var string $customerName */
$contract = $contract ?? [];
$customerName = $customerName ?? '';
$contractId = $contract['id'] ?? '';
$customerEmail = trim((string) ($contract['customer_email'] ?? ''));
$hasCustomerEmail = $customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL);
$bothSigned = !empty($customerSignatureHref) && !empty($companySignatureHref);
$pdfDownloadUrl = '/girisler/' . $contractId . '/pdf-indir';
$pdfFilename = ContractPdf::filename($contract);
$waIntlPhone = whatsappIntlPhoneFromCustomerFields(
    $contract['customer_phone'] ?? null,
    $contract['customer_phone_2'] ?? null
);
$waMessage = 'Merhaba' . ($customerName !== '' ? ' ' . $customerName : '') . ', '
    . ($contract['contract_number'] ?? 'sözleşme') . ' numaralı imzalı depolama sözleşme belgeniz ektedir. İyi günler dileriz.';
$waUrl = $waIntlPhone !== '' ? ('https://wa.me/' . $waIntlPhone . '?text=' . rawurlencode($waMessage)) : '';
$waPhoneDisplay = $waIntlPhone !== '' ? formatPhoneDisplay($contract['customer_phone'] ?? $contract['customer_phone_2'] ?? '') : '';
?>
<div id="contractSendPanel" class="no-print mt-6 p-4 rounded-xl border border-emerald-200 bg-emerald-50/60<?= $bothSigned ? '' : ' hidden' ?>">
    <h3 class="text-sm font-bold text-emerald-800 uppercase tracking-widest mb-2">Müşteriye Gönder</h3>
    <p class="text-sm text-gray-700 mb-4">İmzalar tamamlandı. İmzalı sözleşmeyi müşteriye iletebilirsiniz.</p>
    <div class="flex flex-wrap items-center gap-3">
        <button type="button"
                id="contractEmailSendBtn"
                class="inline-flex items-center px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-60"
                data-contract-id="<?= htmlspecialchars($contractId) ?>"
                <?= !$hasCustomerEmail ? 'disabled title="Müşteri e-posta adresi yok"' : '' ?>>
            <i class="bi bi-envelope mr-2"></i> Müşteriye E-posta Gönder
        </button>
        <button type="button"
                id="contractWhatsAppSendBtn"
                class="inline-flex items-center px-4 py-2.5 rounded-xl bg-green-600 text-white text-sm font-medium hover:bg-green-700 disabled:opacity-60"
                data-pdf-url="<?= htmlspecialchars($pdfDownloadUrl) ?>"
                data-wa-url="<?= htmlspecialchars($waUrl) ?>"
                data-wa-phone="<?= htmlspecialchars($waIntlPhone) ?>"
                data-wa-phone-display="<?= htmlspecialchars($waPhoneDisplay) ?>"
                data-filename="<?= htmlspecialchars($pdfFilename) ?>"
                <?= $waIntlPhone === '' ? 'disabled title="Müşteri cep telefonu yok"' : '' ?>>
            <i class="bi bi-whatsapp mr-2"></i> WhatsApp’tan Gönder
        </button>
    </div>
    <?php if (!$hasCustomerEmail): ?>
        <p class="text-xs text-amber-700 mt-3">E-posta için müşteri kaydına geçerli bir e-posta adresi ekleyin.</p>
    <?php endif; ?>
    <?php if ($waIntlPhone === ''): ?>
        <p class="text-xs text-amber-700 mt-1">WhatsApp için müşteri cep telefonu (05xx) gerekli.</p>
    <?php endif; ?>
    <p id="contractSendStatus" class="text-sm text-gray-600 mt-3"></p>
</div>
<script>
window.updateContractSendPanel = function(customerUrl, companyUrl) {
    var panel = document.getElementById('contractSendPanel');
    if (!panel) return;
    var ready = !!(customerUrl && companyUrl);
    panel.classList.toggle('hidden', !ready);
};
(function() {
    var emailBtn = document.getElementById('contractEmailSendBtn');
    if (emailBtn) {
        emailBtn.addEventListener('click', function() {
            if (emailBtn.disabled) return;
            var contractId = emailBtn.getAttribute('data-contract-id');
            var status = document.getElementById('contractSendStatus');
            var fd = new FormData();
            fd.append('contract_id', contractId);
            emailBtn.disabled = true;
            if (status) status.textContent = 'E-posta gönderiliyor…';
            fetch('/girisler/sozlesme-eposta-gonder', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    emailBtn.disabled = false;
                    if (!data.ok) {
                        if (status) {
                            status.textContent = data.error || 'E-posta gönderilemedi.';
                            status.className = 'text-sm text-red-600 mt-3';
                        }
                        return;
                    }
                    if (status) {
                        status.textContent = data.message || 'E-posta gönderildi.';
                        status.className = 'text-sm text-emerald-700 mt-3';
                    }
                })
                .catch(function() {
                    emailBtn.disabled = false;
                    if (status) status.textContent = 'Bağlantı hatası.';
                });
        });
    }

    var waBtn = document.getElementById('contractWhatsAppSendBtn');
    if (!waBtn) return;

    function isMobileDevice() {
        return /Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
            || (navigator.maxTouchPoints > 1 && window.innerWidth < 900);
    }
    function downloadPdfBlob(blob, filename) {
        var objectUrl = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = objectUrl;
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function() { URL.revokeObjectURL(objectUrl); }, 2000);
    }
    function openWhatsAppChat(waUrl) {
        if (!waUrl) {
            alert('Müşteri WhatsApp numarası bulunamadı.');
            return;
        }
        window.location.href = waUrl;
    }

    waBtn.addEventListener('click', function() {
        if (waBtn.disabled) return;
        var pdfUrl = waBtn.getAttribute('data-pdf-url');
        var waUrl = waBtn.getAttribute('data-wa-url') || '';
        var filename = waBtn.getAttribute('data-filename') || 'Sozlesme.pdf';
        var status = document.getElementById('contractSendStatus');
        if (!pdfUrl) return;
        if (!waUrl) {
            alert('Müşteri cep telefonu kayıtlı değil.');
            return;
        }
        waBtn.disabled = true;
        var originalHtml = waBtn.innerHTML;
        waBtn.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i> PDF hazırlanıyor…';
        if (status) status.textContent = 'PDF indiriliyor, WhatsApp açılıyor…';
        fetch(pdfUrl, { credentials: 'same-origin' })
            .then(function(res) {
                if (!res.ok) throw new Error('PDF indirilemedi');
                return res.blob();
            })
            .then(function(blob) {
                downloadPdfBlob(blob, filename);
                var delay = isMobileDevice() ? 450 : 700;
                setTimeout(function() { openWhatsAppChat(waUrl); }, delay);
                if (status) {
                    status.textContent = 'PDF indirildi. WhatsApp’ta dosyayı ekleyerek gönderin.';
                    status.className = 'text-sm text-emerald-700 mt-3';
                }
            })
            .catch(function() {
                openWhatsAppChat(waUrl);
            })
            .finally(function() {
                waBtn.disabled = false;
                waBtn.innerHTML = originalHtml;
            });
    });
})();
</script>
