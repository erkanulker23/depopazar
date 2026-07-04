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
                data-customer-email="<?= htmlspecialchars($customerEmail) ?>"
                data-customer-name="<?= htmlspecialchars($customerName) ?>"
                data-contract-number="<?= htmlspecialchars($contract['contract_number'] ?? '') ?>"
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

<div id="contractEmailConfirmModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto no-print" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeContractEmailConfirmModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-start gap-3 mb-4">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 shrink-0">
                    <i class="bi bi-envelope"></i>
                </span>
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">E-posta Gönder</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">E-posta göndermek istediğinizden emin misiniz?</p>
                </div>
            </div>
            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-600 px-4 py-3 mb-4 text-sm">
                <p class="text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Alıcı</p>
                <p id="contractEmailConfirmRecipient" class="font-medium text-gray-900 dark:text-white break-all">—</p>
                <p class="text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-widest mt-3 mb-1">Sözleşme</p>
                <p id="contractEmailConfirmContract" class="font-medium text-gray-900 dark:text-white">—</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Sözleşme PDF belgesi e-posta eki olarak gönderilir.</p>
            </div>
            <p id="contractEmailConfirmStatus" class="text-sm mb-4 hidden"></p>
            <div class="flex justify-end gap-2">
                <button type="button" id="contractEmailConfirmCancelBtn" onclick="closeContractEmailConfirmModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                <button type="button" id="contractEmailConfirmSendBtn" class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:opacity-60">
                    <i class="bi bi-send mr-2"></i> Gönder
                </button>
            </div>
        </div>
    </div>
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
    var modal = document.getElementById('contractEmailConfirmModal');
    var sendBtn = document.getElementById('contractEmailConfirmSendBtn');
    var cancelBtn = document.getElementById('contractEmailConfirmCancelBtn');
    var statusEl = document.getElementById('contractEmailConfirmStatus');
    var recipientEl = document.getElementById('contractEmailConfirmRecipient');
    var contractEl = document.getElementById('contractEmailConfirmContract');
    var panelStatus = document.getElementById('contractSendStatus');
    var activeContractId = '';
    var sending = false;

    window.closeContractEmailConfirmModal = function() {
        if (sending) return;
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
        if (statusEl) {
            statusEl.classList.add('hidden');
            statusEl.textContent = '';
        }
    };

    function setStatus(message, isError) {
        if (!statusEl) return;
        statusEl.textContent = message || '';
        statusEl.classList.toggle('hidden', !message);
        statusEl.className = 'text-sm mb-4 ' + (isError ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-300');
    }

    function openEmailConfirmModal(btn) {
        if (!btn || btn.disabled || !modal) return;
        activeContractId = btn.getAttribute('data-contract-id') || '';
        var email = btn.getAttribute('data-customer-email') || '';
        var name = btn.getAttribute('data-customer-name') || '';
        var number = btn.getAttribute('data-contract-number') || '';
        if (!email) {
            alert('Müşteri e-posta adresi kayıtlı değil.');
            return;
        }
        if (recipientEl) recipientEl.textContent = (name ? name + ' · ' : '') + email;
        if (contractEl) contractEl.textContent = number || activeContractId;
        setStatus('', false);
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="bi bi-send mr-2"></i> Gönder';
        }
        if (cancelBtn) cancelBtn.disabled = false;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    }

    if (emailBtn) {
        emailBtn.addEventListener('click', function() {
            openEmailConfirmModal(emailBtn);
        });
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            if (!activeContractId || sending) return;
            sending = true;
            sendBtn.disabled = true;
            if (cancelBtn) cancelBtn.disabled = true;
            sendBtn.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i> Gönderiliyor…';
            setStatus('E-posta gönderiliyor…', false);
            var fd = new FormData();
            fd.append('contract_id', activeContractId);
            fetch('/girisler/sozlesme-eposta-gonder', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json().then(function(data) { return { data: data }; }); })
                .then(function(res) {
                    if (!res.data || !res.data.ok) {
                        sending = false;
                        sendBtn.disabled = false;
                        if (cancelBtn) cancelBtn.disabled = false;
                        sendBtn.innerHTML = '<i class="bi bi-send mr-2"></i> Gönder';
                        setStatus((res.data && res.data.error) || 'E-posta gönderilemedi.', true);
                        return;
                    }
                    sendBtn.innerHTML = '<i class="bi bi-check2 mr-2"></i> Gönderildi';
                    setStatus(res.data.message || 'E-posta gönderildi.', false);
                    if (panelStatus) {
                        panelStatus.textContent = res.data.message || 'E-posta gönderildi.';
                        panelStatus.className = 'text-sm text-emerald-700 mt-3';
                    }
                    setTimeout(function() {
                        sending = false;
                        if (cancelBtn) cancelBtn.disabled = false;
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = '<i class="bi bi-send mr-2"></i> Gönder';
                        closeContractEmailConfirmModal();
                    }, 1400);
                })
                .catch(function() {
                    sending = false;
                    sendBtn.disabled = false;
                    if (cancelBtn) cancelBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="bi bi-send mr-2"></i> Gönder';
                    setStatus('Bağlantı hatası. Tekrar deneyin.', true);
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
