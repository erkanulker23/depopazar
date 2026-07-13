/**
 * Sözleşme KDV önizlemesi — ana fiyat, aylık fiyat listesi
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

    function grossFromEntered(entered, rate, includesVat) {
        if (entered <= 0) return 0;
        if (includesVat) return Math.round(entered * 100) / 100;
        return Math.round(entered * (1 + rate / 100) * 100) / 100;
    }

    function breakdownFromGross(gross, rate, includesVat) {
        gross = Math.max(0, gross);
        if (gross <= 0) return { gross: 0, net: 0, vat: 0 };
        var net = includesVat && rate > 0 ? gross / (1 + rate / 100) : (includesVat ? gross : gross / (1 + rate / 100));
        net = Math.round(net * 100) / 100;
        var vat = Math.round(Math.max(0, gross - net) * 100) / 100;
        return { gross: gross, net: net, vat: vat };
    }

    function statusLabel(includesVat) {
        return includesVat ? 'KDV Dahil' : 'KDV Hariç';
    }

    function monthlyPriceHint(entered, rate, includesVat) {
        if (entered <= 0 || includesVat) return '';
        var gross = grossFromEntered(entered, rate, includesVat);
        var bd = breakdownFromGross(gross, rate, includesVat);
        return statusLabel(includesVat) + ' · Tahsil: ' + formatMoney(gross) + ' (KDV: ' + formatMoney(bd.vat) + ')';
    }

    function paymentAmountHint(gross, rate, includesVat) {
        if (gross <= 0 || includesVat) return '';
        var bd = breakdownFromGross(gross, rate, includesVat);
        return 'Tahsil (KDV Dahil) · Girilen fiyat KDV hariç · Net: ' + formatMoney(bd.net) + ' · KDV: ' + formatMoney(bd.vat);
    }

    function readVatSettings(prefix) {
        var rateEl = document.getElementById(prefix + '_vat_rate');
        var includesEl = document.getElementById(prefix + '_price_includes_vat');
        var rate = rateEl ? parseFloat(rateEl.value) : 20;
        if (isNaN(rate) || rate < 0) rate = 0;
        if (rate > 100) rate = 100;
        var includesVat = includesEl ? includesEl.checked : true;
        return { rate: rate, includesVat: includesVat };
    }

    function updateMonthlyRowHint(row, rate, includesVat) {
        if (!row) return;
        var inp = row.querySelector('input[name^="monthly_prices"]');
        var hint = row.querySelector('.monthly-price-vat-hint');
        if (!inp || !hint) return;
        var entered = parseMoney(inp.value);
        var text = monthlyPriceHint(entered, rate, includesVat);
        hint.textContent = text;
        hint.classList.toggle('hidden', text === '');
    }

    function refreshMonthlyPriceHints(listEl, prefix) {
        if (!listEl) return;
        var settings = readVatSettings(prefix);
        listEl.querySelectorAll('.monthly-price-row').forEach(function (row) {
            updateMonthlyRowHint(row, settings.rate, settings.includesVat);
        });
        var noteEl = document.getElementById(prefix + '_monthly_prices_vat_note');
        if (noteEl) {
            if (settings.includesVat) {
                noteEl.textContent = '';
                noteEl.classList.add('hidden');
            } else {
                noteEl.textContent = 'Aylık fiyatlar KDV Hariç olarak girilir. Ödeme takviminde KDV eklenmiş tahsil tutarı oluşur.';
                noteEl.classList.remove('hidden');
            }
        }
    }

    function bindMonthlyPriceList(listEl, prefix) {
        if (!listEl) return;
        listEl.addEventListener('input', function (e) {
            if (!e.target || !e.target.matches('input[name^="monthly_prices"]')) return;
            var row = e.target.closest('.monthly-price-row');
            var settings = readVatSettings(prefix);
            updateMonthlyRowHint(row, settings.rate, settings.includesVat);
        });
    }

    function bindVatPreview(container) {
        if (!container) return;
        var prefix = container.getAttribute('data-vat-prefix') || '';
        var priceEl = container.querySelector('[data-vat-price]') || document.getElementById(prefix + '_monthly_price');
        var rateEl = container.querySelector('.contract-vat-rate') || document.getElementById(prefix + '_vat_rate');
        var includesEl = container.querySelector('.contract-price-includes-vat') || document.getElementById(prefix + '_price_includes_vat');
        var previewEl = document.getElementById(prefix + '_vat_preview');
        var listEl = document.getElementById(prefix + '_monthly_prices_list');
        if (!priceEl || !rateEl || !includesEl) return;

        function update() {
            var entered = parseMoney(priceEl.value);
            var rate = parseFloat(rateEl.value) || 0;
            if (rate < 0) rate = 0;
            if (rate > 100) rate = 100;
            var includesVat = includesEl.checked;
            if (previewEl) {
                if (entered <= 0) {
                    previewEl.classList.add('hidden');
                    previewEl.textContent = '';
                } else {
                    previewEl.textContent = monthlyPriceHint(entered, rate, includesVat);
                    previewEl.classList.remove('hidden');
                }
            }
            refreshMonthlyPriceHints(listEl, prefix);
        }

        ['input', 'change', 'blur'].forEach(function (ev) {
            priceEl.addEventListener(ev, update);
            rateEl.addEventListener(ev, update);
        });
        includesEl.addEventListener('change', update);
        bindMonthlyPriceList(listEl, prefix);
        update();
    }

    function initContractVatPreviews() {
        document.querySelectorAll('[data-contract-vat-block]').forEach(bindVatPreview);
    }

    global.ContractVat = {
        parseMoney: parseMoney,
        formatMoney: formatMoney,
        grossFromEntered: grossFromEntered,
        breakdownFromGross: breakdownFromGross,
        statusLabel: statusLabel,
        monthlyPriceHint: monthlyPriceHint,
        paymentAmountHint: paymentAmountHint,
        refreshMonthlyPriceHints: refreshMonthlyPriceHints,
        updateMonthlyRowHint: updateMonthlyRowHint,
        readVatSettings: readVatSettings
    };
    global.initContractVatPreviews = initContractVatPreviews;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initContractVatPreviews);
    } else {
        initContractVatPreviews();
    }
})(typeof window !== 'undefined' ? window : this);
