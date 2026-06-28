<?php
$currentPage = 'girisler';
$openNewSale = $openNewSale ?? false;
$newCustomerId = $newCustomerId ?? '';
$contractsTotal = $contractsTotal ?? 0;
$roomsByWarehouse = [];
foreach ($rooms as $r) {
    $wid = $r['warehouse_id'] ?? '';
    if (!isset($roomsByWarehouse[$wid])) $roomsByWarehouse[$wid] = [];
    $roomsByWarehouse[$wid][] = $r;
}
$owners = $owners ?? [];
$personnel = $personnel ?? [];
if (!function_exists('formatWarehouseAddress')) {
    function formatWarehouseAddress(array $wh): string
    {
        return trim(implode(', ', array_filter([
            $wh['name'] ?? '',
            $wh['address'] ?? '',
            $wh['district'] ?? '',
            $wh['city'] ?? '',
        ])));
    }
}
ob_start();
?>
<div class="mb-6">
    <h1 class="page-title gradient-title">Tüm Girişler</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        <span class="font-semibold text-emerald-700 dark:text-emerald-400"><?= number_format((int) $contractsTotal, 0, ',', '.') ?> sözleşme</span>
    </p>
</div>

<?php
$durumGet = isset($_GET['durum']) ? $_GET['durum'] : '';
$borcGet = isset($_GET['borc']) ? $_GET['borc'] : '';
$qGet = isset($_GET['q']) ? trim($_GET['q']) : '';
$hasActiveContractFilters = $qGet !== '' || $durumGet !== '' || $borcGet !== '';
$activeFilterTags = [];
if ($qGet !== '') $activeFilterTags[] = 'Arama: ' . $qGet;
if ($durumGet === 'active') $activeFilterTags[] = 'Durum: Aktif';
elseif ($durumGet === 'inactive') $activeFilterTags[] = 'Durum: Sonlandırılanlar';
if ($borcGet === 'with_debt') $activeFilterTags[] = 'Borcu olanlar';
elseif ($borcGet === 'no_debt') $activeFilterTags[] = 'Borcu olmayanlar';
?>
<div class="page-toolbar flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <?php
    $filterModalId = 'contractFilterModal';
    $filterClearUrl = '/girisler';
    $hasActiveFilters = $hasActiveContractFilters;
    require __DIR__ . '/../partials/page_filter_trigger.php';
    ?>
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

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible">
    <?php if (empty($contracts)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz sözleşme yok. "Yeni Satış Gir" ile ekleyebilirsiniz.</div>
    <?php else: ?>
        <!-- Toplu işlem çubuğu -->
        <div id="bulkBar" class="hidden flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><span id="bulkCount">0</span> sözleşme seçildi</span>
            <form method="post" action="/girisler/sil" id="bulkDeleteForm" onsubmit="return submitBulkDelete();">
                <div id="bulkIdsContainer"></div>
                <button type="submit" class="w-full sm:w-auto px-3 py-2 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Toplu Sil</button>
            </form>
        </div>
        <!-- Mobil: kart listesi -->
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
            <?php $contractDebt = $contractDebt ?? []; foreach ($contracts as $c):
                $debt = $contractDebt[$c['id'] ?? ''] ?? ['overdue' => 0, 'pending' => 0];
            ?>
                <div class="p-4 active:bg-gray-50 dark:active:bg-gray-700/50">
                    <div class="flex items-start gap-3">
                        <label class="flex-shrink-0 mt-1 cursor-pointer">
                            <input type="checkbox" class="contract-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($c['id'] ?? '') ?>">
                        </label>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="min-w-0 flex-1">
                                    <p class="font-semibold text-emerald-600 dark:text-emerald-400 truncate"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></p>
                                    <p class="text-sm text-gray-900 dark:text-white mt-0.5 truncate"><?= htmlspecialchars(trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''))) ?></p>
                                </a>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap shrink-0"><?= fmtPrice($c['monthly_price'] ?? 0) ?><span class="text-xs font-normal text-gray-500 dark:text-gray-400">/ay</span></p>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> · Oda <?= htmlspecialchars($c['room_number'] ?? '') ?></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5"><?= fmtDateTime($c['created_at'] ?? null) ?></p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <?php if (!empty($c['is_active'])): ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                    <?php if ($debt['overdue'] > 0): ?><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">Gecikmiş</span><?php endif; ?>
                                    <?php if ($debt['overdue'] === 0 && $debt['pending'] > 0): ?><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">Ödenmedi</span><?php endif; ?>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-300">Sonlandı</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-3">
                                <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>?collectPay=1" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">Ödeme Al</a>
                                <a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600">Detay</a>
                                <?php if (!empty($c['is_active'])): ?>
                                    <form method="post" action="/girisler/sonlandir" class="inline" onsubmit="return confirm('Bu sözleşmeyi sonlandırmak istediğinize emin misiniz?');">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($c['id'] ?? '') ?>">
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20">Sonlandır</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="/girisler/sil" class="inline" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('sözleşme')) ?>);">
                                    <input type="hidden" name="ids[]" value="<?= htmlspecialchars($c['id'] ?? '') ?>">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20">Sil</button>
                                </form>
                            </div>
                        </div>
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
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Kayıt Tarihi</th>
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
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtDateTime($c['created_at'] ?? null) ?></td>
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
                                <form method="post" action="/girisler/sil" class="inline ml-1" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('sözleşme')) ?>);">
                                    <input type="hidden" name="ids[]" value="<?= htmlspecialchars($c['id'] ?? '') ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php if (!empty($contracts)):
    $contractsTotal = $contractsTotal ?? 0;
    $perPage = $perPage ?? 50;
    $page = $page ?? max(1, (int) ($_GET['page'] ?? 1));
    $keepParams = array_filter(['q' => $qGet !== '' ? $qGet : null, 'durum' => $durumGet !== '' ? $durumGet : null, 'borc' => $borcGet !== '' ? $borcGet : null, 'newSale' => ($openNewSale ?? false) ? '1' : null]);
    echo renderPagination($contractsTotal, $perPage, $page, '/girisler', $keepParams);
endif; ?>

<?php
ob_start();
?>
    <div class="filter-field">
        <label class="filter-label" for="contract_filter_q">Ara</label>
        <input type="search" name="q" id="contract_filter_q" value="<?= htmlspecialchars($qGet) ?>" placeholder="Sözleşme no, müşteri, oda, depo, plaka..." class="filter-input">
    </div>
    <div class="filter-field">
        <label class="filter-label" for="contract_filter_durum">Durum</label>
        <select name="durum" id="contract_filter_durum" class="filter-input">
            <option value="">Tüm Durumlar</option>
            <option value="active" <?= $durumGet === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= $durumGet === 'inactive' ? 'selected' : '' ?>>Sonlandırılanlar</option>
        </select>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="contract_filter_borc">Borç</label>
        <select name="borc" id="contract_filter_borc" class="filter-input">
            <option value="">Tümü</option>
            <option value="with_debt" <?= $borcGet === 'with_debt' ? 'selected' : '' ?>>Borcu olanlar</option>
            <option value="no_debt" <?= $borcGet === 'no_debt' ? 'selected' : '' ?>>Borcu olmayanlar</option>
        </select>
    </div>
<?php
$filterModalBody = ob_get_clean();
$filterFormId = 'contractFilterForm';
$filterFormAction = '/girisler';
$filterSubmitLabel = 'Filtrele';
$filterModalTitle = 'Giriş Filtreleri';
require __DIR__ . '/../partials/page_filter_modal.php';
?>

<!-- Modal: Yeni Satış Gir -->
<div id="newSaleModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeNewSaleModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white shrink-0"><i class="bi bi-plus-lg text-lg"></i></div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Satış Gir</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sözleşme bilgilerini doldurun</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 text-xs font-semibold whitespace-nowrap" title="Kayıtlı müşteri sayısı">
                        <i class="bi bi-people"></i>
                        <?= number_format((int) ($customersTotal ?? 0), 0, ',', '.') ?> müşteri
                    </span>
                    <button type="button" onclick="closeNewSaleModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <form method="post" action="/girisler/ekle" id="newSaleForm" enctype="multipart/form-data">
                <div class="space-y-6">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2"><i class="bi bi-person"></i> Temel Bilgiler</h4>
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="newSale_customer_search">Müşteri <span class="text-red-500">*</span></label>
                                    <button type="button" onclick="event.preventDefault(); document.getElementById('quickAddCustomerModal').classList.remove('hidden'); document.getElementById('quickAddCustomerModal').setAttribute('aria-hidden','false');" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">+ Hızlı müşteri ekle</button>
                                </div>
                                <input type="hidden" name="customer_id" id="newSale_customer_id" value="">
                                <div class="relative">
                                    <input type="search" id="newSale_customer_search" placeholder="Ad, e-posta veya telefon ile ara..." autocomplete="off" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <div id="newSale_customer_results" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-52 overflow-y-auto"></div>
                                </div>
                                <p id="newSale_customer_selected" class="hidden mt-2 text-sm text-emerald-700 dark:text-emerald-300 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depo <span class="text-red-500">*</span></label>
                                <select name="warehouse_id" id="newSale_warehouse" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Depo Seçin</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <?php $whFee = isset($w['monthly_base_fee']) && $w['monthly_base_fee'] !== null && $w['monthly_base_fee'] !== '' ? (float)$w['monthly_base_fee'] : null; ?>
                                        <option value="<?= htmlspecialchars($w['id']) ?>" data-monthly-base-fee="<?= $whFee !== null ? htmlspecialchars(number_format((float)$whFee, 2, '.', '')) : '' ?>" data-full-address="<?= htmlspecialchars(formatWarehouseAddress($w)) ?>"><?= htmlspecialchars($w['name']) ?><?= $whFee !== null ? ' (' . fmtPrice($whFee) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="newSale_room_search">1. Oda <span class="text-red-500">*</span></label>
                                <input type="hidden" name="room_id" id="newSale_room_id" value="">
                                <div class="relative">
                                    <input type="search" id="newSale_room_search" placeholder="Önce depo seçin" autocomplete="off" disabled class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white opacity-60 cursor-not-allowed">
                                    <div id="newSale_room_results" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-52 overflow-y-auto"></div>
                                </div>
                                <p id="newSale_room_hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Önce depo seçin</p>
                            </div>
                            <div class="pt-2">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="newSale_needs_extra_rooms" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" onchange="toggleExtraRoomsBlock(this.checked)">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Müşterinin bir odaya daha ihtiyacı var</span>
                                </label>
                                <input type="hidden" name="needs_additional_rooms" id="newSale_needs_additional_rooms_val" value="0">
                            </div>
                            <div id="newSale_extra_rooms_block" class="hidden space-y-3 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                <div id="newSale_extra_rooms_list" class="space-y-4"></div>
                                <button type="button" id="newSale_add_extra_room_btn" class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                                    <i class="bi bi-plus-circle"></i> Başka oda ekle
                                </button>
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
                            <p class="text-xs text-gray-500 dark:text-gray-400">Eşyanın nereden alınacağını seçin (ör. müşterinin evinden veya başka bir depodan).</p>
                            <div class="flex flex-wrap gap-2" id="newSale_pickup_type_group">
                                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 cursor-pointer pickup-type-label">
                                    <input type="radio" name="pickup_source_type" value="evden" class="text-emerald-600 focus:ring-emerald-500" checked onchange="togglePickupSourceType('evden')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Evden</span>
                                </label>
                                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 cursor-pointer pickup-type-label">
                                    <input type="radio" name="pickup_source_type" value="ofisten" class="text-emerald-600 focus:ring-emerald-500" onchange="togglePickupSourceType('ofisten')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ofisten</span>
                                </label>
                                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 cursor-pointer pickup-type-label">
                                    <input type="radio" name="pickup_source_type" value="depo" class="text-emerald-600 focus:ring-emerald-500" onchange="togglePickupSourceType('depo')">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Depo</span>
                                </label>
                            </div>
                            <div id="newSale_pickup_address_block" class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İl / İlçe</label>
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
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açık Adres</label>
                                    <input type="text" name="pickup_address_detail" id="newSale_pickup_address_detail" placeholder="Mahalle, sokak, bina no..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <?php if (!empty($warehouses)): ?>
                            <div id="newSale_pickup_depo_block" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alınacak depo <span class="text-red-500">*</span></label>
                                <select name="pickup_warehouse_id" id="newSale_pickup_warehouse_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Depo seçin</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <option value="<?= htmlspecialchars($wh['id']) ?>" data-address="<?= htmlspecialchars(formatWarehouseAddress($wh)) ?>"><?= htmlspecialchars($wh['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <input type="hidden" name="pickup_location" id="newSale_pickup_location" value="">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 mt-4"><i class="bi bi-geo-alt text-green-600"></i> Eşyanın Gideceği Yer (Depo)</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Eşya, sözleşmede seçtiğiniz depoya gidecektir.</p>
                            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800">
                                <p id="newSale_delivery_preview" class="text-sm font-medium text-emerald-800 dark:text-emerald-300">Önce sözleşme deposu seçin</p>
                            </div>
                            <input type="hidden" name="delivery_location" id="newSale_delivery_location" value="">
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
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Giriş tarihinden itibaren her ayın vade gününe göre listelenir (ör. giriş 28.06 → vadeler 28.06, 28.07 …).</p>
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
                    <?php if (!empty($personnel)): ?>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2"><i class="bi bi-people"></i> Saha Personeli</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Hizmet veren saha personelini seçin (çoklu seçim). <a href="/personel" class="text-emerald-600 dark:text-emerald-400 hover:underline">Personel yönetimi</a></p>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $personnelList = $personnel ?? [];
                            $jobTypeLabels = $jobTypeLabels ?? Personnel::jobTypeLabels();
                            foreach ($personnelList as $s): ?>
                                <label class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <input type="checkbox" name="personnel_ids[]" value="<?= htmlspecialchars($s['id']) ?>" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="ml-2 text-sm"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></span>
                                    <span class="ml-1 text-xs text-gray-500">(<?= htmlspecialchars($jobTypeLabels[$s['job_type'] ?? 'diger'] ?? 'Diğer') ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2"><i class="bi bi-box-seam"></i> Giriş Yapılan Ürün Durumu <span class="text-red-500">*</span></h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                            <?php foreach (storedItemsConditionOptions() as $code => $label): ?>
                                <label class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20">
                                    <input type="radio" name="stored_items_condition" value="<?= htmlspecialchars($code) ?>" required class="rounded-full border-gray-300 text-emerald-600 focus:ring-emerald-500" onchange="toggleStoredItemsConditionNote(this.value)">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-200"><?= htmlspecialchars($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div id="newSale_stored_items_condition_note_block" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasar Notu <span class="text-red-500">*</span></label>
                            <textarea name="stored_items_condition_note" id="newSale_stored_items_condition_note" rows="2" placeholder="Hasarın açıklamasını yazın..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <?php $items = []; require __DIR__ . '/_stored_items_form.php'; ?>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="form-submit-bar mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeNewSaleModal()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
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
                        <input type="tel" name="phone" inputmode="numeric" autocomplete="tel" placeholder="0555 123 45 67" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" title="11 hane: 05xx xxx xx xx" data-phone-mask>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon 2</label>
                        <input type="tel" name="phone_2" inputmode="numeric" autocomplete="tel" placeholder="0555 123 45 67" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" title="11 hane: 05xx xxx xx xx" data-phone-mask>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">TC Kimlik No</label>
                        <input type="text" name="identity_number" maxlength="11" inputmode="numeric" pattern="[0-9]*" placeholder="En fazla 11 hane" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" title="Sadece rakam, en fazla 11 hane">
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
                <div class="form-submit-bar mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeQuickAddCustomer()" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Ekle ve satışa dön</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/customer-picker.js"></script>
<script src="/room-picker.js"></script>
<script src="/contract-billing.js"></script>
<?php
$newSaleRoomsJson = [];
foreach ($rooms as $r) {
    $roomNum = preg_replace('/\s*\([^)]*\)\s*$/', '', (string) ($r['room_number'] ?? ''));
    $roomPrice = isset($r['monthly_price']) && $r['monthly_price'] !== null && $r['monthly_price'] !== ''
        ? (float) $r['monthly_price'] : null;
    $newSaleRoomsJson[] = [
        'id' => $r['id'] ?? '',
        'warehouse_id' => $r['warehouse_id'] ?? '',
        'room_number' => $roomNum,
        'monthly_price' => $roomPrice,
        'status' => $r['status'] ?? '',
    ];
}
?>
<script>
function toggleTransportationBlock(show) {
    var block = document.getElementById('newSale_transportation_block');
    var hiddenInput = document.getElementById('newSale_has_transportation_val');
    if (block) block.classList.toggle('hidden', !show);
    if (hiddenInput) hiddenInput.value = show ? '1' : '0';
    if (show) {
        loadNewSaleIller();
        syncDeliveryFromContractWarehouse();
        composePickupLocation();
    }
}
function togglePickupSourceType(type) {
    var addressBlock = document.getElementById('newSale_pickup_address_block');
    var depoBlock = document.getElementById('newSale_pickup_depo_block');
    var depoSelect = document.getElementById('newSale_pickup_warehouse_id');
    if (addressBlock) addressBlock.classList.toggle('hidden', type === 'depo');
    if (depoBlock) depoBlock.classList.toggle('hidden', type !== 'depo');
    if (depoSelect) depoSelect.required = type === 'depo';
    document.querySelectorAll('#newSale_pickup_type_group .pickup-type-label').forEach(function(label) {
        var input = label.querySelector('input[type="radio"]');
        var active = input && input.value === type;
        label.classList.toggle('border-emerald-500', active);
        label.classList.toggle('bg-emerald-50', active);
        label.classList.toggle('dark:bg-emerald-900/20', active);
        label.classList.toggle('border-gray-300', !active);
        label.classList.toggle('dark:border-gray-600', !active);
    });
    composePickupLocation();
}
function getPickupSourceType() {
    var checked = document.querySelector('input[name="pickup_source_type"]:checked');
    return checked ? checked.value : 'evden';
}
function composePickupLocation() {
    var hidden = document.getElementById('newSale_pickup_location');
    if (!hidden) return;
    var type = getPickupSourceType();
    if (type === 'depo') {
        var depoSelect = document.getElementById('newSale_pickup_warehouse_id');
        var opt = depoSelect && depoSelect.options[depoSelect.selectedIndex];
        var addr = opt && opt.getAttribute('data-address');
        hidden.value = addr ? ('Depo: ' + addr) : '';
        return;
    }
    var parts = [];
    var il = document.getElementById('newSale_pickup_il');
    var ilce = document.getElementById('newSale_pickup_ilce');
    var detail = document.getElementById('newSale_pickup_address_detail');
    if (il && il.selectedIndex > 0) parts.push(il.options[il.selectedIndex].text);
    if (ilce && ilce.value) parts.push(ilce.value);
    if (detail && detail.value.trim()) parts.push(detail.value.trim());
    var label = type === 'ofisten' ? 'Ofisten' : 'Evden';
    hidden.value = parts.length ? (label + ': ' + parts.join(', ')) : '';
}
function syncDeliveryFromContractWarehouse() {
    var whSelect = document.getElementById('newSale_warehouse');
    var preview = document.getElementById('newSale_delivery_preview');
    var hidden = document.getElementById('newSale_delivery_location');
    if (!whSelect || !preview || !hidden) return;
    var opt = whSelect.options[whSelect.selectedIndex];
    if (!whSelect.value || !opt) {
        preview.textContent = 'Önce sözleşme deposu seçin';
        hidden.value = '';
        return;
    }
    var addr = opt.getAttribute('data-full-address') || opt.textContent.trim();
    preview.textContent = addr;
    hidden.value = addr;
}
function toggleExtraRoomsBlock(show) {
    var block = document.getElementById('newSale_extra_rooms_block');
    var hiddenInput = document.getElementById('newSale_needs_additional_rooms_val');
    if (block) block.classList.toggle('hidden', !show);
    if (hiddenInput) hiddenInput.value = show ? '1' : '0';
    if (show) {
        if (!document.querySelector('#newSale_extra_rooms_list .extra-room-row')) {
            addExtraRoomRow();
        }
        syncExtraRoomPickersEnabled();
    } else {
        clearExtraRoomRows();
    }
}
function collectOtherRoomIds(currentHiddenId) {
    var ids = [];
    var primary = document.getElementById('newSale_room_id');
    if (primary && primary.value && primary.id !== currentHiddenId) {
        ids.push(primary.value);
    }
    document.querySelectorAll('#newSale_extra_rooms_list .extra-room-id').forEach(function (inp) {
        if (inp.value && inp.id !== currentHiddenId) {
            ids.push(inp.value);
        }
    });
    return ids;
}
function clearExtraRoomRows() {
    window.newSaleExtraRoomPickers = [];
    var list = document.getElementById('newSale_extra_rooms_list');
    if (list) list.innerHTML = '';
    window.newSaleExtraRoomRowCounter = 0;
}
function refreshExtraRoomLabels() {
    document.querySelectorAll('#newSale_extra_rooms_list .extra-room-row').forEach(function (row, index) {
        var label = row.querySelector('.extra-room-label');
        if (label) {
            label.textContent = (index + 2) + '. Oda';
        }
    });
}
function removeExtraRoomRow(rowEl) {
    if (!rowEl) return;
    var idx = rowEl.getAttribute('data-row-index');
    window.newSaleExtraRoomPickers = (window.newSaleExtraRoomPickers || []).filter(function (entry) {
        return String(entry.idx) !== String(idx);
    });
    rowEl.remove();
    refreshExtraRoomLabels();
    var list = document.getElementById('newSale_extra_rooms_list');
    if (list && !list.querySelector('.extra-room-row')) {
        var needsExtra = document.getElementById('newSale_needs_extra_rooms');
        if (needsExtra && needsExtra.checked) {
            addExtraRoomRow();
        }
    }
}
function addExtraRoomRow() {
    if (typeof initRoomPicker !== 'function') return;
    var list = document.getElementById('newSale_extra_rooms_list');
    if (!list) return;
    var idx = window.newSaleExtraRoomRowCounter = (window.newSaleExtraRoomRowCounter || 0) + 1;
    var hiddenId = 'newSale_extra_room_id_' + idx;
    var searchId = 'newSale_extra_room_search_' + idx;
    var resultsId = 'newSale_extra_room_results_' + idx;
    var hintId = 'newSale_extra_room_hint_' + idx;
    var row = document.createElement('div');
    row.className = 'extra-room-row rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800/50 p-3 space-y-3';
    row.setAttribute('data-row-index', String(idx));
    row.innerHTML =
        '<div class="flex items-center justify-between gap-2">' +
            '<label class="extra-room-label block text-sm font-medium text-gray-700 dark:text-gray-300"></label>' +
            '<button type="button" class="extra-room-remove text-xs text-red-600 dark:text-red-400 hover:underline">Kaldır</button>' +
        '</div>' +
        '<input type="hidden" name="additional_room_ids[]" id="' + hiddenId + '" class="extra-room-id" value="">' +
        '<div class="relative">' +
            '<input type="search" id="' + searchId + '" placeholder="Önce depo seçin" autocomplete="off" disabled class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white opacity-60 cursor-not-allowed">' +
            '<div id="' + resultsId + '" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-52 overflow-y-auto"></div>' +
        '</div>' +
        '<p id="' + hintId + '" class="text-xs text-gray-500 dark:text-gray-400">Aynı depodaki başka bir oda seçin</p>' +
        '<div>' +
            '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aylık Ücret (₺) <span class="text-red-500">*</span></label>' +
            '<input type="text" name="additional_monthly_prices[]" class="extra-room-price w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="0,00">' +
        '</div>';
    list.appendChild(row);
    row.querySelector('.extra-room-remove').addEventListener('click', function () {
        removeExtraRoomRow(row);
    });
    refreshExtraRoomLabels();
    var roomsData = window.newSaleRoomsData || [];
    var picker = initRoomPicker({
        hiddenInputId: hiddenId,
        searchInputId: searchId,
        resultsId: resultsId,
        warehouseSelectId: 'newSale_warehouse',
        hintId: hintId,
        rooms: roomsData,
        isRequired: function () {
            var needsExtra = document.getElementById('newSale_needs_extra_rooms');
            return !!(needsExtra && needsExtra.checked);
        },
        getExcludeIds: function () {
            return collectOtherRoomIds(hiddenId);
        },
        onSelect: function (room) {
            var priceEl = row.querySelector('.extra-room-price');
            if (priceEl && room && room.monthly_price !== null && room.monthly_price !== undefined && room.monthly_price !== '') {
                priceEl.value = String(room.monthly_price).replace('.', ',');
            }
        }
    });
    window.newSaleExtraRoomPickers = window.newSaleExtraRoomPickers || [];
    window.newSaleExtraRoomPickers.push({ idx: idx, picker: picker, row: row });
    syncExtraRoomPickersEnabled();
}
function syncExtraRoomPickersEnabled() {
    var wh = document.getElementById('newSale_warehouse');
    var enabled = !!(wh && wh.value);
    (window.newSaleExtraRoomPickers || []).forEach(function (entry) {
        if (entry.picker && typeof entry.picker.setEnabled === 'function') {
            entry.picker.setEnabled(enabled);
        }
    });
}
function toggleStoredItemsConditionNote(value) {
    var block = document.getElementById('newSale_stored_items_condition_note_block');
    var note = document.getElementById('newSale_stored_items_condition_note');
    var show = value === 'hasarli';
    if (block) block.classList.toggle('hidden', !show);
    if (note) {
        note.required = show;
        if (!show) note.value = '';
    }
}
function loadNewSaleIller() {
    var pickupIl = document.getElementById('newSale_pickup_il');
    if (!pickupIl || pickupIl.options.length > 1) return;
    fetch('/api/iller', { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(res){
        var list = (res && res.data) ? res.data : [];
        pickupIl.innerHTML = '<option value="">İl seçin</option>';
        list.forEach(function(p){ var o = document.createElement('option'); o.value = p.id; o.textContent = p.name; pickupIl.appendChild(o); });
    });
}
function closeQuickAddCustomer() {
    document.getElementById('quickAddCustomerModal').classList.add('hidden');
    document.getElementById('quickAddCustomerModal').setAttribute('aria-hidden', 'true');
}
function openNewSaleModal() {
    document.getElementById('newSaleModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    var wh = document.getElementById('newSale_warehouse');
    if (wh) wh.value = '';
    if (window.newSaleRoomPicker) {
        window.newSaleRoomPicker.clearSelected();
        window.newSaleRoomPicker.setEnabled(false);
    }
    clearExtraRoomRows();
    var roomSearch = document.getElementById('newSale_room_search');
    if (roomSearch) {
        roomSearch.value = '';
        roomSearch.placeholder = 'Önce depo seçin';
    }
    var needsExtra = document.getElementById('newSale_needs_extra_rooms');
    if (needsExtra) {
        needsExtra.checked = false;
        toggleExtraRoomsBlock(false);
    }
}
function closeNewSaleModal() {
    document.getElementById('newSaleModal').classList.add('hidden');
    document.body.style.overflow = '';
}
(function() {
    var whSelect = document.getElementById('newSale_warehouse');
    var monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    function applyWarehouseBaseFee() {
        var priceEl = document.getElementById('newSale_monthly_price');
        if (!priceEl || !whSelect) return;
        var opt = whSelect.options[whSelect.selectedIndex];
        var baseFee = opt && opt.getAttribute('data-monthly-base-fee');
        priceEl.value = baseFee ? baseFee.replace('.', ',') : '';
    }
    function applyRoomMonthlyPrice(room) {
        var priceEl = document.getElementById('newSale_monthly_price');
        if (!priceEl || !room) return;
        if (room.monthly_price !== null && room.monthly_price !== undefined && room.monthly_price !== '') {
            priceEl.value = String(room.monthly_price).replace('.', ',');
        }
        buildMonthlyPricesList();
    }
    window.newSaleRoomsData = <?= json_encode($newSaleRoomsJson, JSON_UNESCAPED_UNICODE) ?>;
    window.newSaleExtraRoomPickers = [];
    window.newSaleExtraRoomRowCounter = 0;
    var addExtraBtn = document.getElementById('newSale_add_extra_room_btn');
    if (addExtraBtn) {
        addExtraBtn.addEventListener('click', function () {
            addExtraRoomRow();
        });
    }
    if (typeof initRoomPicker === 'function') {
        window.newSaleRoomPicker = initRoomPicker({
            hiddenInputId: 'newSale_room_id',
            searchInputId: 'newSale_room_search',
            resultsId: 'newSale_room_results',
            warehouseSelectId: 'newSale_warehouse',
            hintId: 'newSale_room_hint',
            rooms: window.newSaleRoomsData,
            getExcludeIds: function () {
                return collectOtherRoomIds('newSale_room_id');
            },
            onWarehouseChange: function () {
                applyWarehouseBaseFee();
                buildMonthlyPricesList();
                syncDeliveryFromContractWarehouse();
                syncExtraRoomPickersEnabled();
                (window.newSaleExtraRoomPickers || []).forEach(function (entry) {
                    if (entry.picker && typeof entry.picker.clearSelected === 'function') {
                        entry.picker.clearSelected();
                    }
                });
            },
            onSelect: function (room) {
                applyRoomMonthlyPrice(room);
            },
            onClear: function () {
                applyWarehouseBaseFee();
            }
        });
    }
    function buildMonthlyPricesList() {
        var startEl = document.getElementById('newSale_start_date');
        var endEl = document.getElementById('newSale_end_date');
        var section = document.getElementById('newSale_monthly_prices_section');
        var list = document.getElementById('newSale_monthly_prices_list');
        var defaultPriceEl = document.getElementById('newSale_monthly_price');
        var defaultVal = (defaultPriceEl && defaultPriceEl.value) ? defaultPriceEl.value.replace(',', '.') : '';
        if (!startEl || !endEl || !section || !list || typeof ContractBilling === 'undefined') return;
        var startStr = startEl.value, endStr = endEl.value;
        if (!startStr || !endStr) { section.classList.add('hidden'); list.innerHTML = ''; return; }
        if (endStr < startStr) { section.classList.add('hidden'); list.innerHTML = ''; return; }
        var periods = ContractBilling.billingPeriods(startStr, endStr);
        list.innerHTML = '';
        periods.forEach(function(item) {
            var row = document.createElement('div');
            row.className = 'flex items-center gap-3';
            row.innerHTML = '<label class="w-28 text-sm text-gray-700 shrink-0" title="Vade tarihi">' + item.label + '</label>' +
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
    var pickupAddrDetail = document.getElementById('newSale_pickup_address_detail');
    var pickupDepo = document.getElementById('newSale_pickup_warehouse_id');
    if (pickupDepo) pickupDepo.addEventListener('change', composePickupLocation);
    var pickupIl = document.getElementById('newSale_pickup_il');
    var pickupIlce = document.getElementById('newSale_pickup_ilce');
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
        pickupIl.addEventListener('change', function(){ loadIlceler(this.value, pickupIlce); composePickupLocation(); });
        pickupIlce.addEventListener('change', composePickupLocation);
    }
    if (pickupAddrDetail) pickupAddrDetail.addEventListener('input', composePickupLocation);
    if (whSelect) whSelect.addEventListener('change', syncDeliveryFromContractWarehouse);
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
        if (bulkBar) {
            bulkBar.classList.toggle('hidden', n === 0);
            bulkBar.classList.toggle('flex', n > 0);
        }
        if (selectAll) selectAll.checked = n > 0 && document.querySelectorAll('.contract-cb').length === n;
    }
    function submitBulkDelete() {
        var cbs = document.querySelectorAll('.contract-cb:checked');
        if (cbs.length === 0) return false;
        if (!confirm(deleteConfirmMsg('sözleşme', cbs.length))) return false;
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
    var form = document.getElementById('newSaleForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            composePickupLocation();
            syncDeliveryFromContractWarehouse();
            var hasTransport = document.getElementById('newSale_has_transportation_val');
            if (hasTransport && hasTransport.value === '1') {
                var type = getPickupSourceType();
                if (type === 'depo') {
                    var depoSel = document.getElementById('newSale_pickup_warehouse_id');
                    if (!depoSel || !depoSel.value) {
                        e.preventDefault();
                        alert('Nakliye için alınacak depoyu seçin.');
                        if (depoSel) depoSel.focus();
                        return;
                    }
                } else {
                    var pickupHidden = document.getElementById('newSale_pickup_location');
                    if (!pickupHidden || !pickupHidden.value.trim()) {
                        e.preventDefault();
                        alert(type === 'ofisten' ? 'Ofis adresini girin.' : 'Ev adresini girin.');
                        var detail = document.getElementById('newSale_pickup_address_detail');
                        if (detail) detail.focus();
                        return;
                    }
                }
            }
            var selected = form.querySelector('input[name="stored_items_condition"]:checked');
            if (!selected) {
                e.preventDefault();
                alert('Giriş yapılan ürün durumu seçilmelidir.');
                return;
            }
            if (selected.value === 'hasarli') {
                var note = document.getElementById('newSale_stored_items_condition_note');
                if (!note || !note.value.trim()) {
                    e.preventDefault();
                    alert('Hasarlı ürünler için hasar notu zorunludur.');
                    if (note) note.focus();
                    return;
                }
            }
            var needsExtra = document.getElementById('newSale_needs_extra_rooms');
            if (needsExtra && needsExtra.checked) {
                var extraRows = document.querySelectorAll('#newSale_extra_rooms_list .extra-room-row');
                if (!extraRows.length) {
                    e.preventDefault();
                    alert('Lütfen en az bir ek oda ekleyin.');
                    return;
                }
                var seenRoomIds = {};
                var primaryRoom = document.getElementById('newSale_room_id');
                if (primaryRoom && primaryRoom.value) {
                    seenRoomIds[primaryRoom.value] = true;
                }
                for (var i = 0; i < extraRows.length; i++) {
                    var row = extraRows[i];
                    var roomInp = row.querySelector('.extra-room-id');
                    var priceInp = row.querySelector('.extra-room-price');
                    var roomLabel = row.querySelector('.extra-room-label');
                    var labelText = roomLabel ? roomLabel.textContent : ((i + 2) + '. oda');
                    if (!roomInp || !roomInp.value) {
                        e.preventDefault();
                        alert(labelText + ' seçilmelidir.');
                        var searchInp = row.querySelector('input[type="search"]');
                        if (searchInp) searchInp.focus();
                        return;
                    }
                    if (seenRoomIds[roomInp.value]) {
                        e.preventDefault();
                        alert('Aynı oda birden fazla kez seçilemez.');
                        return;
                    }
                    seenRoomIds[roomInp.value] = true;
                    if (!priceInp || !priceInp.value.trim()) {
                        e.preventDefault();
                        alert(labelText + ' için aylık ücret girilmelidir.');
                        if (priceInp) priceInp.focus();
                        return;
                    }
                }
            }
        });
    }
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
var newSaleCustomerPicker = null;
if (typeof initCustomerPicker === 'function') {
    newSaleCustomerPicker = initCustomerPicker({
        hiddenInputId: 'newSale_customer_id',
        searchInputId: 'newSale_customer_search',
        resultsId: 'newSale_customer_results',
        selectedLabelId: 'newSale_customer_selected',
        initialCustomerId: newCustomerId || ''
    });
}
<?php if ($openNewSale): ?>openNewSaleModal();<?php endif; ?>
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
