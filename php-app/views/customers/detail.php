<?php
$currentPage = 'musteriler';
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
ob_start();
?>
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="/musteriler" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium">Müşteriler</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-gray-700 dark:text-gray-300 font-medium"><?= htmlspecialchars($customerName) ?></span>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Müşteri Detayı</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($customerName) ?></p>
    </div>
    <div class="flex gap-2">
        <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/yazdir" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-printer mr-2"></i> Sayfayı Yazdır
        </a>
        <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/barkod" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-700 text-white font-medium hover:bg-gray-800 transition-colors">
            <i class="bi bi-upc-scan mr-2"></i> Barkod Yazdır
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Sol: Profil -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-person text-emerald-600"></i> Müşteri Bilgileri
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ad Soyad</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($customerName ?: '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">E-posta</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($customer['email'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Telefon</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($customer['phone'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">TC Kimlik No</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($customer['identity_number'] ?? '-') ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Adres</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($customer['address'] ?? '-') ?></dd>
                </div>
                <?php if (!empty($customer['notes'])): ?>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Not</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= nl2br(htmlspecialchars($customer['notes'])) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Sözleşmeler -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-file-text text-emerald-600"></i> Sözleşmeler
            </h2>
            <?php if (empty($contracts)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Bu müşteriye ait sözleşme yok.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Depo / Oda</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Başlangıç</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Bitiş</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($contracts as $c): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></a></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= date('d.m.Y', strtotime($c['start_date'] ?? '')) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= date('d.m.Y', strtotime($c['end_date'] ?? '')) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= number_format((float)($c['monthly_price'] ?? 0), 2, ',', '.') ?> ₺</td>
                                    <td class="px-4 py-3"><a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">Detay</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Aylar takvimi: Ocak, Şubat, Mart... hangi ay ödendi / ödenmedi -->
        <?php
        $monthNames = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
        $monthsStatus = [];
        foreach ($payments as $p) {
            $due = $p['due_date'] ?? '';
            if ($due === '') continue;
            $key = date('Y-m', strtotime($due));
            $status = $p['status'] ?? 'pending';
            $label = $status === 'paid' ? 'Ödendi' : ($status === 'overdue' ? 'Gecikmede' : 'Ödenmedi');
            $monthsStatus[$key] = ['status' => $status, 'label' => $label, 'amount' => $p['amount'] ?? 0, 'contract_number' => $p['contract_number'] ?? ''];
        }
        $minYear = date('Y');
        $maxYear = date('Y');
        foreach (array_keys($monthsStatus) as $ym) {
            $y = (int) substr($ym, 0, 4);
            if ($y < $minYear) $minYear = $y;
            if ($y > $maxYear) $maxYear = $y;
        }
        foreach ($contracts as $c) {
            if (!empty($c['start_date'])) { $y = (int) date('Y', strtotime($c['start_date'])); if ($y < $minYear) $minYear = $y; }
            if (!empty($c['end_date'])) { $y = (int) date('Y', strtotime($c['end_date'])); if ($y > $maxYear) $maxYear = $y; }
        }
        ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-calendar-month text-emerald-600"></i> Aylar Takvimi – Ödendi / Ödenmedi
            </h2>
            <div class="p-4 overflow-x-auto">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Ocak, Şubat, Mart… hangi ay ödendi, hangi ay ödenmedi</p>
                <?php if ($maxYear >= $minYear): ?>
                <table class="min-w-full border border-gray-200 dark:border-gray-600 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="border border-gray-200 dark:border-gray-600 px-2 py-2 text-left font-bold text-gray-700 dark:text-gray-300">Yıl</th>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <th class="border border-gray-200 dark:border-gray-600 px-2 py-2 text-center font-bold text-gray-700 dark:text-gray-300"><?= $monthNames[$m - 1] ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($year = $maxYear; $year >= $minYear; $year--): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="border border-gray-200 dark:border-gray-600 px-2 py-2 font-medium text-gray-900 dark:text-white"><?= $year ?></td>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <?php
                                $key = $year . '-' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
                                $info = $monthsStatus[$key] ?? null;
                                $status = $info['status'] ?? null;
                                $label = $info ? $info['label'] : '–';
                                $bg = $status === 'paid' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : ($status === 'overdue' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : ($status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-gray-50 dark:bg-gray-700/30 text-gray-500 dark:text-gray-400'));
                                $title = $info ? (($info['contract_number'] ?? '') . ' – ' . fmtPrice($info['amount'] ?? 0)) : '';
                                ?>
                                <td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5 text-center <?= $bg ?>" title="<?= htmlspecialchars($title) ?>"><?= htmlspecialchars($label) ?></td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sözleşme veya ödeme kaydı yok.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ödeme takvimi (son ödemeler) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-credit-card text-emerald-600"></i> Ödeme Takvimi / Ödenenler
            </h2>
            <?php if (empty($payments)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">Ödeme kaydı yok.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sözleşme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Vade</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ödenme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($p['contract_number'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= number_format((float)($p['amount'] ?? 0), 2, ',', '.') ?> ₺</td>
                                    <td class="px-4 py-3">
                                        <?php
                                        $status = $p['status'] ?? 'pending';
                                        $badge = ['pending' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300', 'paid' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300', 'overdue' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300', 'cancelled' => 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200'][$status] ?? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
                                        $label = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal'][$status] ?? $status;
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badge ?>"><?= $label ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= !empty($p['paid_at']) ? date('d.m.Y', strtotime($p['paid_at'])) : '–' ?></td>
                                    <td class="px-4 py-3">
                                        <?php if (($p['status'] ?? '') === 'pending' || ($p['status'] ?? '') === 'overdue'): ?>
                                            <a href="/odemeler?payment=<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 text-sm font-medium">Ödeme al</a>
                                        <?php else: ?>
                                            <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm">Detay</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sağ: Borç Özeti -->
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-cash-stack text-emerald-600"></i> Borç Özeti
            </h3>
            <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-4 mb-4">
                <p class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-widest">Toplam Borç</p>
                <p class="text-2xl font-bold text-amber-800 dark:text-amber-300"><?= number_format($debt, 2, ',', '.') ?> ₺</p>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Sözleşme sayısı: <strong><?= count($contracts) ?></strong></p>
            <?php if ($debt > 0): ?>
            <a href="/odemeler?collect=1&customer=<?= htmlspecialchars($customer['id']) ?>" class="mt-4 block w-full text-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
                <i class="bi bi-bank mr-2"></i> Ödeme Al
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
