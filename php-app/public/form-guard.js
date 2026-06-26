(function () {
    'use strict';

    var lockedForms = new WeakSet();

    function unlockForm(form) {
        if (!form) {
            return;
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
        lockedForms.add(form);
        form.dataset.submitting = '1';
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
            btn.disabled = true;
            if (btn.tagName === 'BUTTON' && !btn.dataset.loadingText) {
                btn.dataset.loadingText = btn.textContent;
                btn.textContent = 'Kaydediliyor...';
            }
        });
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
        if (method === 'get') {
            return;
        }
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
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
    }, true);

    window.resetSubmitForm = unlockForm;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            var obs = new MutationObserver(function () {
                if (!overlay.classList.contains('hidden')) {
                    overlay.querySelectorAll('form').forEach(unlockForm);
                }
            });
            obs.observe(overlay, { attributes: true, attributeFilter: ['class'] });
        });
    });
})();
