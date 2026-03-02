<?php
$currentPage = 'girisler';
$openNewSale = $openNewSale ?? false;
$newCustomerId = $newCustomerId ?? '';
$roomsByWarehouse = [];
foreach ($rooms as $r) {
    $wid = $r['warehouse_id'] ?? '';
    if (!isset($roomsByWarehouse[$wid])) $roomsByWarehouse[$wid] = [];
    $roomsByWarehouse[$wid][] = $r;
}
$owners = $owners ?? [];
ob_start();
?>
<div class="mb-6">
    <h1 class="page-title gradient-title">Tüm Girişler</h1>
    <p class="page-subtitle uppercase tracking-widest font-bold">Sözleşme bilgileri</p>
</div>

<?php
$durumGet = isset($_GET['durum']) ? $_GET['durum'] : '';
$borcGet = isset($_GET['borc']) ? $_GET['borc'] : '';
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <form method="get" action="/girisler" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <input type="hidden" name="newSale" value="">
        <select name="durum" class="btn-touch flex-1 min-w-0 sm:w-auto px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            <option value="">Tüm Durumlar</option>
            <option value="active" <?= $durumGet === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= $durumGet === 'inactive' ? 'selected' : '' ?>>Sonlandırılanlar</option>
        </select>
        <select name="borc" class="btn-touch flex-1 min-w-0 sm:w-auto px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            <option value="">Tümü</option>
            <option value="with_debt" <?= $borcGet === 'with_debt' ? 'selected' : '' ?>>Borcu olanlar</option>
            <option value="no_debt" <?= $borcGet === 'no_debt' ? 'selected' : '' ?>>Borcu olmayanlar</option>
        </select>
        <button type="submit" class="btn-touch px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600">Filtrele</button>
        <a href="/girisler" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm">Temizle</a>
    </form>
    <button type="button" onclick="openNewSaleModal()" class="btn-touch w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
        <i class="bi bi-plus-circle mr-2"></i> Yeni Satış Gir
    </button>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm flex items-center justify-between">
        <span><?= htmlspecialchars($flashSuccess) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm flex items-center justify-between">
        <span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($contracts)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz sözleşme yok. "Yeni Satış Gir" ile ekleyebilirsiniz.</div>
    <?php else: ?>
        <!-- Toplu işlem çubuğu -->
        <div id="bulkBar" class="hidden md:flex items-center justify-between gap-3 px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><span id="bulkCount">0</span> sözleşme seçildi</span>
            <form method="post" action="/girisler/sil" id="bulkDeleteForm" onsubmit="return submitBulkDelete();">
                <div id="bulkIdsContainer"></div>
                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Toplu Sil</button>
            </form>
        </div>
        <!-- Mobil: kart listesi -->
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($contracts as $c): ?>
                <div class="p-4 active:bg-gray-50 dark:active:bg-gray-700/50 flex items-start gap-3">
                    <label class="flex-shrink-0 mt-0.5"><input type="checkbox" class="contract-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($c['id'] ?? '') ?>"></label>
                    <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="block flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5"><?= htmlspecialchars(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? '')) ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-500"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?></p>
                    </a>
                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-2"><?= fmtPrice($c['monthly_price'] ?? 0) ?>/ay</p>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <?php if (!empty($c['is_active'])): ?>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-300">Sonlandı</span>
                        <?php endif; ?>
                        <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>?collectPay=1" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Ödeme Al</a>
                        <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="text-sm font-medium text-gray-600 dark:text-gray-400">Detay</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Masaüstü: tablo -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <label class="inline-flex items-center cursor-pointer"><input type="checkbox" id="selectAllContracts" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" title="Tümünü seç"> <span class="sr-only">Seç</span></label>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo / Oda</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Başlangıç</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık Fiyat</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php $contractDebt = $contractDebt ?? []; foreach ($contracts as $c): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="contract-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($c['id'] ?? '') ?>"></label></td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></a></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? '')) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= date('d.m.Y', strtotime($c['start_date'] ?? '')) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtPrice($c['monthly_price'] ?? 0) ?></td>
                            <td class="px-4 py-3">
                                <?php
                                $debt = $contractDebt[$c['id'] ?? ''] ?? ['overdue' => 0, 'pending' => 0];
                                if (!empty($c['is_active'])): ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                    <?php if ($debt['overdue'] > 0): ?><span class="ml-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">Gecikmiş</span><?php endif; ?>
                                    <?php if ($debt['overdue'] === 0 && $debt['pending'] > 0): ?><span class="ml-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">Ödenmedi</span><?php endif; ?>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-300">Sonlandı</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>?collectPay=1" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 mr-1">Ödeme Al</a>
                                <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 mr-1">Detay</a>
                                <?php if (!empty($c['is_active'])): ?>
                                    <form method="post" action="/girisler/sonlandir" class="inline" onsubmit="return confirm('Bu sözleşmeyi sonlandırmak istediğinize emin misiniz?');">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($c['id'] ?? '') ?>">
                                        <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100">Sonlandır</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="/girisler/sil" class="inline ml-1" onsubmit="return confirm('Bu sözleşmeyi silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="ids[]" value="<?= htmlspecialchars($c['id'] ?? '') ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $contractsTotal = $contractsTotal ?? 0;
        $perPage = $perPage ?? 50;
        $page = $page ?? max(1, (int) ($_GET['page'] ?? 1));
        $keepParams = array_filter(['durum' => $durumGet ?? '', 'borc' => $borcGet ?? '', 'newSale' => ($openNewSale ?? false) ? '1' : '']);
        echo renderPagination($contractsTotal, $perPage, $page, '/girisler', $keepParams);
        ?>
    <?php endif; ?>
</div>

<!-- Modal: Yeni Satış Gir -->
<div id="newSaleModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeNewSaleModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white"><i class="bi bi-plus-lg text-lg"></i></div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Satış Gir</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sözleşme bilgilerini doldurun</p>
                    </div>
                </div>
                <button type="button" onclick="closeNewSaleModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/girisler/ekle" id="newSaleForm" enctype="multipart/form-data">
                <div class="space-y-6">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2"><i class="bi bi-person"></i> Temel Bilgiler</h4>
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Müşteri <span class="text-red-500">*</span></label>
                                    <button type="button" onclick="event.preventDefault(); document.getElementById('quickAddCustomerModal').classList.remove('hidden'); document.getElementById('quickAddCustomerModal').setAttribute('aria-hidden','false');" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">+ Hızlı müşteri ekle</button>
                                </div>
                                <select name="customer_id" id="newSale_customer_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Müşteri seçin</option>
                                    <?php foreach ($customers as $cu): ?>
                                        <option value="<?= htmlspecialchars($cu['id']) ?>"><?= htmlspecialchars($cu['first_name'] . ' ' . $cu['last_name'] . ' - ' . ($cu['email'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo <span class="text-red-500">*</span></label>
                                <select name="warehouse_id" id="newSale_warehouse" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Depo Seçin</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <?php $whFee = isset($w['monthly_base_fee']) && $w['monthly_base_fee'] !== null && $w['monthly_base_fee'] !== '' ? (float)$w['monthly_base_fee'] : null; ?>
                                        <option value="<?= htmlspecialchars($w['id']) ?>" data-monthly-base-fee="<?= $whFee !== null ? htmlspecialchars(number_format((float)$whFee, 2, '.', '')) : '' ?>"><?= htmlspecialchars($w['name']) ?><?= $whFee !== null ? ' (' . fmtPrice($whFee) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Oda <span class="text-red-500">*</span></label>
                                <select name="room_id" id="newSale_room" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Önce Depo Seçin</option>
                                    <?php foreach ($rooms as $r): ?>
                                        <option value="<?= htmlspecialchars($r['id']) ?>" data-warehouse="<?= htmlspecialchars($r['warehouse_id']) ?>"><?= htmlspecialchars($r['room_number']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2"><i class="bi bi-currency-exchange"></i> Depo Aylık Ücreti</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aylık Ücret (₺) <span class="text-red-500">*</span></label>
                                <input type="text" name="monthly_price" id="newSale_monthly_price" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="newSale_has_transportation" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" onchange="toggleTransportationBlock(this.checked)">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nakliye bilgisi ekle</span>
                                </label>
                                <input type="hidden" name="has_transportation" id="newSale_has_transportation_val" value="0">
                            </div>
                        </div>
                        <div id="newSale_transportation_block" class="space-y-4 mt-3 hidden">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2"><i class="bi bi-geo-alt text-emerald-600"></i> Eşyanın Alınacağı Yer</h4>
                            <?php if (!empty($warehouses)): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo adresinden seç (opsiyonel)</label>
                                <select id="newSale_pickup_warehouse" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Serbest giriş / Depo seçmeyin</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <?php $whAddr = trim(($wh['name'] ?? '') . ($wh['address'] ? ', ' . $wh['address'] : '') . ($wh['city'] ? ', ' . $wh['city'] : '') . ($wh['district'] ? ' / ' . $wh['district'] : '')); ?>
                                        <option value="<?= htmlspecialchars($whAddr) ?>"><?= htmlspecialchars($wh['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Veya İl / İlçe ile belirtin</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <select id="newSale_pickup_il" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">İl seçin</option>
                                    </select>
                                    <select id="newSale_pickup_ilce" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Önce il seçin</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres (Eşyanın alındığı yer)</label>
                                <input type="text" name="pickup_location" id="newSale_pickup_location" placeholder="İl, İlçe veya tam adres" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 mt-4"><i class="bi bi-geo-alt text-green-600"></i> Eşyanın Gideceği Yer (Depo)</h4>
                            <?php if (!empty($warehouses)): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo adresinden seç</label>
                                <select id="newSale_delivery_warehouse" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Yukarıda seçtiğiniz depo kullanılır</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <?php $whAddr = trim(($wh['name'] ?? '') . ($wh['address'] ? ', ' . $wh['address'] : '') . ($wh['city'] ? ', ' . $wh['city'] : '') . ($wh['district'] ? ' / ' . $wh['district'] : '')); ?>
                                        <option value="<?= htmlspecialchars($whAddr) ?>"><?= htmlspecialchars($wh['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Veya İl / İlçe ile belirtin</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <select id="newSale_delivery_il" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">İl seçin</option>
                                    </select>
                                    <select id="newSale_delivery_ilce" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Önce il seçin</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres (Gideceği yer – boş bırakılırsa seçilen depo kullanılır)</label>
                                <input type="text" name="delivery_location" id="newSale_delivery_location" placeholder="İl, İlçe veya tam adres" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-gray-200 dark:border-gray-600">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İndirim (₺)</label>
                                    <input type="text" name="discount" id="newSale_discount" value="0" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nakliye Ücreti (₺)</label>
                                    <input type="text" name="transportation_fee" id="newSale_transportation_fee" value="0" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şoför Adı</label>
                                    <input type="text" name="driver_name" id="newSale_driver_name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şoför Telefon</label>
                                    <input type="text" name="driver_phone" id="newSale_driver_phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Araç</label>
                                    <select name="vehicle_id" id="newSale_vehicle_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Araç seçin veya plaka girin</option>
                                        <?php foreach ($vehicles ?? [] as $v): ?>
                                            <option value="<?= htmlspecialchars($v['id']) ?>" data-plate="<?= htmlspecialchars($v['plate'] ?? '') ?>"><?= htmlspecialchars($v['plate'] ?? '') ?> <?= !empty($v['model_year']) ? '(' . $v['model_year'] . ')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="vehicle_plate" id="newSale_vehicle_plate">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2"><i class="bi bi-calendar3"></i> Tarih Bilgileri</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç Tarihi <span class="text-red-500">*</span></label>
                                <input type="date" name="start_date" id="newSale_start_date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş Tarihi <span class="text-red-500">*</span></label>
                                <input type="date" name="end_date" id="newSale_end_date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    </div>
                    <div id="newSale_monthly_prices_section" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 hidden">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2"><i class="bi bi-calendar-month"></i> Aylık Fiyatlar</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Başlangıç–bitiş tarihlerine göre aylar listelenir. Her ay için fiyatı düzenleyebilirsiniz.</p>
                        <div id="newSale_monthly_prices_list" class="space-y-2 max-h-48 overflow-y-auto pr-2"></div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sözleşme PDF (Opsiyonel)</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Şirket ile depoya girişi yapan kişi arasındaki sözleşmeyi yükleyin.</p>
                        <input type="file" name="contract_pdf" accept=".pdf,application/pdf" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
                    </div>
                    <?php if (!empty($owners)): ?>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2"><i class="bi bi-person-badge"></i> Satışı Yapan Kişi (Depo sahibi)</h4>
                        <select name="sold_by_user_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Seçin</option>
                            <?php foreach ($owners as $o): ?>
                                <option value="<?= htmlspecialchars($o['id']) ?>"><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($staff)): ?>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2"><i class="bi bi-people"></i> Personel Seçimi</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">(Çoklu seçim yapabilirsiniz - Hizmet veren personeller)</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($staff as $s): ?>
                                <label class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <input type="checkbox" name="staff_ids[]" value="<?= htmlspecialchars($s['id']) ?>" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="ml-2 text-sm"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeNewSaleModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Hızlı müşteri ekle (Yeni Satış içinden) -->
<div id="quickAddCustomerModal" class="modal-overlay hidden fixed inset-0 z-[60] overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeQuickAddCustomer()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Müşteri Ekle</h3>
                <button type="button" onclick="closeQuickAddCustomer()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/musteriler/ekle">
                <input type="hidden" name="redirect_to" value="new_sale">
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
                    <button type="button" onclick="closeQuickAddCustomer()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Ekle ve satışa dön</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTransportationBlock(show) {
    var block = document.getElementById('newSale_transportation_block');
    var hiddenInput = document.getElementById('newSale_has_transportation_val');
    if (block) block.classList.toggle('hidden', !show);
    if (hiddenInput) hiddenInput.value = show ? '1' : '0';
    if (show) loadNewSaleIller();
}
function loadNewSaleIller() {
    var pickupIl = document.getElementById('newSale_pickup_il');
    if (!pickupIl || pickupIl.options.length > 1) return;
    fetch('/api/iller', { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(res){
        var list = (res && res.data) ? res.data : [];
        [pickupIl, document.getElementById('newSale_delivery_il')].forEach(function(sel){
            if (!sel) return;
            sel.innerHTML = '<option value="">İl seçin</option>';
            list.forEach(function(p){ var o = document.createElement('option'); o.value = p.id; o.textContent = p.name; sel.appendChild(o); });
        });
    });
}
function closeQuickAddCustomer() {
    document.getElementById('quickAddCustomerModal').classList.add('hidden');
    document.getElementById('quickAddCustomerModal').setAttribute('aria-hidden', 'true');
}
function openNewSaleModal() {
    document.getElementById('newSaleModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeNewSaleModal() {
    document.getElementById('newSaleModal').classList.add('hidden');
    document.body.style.overflow = '';
}
(function() {
    var whSelect = document.getElementById('newSale_warehouse');
    var roomSelect = document.getElementById('newSale_room');
    var roomOptions = [];
    roomSelect.querySelectorAll('option[data-warehouse]').forEach(function(o) {
        roomOptions.push({ value: o.value, warehouse: o.getAttribute('data-warehouse'), text: o.textContent.trim() });
    });
    whSelect.addEventListener('change', function() {
        var wh = this.value;
        roomSelect.innerHTML = '<option value="">' + (wh ? 'Oda Seçin' : 'Önce Depo Seçin') + '</option>';
        roomOptions.forEach(function(o) {
            if (o.warehouse === wh) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.text;
                roomSelect.appendChild(opt);
            }
        });
        var priceEl = document.getElementById('newSale_monthly_price');
        if (priceEl) {
            var whOpt = this.options[this.selectedIndex];
            var depotFee = whOpt && whOpt.getAttribute('data-monthly-base-fee');
            priceEl.value = depotFee ? depotFee.replace('.', ',') : '';
        }
    });
    roomSelect.addEventListener('change', function() {
        var priceEl = document.getElementById('newSale_monthly_price');
        var whSelect = document.getElementById('newSale_warehouse');
        if (priceEl && whSelect) {
            var whOpt = whSelect.options[whSelect.selectedIndex];
            var depotFee = whOpt && whOpt.getAttribute('data-monthly-base-fee');
            if (depotFee) priceEl.value = depotFee.replace('.', ',');
        }
        buildMonthlyPricesList();
    });
    var monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    function buildMonthlyPricesList() {
        var startEl = document.getElementById('newSale_start_date');
        var endEl = document.getElementById('newSale_end_date');
        var section = document.getElementById('newSale_monthly_prices_section');
        var list = document.getElementById('newSale_monthly_prices_list');
        var defaultPriceEl = document.getElementById('newSale_monthly_price');
        var defaultVal = (defaultPriceEl && defaultPriceEl.value) ? defaultPriceEl.value.replace(',', '.') : '';
        if (!startEl || !endEl || !section || !list) return;
        var startStr = startEl.value, endStr = endEl.value;
        if (!startStr || !endStr) { section.classList.add('hidden'); list.innerHTML = ''; return; }
        var start = new Date(startStr + 'T00:00:00');
        var end = new Date(endStr + 'T00:00:00');
        if (end < start) { section.classList.add('hidden'); list.innerHTML = ''; return; }
        var months = [];
        var d = new Date(start.getFullYear(), start.getMonth(), 1);
        var endFirst = new Date(end.getFullYear(), end.getMonth(), 1);
        while (d <= endFirst) {
            months.push({ y: d.getFullYear(), m: d.getMonth(), key: d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') });
            d.setMonth(d.getMonth() + 1);
        }
        list.innerHTML = '';
        months.forEach(function(item) {
            var label = monthNames[item.m] + ' ' + item.y;
            var row = document.createElement('div');
            row.className = 'flex items-center gap-3';
            row.innerHTML = '<label class="w-28 text-sm text-gray-700 shrink-0">' + label + '</label>' +
                '<input type="text" name="monthly_prices[' + item.key + ']" value="' + (defaultVal ? defaultVal.replace('.', ',') : '') + '" placeholder="0,00" class="flex-1 px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">' +
                '<span class="text-gray-500 text-sm shrink-0">₺</span>';
            list.appendChild(row);
        });
        section.classList.remove('hidden');
    }
    document.getElementById('newSale_start_date').addEventListener('change', buildMonthlyPricesList);
    document.getElementById('newSale_end_date').addEventListener('change', buildMonthlyPricesList);
    document.getElementById('newSale_monthly_price').addEventListener('blur', function() {
        var list = document.getElementById('newSale_monthly_prices_list');
        if (!list || !list.querySelectorAll('input').length) return;
        var val = this.value.replace(',', '.');
        list.querySelectorAll('input').forEach(function(inp) { if (!inp.value || inp.value === '0') inp.value = val ? val.replace('.', ',') : ''; });
    });
    var pickupWh = document.getElementById('newSale_pickup_warehouse');
    var deliveryWh = document.getElementById('newSale_delivery_warehouse');
    var pickupAddr = document.getElementById('newSale_pickup_location');
    var deliveryAddr = document.getElementById('newSale_delivery_location');
    if (pickupWh && pickupAddr) pickupWh.addEventListener('change', function() { if (this.value) pickupAddr.value = this.value; });
    if (deliveryWh && deliveryAddr) deliveryWh.addEventListener('change', function() { if (this.value) deliveryAddr.value = this.value; });
    var pickupIl = document.getElementById('newSale_pickup_il');
    var pickupIlce = document.getElementById('newSale_pickup_ilce');
    var deliveryIl = document.getElementById('newSale_delivery_il');
    var deliveryIlce = document.getElementById('newSale_delivery_ilce');
    function loadIlceler(ilId, ilceSelect) {
        if (!ilceSelect) return;
        ilceSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        if (!ilId) { ilceSelect.innerHTML = '<option value="">Önce il seçin</option>'; return; }
        fetch('/api/ilceler?il_id=' + ilId, { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(res){
            var list = (res && res.data) ? res.data : [];
            ilceSelect.innerHTML = '<option value="">İlçe seçin</option>';
            list.forEach(function(d){ var o = document.createElement('option'); o.value = d.name; o.textContent = d.name; ilceSelect.appendChild(o); });
        });
    }
    if (pickupIl && pickupIlce) {
        pickupIl.addEventListener('change', function(){ loadIlceler(this.value, pickupIlce); pickupAddr.value = ''; });
        pickupIlce.addEventListener('change', function(){ if (this.value && pickupIl.options[pickupIl.selectedIndex]) pickupAddr.value = pickupIl.options[pickupIl.selectedIndex].text + ', ' + this.value; });
    }
    if (deliveryIl && deliveryIlce) {
        deliveryIl.addEventListener('change', function(){ loadIlceler(this.value, deliveryIlce); deliveryAddr.value = ''; });
        deliveryIlce.addEventListener('change', function(){ if (this.value && deliveryIl.options[deliveryIl.selectedIndex]) deliveryAddr.value = deliveryIl.options[deliveryIl.selectedIndex].text + ', ' + this.value; });
    }
})();
document.getElementById('newSaleModal').addEventListener('keydown', function(e) { if (e.key === 'Escape') closeNewSaleModal(); });
(function() {
    var bulkBar = document.getElementById('bulkBar');
    var bulkCountEl = document.getElementById('bulkCount');
    var selectAll = document.getElementById('selectAllContracts');
    var form = document.getElementById('bulkDeleteForm');
    var container = document.getElementById('bulkIdsContainer');
    function updateBulkBar() {
        var cbs = document.querySelectorAll('.contract-cb:checked');
        var n = cbs.length;
        if (bulkCountEl) bulkCountEl.textContent = n;
        if (bulkBar) bulkBar.classList.toggle('hidden', n === 0);
        if (selectAll) selectAll.checked = n > 0 && document.querySelectorAll('.contract-cb').length === n;
    }
    function submitBulkDelete() {
        var cbs = document.querySelectorAll('.contract-cb:checked');
        if (cbs.length === 0) return false;
        if (!confirm('Seçili ' + cbs.length + ' sözleşmeyi silmek istediğinize emin misiniz?')) return false;
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
        return true;
    }
    window.submitBulkDelete = submitBulkDelete;
    document.querySelectorAll('.contract-cb').forEach(function(cb) { cb.addEventListener('change', updateBulkBar); });
    if (selectAll) selectAll.addEventListener('change', function() { document.querySelectorAll('.contract-cb').forEach(function(cb) { cb.checked = selectAll.checked; }); updateBulkBar(); });
})();
(function() {
    var sel = document.getElementById('newSale_vehicle_id');
    var hid = document.getElementById('newSale_vehicle_plate');
    if (sel && hid) {
        sel.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            hid.value = (opt && opt.dataset.plate) ? opt.dataset.plate : '';
        });
        if (sel.selectedIndex >= 0) sel.dispatchEvent(new Event('change'));
    }
})();
var newCustomerId = <?= json_encode($newCustomerId) ?>;
if (newCustomerId && document.getElementById('newSale_customer_id')) {
    var sel = document.getElementById('newSale_customer_id');
    if (sel.querySelector('option[value="' + newCustomerId + '"]')) { sel.value = newCustomerId; }
}
<?php if ($openNewSale): ?>openNewSaleModal();<?php endif; ?>
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
