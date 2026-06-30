<!-- Modal: Depo Düzenle -->
<div id="editWarehouseModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-end md:items-center justify-center p-0 md:p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeModal('editWarehouseModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-t-2xl md:rounded-xl shadow-xl w-full max-w-lg md:max-w-2xl p-5 md:p-6 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 sticky top-0 bg-white dark:bg-gray-800 z-10 pb-2 border-b border-gray-100 dark:border-gray-700 md:border-0 md:static md:pb-0">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Depo Düzenle</h3>
                <button type="button" onclick="closeModal('editWarehouseModal')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 btn-touch"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/depolar/guncelle" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <div id="edit_logo_preview_wrap" class="flex items-center gap-3">
                        <div id="edit_logo_preview"></div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Mevcut logo</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo Adı <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo Logosu</label>
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Yeni dosya seçerseniz mevcut logo değişir</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adres</label>
                        <input type="text" name="address" id="edit_address" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İl</label>
                            <input type="text" name="city" id="edit_city" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İlçe</label>
                            <input type="text" name="district" id="edit_district" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kat Sayısı</label>
                        <input type="number" name="total_floors" id="edit_floors" min="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aylık Depo Ücreti (₺)</label>
                        <input type="text" name="monthly_base_fee" id="edit_monthly_base_fee" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" id="edit_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <?php $prefix = 'edit_'; require __DIR__ . '/../partials/warehouse_contact_form_fields.php'; ?>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" id="edit_active" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="edit_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</label>
                    </div>
                </div>
                <div class="form-submit-bar mt-6 flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <button type="button" onclick="closeModal('editWarehouseModal')" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 btn-touch">İptal</button>
                    <button type="submit" class="w-full sm:w-auto btn-touch px-4 py-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>
