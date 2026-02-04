<?php
$currentPage = 'girisler';
$contract = $contract ?? [];
ob_start();
?>
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>" class="text-emerald-600 hover:text-emerald-700 font-medium">Sözleşme detayı</a>
        <i class="bi bi-chevron-right"></i>
        <span class="text-gray-700 font-medium">Düzenle</span>
    </div>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Sözleşme Düzenle</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($contract['contract_number'] ?? '') ?></p>
</div>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form method="post" action="/girisler/guncelle" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 max-w-2xl">
    <input type="hidden" name="contract_id" value="<?= htmlspecialchars($contract['id'] ?? '') ?>">
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Başlangıç Tarihi</label>
                <input type="date" name="start_date" value="<?= !empty($contract['start_date']) ? date('Y-m-d', strtotime($contract['start_date'])) : '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bitiş Tarihi</label>
                <input type="date" name="end_date" value="<?= !empty($contract['end_date']) ? date('Y-m-d', strtotime($contract['end_date'])) : '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nakliye Ücreti (₺)</label>
                <input type="text" name="transportation_fee" value="<?= htmlspecialchars($contract['transportation_fee'] ?? '0') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">İndirim (₺)</label>
                <input type="text" name="discount" value="<?= htmlspecialchars($contract['discount'] ?? '0') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Eşyanın Alındığı Yer</label>
            <input type="text" name="pickup_location" value="<?= htmlspecialchars($contract['pickup_location'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Şoför Adı</label>
                <input type="text" name="driver_name" value="<?= htmlspecialchars($contract['driver_name'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Şoför Telefon</label>
                <input type="text" name="driver_phone" value="<?= htmlspecialchars($contract['driver_phone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Araç Plakası</label>
                <input type="text" name="vehicle_plate" value="<?= htmlspecialchars($contract['vehicle_plate'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notlar</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($contract['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-6 flex gap-2">
        <a href="/girisler/<?= htmlspecialchars($contract['id'] ?? '') ?>" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">İptal</a>
        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
