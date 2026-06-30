<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
function openEditWarehouse(d) {
    document.getElementById('edit_id').value = d.id || '';
    document.getElementById('edit_name').value = d.name || '';
    document.getElementById('edit_address').value = d.address || '';
    document.getElementById('edit_city').value = d.city || '';
    document.getElementById('edit_district').value = d.district || '';
    document.getElementById('edit_floors').value = d.total_floors || '';
    document.getElementById('edit_monthly_base_fee').value = d.monthly_base_fee != null && d.monthly_base_fee !== '' ? d.monthly_base_fee : '';
    document.getElementById('edit_desc').value = d.description || '';
    document.getElementById('edit_phone').value = d.phone || '';
    document.getElementById('edit_whatsapp_number').value = d.whatsapp_number || '';
    document.getElementById('edit_email').value = d.email || '';
    document.getElementById('edit_website').value = d.website ? String(d.website).replace(/^https?:\/\//i, '') : '';
    document.getElementById('edit_active').checked = !!d.is_active;
    var preview = document.getElementById('edit_logo_preview');
    var previewWrap = document.getElementById('edit_logo_preview_wrap');
    if (preview) {
        if (d.logo_url) {
            preview.innerHTML = '<img src="' + d.logo_url.replace(/"/g, '&quot;') + '" alt="Logo" class="w-12 h-12 rounded-xl object-contain border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 p-0.5">';
            if (previewWrap) previewWrap.classList.remove('hidden');
        } else {
            preview.innerHTML = '<span class="w-12 h-12 rounded-xl inline-flex items-center justify-center text-sm font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">' + (d.name ? String(d.name).substring(0, 2).toUpperCase() : 'D') + '</span>';
            if (previewWrap) previewWrap.classList.remove('hidden');
        }
    }
    openModal('editWarehouseModal');
}
document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(el.id); });
});
</script>
