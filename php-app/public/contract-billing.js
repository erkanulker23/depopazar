/**
 * Sözleşme aylık vade dönemleri — PHP ContractBilling ile aynı mantık.
 * Giriş tarihi = 1. vade; sonraki vadeler aynı gün (ay yıl dönümü).
 */
(function (global) {
    'use strict';

    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    function normalizeDateStr(s) {
        return (s || '').slice(0, 10);
    }

    function addMonthsSameDay(anchorYmd, months) {
        anchorYmd = normalizeDateStr(anchorYmd);
        if (!anchorYmd) return '';
        var parts = anchorYmd.split('-');
        var day = parseInt(parts[2], 10);
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10) - 1;
        var target = new Date(year, month + months, 1);
        var lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
        target.setDate(Math.min(day, lastDay));
        return target.getFullYear() + '-' + pad2(target.getMonth() + 1) + '-' + pad2(target.getDate());
    }

    function formatPeriodLabel(ymd) {
        ymd = normalizeDateStr(ymd);
        if (!ymd) return '';
        var p = ymd.split('-');
        return p[2] + '.' + p[1] + '.' + p[0];
    }

    function billingPeriods(startStr, endStr) {
        startStr = normalizeDateStr(startStr);
        endStr = normalizeDateStr(endStr);
        if (!startStr || !endStr || endStr < startStr) return [];
        var periods = [];
        var index = 0;
        while (true) {
            var due = addMonthsSameDay(startStr, index);
            if (!due || due > endStr) break;
            periods.push({
                key: due,
                label: formatPeriodLabel(due),
                dueDate: due
            });
            index++;
        }
        return periods;
    }

    function isPaidPeriodKey(periodKey, paidPeriodKeys) {
        periodKey = normalizeDateStr(periodKey);
        if (!periodKey || !paidPeriodKeys || !paidPeriodKeys.length) return false;
        if (paidPeriodKeys.indexOf(periodKey) >= 0) return true;
        var legacyYm = periodKey.slice(0, 7);
        if (paidPeriodKeys.indexOf(legacyYm) >= 0) return true;
        for (var i = 0; i < paidPeriodKeys.length; i++) {
            var paid = normalizeDateStr(paidPeriodKeys[i]);
            if (paid && paid.slice(0, 7) === legacyYm) return true;
        }
        return false;
    }

    function paidAmountForPeriodKey(periodKey, paidAmountsByPeriod) {
        periodKey = normalizeDateStr(periodKey);
        if (!periodKey || !paidAmountsByPeriod) return null;
        if (paidAmountsByPeriod[periodKey] != null) return paidAmountsByPeriod[periodKey];
        var legacyYm = periodKey.slice(0, 7);
        if (paidAmountsByPeriod[legacyYm] != null) return paidAmountsByPeriod[legacyYm];
        var keys = Object.keys(paidAmountsByPeriod);
        for (var i = 0; i < keys.length; i++) {
            var paid = normalizeDateStr(keys[i]);
            if (paid && paid.slice(0, 7) === legacyYm) return paidAmountsByPeriod[keys[i]];
        }
        return null;
    }

    global.ContractBilling = {
        addMonthsSameDay: addMonthsSameDay,
        billingPeriods: billingPeriods,
        formatPeriodLabel: formatPeriodLabel,
        normalizeDateStr: normalizeDateStr,
        isPaidPeriodKey: isPaidPeriodKey,
        paidAmountForPeriodKey: paidAmountForPeriodKey
    };
})(typeof window !== 'undefined' ? window : this);
