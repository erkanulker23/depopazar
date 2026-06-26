(function () {
    'use strict';

    var lockedForms = new WeakSet();
    var unlockTimers = new WeakMap();

    function unlockForm(form) {
        if (!form) {
            return;
        }
        var timer = unlockTimers.get(form);
        if (timer) {
            clearTimeout(timer);
            unlockTimers.delete(form);
        }
        lockedForms.delete(form);
        delete form.dataset.submitting;
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
            btn.disabled = false;
            if (btn.dataset.loadingText) {
                btn.textContent = btn.dataset.loadingText;
                delete btn.dataset.loadingText;
            }
        });
    }

    function lockForm(form) {
        if (!form || form.tagName !== 'FORM' || lockedForms.has(form)) {
            return false;
        }
        if (form.dataset.noGuard === '1') {
            return true;
        }
        lockedForms.add(form);
        form.dataset.submitting = '1';
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
            btn.disabled = true;
            if (btn.tagName === 'BUTTON' && !btn.dataset.loadingText) {
                btn.dataset.loadingText = btn.textContent;
                btn.textContent = 'Kaydediliyor...';
            }
        });
        unlockTimers.set(form, setTimeout(function () {
            unlockForm(form);
        }, 60000));
        return true;
    }

    document.addEventListener('invalid', function (e) {
        var form = e.target && e.target.closest ? e.target.closest('form') : null;
        if (form) {
            unlockForm(form);
        }
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method === 'get' || form.dataset.noGuard === '1') {
            return;
        }
        if (form.dataset.submitting === '1' && lockedForms.has(form)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }
        if (!lockForm(form)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    window.resetSubmitForm = unlockForm;

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submitting="1"]').forEach(unlockForm);
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            var obs = new MutationObserver(function () {
                if (!overlay.classList.contains('hidden')) {
                    overlay.querySelectorAll('form').forEach(unlockForm);
                }
            });
            obs.observe(overlay, { attributes: true, attributeFilter: ['class'] });
        });

        document.querySelectorAll('[data-flash-error]').forEach(function (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });
})();
