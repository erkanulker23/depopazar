(function () {
    'use strict';

    function digitsOnly(value) {
        return (value || '').replace(/\D/g, '');
    }

    function normalizeTrMobileDigits(raw) {
        var v = digitsOnly(raw);
        if (v.length >= 12 && v.indexOf('90') === 0) {
            v = v.substring(2);
        }
        if (v.length === 10 && v.charAt(0) === '5') {
            v = '0' + v;
        }
        if (v.length > 0 && v.charAt(0) !== '0') {
            v = '0' + v;
        }
        if (v.length > 11) {
            v = v.substring(0, 11);
        }
        return v;
    }

    function formatDisplay(digits) {
        if (!digits) {
            return '';
        }
        if (digits.length <= 1) {
            return digits;
        }
        var parts = [digits.substring(0, 1)];
        if (digits.length > 1) {
            parts.push(digits.substring(1, Math.min(4, digits.length)));
        }
        if (digits.length > 4) {
            parts.push(digits.substring(4, Math.min(7, digits.length)));
        }
        if (digits.length > 7) {
            parts.push(digits.substring(7, Math.min(9, digits.length)));
        }
        if (digits.length > 9) {
            parts.push(digits.substring(9, Math.min(11, digits.length)));
        }
        return parts.join(' ');
    }

    function applyPhoneMask(inp) {
        if (!inp) {
            return;
        }
        var digits = normalizeTrMobileDigits(inp.value);
        inp.value = formatDisplay(digits);
    }

    function initAll() {
        document.querySelectorAll('input[data-phone-mask]').forEach(applyPhoneMask);
    }

    document.addEventListener('input', function (e) {
        if (e.target && e.target.matches && e.target.matches('input[data-phone-mask]')) {
            applyPhoneMask(e.target);
        }
    });

    document.addEventListener('paste', function (e) {
        if (e.target && e.target.matches && e.target.matches('input[data-phone-mask]')) {
            setTimeout(function () {
                applyPhoneMask(e.target);
            }, 0);
        }
    });

    document.addEventListener('blur', function (e) {
        if (e.target && e.target.matches && e.target.matches('input[data-phone-mask]')) {
            applyPhoneMask(e.target);
        }
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.querySelectorAll) {
            return;
        }
        form.querySelectorAll('input[data-phone-mask]').forEach(function (inp) {
            inp.value = normalizeTrMobileDigits(inp.value);
        });
    }, true);

    window.applyPhoneMaskToInput = applyPhoneMask;
    window.initPhoneMasks = initAll;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
