<?php
$currentPage = 'odalar';
$statusLabels = ['empty' => 'Boş', 'occupied' => 'Dolu', 'reserved' => 'Rezerve', 'locked' => 'Kilitli'];
$statusClass = ($room['status'] ?? '') === 'empty' ? 'bg-green-100 text-green-800' : (($room['status'] ?? '') === 'occupied' ? 'bg-red-100 text-red-800' : (($room['status'] ?? '') === 'reserved' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'));
ob_start();
?>
<div class="mb-4">
    <a href="/odalar" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
        <i class="bi bi-arrow-left mr-1"></i> Odalara dön
    </a>
</div>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-xl font-bold text-gray-900">Oda: <?= htmlspecialchars($room['room_number']) ?></h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <dl class="space-y-3">
                    <div><dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Depo</dt><dd class="text-gray-900"><?= htmlspecialchars($room['warehouse_name'] ?? '-') ?></dd></div>
                    <div><dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Alan (m²)</dt><dd class="text-gray-900"><?= number_format((float)$room['area_m2'], 2, ',', '.') ?></dd></div>
                    <div><dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Aylık Fiyat</dt><dd class="text-gray-900"><?= number_format((float)$room['monthly_price'], 2, ',', '.') ?> ₺</dd></div>
                    <div><dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Durum</dt><dd><span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $statusClass ?>"><?= $statusLabels[$room['status'] ?? ''] ?? $room['status'] ?? '-' ?></span></dd></div>
                    <div><dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Kat / Blok / Koridor</dt><dd class="text-gray-900"><?= htmlspecialchars(trim(($room['floor'] ?? '') . ' / ' . ($room['block'] ?? '') . ' / ' . ($room['corridor'] ?? '')) ?: '-') ?></dd></div>
                </dl>
            </div>
            <div>
                <?php if (!empty($room['description'])): ?>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Açıklama</p>
                    <p class="text-gray-700 mb-4"><?= nl2br(htmlspecialchars($room['description'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($room['notes'])): ?>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Notlar</p>
                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($room['notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <hr class="my-6 border-gray-100">
        <div class="flex flex-wrap gap-2">
            <a href="/odalar" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Listeye dön</a>
            <a href="/odalar" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">Oda listesinde düzenle</a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
