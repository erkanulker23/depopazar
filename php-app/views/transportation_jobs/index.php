<?php
$currentPage = 'nakliye-isler';
$jobs = $jobs ?? [];
$years = $years ?? [];
$customers = $customers ?? [];
$services = $services ?? [];
$staff = $staff ?? [];
$newCustomerId = $newCustomerId ?? '';
$currentQ = isset($_GET['q']) ? trim($_GET['q']) : '';
$currentYear = isset($_GET['year']) && $_GET['year'] !== '' ? (int) $_GET['year'] : '';
$currentMonth = isset($_GET['month']) && $_GET['month'] !== '' ? (int) $_GET['month'] : '';
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Nakliye İşleri</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Nakliye işleri yönetimi ve takibi</p>
</div>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div class="flex flex-wrap items-center gap-2">
        <form method="get" action="/nakliye-isler" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="<?= htmlspecialchars($currentQ) ?>" placeholder="Müşteri ara..." class="px-3 py-2 border border-gray-300 rounded-xl text-sm w-48 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            <select name="year" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Tüm Yıllar</option>
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= $currentYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
                <?php foreach ($years as $y): if ($y && !in_array($y, range(date('Y'), date('Y') - 5), true)): ?>
                    <option value="<?= $y ?>" <?= $currentYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endif; endforeach; ?>
            </select>
            <select name="month" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Tüm Aylar</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $currentMonth === $m ? 'selected' : '' ?>><?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="px-3 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Filtrele</button>
        </form>
        <a href="/nakliye-isler" class="px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50">Filtreleri Temizle</a>
    </div>
    <button type="button" onclick="openNewJobModal()" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
        <i class="bi bi-plus-lg mr-2"></i> Yeni Nakliye İşi Ekle
    </button>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($jobs)): ?>
        <div class="p-12 text-center text-gray-500 dark:text-gray-400">
            <i class="bi bi-truck text-5xl text-gray-300 dark:text-gray-600 block mb-3"></i>
            <p>Henüz nakliye işi bulunmamaktadır.</p>
        </div>
    <?php else: ?>
        <div id="jobBulkBar" class="hidden flex items-center justify-between gap-3 px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><span id="jobBulkCount">0</span> iş seçildi</span>
            <form method="post" action="/nakliye-isler/sil" id="jobBulkDeleteForm">
                <div id="jobBulkIdsContainer"></div>
                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Toplu Sil</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <?php
                    $statusLabels = ['pending' => 'Beklemede', 'in_progress' => 'Devam Ediyor', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal Edildi'];
                    $statusClasses = [
                        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                        'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                        'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                        'cancelled' => 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-200',
                    ];
                    ?>
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" id="selectAllJobs" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" title="Tümünü seç"></label></th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tarih</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Alış / Teslim</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Araç Plakası</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşe Giden Personel</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($jobs as $j): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="job-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($j['id']) ?>"></label></td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars(($j['customer_first_name'] ?? '') . ' ' . ($j['customer_last_name'] ?? '')) ?></span>
                                <?php if (!empty($j['customer_phone'])): ?><br><span class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($j['customer_phone']) ?></span><?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= $j['job_date'] ? date('d.m.Y', strtotime($j['job_date'])) : '-' ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                <?php
                                $p = mb_substr(trim($j['pickup_address'] ?? ''), 0, 25);
                                $d = mb_substr(trim($j['delivery_address'] ?? ''), 0, 25);
                                echo $p || $d ? htmlspecialchars($p . ($p && $d ? ' / ' : '') . $d) . (mb_strlen($j['pickup_address'] ?? '') > 25 || mb_strlen($j['delivery_address'] ?? '') > 25 ? '…' : '') : '-';
                                ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                <?php if (!empty($j['vehicle_plate'])): ?>
                                    <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($j['vehicle_plate']) ?></span>
                                <?php else: ?>–<?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= !empty($j['staff_names']) ? htmlspecialchars($j['staff_names']) : '–' ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= ($j['price'] !== null && $j['price'] !== '') ? fmtPrice($j['price']) : '-' ?></td>
                            <td class="px-4 py-3">
                                <?php $st = $j['status'] ?? 'pending'; $stClass = $statusClasses[$st] ?? $statusClasses['pending']; $stLabel = $statusLabels[$st] ?? $st; ?>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $stClass ?>"><?= htmlspecialchars($stLabel) ?></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="/nakliye-isler/<?= htmlspecialchars($j['id']) ?>" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 mr-1" title="Detay"><i class="bi bi-eye"></i></a>
                                <a href="/nakliye-isler/<?= htmlspecialchars($j['id']) ?>/duzenle" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 mr-1" title="Düzenle"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="/nakliye-isler/sil" class="inline" onsubmit="return confirm('Bu nakliye işini silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="ids[]" value="<?= htmlspecialchars($j['id']) ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100" title="Sil"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Yeni Nakliye İşi Ekle (eski sistemle uyumlu) -->
<div id="newJobModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeNewJobModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Nakliye İşi Ekle</h3>
                <button type="button" onclick="closeNewJobModal()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/nakliye-isler/ekle">
                <div class="space-y-6">
                    <!-- Müşteri + Hizmet Türü -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Müşteri <span class="text-red-500">*</span></label>
                                <button type="button" onclick="event.preventDefault(); document.getElementById('newJobCustomerModal').classList.remove('hidden'); document.getElementById('newJobCustomerModal').setAttribute('aria-hidden','false');" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">+ Yeni müşteri ekle</button>
                            </div>
                            <select name="customer_id" id="newJob_customer_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Müşteri seçin</option>
                                <?php foreach ($customers as $cu): ?>
                                    <option value="<?= htmlspecialchars($cu['id']) ?>"><?= htmlspecialchars($cu['first_name'] . ' ' . $cu['last_name'] . ($cu['email'] ? ' - ' . $cu['email'] : '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hizmet Türü <span class="text-red-500">*</span></label>
                            <select name="job_type" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Hizmet seçin</option>
                                <?php foreach ($services as $sv): ?>
                                    <option value="<?= htmlspecialchars($sv['name'] ?? $sv['id']) ?>"><?= htmlspecialchars($sv['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                                <?php if (empty($services)): ?><option value="" disabled>Önce Hizmetler sayfasından hizmet ekleyin</option><?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Eşya Alındığı Yer -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-geo-alt text-emerald-600"></i> Eşya Alındığı Yer</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres <span class="text-red-500">*</span></label>
                                <textarea name="pickup_address" rows="3" placeholder="İl, İlçe, Mahalle, Sokak, Bina No vb. tam adres bilgisi" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kat Durumu</label>
                                    <input type="text" name="pickup_floor_status" placeholder="örn: Zemin, 1. Kat, 2. Kat" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asansör Durumu</label>
                                    <select name="pickup_elevator_status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Seçin</option>
                                        <option value="Var">Var</option>
                                        <option value="Yok">Yok</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Oda Sayısı</label>
                                    <input type="number" name="pickup_room_count" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eşyanın Gittiği Adres -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-geo-alt text-green-600"></i> Eşyanın Gittiği Adres</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres <span class="text-red-500">*</span></label>
                                <textarea name="delivery_address" rows="3" placeholder="İl, İlçe, Mahalle, Sokak, Bina No vb. tam adres bilgisi" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kat Durumu</label>
                                    <input type="text" name="delivery_floor_status" placeholder="örn: Zemin, 1. Kat, 2. Kat" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asansör Durumu</label>
                                    <select name="delivery_elevator_status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Seçin</option>
                                        <option value="Var">Var</option>
                                        <option value="Yok">Yok</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Oda Sayısı</label>
                                    <input type="number" name="delivery_room_count" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Diğer Bilgiler -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Diğer Bilgiler</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hangi plakalı araçlar gitti (virgülle ayırarak birden fazla ekleyebilirsiniz)</label>
                                <input type="text" name="vehicle_plate" placeholder="Örn: 34 ABC 123, 06 XYZ 456" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">İşe giden personel</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">İşe gidecek personeli seçin (seçilenler listede görünür)</p>
                                <div class="border border-gray-300 dark:border-gray-600 rounded-xl max-h-40 overflow-y-auto p-3 bg-gray-50 dark:bg-gray-700/50">
                                    <?php if (empty($staff)): ?>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Personel bulunamadı.</p>
                                    <?php else: ?>
                                        <div class="space-y-2">
                                            <?php foreach ($staff as $s): ?>
                                                <label class="flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer">
                                                    <input type="checkbox" name="staff_ids[]" value="<?= htmlspecialchars($s['id']) ?>" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                                    <span class="ml-3 text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İş Tarihi</label>
                                <input type="date" name="job_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiyat (₺)</label>
                                <input type="text" name="price" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">KDV Oranı (%)</label>
                                <input type="number" name="vat_rate" value="20" step="0.01" min="0" max="100" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="pending">Beklemede</option>
                                    <option value="in_progress">Devam Ediyor</option>
                                    <option value="completed">Tamamlandı</option>
                                    <option value="cancelled">İptal Edildi</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="price_includes_vat" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">KDV Dahil</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_paid" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Ödeme Alındı</span>
                            </label>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PDF Sözleşme (Opsiyonel)</label>
                            <input type="file" name="contract_pdf" accept=".pdf" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 mt-1">Dosya seçilmedi. PDF yükleme sunucu ayarları ile kullanılabilir.</p>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                            <textarea name="notes" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeNewJobModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Yeni Müşteri Ekle (Nakliye İşi içinden) -->
<div id="newJobCustomerModal" class="modal-overlay hidden fixed inset-0 z-[60] overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeNewJobCustomer()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Müşteri Ekle</h3>
                <button type="button" onclick="closeNewJobCustomer()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/musteriler/ekle">
                <input type="hidden" name="redirect_to" value="new_job">
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soyad <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta</label>
                        <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                        <input type="text" name="phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">TC Kimlik No</label>
                        <input type="text" name="identity_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adres</label>
                        <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeNewJobCustomer()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Ekle ve nakliye formuna dön</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNewJobModal() { document.getElementById('newJobModal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeNewJobModal() { document.getElementById('newJobModal').classList.add('hidden'); document.body.style.overflow = ''; }
function closeNewJobCustomer() {
    document.getElementById('newJobCustomerModal').classList.add('hidden');
    document.getElementById('newJobCustomerModal').setAttribute('aria-hidden', 'true');
}
(function() {
    var bulkBar = document.getElementById('jobBulkBar');
    var bulkCountEl = document.getElementById('jobBulkCount');
    var selectAll = document.getElementById('selectAllJobs');
    var form = document.getElementById('jobBulkDeleteForm');
    var container = document.getElementById('jobBulkIdsContainer');
    function updateBulkBar() {
        var cbs = document.querySelectorAll('.job-cb:checked');
        var n = cbs.length;
        if (bulkCountEl) bulkCountEl.textContent = n;
        if (bulkBar) bulkBar.classList.toggle('hidden', n === 0);
        if (selectAll) selectAll.checked = n > 0 && document.querySelectorAll('.job-cb').length === n;
    }
    if (form) form.addEventListener('submit', function(e) {
        var cbs = document.querySelectorAll('.job-cb:checked');
        if (cbs.length === 0) { e.preventDefault(); return; }
        if (!confirm('Seçili ' + cbs.length + ' nakliye işini silmek istediğinize emin misiniz?')) { e.preventDefault(); return; }
        if (container) {
            container.innerHTML = '';
            cbs.forEach(function(cb) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = cb.value;
                container.appendChild(inp);
            });
        }
    });
    document.querySelectorAll('.job-cb').forEach(function(cb) { cb.addEventListener('change', updateBulkBar); });
    if (selectAll) selectAll.addEventListener('change', function() { document.querySelectorAll('.job-cb').forEach(function(cb) { cb.checked = selectAll.checked; }); updateBulkBar(); });
})();
var newJobCustomerId = <?= json_encode($newCustomerId) ?>;
if (newJobCustomerId && document.getElementById('newJob_customer_id')) {
    var sel = document.getElementById('newJob_customer_id');
    if (sel && sel.querySelector('option[value="' + newJobCustomerId + '"]')) { sel.value = newJobCustomerId; }
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
