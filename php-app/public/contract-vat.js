/**
 * Sözleşme formlarında KDV önizlemesi
 */
(function (global) {
    'use strict';

    function parseMoney(val) {
        val = String(val || '').trim().replace(/\s/g, '').replace(/₺/g, '');
        if (!val) return 0;
        if (val.indexOf(',') >= 0) {
            val = val.replace(/\./g, '').replace(',', '.');
        }
        var n = parseFloat(val);
        return isNaN(n) ? 0 : n;
    }

    function formatMoney(n) {
        if (!n || n <= 0) return '0,00 ₺';
        var fixed = (Math.round(n * 100) / 100).toFixed(2);
        var parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return parts.join(',') + ' ₺';
    }

    function bindVatPreview(container) {
        if (!container) return;
        var prefix = container.getAttribute('data-vat-prefix') || '';
        var priceEl = container.querySelector('[data-vat-price]') || document.getElementById(prefix + '_monthly_price');
        var rateEl = container.querySelector('.contract-vat-rate') || document.getElementById(prefix + '_vat_rate');
        var includesEl = container.querySelector('.contract-price-includes-vat') || document.getElementById(prefix + '_price_includes_vat');
        var previewEl = document.getElementById(prefix + '_vat_preview');
        if (!priceEl || !rateEl || !includesEl || !previewEl) return;

        function update() {
            var entered = parseMoney(priceEl.value);
            var rate = parseFloat(rateEl.value) || 0;
            if (rate < 0) rate = 0;
            if (rate > 100) rate = 100;
            var includesVat = includesEl.checked;
            if (entered <= 0) {
                previewEl.classList.add('hidden');
                previewEl.textContent = '';
                return;
            }
            var gross = includesVat ? entered : entered * (1 + rate / 100);
            var net = includesVat && rate > 0 ? gross / (1 + rate / 100) : (includesVat ? gross : entered);
            var vat = Math.max(0, gross - net);
            var msg = includesVat
                ? 'Girilen tutar KDV dahil. Net: ' + formatMoney(net) + ', KDV (%' + rate + '): ' + formatMoney(vat) + ', Tahsil: ' + formatMoney(gross)
                : 'Girilen tutar KDV hariç. Net: ' + formatMoney(entered) + ', KDV (%' + rate + '): ' + formatMoney(vat) + ', Tahsil: ' + formatMoney(gross);
            previewEl.textContent = msg;
            previewEl.classList.remove('hidden');
        }

        ['input', 'change', 'blur'].forEach(function (ev) {
            priceEl.addEventListener(ev, update);
            rateEl.addEventListener(ev, update);
        });
        includesEl.addEventListener('change', update);
        update();
    }

    function initContractVatPreviews() {
        document.querySelectorAll('[data-contract-vat-block]').forEach(bindVatPreview);
    }

    global.initContractVatPreviews = initContractVatPreviews;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initContractVatPreviews);
    } else {
        initContractVatPreviews();
    }
})(typeof window !== 'undefined' ? window : this);
