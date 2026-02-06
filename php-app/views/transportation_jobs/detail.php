<?php
$currentPage = 'nakliye-isler';
$job = $job ?? [];
$company = $company ?? null;
$staffNames = $staffNames ?? [];
$jobExpenses = $jobExpenses ?? [];
$totalExpenses = $totalExpenses ?? 0;
$jobRevenue = $jobRevenue ?? 0;
$categories = $categories ?? [];
$bankAccounts = $bankAccounts ?? [];
$creditCards = $creditCards ?? [];
$expensesMigrationOk = $expensesMigrationOk ?? false;
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
$customerName = trim(($job['customer_first_name'] ?? '') . ' ' . ($job['customer_last_name'] ?? ''));
$statusLabels = ['pending' => 'Beklemede', 'in_progress' => 'Devam Ediyor', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal Edildi'];
$status = $job['status'] ?? 'pending';
$statusLabel = $statusLabels[$status] ?? $status;
$profitOrLoss = $jobRevenue - $totalExpenses;
if (!function_exists('fmtPrice')) {
    function fmtPrice($n) {
        if ($n === null || $n === '') return '-';
        $f = (float) $n;
        return ($f == (int)$f ? number_format((int)$f, 0, '', '.') : number_format($f, 2, ',', '.')) . ' ₺';
    }
}
function fmtMoneyDetail($n) {
    $f = (float) $n;
    return ($f == (int)$f ? number_format((int)$f, 0, '', '.') : number_format($f, 2, ',', '.')) . ' ₺';
}
$seoAppName = trim($_SESSION['company_project_name'] ?? '') !== '' ? $_SESSION['company_project_name'] : 'Depo ve Nakliye Takip';
$seoCn = trim($_SESSION['company_name'] ?? '');
$seoDescription = ($seoCn !== '' && $seoAppName !== '') ? ($seoCn . ' - ' . $seoAppName . '. Depo ve nakliye yönetimi.') : ($seoAppName . '. Depo ve nakliye işlemlerinizi tek panelden yönetin.');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <title>Nakliye İşi - <?= htmlspecialchars($customerName) ?> - <?= htmlspecialchars($seoAppName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-6 max-w-4xl mx-auto">
    <div class="no-print mb-4 flex justify-between items-center flex-wrap gap-2">
        <a href="/nakliye-isler" class="text-emerald-600 hover:underline">&larr; Nakliye işlerine dön</a>
        <div class="flex gap-2 flex-wrap">
            <button type="button" id="btnMasrafGir" class="inline-flex items-center px-4 py-2 rounded-xl bg-amber-500 text-white font-medium hover:bg-amber-600">
                <i class="bi bi-cash-stack mr-2"></i> Nakliye masrafları gir
            </button>
            <a href="/nakliye-isler/<?= htmlspecialchars($job['id'] ?? '') ?>/duzenle" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                <i class="bi bi-pencil mr-2"></i> Düzenle
            </a>
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
                <i class="bi bi-printer inline-block mr-2"></i>Yazdır / PDF
            </button>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
    <div class="no-print mb-4 p-3 rounded-xl bg-green-50 text-green-800 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
    <div class="no-print mb-4 p-3 rounded-xl bg-red-50 text-red-800 text-sm"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="border-2 border-gray-200 rounded-xl p-6 print:border-gray-400">
        <?php if ($company && !empty($company['logo_url'])): ?>
        <div class="mb-4"><img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="h-14 object-contain"></div>
        <?php endif; ?>
        <h1 class="text-xl font-bold text-center text-gray-900 mb-6">Nakliye İşi</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Firma</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($company['name'] ?? 'Firma Adı') ?></p>
                <?php if (!empty($company['address'])): ?><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($company['phone']) ?></p><?php endif; ?>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Müşteri</h2>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($customerName ?: '-') ?></p>
                <?php if (!empty($job['customer_email'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($job['customer_email']) ?></p><?php endif; ?>
                <?php if (!empty($job['customer_phone'])): ?><p class="text-sm text-gray-600">Tel: <?= htmlspecialchars($job['customer_phone']) ?></p><?php endif; ?>
            </div>
        </div>
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Nakliye Bilgileri</h2>
        <table class="min-w-full border border-gray-300 text-sm">
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 w-40">Tarih</td><td class="border border-gray-300 px-3 py-2"><?= !empty($job['job_date']) ? date('d.m.Y', strtotime($job['job_date'])) : '–' ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Alış Adresi</td><td class="border border-gray-300 px-3 py-2"><?= nl2br(htmlspecialchars($job['pickup_address'] ?? '–')) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Teslim Adresi</td><td class="border border-gray-300 px-3 py-2"><?= nl2br(htmlspecialchars($job['delivery_address'] ?? '–')) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Araç Plakası</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($job['vehicle_plate'] ?? '–') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">İşe Giden Personel</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars(implode(', ', $staffNames) ?: '–') ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Tutar</td><td class="border border-gray-300 px-3 py-2 font-bold"><?= fmtPrice($job['price'] ?? null) ?></td></tr>
            <tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Durum</td><td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($statusLabel) ?></td></tr>
            <tr class="no-print" id="masraf-gir"><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100 align-top">Nakliye masrafları</td><td class="border border-gray-300 px-3 py-2">
                <p class="text-xs text-gray-500 mb-2">Bu gidilen nakliye hizmetine yapılan masrafları girmek için:</p>
                <button type="button" id="btnMasrafGirAlt" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-500 text-white text-sm font-medium hover:bg-amber-600">Nakliye masrafları gir</button>
            </td></tr>
            <?php if (!empty($job['notes'])): ?><tr><td class="border border-gray-300 px-3 py-2 font-medium bg-gray-100">Not</td><td class="border border-gray-300 px-3 py-2"><?= nl2br(htmlspecialchars($job['notes'])) ?></td></tr><?php endif; ?>
        </table>
        <p class="text-xs text-gray-500 mt-4">Oluşturulma: <?= date('d.m.Y H:i') ?></p>
    </div>

    <!-- Nakliye masrafları ve kar/zarar (no-print) -->
    <div class="no-print mt-8 border-2 border-gray-200 rounded-xl p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="bi bi-cash-stack text-emerald-600"></i> Nakliye masrafları ve kar/zarar
        </h2>
        <?php if ($expensesMigrationOk): ?>
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <button type="button" onclick="openAddJobExpenseModal()" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
                    <i class="bi bi-plus-lg mr-2"></i> Nakliye masrafı oluştur
                </button>
                <a href="/masraflar" class="text-sm text-gray-600 hover:underline">Tüm masraflar</a>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">İş geliri (nakliye ücreti)</p>
                <p class="text-xl font-bold text-gray-900 mt-1"><?= fmtMoneyDetail($jobRevenue) ?></p>
            </div>
            <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam masraflar</p>
                <p class="text-xl font-bold text-red-800 mt-1"><?= fmtMoneyDetail($totalExpenses) ?></p>
            </div>
            <div class="rounded-xl p-4 border-2 <?= $profitOrLoss >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' ?>">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kar / Zarar</p>
                <p class="text-xl font-bold mt-1 <?= $profitOrLoss >= 0 ? 'text-green-800' : 'text-red-800' ?>"><?= fmtMoneyDetail($profitOrLoss) ?></p>
                <p class="text-xs mt-1 <?= $profitOrLoss >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= $profitOrLoss >= 0 ? 'Bu işte kar var' : 'Bu işte zarar var' ?></p>
            </div>
        </div>

        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Bu işe ait masraflar</h3>
        <?php if (empty($jobExpenses)): ?>
            <p class="text-gray-500 py-4">Henüz bu nakliye işi için girilmiş masraf yok. &quot;Nakliye masrafı oluştur&quot; ile yakıt, yol, personel vb. masrafları ekleyebilirsiniz.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-3 py-2 text-left">Tarih</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Kategori</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Açıklama</th>
                            <th class="border border-gray-300 px-3 py-2 text-right">Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobExpenses as $e): ?>
                        <tr>
                            <td class="border border-gray-300 px-3 py-2"><?= $e['expense_date'] ? date('d.m.Y', strtotime($e['expense_date'])) : '–' ?></td>
                            <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($e['category_name'] ?? '–') ?></td>
                            <td class="border border-gray-300 px-3 py-2"><?= htmlspecialchars($e['description'] ?? '–') ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-medium"><?= fmtMoneyDetail($e['amount'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal: Nakliye masrafları gir – Bu nakliye hizmetine özel (her zaman DOM'da, buton tıklanınca açılır) -->
    <div id="addJobExpenseModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" id="addJobExpenseModalBackdrop" role="button" tabindex="0" aria-label="Kapat"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Nakliye masrafları gir</h3>
                <p class="text-sm text-gray-500 mb-4">Bu gidilen nakliye hizmetine ne kadar masraf yapıldığını girin. Girilen tutarlar sadece bu işe bağlanır.</p>
                <?php if ($expensesMigrationOk): ?>
                <form method="post" action="/nakliye-isler/<?= htmlspecialchars($job['id'] ?? '') ?>/masraf-ekle" id="masrafGirForm" class="space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-100">
                            <label class="text-sm font-medium text-gray-700 w-40">Personel masrafı (₺)</label>
                            <input type="number" name="amount_personel" step="0.01" min="0" placeholder="0" class="w-32 px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 text-right" inputmode="decimal">
                        </div>
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-100">
                            <label class="text-sm font-medium text-gray-700 w-40">Mazot masrafı (₺)</label>
                            <input type="number" name="amount_mazot" step="0.01" min="0" placeholder="0" class="w-32 px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 text-right" inputmode="decimal">
                        </div>
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-100">
                            <label class="text-sm font-medium text-gray-700 w-40">Paketleme masrafı (₺)</label>
                            <input type="number" name="amount_paketleme" step="0.01" min="0" placeholder="0" class="w-32 px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 text-right" inputmode="decimal">
                        </div>
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-100">
                            <label class="text-sm font-medium text-gray-700 w-40">Diğer masraf (₺)</label>
                            <input type="number" name="amount_diger" step="0.01" min="0" placeholder="0" class="w-32 px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 text-right" inputmode="decimal">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tarih <span class="text-red-500">*</span></label>
                        <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ödeme kaynağı <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-3 mb-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="payment_source_type" value="nakit" <?= empty($bankAccounts) && empty($creditCards) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600" onchange="toggleJobExpensePaymentSource('nakit')">
                                <span>Nakit (işten alınan ödeme)</span>
                            </label>
                            <?php if (!empty($bankAccounts)): ?>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="payment_source_type" value="bank_account" <?= !empty($bankAccounts) && empty($creditCards) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600" onchange="toggleJobExpensePaymentSource('bank_account')">
                                <span>Banka</span>
                            </label>
                            <?php endif; ?>
                            <?php if (!empty($creditCards)): ?>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="payment_source_type" value="credit_card" <?= empty($bankAccounts) && !empty($creditCards) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600" onchange="toggleJobExpensePaymentSource('credit_card')">
                                <span>Kredi kartı</span>
                            </label>
                            <?php endif; ?>
                        </div>
                        <select name="payment_source_id" id="job_expense_payment_source_id" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                            <option value="">Nakit (işten alınan ödeme)</option>
                            <?php foreach ($bankAccounts as $ba): ?>
                                <option value="<?= htmlspecialchars($ba['id']) ?>"><?= htmlspecialchars($ba['bank_name'] . ' - ' . ($ba['account_holder_name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                            <?php foreach ($creditCards as $cc): ?>
                                <option value="<?= htmlspecialchars($cc['id']) ?>"><?= htmlspecialchars(CreditCard::getDisplayName($cc)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Not (isteğe bağlı)</label>
                        <input type="text" name="notes" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500" placeholder="Bu işe ait not">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btnMasrafModalKapat px-4 py-2 rounded-xl border border-gray-300 text-gray-700">İptal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
                    </div>
                </form>
                <?php else: ?>
                <div class="py-4 text-gray-600 text-sm">
                    <p>Masraf girişi şu an kullanılamıyor.</p>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="button" class="btnMasrafModalKapat px-4 py-2 rounded-xl border border-gray-300 text-gray-700">Kapat</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    (function(){
        function closeMasrafModal() {
            var m = document.getElementById('addJobExpenseModal');
            if (m) m.classList.add('hidden');
        }
        function openMasrafModal() {
            var m = document.getElementById('addJobExpenseModal');
            if (m) m.classList.remove('hidden');
        }
        document.getElementById('addJobExpenseModalBackdrop').addEventListener('click', closeMasrafModal);
        var kapatBtns = document.querySelectorAll('.btnMasrafModalKapat');
        kapatBtns.forEach(function(b){ b.addEventListener('click', closeMasrafModal); });
        var btn1 = document.getElementById('btnMasrafGir');
        var btn2 = document.getElementById('btnMasrafGirAlt');
        if (btn1) btn1.addEventListener('click', openMasrafModal);
        if (btn2) btn2.addEventListener('click', openMasrafModal);
        window.openAddJobExpenseModal = openMasrafModal;
        <?php if ($expensesMigrationOk): ?>
        var bankOpts = <?= json_encode(array_map(fn($ba) => ['id' => $ba['id'], 'label' => $ba['bank_name'] . ' - ' . ($ba['account_holder_name'] ?? '')], $bankAccounts ?? [])) ?>;
        var cardOpts = <?= json_encode(array_map(fn($cc) => ['id' => $cc['id'], 'label' => CreditCard::getDisplayName($cc)], $creditCards ?? [])) ?>;
        window.toggleJobExpensePaymentSource = function(type) {
            var sel = document.getElementById('job_expense_payment_source_id');
            if (!sel) return;
            if (type === 'nakit') {
                sel.value = '';
                return;
            }
            sel.innerHTML = '';
            var opts = type === 'bank_account' ? bankOpts : cardOpts;
            var nakitOpt = document.createElement('option');
            nakitOpt.value = '';
            nakitOpt.textContent = 'Nakit (işten alınan ödeme)';
            sel.appendChild(nakitOpt);
            opts.forEach(function(o) {
                var opt = document.createElement('option');
                opt.value = o.id;
                opt.textContent = o.label;
                sel.appendChild(opt);
            });
            if (opts.length) sel.value = opts[0].id;
        };
        var form = document.getElementById('masrafGirForm');
        if (form) form.addEventListener('submit', function(e) {
            var a = parseFloat(document.querySelector('input[name="amount_personel"]').value) || 0;
            var b = parseFloat(document.querySelector('input[name="amount_mazot"]').value) || 0;
            var c = parseFloat(document.querySelector('input[name="amount_paketleme"]').value) || 0;
            var d = parseFloat(document.querySelector('input[name="amount_diger"]').value) || 0;
            if (a + b + c + d <= 0) {
                e.preventDefault();
                alert('En az bir masraf türüne tutar girin.');
                return false;
            }
        });
        var initialPayment = document.querySelector('input[name="payment_source_type"]:checked');
        if (initialPayment && window.toggleJobExpensePaymentSource) window.toggleJobExpensePaymentSource(initialPayment.value);
        <?php endif; ?>
    })();
    </script>
</body>
</html>
