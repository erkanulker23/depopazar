(function () {
    'use strict';

    function roomLabel(r) {
        var parts = [r.room_number || ''];
        if (r.status && r.status !== 'empty') {
            parts.push('(' + r.status + ')');
        }
        if (r.monthly_price !== null && r.monthly_price !== undefined && r.monthly_price !== '') {
            parts.push(Number(r.monthly_price).toLocaleString('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' ₺');
        }
        return parts.filter(Boolean).join(' · ');
    }

    window.initRoomPicker = function (options) {
        var hiddenId = document.getElementById(options.hiddenInputId);
        var searchInput = document.getElementById(options.searchInputId);
        var resultsEl = document.getElementById(options.resultsId);
        var warehouseSelect = document.getElementById(options.warehouseSelectId);
        var hintEl = options.hintId ? document.getElementById(options.hintId) : null;
        var rooms = Array.isArray(options.rooms) ? options.rooms : [];
        var selected = null;
        var debounceTimer = null;

        if (!hiddenId || !searchInput || !resultsEl || !warehouseSelect) {
            return null;
        }

        function currentWarehouseId() {
            return warehouseSelect.value || '';
        }

        function filteredRooms(query) {
            var wh = currentWarehouseId();
            if (!wh) {
                return [];
            }
            var q = (query || '').trim();
            var exclude = typeof options.getExcludeIds === 'function' ? options.getExcludeIds() : [];
            return rooms.filter(function (r) {
                if ((r.warehouse_id || '') !== wh) {
                    return false;
                }
                if (exclude.indexOf(r.id) !== -1 || exclude.indexOf(String(r.id)) !== -1) {
                    return false;
                }
                if (!q) {
                    return true;
                }
                var num = String(r.room_number || '');
                var matchFn = typeof window.turkishSearchMatch === 'function'
                    ? window.turkishSearchMatch
                    : function (h, n) { return String(h).toLowerCase().indexOf(String(n).toLowerCase()) !== -1; };
                return matchFn(num, q);
            });
        }

        function setEnabled(enabled) {
            searchInput.disabled = !enabled;
            searchInput.placeholder = enabled ? 'Oda no ile ara...' : 'Önce depo seçin';
            searchInput.classList.toggle('opacity-60', !enabled);
            searchInput.classList.toggle('cursor-not-allowed', !enabled);
            if (hintEl) {
                hintEl.textContent = enabled ? 'Oda numarası yazarak arayın' : 'Önce depo seçin';
            }
        }

        function clearSelected() {
            selected = null;
            hiddenId.value = '';
            searchInput.value = '';
            if (typeof options.onClear === 'function') {
                options.onClear();
            }
        }

        function setSelected(r) {
            if (!r || !r.id) {
                return;
            }
            selected = r;
            hiddenId.value = r.id;
            searchInput.value = r.room_number || '';
            resultsEl.classList.add('hidden');
            if (typeof options.onSelect === 'function') {
                options.onSelect(r);
            }
        }

        function renderResults(list) {
            resultsEl.innerHTML = '';
            if (!currentWarehouseId()) {
                var needWh = document.createElement('p');
                needWh.className = 'p-3 text-sm text-gray-500 dark:text-gray-400';
                needWh.textContent = 'Önce depo seçin.';
                resultsEl.appendChild(needWh);
            } else if (!list.length) {
                var empty = document.createElement('p');
                empty.className = 'p-3 text-sm text-gray-500 dark:text-gray-400';
                empty.textContent = 'Bu depoda uygun oda bulunamadı. Aramayı değiştirin.';
                resultsEl.appendChild(empty);
            } else {
                list.forEach(function (r) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-full text-left px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-600 last:border-0';
                    btn.textContent = roomLabel(r);
                    btn.addEventListener('mousedown', function (ev) {
                        ev.preventDefault();
                    });
                    btn.addEventListener('click', function () {
                        setSelected(r);
                    });
                    resultsEl.appendChild(btn);
                });
            }
            resultsEl.classList.remove('hidden');
        }

        function showResults() {
            renderResults(filteredRooms(searchInput.value.trim()));
        }

        warehouseSelect.addEventListener('change', function () {
            clearSelected();
            setEnabled(!!currentWarehouseId());
            resultsEl.classList.add('hidden');
            if (typeof options.onWarehouseChange === 'function') {
                options.onWarehouseChange(currentWarehouseId());
            }
        });

        searchInput.addEventListener('input', function () {
            if (!currentWarehouseId()) {
                return;
            }
            if (selected && searchInput.value.trim() !== String(selected.room_number || '').trim()) {
                clearSelected();
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(showResults, 150);
        });

        searchInput.addEventListener('focus', function () {
            if (!currentWarehouseId()) {
                warehouseSelect.focus();
                return;
            }
            showResults();
        });

        searchInput.addEventListener('blur', function () {
            setTimeout(function () {
                resultsEl.classList.add('hidden');
            }, 180);
        });

        var form = hiddenId.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                var required = typeof options.isRequired === 'function' ? options.isRequired() : true;
                if (!required) {
                    return;
                }
                if (!hiddenId.value) {
                    e.preventDefault();
                    if (!currentWarehouseId()) {
                        warehouseSelect.focus();
                        alert('Lütfen önce depo seçin.');
                    } else {
                        searchInput.focus();
                        alert('Lütfen listeden bir oda seçin.');
                    }
                }
            });
        }

        setEnabled(!!currentWarehouseId());

        return {
            clearSelected: clearSelected,
            setSelected: setSelected,
            filteredRooms: filteredRooms,
            setEnabled: setEnabled,
        };
    };
})();
