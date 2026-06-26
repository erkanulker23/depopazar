(function () {
    'use strict';

    function customerLabel(c) {
        var parts = [c.name || ''];
        if (c.phone) {
            parts.push(c.phone);
        }
        if (c.email) {
            parts.push(c.email);
        }
        return parts.filter(Boolean).join(' · ');
    }

    window.initCustomerPicker = function (options) {
        var hiddenId = document.getElementById(options.hiddenInputId);
        var searchInput = document.getElementById(options.searchInputId);
        var resultsEl = document.getElementById(options.resultsId);
        var selectedEl = options.selectedLabelId ? document.getElementById(options.selectedLabelId) : null;
        var debounceTimer = null;
        var selected = null;

        if (!hiddenId || !searchInput || !resultsEl) {
            return null;
        }

        function setSelected(c) {
            if (!c || !c.id) {
                return;
            }
            selected = c;
            hiddenId.value = c.id;
            searchInput.value = c.name || '';
            if (selectedEl) {
                selectedEl.textContent = customerLabel(c);
                selectedEl.classList.remove('hidden');
            }
            resultsEl.classList.add('hidden');
        }

        function clearSelected() {
            selected = null;
            hiddenId.value = '';
            if (selectedEl) {
                selectedEl.classList.add('hidden');
            }
        }

        function renderResults(list) {
            resultsEl.innerHTML = '';
            if (!list.length) {
                var empty = document.createElement('p');
                empty.className = 'p-3 text-sm text-gray-500 dark:text-gray-400';
                empty.textContent = 'Müşteri bulunamadı. Aramayı değiştirin veya hızlı müşteri ekleyin.';
                resultsEl.appendChild(empty);
            } else {
                list.forEach(function (c) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-full text-left px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-600 last:border-0';
                    btn.textContent = customerLabel(c);
                    btn.addEventListener('mousedown', function (ev) {
                        ev.preventDefault();
                    });
                    btn.addEventListener('click', function () {
                        setSelected(c);
                    });
                    resultsEl.appendChild(btn);
                });
            }
            resultsEl.classList.remove('hidden');
        }

        function fetchCustomers(q) {
            var params = new URLSearchParams({ limit: String(options.limit || 50) });
            if (q) {
                params.set('q', q);
            }
            return fetch('/api/musteriler?' + params.toString(), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    renderResults((res && res.data) ? res.data : []);
                })
                .catch(function () {
                    renderResults([]);
                });
        }

        function fetchCustomerById(id) {
            if (!id) {
                return Promise.resolve();
            }
            return fetch('/api/musteriler?id=' + encodeURIComponent(id), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.data) {
                        setSelected(res.data);
                    }
                });
        }

        searchInput.addEventListener('input', function () {
            if (selected && searchInput.value.trim() !== (selected.name || '').trim()) {
                clearSelected();
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                fetchCustomers(searchInput.value.trim());
            }, 250);
        });

        searchInput.addEventListener('focus', function () {
            fetchCustomers(searchInput.value.trim());
        });

        searchInput.addEventListener('blur', function () {
            setTimeout(function () {
                resultsEl.classList.add('hidden');
            }, 180);
        });

        var form = hiddenId.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!hiddenId.value) {
                    e.preventDefault();
                    searchInput.focus();
                    alert('Lütfen listeden bir müşteri seçin.');
                }
            });
        }

        if (options.initialCustomerId) {
            fetchCustomerById(options.initialCustomerId);
        }

        return {
            setSelected: setSelected,
            clearSelected: clearSelected,
            fetchCustomers: fetchCustomers,
            fetchCustomerById: fetchCustomerById,
        };
    };
})();
