(function () {
    'use strict';

    window.turkishSearchKey = function (text) {
        if (text == null) {
            return '';
        }
        text = String(text).trim();
        if (!text) {
            return '';
        }
        text = text.replace(/İ/g, 'i').replace(/I/g, 'i');
        try {
            text = text.toLocaleLowerCase('tr-TR');
        } catch (e) {
            text = text.toLowerCase();
        }
        return text
            .replace(/ı/g, 'i')
            .replace(/ğ/g, 'g')
            .replace(/ü/g, 'u')
            .replace(/ş/g, 's')
            .replace(/ö/g, 'o')
            .replace(/ç/g, 'c');
    };

    window.turkishSearchMatch = function (haystack, needle) {
        needle = window.turkishSearchKey(needle);
        if (!needle) {
            return true;
        }
        return window.turkishSearchKey(haystack).indexOf(needle) !== -1;
    };
})();
