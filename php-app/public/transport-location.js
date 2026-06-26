(function () {
    function el(id) {
        return document.getElementById(id);
    }

    function sideIds(idPrefix, side) {
        return {
            typeGroup: idPrefix + '_' + side + '_type_group',
            addressBlock: idPrefix + '_' + side + '_address_block',
            depoBlock: idPrefix + '_' + side + '_depo_block',
            il: idPrefix + '_' + side + '_il',
            ilce: idPrefix + '_' + side + '_ilce',
            ilName: idPrefix + '_' + side + '_il_name',
            detail: idPrefix + '_' + side + '_address_detail',
            warehouse: idPrefix + '_' + side + '_warehouse_id',
            depoPreview: idPrefix + '_' + side + '_depo_preview',
        };
    }

    function getSourceType(idPrefix, side) {
        var container = document.querySelector('.transport-location-fields[data-id-prefix="' + idPrefix + '"][data-field-prefix="' + side + '"]');
        if (!container) return 'evden';
        var checked = container.querySelector('input[name="' + side + '_source_type"]:checked');
        return checked ? checked.value : 'evden';
    }

    function toggleLocationType(idPrefix, side, type) {
        var ids = sideIds(idPrefix, side);
        var addressBlock = el(ids.addressBlock);
        var depoBlock = el(ids.depoBlock);
        var warehouse = el(ids.warehouse);
        if (addressBlock) addressBlock.classList.toggle('hidden', type === 'depo');
        if (depoBlock) depoBlock.classList.toggle('hidden', type !== 'depo');
        if (warehouse) warehouse.required = type === 'depo';
        var group = el(ids.typeGroup);
        if (group) {
            group.querySelectorAll('.transport-location-type-label').forEach(function (label) {
                var input = label.querySelector('input[type="radio"]');
                var active = input && input.value === type;
                label.classList.toggle('border-emerald-500', active);
                label.classList.toggle('bg-emerald-50', active);
                label.classList.toggle('dark:bg-emerald-900/20', active);
                label.classList.toggle('border-gray-300', !active);
                label.classList.toggle('dark:border-gray-600', !active);
            });
        }
        updateDepoPreview(idPrefix, side);
    }

    function updateDepoPreview(idPrefix, side) {
        var ids = sideIds(idPrefix, side);
        var preview = el(ids.depoPreview);
        var warehouse = el(ids.warehouse);
        if (!preview || !warehouse) return;
        var opt = warehouse.options[warehouse.selectedIndex];
        var addr = opt && opt.getAttribute('data-address');
        preview.textContent = addr || 'Depo seçin';
    }

    function syncIlName(idPrefix, side) {
        var ids = sideIds(idPrefix, side);
        var il = el(ids.il);
        var ilName = el(ids.ilName);
        if (!il || !ilName) return;
        ilName.value = il.selectedIndex > 0 ? il.options[il.selectedIndex].text : '';
    }

    function loadIlceler(ilId, ilceSelect, callback) {
        if (!ilceSelect) return;
        ilceSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        if (!ilId) {
            ilceSelect.innerHTML = '<option value="">Önce il seçin</option>';
            return;
        }
        fetch('/api/ilceler?il_id=' + encodeURIComponent(ilId), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var list = (res && res.data) ? res.data : [];
                ilceSelect.innerHTML = '<option value="">İlçe seçin</option>';
                list.forEach(function (d) {
                    var o = document.createElement('option');
                    o.value = d.name;
                    o.textContent = d.name;
                    ilceSelect.appendChild(o);
                });
                if (typeof callback === 'function') callback();
            })
            .catch(function () {
                ilceSelect.innerHTML = '<option value="">İlçe yüklenemedi</option>';
            });
    }

    function loadIllerForSide(idPrefix, side) {
        var ids = sideIds(idPrefix, side);
        var ilSelect = el(ids.il);
        if (!ilSelect || ilSelect.options.length > 1) return Promise.resolve();
        return fetch('/api/iller', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var list = (res && res.data) ? res.data : [];
                ilSelect.innerHTML = '<option value="">İl seçin</option>';
                list.forEach(function (p) {
                    var o = document.createElement('option');
                    o.value = p.id;
                    o.textContent = p.name;
                    ilSelect.appendChild(o);
                });
            });
    }

    function bindSide(idPrefix, side) {
        var container = document.querySelector('.transport-location-fields[data-id-prefix="' + idPrefix + '"][data-field-prefix="' + side + '"]');
        if (!container) return;
        var ids = sideIds(idPrefix, side);
        var typeRadios = container.querySelectorAll('input[name="' + side + '_source_type"]');
        typeRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (this.checked) toggleLocationType(idPrefix, side, this.value);
            });
        });
        toggleLocationType(idPrefix, side, getSourceType(idPrefix, side));

        var il = el(ids.il);
        var ilce = el(ids.ilce);
        if (il && ilce) {
            il.addEventListener('change', function () {
                syncIlName(idPrefix, side);
                loadIlceler(this.value, ilce);
            });
        }
        var warehouse = el(ids.warehouse);
        if (warehouse) {
            warehouse.addEventListener('change', function () {
                updateDepoPreview(idPrefix, side);
            });
        }
    }

    function validateSide(idPrefix, side, label) {
        var type = getSourceType(idPrefix, side);
        if (type === 'depo') {
            var warehouse = el(sideIds(idPrefix, side).warehouse);
            if (!warehouse) {
                alert(label + ' için önce Depolar sayfasından depo ekleyin.');
                return false;
            }
            if (!warehouse.value) {
                alert(label + ' için depo seçmelisiniz.');
                if (warehouse) warehouse.focus();
                return false;
            }
            return true;
        }
        var detail = el(sideIds(idPrefix, side).detail);
        var ilName = el(sideIds(idPrefix, side).ilName);
        var ilce = el(sideIds(idPrefix, side).ilce);
        var hasDetail = detail && detail.value.trim() !== '';
        var hasPlace = (ilName && ilName.value.trim() !== '') || (ilce && ilce.value.trim() !== '');
        if (!hasDetail && !hasPlace) {
            alert(label + ' için il/ilçe veya açık adres girin.');
            if (detail) detail.focus();
            return false;
        }
        return true;
    }

    window.initTransportJobLocations = function (config) {
        config = config || {};
        var idPrefix = config.idPrefix || 'newJob';
        var sides = config.sides || ['pickup', 'delivery'];
        sides.forEach(function (side) {
            loadIllerForSide(idPrefix, side).then(function () {
                bindSide(idPrefix, side);
                syncIlName(idPrefix, side);
                updateDepoPreview(idPrefix, side);
            });
        });
        var form = config.form;
        if (form) {
            form.addEventListener('submit', function (e) {
                sides.forEach(function (side) {
                    syncIlName(idPrefix, side);
                });
                if (!validateSide(idPrefix, 'pickup', 'Eşyanın alınacağı yer')) {
                    e.preventDefault();
                    return;
                }
                if (sides.indexOf('delivery') !== -1 && !validateSide(idPrefix, 'delivery', 'Eşyanın gideceği yer')) {
                    e.preventDefault();
                }
            });
        }
    };
})();
