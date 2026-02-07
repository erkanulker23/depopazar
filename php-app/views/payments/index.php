<?php
$currentPage = 'odemeler';
$statusLabels = $statusLabels ?? [];
$payments = $payments ?? [];
$collectMode = $collectMode ?? false;
$bankAccounts = $bankAccounts ?? [];
$customersWithDebt = $customersWithDebt ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
ob_start();
function fmtMoney($n) { return number_format((float)$n, 2, ',', '.'); }
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1"><?= $collectMode ? 'Ödeme Tahsil Et' : 'Ödemeler' ?></h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= $collectMode ? 'Borcu olan müşterilerden ödeme alın' : 'Ödeme listesi ve tahsilat' ?></p>
</div>

<?php
$payStatus = isset($_GET['status']) ? $_GET['status'] : '';
$payQ = isset($_GET['q']) ? trim($_GET['q']) : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$totalPages = $totalPages ?? 1;
$page = $page ?? 1;
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <form method="get" action="/odemeler" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <?php if ($collectMode): ?><input type="hidden" name="collect" value="1"><?php endif; ?>
        <input type="search" name="q" value="<?= htmlspecialchars($payQ) ?>" placeholder="Ödeme no, sözleşme, müşteri ara..." class="flex-1 min-w-0 sm:w-48 px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" title="Vade tarihi başlangıç">
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" title="Vade tarihi bitiş">
        <select name="status" class="btn-touch flex-1 min-w-0 sm:w-auto px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            <option value="">Tüm Durumlar</option>
            <option value="unpaid" <?= $payStatus === 'unpaid' ? 'selected' : '' ?>>Bekleyen / Gecikmiş</option>
            <option value="pending" <?= $payStatus === 'pending' ? 'selected' : '' ?>>Bekliyor</option>
            <option value="overdue" <?= $payStatus === 'overdue' ? 'selected' : '' ?>>Gecikmiş</option>
            <option value="paid" <?= $payStatus === 'paid' ? 'selected' : '' ?>>Ödendi</option>
            <option value="cancelled" <?= $payStatus === 'cancelled' ? 'selected' : '' ?>>İptal</option>
        </select>
        <button type="submit" class="btn-touch px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600">Filtrele</button>
        <?php if ($payStatus !== '' || $payQ !== '' || $dateFrom !== '' || $dateTo !== ''): ?><a href="/odemeler<?= $collectMode ? '?collect=1' : '' ?>" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-sm">Temizle</a><?php endif; ?>
    </form>
    <button type="button" onclick="openCollectModal()" class="btn-touch w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
        <i class="bi bi-bank mr-2"></i> Ödeme Al
    </button>
</div>

<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if ($collectMode && !empty($customersWithDebt)): ?>
        <!-- Ödeme Tahsil modu: Sadece borçlu müşteriler listesi -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-600 flex items-center justify-between flex-wrap gap-2">
            <p class="text-sm text-gray-600 dark:text-gray-400">Borcu olan <?= count($customersWithDebt) ?> müşteri. Ödeme almak için ilgili müşterinin "Ödeme Al" butonuna tıklayın.</p>
            <a href="/odemeler" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Tüm ödemelere git →</a>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($customersWithDebt as $c):
                $total = array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), $c['payments']));
                $name = trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''));
                $paymentIds = array_column($c['payments'], 'id');
                $paymentsJson = json_encode(array_map(fn($p) => ['id' => $p['id'], 'payment_number' => $p['payment_number'] ?? '', 'amount' => $p['amount'] ?? 0, 'due_date' => $p['due_date'] ?? ''], $c['payments']));
            ?>
            <div class="p-4 flex items-center justify-between gap-4">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($name) ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= count($c['payments']) ?> adet bekleyen ödeme · Toplam <?= fmtMoney($total) ?> ₺</p>
                </div>
                <button type="button" onclick="selectCustomer('<?= htmlspecialchars($c['id']) ?>', <?= htmlspecialchars($paymentsJson) ?>)" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 text-sm">
                    Ödeme Al
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($collectMode && empty($customersWithDebt)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Borcu olan müşteri yok.</div>
    <?php elseif (empty($payments)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz ödeme kaydı yok.</div>
    <?php else: ?>
        <!-- Mobil: kart listesi -->
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($payments as $p):
                $st = $p['status'] ?? 'pending';
                $canCollect = in_array($st, ['pending', 'overdue']);
                $pJson = json_encode(['id' => $p['id'], 'payment_number' => $p['payment_number'] ?? '', 'amount' => $p['amount'] ?? 0, 'due_date' => $p['due_date'] ?? '']);
                $cls = $st === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : ($st === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : ($st === 'cancelled' ? 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'));
            ?>
                <div class="p-4 flex flex-col gap-2">
                    <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="active:bg-gray-50 dark:active:bg-gray-700/50 -m-2 p-2 rounded-lg">
                        <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($p['payment_number'] ?? '-') ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5"><?= htmlspecialchars($p['contract_number'] ?? '') ?> · <?= htmlspecialchars(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? '')) ?></p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-sm text-gray-500 dark:text-gray-500"><?= $p['due_date'] ? date('d.m.Y', strtotime($p['due_date'])) : '-' ?></span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?= fmtMoney($p['amount'] ?? 0) ?> ₺</span>
                        </div>
                        <span class="inline-block mt-2 px-2 py-0.5 text-xs font-semibold rounded-full <?= $cls ?>"><?= htmlspecialchars($statusLabels[$st] ?? $st) ?></span>
                    </a>
                    <?php if ($canCollect): ?>
                        <button type="button" onclick="event.preventDefault(); openCollectForPayment(<?= htmlspecialchars($pJson) ?>)" class="btn-touch w-full py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Ödeme Al</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Masaüstü: tablo -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödeme No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme / Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Vade</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($payments as $p):
                        $st = $p['status'] ?? 'pending';
                        $canCollect = in_array($st, ['pending', 'overdue']);
                        $pJson = json_encode(['id' => $p['id'], 'payment_number' => $p['payment_number'] ?? '', 'amount' => $p['amount'] ?? 0, 'due_date' => $p['due_date'] ?? '']);
                        $cls = $st === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : ($st === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : ($st === 'cancelled' ? 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'));
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700"><?= htmlspecialchars($p['payment_number'] ?? '-') ?></a></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($p['contract_number'] ?? '') ?> / <?= htmlspecialchars(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? '')) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= $p['due_date'] ? date('d.m.Y', strtotime($p['due_date'])) : '-' ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtMoney($p['amount'] ?? 0) ?> ₺</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $cls ?>"><?= htmlspecialchars($statusLabels[$st] ?? $st) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($canCollect): ?>
                                    <button type="button" onclick="openCollectForPayment(<?= htmlspecialchars($pJson) ?>)" class="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">Ödeme Al</button>
                                <?php else: ?>
                                    <span class="text-gray-400 dark:text-gray-500 text-sm">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-600 flex flex-wrap items-center justify-between gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Sayfa <?= $page ?> / <?= $totalPages ?> (<?= $totalPayments ?? count($payments) ?> kayıt)</span>
            <div class="flex gap-1">
                <?php
                $qp = array_filter(['q' => $payQ, 'status' => $payStatus, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
                if ($collectMode) $qp['collect'] = '1';
                $base = '/odemeler' . (empty($qp) ? '' : '?' . http_build_query($qp));
                $sep = strpos($base, '?') !== false ? '&' : '?';
                if ($page > 1): ?><a href="<?= $base . $sep ?>page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">← Önceki</a><?php endif; ?>
                <?php if ($page < $totalPages): ?><a href="<?= $base . $sep ?>page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">Sonraki →</a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Ödeme Al Modal -->
<div id="collectModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" aria-hidden="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeCollectModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-600">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ödeme Al</h3>
                <button type="button" onclick="closeCollectModal()" class="p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="collectError" class="hidden mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"></div>

            <!-- Adım 1: Müşteri seç -->
            <div id="stepCustomer" class="step-content">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Ödeme almak istediğiniz müşteriyi seçin.</p>
                <input type="text" id="customerSearch" placeholder="Müşteri ara..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm mb-4 focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <div class="max-h-64 overflow-y-auto space-y-2" id="customerList">
                    <?php foreach ($customersWithDebt as $c): ?>
                        <?php
                        $total = array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), $c['payments']));
                        $name = trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''));
                        ?>
                        <button type="button" onclick="selectCustomer('<?= htmlspecialchars($c['id']) ?>', <?= htmlspecialchars(json_encode($c['payments'])) ?>)" class="w-full text-left p-4 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between">
                            <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($name) ?></span>
                            <span class="text-sm font-semibold text-amber-700 dark:text-amber-300"><?= fmtMoney($total) ?> ₺</span>
                        </button>
                    <?php endforeach; ?>
                    <?php if (empty($customersWithDebt)): ?>
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">Borcu olan müşteri yok.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Adım 2: Ödeme seç -->
            <div id="stepPayment" class="step-content hidden">
                <button type="button" onclick="collectStep(1)" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline mb-4">← Müşteri seç</button>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Ödemeyi seçin.</p>
                <div id="paymentList" class="space-y-2 max-h-48 overflow-y-auto"></div>
            </div>

            <!-- Adım 3: Ödeme yöntemi -->
            <div id="stepMethod" class="step-content hidden">
                <button type="button" onclick="collectStep(2)" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline mb-4">← Ödeme seç</button>
                <div id="selectedAmountSummary" class="mb-4 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 text-sm font-medium"></div>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Ödeme yöntemini seçin.</p>
                <div class="space-y-3">
                    <button type="button" onclick="setPaymentMethod('cash')" class="collect-method w-full p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 flex items-center gap-3 text-left" data-method="cash">
                        <i class="bi bi-cash-stack text-2xl text-green-600"></i>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Nakit</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Nakit ödeme al</p>
                        </div>
                    </button>
                    <button type="button" onclick="setPaymentMethod('bank_transfer')" class="collect-method w-full p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 flex items-center gap-3 text-left" data-method="bank_transfer">
                        <i class="bi bi-bank text-2xl text-blue-600"></i>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Havale</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Banka havalesi ile ödeme al</p>
                        </div>
                    </button>
                    <button type="button" onclick="setPaymentMethod('credit_card')" class="collect-method w-full p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 flex items-center gap-3 text-left" data-method="credit_card">
                        <i class="bi bi-credit-card text-2xl text-purple-600"></i>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Kredi Kartı</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">PayTR ile online ödeme</p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Adım 4: Havale formu -->
            <div id="stepBankTransfer" class="step-content hidden">
                <button type="button" onclick="collectStep(3)" class="text-sm text-emerald-600 hover:underline mb-4">← Ödeme yöntemi</button>
                <form id="collectForm" method="post" action="/odemeler/odeme-al">
                    <input type="hidden" name="payment_method" id="form_payment_method" value="bank_transfer">
                    <div id="collectFormIds"></div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Banka Hesabı <span class="text-red-500">*</span></label>
                            <?php if (empty($bankAccounts)): ?>
                                <p class="text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 p-3 rounded-xl">Aktif banka hesabı yok. Ayarlar → Banka Hesaplarından ekleyin.</p>
                            <?php else: ?>
                                <select name="bank_account_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Seçin</option>
                                    <?php foreach ($bankAccounts as $ba): ?>
                                        <option value="<?= htmlspecialchars($ba['id']) ?>"><?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_number']) ?> (<?= htmlspecialchars($ba['account_holder_name']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İşlem No (opsiyonel)</label>
                            <input type="text" name="transaction_id" placeholder="Havale işlem numarası" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not (opsiyonel)</label>
                            <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="button" onclick="collectStep(3)" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-600">İptal</button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Ödemeyi Kaydet</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Nakit / Kredi kartı için tek butonla gönderim -->
            <div id="stepSubmitSimple" class="step-content hidden">
                <button type="button" onclick="collectStep(3)" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline mb-4">← Ödeme yöntemi</button>
                <form id="collectFormSimple" method="post" action="/odemeler/odeme-al">
                    <input type="hidden" name="payment_method" id="form_payment_method_simple" value="">
                    <div id="collectFormSimpleIds"></div>
                    <div class="flex gap-2 pt-2">
                        <button type="button" onclick="collectStep(3)" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">İptal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Ödemeyi Kaydet</button>
                    </div>
                </form>
            </div>
            <div id="stepCreditCardNote" class="step-content hidden">
                <button type="button" onclick="collectStep(3)" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline mb-4">← Ödeme yöntemi</button>
                <p class="text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 p-4 rounded-xl text-sm">Kredi kartı ile ödeme için PayTR entegrasyonu gereklidir. Ayarlar sayfasından PayTR bilgilerinizi girip aktif edin. Şu an sadece Nakit veya Havale ile kayıt yapabilirsiniz.</p>
            </div>
        </div>
    </div>
</div>

<script>
var collectCurrentStep = 1;
var collectSelectedPayments = [];

var customersWithDebt = <?= json_encode(array_map(function($c) {
    return ['id' => $c['id'], 'name' => trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? '')), 'payments' => $c['payments']];
}, $customersWithDebt)) ?>;

function openCollectModal() {
    document.getElementById('collectModal').classList.remove('hidden');
    collectCurrentStep = 1;
    collectSelectedPayments = [];
    collectStep(1);
}
function openCollectForPayment(payment) {
    collectSelectedPayments = [payment];
    document.getElementById('collectModal').classList.remove('hidden');
    document.getElementById('selectedAmountSummary').textContent = 'Toplam: ' + parseFloat(payment.amount || 0).toFixed(2).replace('.', ',') + ' ₺';
    collectStep(3);
    document.getElementById('stepMethod').classList.remove('hidden');
}
function closeCollectModal() {
    document.getElementById('collectModal').classList.add('hidden');
}
function collectStep(n) {
    collectCurrentStep = n;
    document.querySelectorAll('.step-content').forEach(function(el) { el.classList.add('hidden'); });
    if (n === 1) document.getElementById('stepCustomer').classList.remove('hidden');
    if (n === 2) document.getElementById('stepPayment').classList.remove('hidden');
    if (n === 3) document.getElementById('stepMethod').classList.remove('hidden');
    document.getElementById('stepBankTransfer').classList.add('hidden');
    document.getElementById('stepSubmitSimple').classList.add('hidden');
    document.getElementById('stepCreditCardNote').classList.add('hidden');
}
function selectCustomer(customerId, payments) {
    collectSelectedPayments = Array.isArray(payments) ? payments : [];
    var list = document.getElementById('paymentList');
    list.innerHTML = '';
    var total = 0;
    collectSelectedPayments.forEach(function(p) {
        total += parseFloat(p.amount || 0);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w-full text-left p-4 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 flex justify-between items-center';
        btn.innerHTML = '<div><span class="font-medium text-gray-900 dark:text-white">' + (p.payment_number || '') + '</span><br><span class="text-xs text-gray-500 dark:text-gray-400">Vade: ' + (p.due_date ? p.due_date.split(' ')[0] : '') + '</span></div><span class="font-semibold text-gray-900 dark:text-white">' + parseFloat(p.amount || 0).toFixed(2).replace('.', ',') + ' ₺</span>';
        btn.onclick = function() { selectOnePayment(p); };
        list.appendChild(btn);
    });
    collectStep(2);
}
function selectOnePayment(p) {
    collectSelectedPayments = [p];
    var total = collectSelectedPayments.reduce(function(s, x) { return s + parseFloat(x.amount || 0); }, 0);
    document.getElementById('selectedAmountSummary').textContent = 'Toplam: ' + total.toFixed(2).replace('.', ',') + ' ₺';
    collectStep(3);
    document.getElementById('stepMethod').classList.remove('hidden');
}
function setPaymentMethod(method) {
    document.querySelectorAll('.collect-method').forEach(function(b) { b.classList.remove('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-900/20'); b.classList.add('border-gray-200', 'dark:border-gray-600'); });
    var btn = document.querySelector('.collect-method[data-method="' + method + '"]');
    if (btn) { btn.classList.add('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-900/20'); btn.classList.remove('border-gray-200', 'dark:border-gray-600'); }
    if (method === 'bank_transfer') {
        document.getElementById('stepMethod').classList.add('hidden');
        document.getElementById('stepBankTransfer').classList.remove('hidden');
        var form = document.getElementById('collectForm');
        var container = document.getElementById('collectFormIds');
        if (container) {
            container.innerHTML = '';
            collectSelectedPayments.forEach(function(p) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'payment_ids[]';
                inp.value = p.id;
                container.appendChild(inp);
            });
        }
    } else if (method === 'cash') {
        document.getElementById('stepMethod').classList.add('hidden');
        document.getElementById('stepSubmitSimple').classList.remove('hidden');
        document.getElementById('form_payment_method_simple').value = 'cash';
        var container = document.getElementById('collectFormSimpleIds');
        container.innerHTML = '';
        collectSelectedPayments.forEach(function(p) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'payment_ids[]';
            inp.value = p.id;
            container.appendChild(inp);
        });
    } else {
        document.getElementById('stepMethod').classList.add('hidden');
        document.getElementById('stepCreditCardNote').classList.remove('hidden');
    }
}
// Müşteri seçildiğinde ödeme listesinde tek tıklamada seç (ilk ödemeyi seç veya tümünü listele - kullanıcı birini seçsin)
function buildPaymentListForSelect() {
    var list = document.getElementById('paymentList');
    list.innerHTML = '';
    if (collectSelectedPayments.length === 0) return;
    var total = 0;
    collectSelectedPayments.forEach(function(p) {
        total += parseFloat(p.amount || 0);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w-full text-left p-4 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 flex justify-between items-center';
        btn.innerHTML = '<div><span class="font-medium text-gray-900 dark:text-white">' + (p.payment_number || '') + '</span><br><span class="text-xs text-gray-500 dark:text-gray-400">Vade: ' + (p.due_date ? p.due_date.split(' ')[0] : '') + '</span></div><span class="font-semibold text-gray-900 dark:text-white">' + parseFloat(p.amount || 0).toFixed(2).replace('.', ',') + ' ₺</span>';
        btn.onclick = function() { selectOnePayment(p); };
        list.appendChild(btn);
    });
}
document.getElementById('customerSearch').addEventListener('input', function() {
    var q = this.value.trim().toLowerCase();
    document.querySelectorAll('#customerList button').forEach(function(btn) {
        var name = (btn.querySelector('span') && btn.querySelector('span').textContent) || '';
        btn.style.display = !q || name.toLowerCase().indexOf(q) >= 0 ? 'block' : 'none';
    });
});
var preselectedCustomerId = <?= json_encode($preselectedCustomerId ?? '') ?>;
<?php if ($collectMode): ?>document.addEventListener('DOMContentLoaded', function() {
    openCollectModal();
    if (preselectedCustomerId && customersWithDebt && customersWithDebt.length) {
        var c = customersWithDebt.find(function(x) { return x.id === preselectedCustomerId; });
        if (c && c.payments && c.payments.length) selectCustomer(c.id, c.payments);
    }
});<?php endif; ?>
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
