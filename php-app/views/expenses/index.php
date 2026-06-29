<?php
$currentPage = 'masraflar';
$expenses = $expenses ?? [];
$categories = $categories ?? [];
$bankAccounts = $bankAccounts ?? [];
$creditCards = $creditCards ?? [];
$totalAmount = $totalAmount ?? 0;
$categoryId = $categoryId ?? null;
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$paymentSourceType = $paymentSourceType ?? null;
$paymentSourceId = $paymentSourceId ?? null;
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
function getPaymentSourceDisplay($e, $bankAccounts, $creditCards) {
    $type = $e['payment_source_type'] ?? 'bank_account';
    $id = $e['payment_source_id'] ?? '';
    if ($type === 'bank_account') {
        foreach ($bankAccounts as $ba) {
            if (($ba['id'] ?? '') === $id) return ($ba['bank_name'] ?? '') . ' - ' . ($ba['account_holder_name'] ?? '');
        }
        return 'Banka Hesabı';
    }
    foreach ($creditCards as $cc) {
        if (($cc['id'] ?? '') === $id) return CreditCard::getDisplayName($cc);
    }
    return 'Kredi Kartı';
}
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Masraflar</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Harcamalar ve masraf kategorileri</p>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<!-- Masraf kategorileri -->
<div class="card-modern p-4 md:p-6 mb-6">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 md:mb-4 flex items-center gap-2"><i class="bi bi-tags text-emerald-600"></i> Masraf Kategorileri</h2>
    <div class="mb-4">
        <button type="button" onclick="document.getElementById('addCategoryModal').classList.remove('hidden')" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
            <i class="bi bi-plus-lg mr-2"></i> Kategori Ekle
        </button>
    </div>
    <?php if (empty($categories)): ?>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Henüz masraf kategorisi yok. Masraf ekleyebilmek için önce kategori ekleyin.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap gap-2">
            <?php foreach ($categories as $c): ?>
                <div class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm min-w-0">
                    <span class="truncate font-medium"><?= htmlspecialchars($c['name']) ?></span>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" onclick='openEditCategory(<?= json_encode($c) ?>)' class="p-2 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-white/60 dark:hover:bg-gray-600" title="Düzenle"><i class="bi bi-pencil"></i></button>
                        <form method="post" action="/masraflar/kategori-sil" class="inline" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('masraf kategorisi')) ?>);">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($c['id']) ?>">
                            <button type="submit" class="p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-white/60 dark:hover:bg-gray-600" title="Sil"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Filtre + Masraf ekle -->
<?php
$expenseQ = isset($_GET['q']) ? trim($_GET['q']) : '';
$hasActiveFilters = $categoryId || $paymentSourceType || $paymentSourceId || $startDate !== date('Y-m-01') || $endDate !== date('Y-m-t') || $expenseQ !== '';
$activeFilterTags = [];
if ($categoryId) {
    foreach ($categories as $c) {
        if (($c['id'] ?? '') === $categoryId) { $activeFilterTags[] = 'Kategori: ' . ($c['name'] ?? ''); break; }
    }
}
if ($paymentSourceType === 'bank_account') $activeFilterTags[] = 'Kaynak: Banka';
elseif ($paymentSourceType === 'credit_card') $activeFilterTags[] = 'Kaynak: Kredi kartı';
if ($expenseQ !== '') $activeFilterTags[] = 'Arama: ' . $expenseQ;
if ($startDate !== date('Y-m-01') || $endDate !== date('Y-m-t')) {
    $activeFilterTags[] = 'Dönem: ' . date('d.m.Y', strtotime($startDate)) . ' – ' . date('d.m.Y', strtotime($endDate));
}
?>
<div class="page-toolbar flex flex-wrap items-center gap-3 mb-6">
    <?php
    $filterModalId = 'expenseFilterModal';
    $filterClearUrl = '/masraflar';
    require __DIR__ . '/../partials/page_filter_trigger.php';
    ?>
    <?php if (!empty($categories)): ?>
    <div class="page-toolbar-actions">
        <button type="button" onclick="document.getElementById('addExpenseModal').classList.remove('hidden')" class="col-span-2 inline-flex items-center justify-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
            <i class="bi bi-plus-lg mr-2"></i> Masraf Ekle
        </button>
    </div>
    <?php endif; ?>
</div>

<?php
ob_start();
?>
    <div class="filter-field">
        <label class="filter-label" for="expense_filter_category">Kategori</label>
        <select name="category_id" id="expense_filter_category" class="filter-input">
            <option value="">Tümü</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>" <?= $categoryId === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="filterPaymentSourceType">Ödeme Kaynağı</label>
        <select name="payment_source_type" id="filterPaymentSourceType" class="filter-input">
            <option value="">Tümü</option>
            <option value="bank_account" <?= $paymentSourceType === 'bank_account' ? 'selected' : '' ?>>Banka Hesabı</option>
            <option value="credit_card" <?= $paymentSourceType === 'credit_card' ? 'selected' : '' ?>>Kredi Kartı</option>
        </select>
    </div>
    <div id="filterBankWrap" class="filter-field <?= $paymentSourceType !== 'credit_card' ? '' : 'hidden' ?>">
        <label class="filter-label" for="filterBankId">Banka</label>
        <select name="payment_source_id" id="filterBankId" class="filter-input">
            <option value="">Tümü</option>
            <?php foreach ($bankAccounts as $ba): ?>
                <option value="<?= htmlspecialchars($ba['id']) ?>" <?= $paymentSourceType === 'bank_account' && $paymentSourceId === $ba['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ba['bank_name'] . ' - ' . ($ba['account_holder_name'] ?? '')) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="filterCardWrap" class="filter-field <?= $paymentSourceType === 'credit_card' ? '' : 'hidden' ?>">
        <label class="filter-label" for="filterCardId">Kredi Kartı</label>
        <select name="payment_source_id" id="filterCardId" class="filter-input">
            <option value="">Tümü</option>
            <?php foreach ($creditCards as $cc): ?>
                <option value="<?= htmlspecialchars($cc['id']) ?>" <?= $paymentSourceType === 'credit_card' && $paymentSourceId === $cc['id'] ? 'selected' : '' ?>><?= htmlspecialchars(CreditCard::getDisplayName($cc)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-field">
        <label class="filter-label" for="expense_start_date">Başlangıç</label>
        <input type="date" name="start_date" id="expense_start_date" value="<?= htmlspecialchars($startDate) ?>" class="filter-input">
    </div>
    <div class="filter-field">
        <label class="filter-label" for="expense_end_date">Bitiş</label>
        <input type="date" name="end_date" id="expense_end_date" value="<?= htmlspecialchars($endDate) ?>" class="filter-input">
    </div>
    <div class="filter-field">
        <label class="filter-label" for="expense_filter_q">Arama</label>
        <input type="search" name="q" id="expense_filter_q" value="<?= htmlspecialchars($expenseQ) ?>" placeholder="Açıklama, not, kategori..." class="filter-input">
    </div>
<?php
$filterModalBody = ob_get_clean();
$filterFormId = 'expenseFilterForm';
$filterFormAction = '/masraflar';
$filterSubmitLabel = 'Filtrele';
$filterModalTitle = 'Masraf Filtreleri';
require __DIR__ . '/../partials/page_filter_modal.php';
?>

<!-- Masraf listesi -->
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm mobile-card overflow-visible md:overflow-hidden">
    <?php if (empty($expenses)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Seçilen kriterlere uygun masraf kaydı yok.</div>
    <?php else: ?>
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 flex justify-between items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= count($expenses) ?> masraf</span>
            <span class="text-base md:text-lg font-bold text-red-600 dark:text-red-400"><?= fmtMoney($totalAmount) ?> ₺</span>
        </div>
        <!-- Mobil: kart listesi -->
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($expenses as $e): ?>
                <div class="mobile-data-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($e['description'] ?? $e['category_name'] ?? 'Masraf') ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= htmlspecialchars($e['category_name'] ?? '-') ?></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= $e['expense_date'] ? date('d.m.Y', strtotime($e['expense_date'])) : '-' ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate"><?= htmlspecialchars(getPaymentSourceDisplay($e, $bankAccounts, $creditCards)) ?></p>
                        </div>
                        <p class="text-sm font-bold text-red-600 dark:text-red-400 whitespace-nowrap shrink-0"><?= fmtMoney($e['amount'] ?? 0) ?> ₺</p>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <button type="button" onclick='openEditExpense(<?= json_encode($e) ?>)' class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20">Düzenle</button>
                        <form method="post" action="/masraflar/sil" class="inline" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('masraf')) ?>);">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20">Sil</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Masaüstü: tablo -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ödeme Kaynağı</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($expenses as $e): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= $e['expense_date'] ? date('d.m.Y', strtotime($e['expense_date'])) : '-' ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($e['category_name'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($e['description'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars(getPaymentSourceDisplay($e, $bankAccounts, $creditCards)) ?></td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white"><?= fmtMoney($e['amount'] ?? 0) ?> ₺</td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" onclick='openEditExpense(<?= json_encode($e) ?>)' class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm">Düzenle</button>
                                <form method="post" action="/masraflar/sil" class="inline ml-2" onsubmit="return confirm(<?= json_encode(deleteConfirmMessage('masraf')) ?>);">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>">
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Kategori ekle -->
<div id="addCategoryModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addCategoryModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Masraf Kategorisi Ekle</h3>
            <form method="post" action="/masraflar/kategori-ekle" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori Adı <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="Örn: Kira, Elektrik, Yakıt">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                    <input type="text" name="description" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="form-submit-bar flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kategori düzenle -->
<div id="editCategoryModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('editCategoryModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Masraf Kategorisi Düzenle</h3>
            <form method="post" action="/masraflar/kategori-guncelle" class="space-y-3">
                <input type="hidden" name="id" id="edit_cat_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori Adı <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="edit_cat_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                    <input type="text" name="description" id="edit_cat_description" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="form-submit-bar flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('editCategoryModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Masraf ekle -->
<div id="addExpenseModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addExpenseModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Masraf Ekle</h3>
            <?php if (empty($bankAccounts) && empty($creditCards)): ?>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Masraf ekleyebilmek için önce <strong>Ayarlar</strong> sayfasından en az bir banka hesabı veya kredi kartı eklemeniz gerekir.</p>
                <div class="form-submit-bar flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">Kapat</button>
                    <a href="/ayarlar" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 inline-block">Ayarlar'a Git</a>
                </div>
            <?php else: ?>
            <form method="post" action="/masraflar/ekle" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Seçin</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tutar (₺) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" step="0.01" min="0.01" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tarih <span class="text-red-500">*</span></label>
                    <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ödeme Kaynağı <span class="text-red-500">*</span></label>
                    <div class="flex gap-4 mb-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_source_type" value="bank_account" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" onchange="togglePaymentSource('bank_account')">
                            <span>Banka Hesabı</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_source_type" value="credit_card" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" onchange="togglePaymentSource('credit_card')">
                            <span>Kredi Kartı</span>
                        </label>
                    </div>
                    <select name="payment_source_id" id="add_payment_source_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <?php foreach ($bankAccounts as $ba): ?>
                            <option value="<?= htmlspecialchars($ba['id']) ?>"><?= htmlspecialchars($ba['bank_name'] . ' - ' . ($ba['account_holder_name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($bankAccounts) && !empty($creditCards)): foreach ($creditCards as $cc): ?>
                            <option value="<?= htmlspecialchars($cc['id']) ?>"><?= htmlspecialchars(CreditCard::getDisplayName($cc)) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <script>
                    (function(){
                        var bankOpts = <?= json_encode(array_map(fn($ba) => ['id' => $ba['id'], 'label' => $ba['bank_name'] . ' - ' . ($ba['account_holder_name'] ?? '')], $bankAccounts)) ?>;
                        var cardOpts = <?= json_encode(array_map(fn($cc) => ['id' => $cc['id'], 'label' => CreditCard::getDisplayName($cc)], $creditCards)) ?>;
                        window.togglePaymentSource = function(type) {
                            var sel = document.getElementById('add_payment_source_id');
                            sel.innerHTML = '';
                            var opts = type === 'bank_account' ? bankOpts : cardOpts;
                            opts.forEach(function(o) {
                                var opt = document.createElement('option');
                                opt.value = o.id;
                                opt.textContent = o.label;
                                sel.appendChild(opt);
                            });
                        };
                        document.querySelectorAll('input[name="payment_source_type"]').forEach(function(r) {
                            r.addEventListener('change', function() { window.togglePaymentSource(this.value); });
                        });
                    })();
                    </script>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                    <input type="text" name="description" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="Örn: Ofis kirası">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                <div class="form-submit-bar flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Masraf düzenle -->
<div id="editExpenseModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('editExpenseModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Masraf Düzenle</h3>
            <form method="post" action="/masraflar/guncelle" class="space-y-3">
                <input type="hidden" name="id" id="edit_exp_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="category_id" id="edit_exp_category_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tutar (₺) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" id="edit_exp_amount" step="0.01" min="0.01" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tarih <span class="text-red-500">*</span></label>
                    <input type="date" name="expense_date" id="edit_exp_date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ödeme Kaynağı <span class="text-red-500">*</span></label>
                    <div class="flex gap-4 mb-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_source_type" value="bank_account" id="edit_exp_src_bank" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" onchange="toggleEditPaymentSource('bank_account')">
                            <span>Banka Hesabı</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_source_type" value="credit_card" id="edit_exp_src_card" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" onchange="toggleEditPaymentSource('credit_card')">
                            <span>Kredi Kartı</span>
                        </label>
                    </div>
                    <select name="payment_source_id" id="edit_payment_source_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                    <input type="text" name="description" id="edit_exp_description" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                    <textarea name="notes" id="edit_exp_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                <div class="form-submit-bar flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('editExpenseModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var bankOpts = <?= json_encode(array_map(fn($ba) => ['id' => $ba['id'], 'label' => $ba['bank_name'] . ' - ' . ($ba['account_holder_name'] ?? '')], $bankAccounts)) ?>;
var cardOpts = <?= json_encode(array_map(fn($cc) => ['id' => $cc['id'], 'label' => CreditCard::getDisplayName($cc)], $creditCards)) ?>;
function toggleEditPaymentSource(type) {
    var sel = document.getElementById('edit_payment_source_id');
    sel.innerHTML = '';
    var opts = type === 'bank_account' ? bankOpts : cardOpts;
    opts.forEach(function(o) {
        var opt = document.createElement('option');
        opt.value = o.id;
        opt.textContent = o.label;
        sel.appendChild(opt);
    });
}
function openEditExpense(e) {
    document.getElementById('edit_exp_id').value = e.id || '';
    document.getElementById('edit_exp_category_id').value = e.category_id || '';
    document.getElementById('edit_exp_amount').value = parseFloat(e.amount || 0);
    document.getElementById('edit_exp_date').value = (e.expense_date || '').slice(0, 10);
    document.getElementById('edit_exp_description').value = e.description || '';
    document.getElementById('edit_exp_notes').value = e.notes || '';
    var type = e.payment_source_type || 'bank_account';
    document.getElementById('edit_exp_src_bank').checked = type === 'bank_account';
    document.getElementById('edit_exp_src_card').checked = type === 'credit_card';
    toggleEditPaymentSource(type);
    document.getElementById('edit_payment_source_id').value = e.payment_source_id || '';
    document.getElementById('editExpenseModal').classList.remove('hidden');
}
function openEditCategory(c) {
    document.getElementById('edit_cat_id').value = c.id || '';
    document.getElementById('edit_cat_name').value = c.name || '';
    document.getElementById('edit_cat_description').value = c.description || '';
    document.getElementById('editCategoryModal').classList.remove('hidden');
}
(function(){
    var sel = document.getElementById('filterPaymentSourceType');
    var bankWrap = document.getElementById('filterBankWrap');
    var cardWrap = document.getElementById('filterCardWrap');
    var bankSel = document.getElementById('filterBankId');
    var cardSel = document.getElementById('filterCardId');
    function updateFilterSource() {
        var t = sel?.value || '';
        if (t === 'credit_card') {
            bankWrap?.classList.add('hidden');
            cardWrap?.classList.remove('hidden');
            if (bankSel) bankSel.removeAttribute('name');
            if (cardSel) cardSel.setAttribute('name', 'payment_source_id');
        } else {
            bankWrap?.classList.remove('hidden');
            cardWrap?.classList.add('hidden');
            if (bankSel) bankSel.setAttribute('name', 'payment_source_id');
            if (cardSel) cardSel.removeAttribute('name');
        }
    }
    sel?.addEventListener('change', updateFilterSource);
    updateFilterSource();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
