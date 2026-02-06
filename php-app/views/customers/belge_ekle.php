<?php
$customer = $customer ?? null;
$customerName = $customerName ?? '';
$customerId = $customer['id'] ?? '';
ob_start();
?>
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/musteriler" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium">Müşteriler</a>
        <i class="bi bi-chevron-right"></i>
        <a href="/musteriler/<?= htmlspecialchars($customerId) ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium"><?= htmlspecialchars($customerName) ?></a>
        <i class="bi bi-chevron-right"></i>
        <span class="text-gray-700 dark:text-gray-300 font-medium">Belge Ekle</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Belge Ekle</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($customerName) ?> için belge yükleyin (PDF, resim, Word).</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-lg">
    <form method="post" action="/musteriler/belge-ekle" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customerId) ?>">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Belge adı</label>
            <input type="text" name="name" placeholder="Örn: Kimlik fotokopisi" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Boş bırakılırsa dosya adı kullanılır.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dosya <span class="text-red-500">*</span></label>
            <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PDF, JPG, PNG, GIF, WebP, DOC, DOCX</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
            <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
        </div>
        <div class="flex flex-wrap gap-2 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
                <i class="bi bi-upload"></i> Yükle
            </button>
            <a href="/musteriler/<?= htmlspecialchars($customerId) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">İptal</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
