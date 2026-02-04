<?php
$currentPage = 'nakliye-isler';
$job = $job ?? [];
$customers = $customers ?? [];
$services = $services ?? [];
$staff = $staff ?? [];
$staffIds = isset($job['staff_ids']) && is_array($job['staff_ids']) ? $job['staff_ids'] : [];
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
ob_start();
?>
<div class="mb-6">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/nakliye-isler" class="text-emerald-600 dark:text-emerald-400 hover:underline">Nakliye İşleri</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Düzenle</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Nakliye İşi Düzenle</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Müşteri: <?= htmlspecialchars(trim(($job['customer_first_name'] ?? '') . ' ' . ($job['customer_last_name'] ?? ''))) ?></p>
</div>

<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
    <form method="post" action="/nakliye-isler/guncelle">
        <input type="hidden" name="id" value="<?= htmlspecialchars($job['id'] ?? '') ?>">
        <div class="space-y-6">
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-geo-alt text-emerald-600"></i> Eşya Alındığı Yer</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres</label>
                        <textarea name="pickup_address" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($job['pickup_address'] ?? '') ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kat Durumu</label>
                            <input type="text" name="pickup_floor_status" value="<?= htmlspecialchars($job['pickup_floor_status'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asansör</label>
                            <select name="pickup_elevator_status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Seçin</option>
                                <option value="Var" <?= ($job['pickup_elevator_status'] ?? '') === 'Var' ? 'selected' : '' ?>>Var</option>
                                <option value="Yok" <?= ($job['pickup_elevator_status'] ?? '') === 'Yok' ? 'selected' : '' ?>>Yok</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Oda Sayısı</label>
                            <input type="number" name="pickup_room_count" min="0" value="<?= htmlspecialchars($job['pickup_room_count'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-geo-alt text-green-600"></i> Eşyanın Gittiği Adres</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres</label>
                        <textarea name="delivery_address" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($job['delivery_address'] ?? '') ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kat Durumu</label>
                            <input type="text" name="delivery_floor_status" value="<?= htmlspecialchars($job['delivery_floor_status'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asansör</label>
                            <select name="delivery_elevator_status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Seçin</option>
                                <option value="Var" <?= ($job['delivery_elevator_status'] ?? '') === 'Var' ? 'selected' : '' ?>>Var</option>
                                <option value="Yok" <?= ($job['delivery_elevator_status'] ?? '') === 'Yok' ? 'selected' : '' ?>>Yok</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Oda Sayısı</label>
                            <input type="number" name="delivery_room_count" min="0" value="<?= htmlspecialchars($job['delivery_room_count'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Diğer Bilgiler</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hizmet Türü</label>
                        <input type="text" name="job_type" value="<?= htmlspecialchars($job['job_type'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İş Tarihi</label>
                        <input type="date" name="job_date" value="<?= htmlspecialchars($job['job_date'] ? date('Y-m-d', strtotime($job['job_date'])) : '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiyat (₺)</label>
                        <input type="text" name="price" value="<?= $job['price'] !== null ? number_format((float)$job['price'], 2, ',', '.') : '' ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">KDV Oranı (%)</label>
                        <input type="number" name="vat_rate" value="<?= htmlspecialchars($job['vat_rate'] ?? '20') ?>" step="0.01" min="0" max="100" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hangi plakalı araçlar gitti (virgülle ayırarak birden fazla ekleyebilirsiniz)</label>
                        <input type="text" name="vehicle_plate" value="<?= htmlspecialchars($job['vehicle_plate'] ?? '') ?>" placeholder="Örn: 34 ABC 123, 06 XYZ 456" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
                        <?php
                        $statusLabels = ['pending' => 'Beklemede', 'in_progress' => 'Devam Ediyor', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal Edildi'];
                        $statusClasses = ['pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', 'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', 'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', 'cancelled' => 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-200'];
                        $jobStatus = $job['status'] ?? 'pending';
                        ?>
                        <div class="flex items-center gap-2">
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                <?php foreach ($statusLabels as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>" <?= $jobStatus === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full shrink-0 <?= $statusClasses[$jobStatus] ?? $statusClasses['pending'] ?>"><?= htmlspecialchars($statusLabels[$jobStatus] ?? $jobStatus) ?></span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-4">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="price_includes_vat" value="1" <?= !empty($job['price_includes_vat']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">KDV Dahil</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_paid" value="1" <?= !empty($job['is_paid']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Ödeme Alındı</span>
                    </label>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">İşe giden personel</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">İşe gidecek personeli seçin (seçilenler listede görünür)</p>
                    <div class="border border-gray-300 dark:border-gray-600 rounded-xl max-h-40 overflow-y-auto p-3 bg-gray-50 dark:bg-gray-700/50">
                        <?php foreach ($staff as $s): ?>
                            <label class="flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer">
                                <input type="checkbox" name="staff_ids[]" value="<?= htmlspecialchars($s['id']) ?>" <?= in_array($s['id'], $staffIds, true) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="ml-3 text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></span>
                                <?php if (in_array($s['id'], $staffIds, true)): ?><span class="ml-2 text-xs text-emerald-600 dark:text-emerald-400 font-medium">(seçili)</span><?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if (empty($staff)): ?><p class="text-sm text-gray-500 dark:text-gray-400">Personel bulunamadı.</p><?php endif; ?>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($job['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="/nakliye-isler" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">İptal</a>
            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Güncelle</button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
