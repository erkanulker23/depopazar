<?php
$currentPage = 'teklifler';
$customers = $customers ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
$statusLabels = ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul', 'rejected' => 'Red'];
ob_start();
?>
<div class="mb-6">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/teklifler" class="text-emerald-600 dark:text-emerald-400 hover:underline">Teklifler</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Yeni Teklif</span>
    </nav>
    <h1 class="page-title gradient-title">Yeni Teklif</h1>
</div>

<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="card-modern p-6 max-w-lg">
    <form method="post" action="/teklifler/ekle" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlık</label>
            <input type="text" name="title" value="Teklif" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Müşteri</label>
            <select name="customer_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <option value="">Seçin (opsiyonel)</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ($c['email'] ? ' - ' . $c['email'] : '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <?php foreach ($statusLabels as $val => $l): ?>
                    <option value="<?= htmlspecialchars($val) ?>" <?= $val === 'draft' ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Toplam Tutar (₺)</label>
            <input type="text" name="total_amount" value="0" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Geçerlilik Tarihi</label>
            <input type="date" name="valid_until" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-600">
            <a href="/teklifler" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</a>
            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Oluştur</button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
