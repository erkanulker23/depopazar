<?php
$currentPage = 'musteriler';
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
ob_start();
?>
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="/musteriler" class="text-emerald-600 hover:text-emerald-700 font-medium">Müşteriler</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-gray-700 dark:text-gray-300 font-medium"><?= htmlspecialchars($customerName) ?></span>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Müşteri Detayı</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($customerName) ?></p>
    </div>
    <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/barkod" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-700 text-white font-medium hover:bg-gray-800 transition-colors">
        <i class="bi bi-upc-scan mr-2"></i> Barkod Yazdır
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Sol: Profil + Borç -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-person text-emerald-600"></i> Müşteri Bilgileri
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Ad Soyad</dt>
                    <dd class="mt-1 font-medium text-gray-900"><?= htmlspecialchars($customerName ?: '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">E-posta</dt>
                    <dd class="mt-1 text-gray-600"><?= htmlspecialchars($customer['email'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Telefon</dt>
                    <dd class="mt-1 text-gray-600"><?= htmlspecialchars($customer['phone'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">TC Kimlik No</dt>
                    <dd class="mt-1 text-gray-600"><?= htmlspecialchars($customer['identity_number'] ?? '-') ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Adres</dt>
                    <dd class="mt-1 text-gray-600"><?= htmlspecialchars($customer['address'] ?? '-') ?></dd>
                </div>
                <?php if (!empty($customer['notes'])): ?>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold text-gray-500 uppercase tracking-widest">Not</dt>
                    <dd class="mt-1 text-gray-600"><?= nl2br(htmlspecialchars($customer['notes'])) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Borç özeti -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-cash-stack text-emerald-600"></i> Borç Özeti
            </h2>
            <div class="flex items-center gap-4">
                <div class="rounded-xl bg-amber-50 border border-amber-100 px-6 py-4">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-widest">Toplam Borç</p>
                    <p class="text-2xl font-bold text-amber-800"><?= number_format($debt, 2, ',', '.') ?> ₺</p>
                </div>
                <?php if ($debt > 0): ?>
                <a href="/odemeler?collect=1&customer=<?= htmlspecialchars($customer['id']) ?>" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
                    <i class="bi bi-bank mr-2"></i> Ödeme Al
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sözleşmeler -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 p-4 border-b border-gray-100 flex items-center gap-2">
                <i class="bi bi-file-text text-emerald-600"></i> Sözleşmeler
            </h2>
            <?php if (empty($contracts)): ?>
                <div class="p-6 text-center text-gray-500">Bu müşteriye ait sözleşme yok.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Sözleşme No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Depo / Oda</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Başlangıç</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Bitiş</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Aylık</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($contracts as $c): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900"><a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="text-emerald-600 hover:text-emerald-700"><?= htmlspecialchars($c['contract_number'] ?? '-') ?></a></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($c['warehouse_name'] ?? '') ?> / <?= htmlspecialchars($c['room_number'] ?? '') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= date('d.m.Y', strtotime($c['start_date'] ?? '')) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= date('d.m.Y', strtotime($c['end_date'] ?? '')) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= number_format((float)($c['monthly_price'] ?? 0), 2, ',', '.') ?> ₺</td>
                                    <td class="px-4 py-3"><a href="/girisler/<?= htmlspecialchars($c['id'] ?? '') ?>" class="text-sm text-emerald-600 hover:text-emerald-700">Detay</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Aylık ödeme takvimi: hangi aylar ödendi / ödenmedi / gecikmede -->
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
        krsort($monthsStatus, SORT_STRING);
        ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-calendar-month text-emerald-600"></i> Aylık Ödeme Durumu (Takvim)
            </h2>
            <div class="p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Hangi aylar ödendi, hangi aylar ödenmedi veya gecikmede</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($monthsStatus as $ym => $info):
                        $status = $info['status'];
                        $bg = $status === 'paid' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : ($status === 'overdue' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300');
                        $parts = explode('-', $ym);
                        $monthLabel = $monthNames[(int)$parts[1] - 1] . ' ' . $parts[0];
                    ?>
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium <?= $bg ?>" title="<?= htmlspecialchars($info['contract_number']) ?> – <?= number_format((float)$info['amount'], 2, ',', '.') ?> ₺">
                            <?= htmlspecialchars($monthLabel) ?>: <?= htmlspecialchars($info['label']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($monthsStatus)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ödeme kaydı yok.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ödeme takvimi (son ödemeler) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white p-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="bi bi-credit-card text-emerald-600"></i> Ödeme Takvimi / Ödenenler
            </h2>
            <?php if (empty($payments)): ?>
                <div class="p-6 text-center text-gray-500">Ödeme kaydı yok.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Sözleşme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Vade</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Durum</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Ödenme</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-widest"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($p['contract_number'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= date('d.m.Y', strtotime($p['due_date'] ?? '')) ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= number_format((float)($p['amount'] ?? 0), 2, ',', '.') ?> ₺</td>
                                    <td class="px-4 py-3">
                                        <?php
                                        $status = $p['status'] ?? 'pending';
                                        $badge = ['pending' => 'bg-amber-100 text-amber-800', 'paid' => 'bg-green-100 text-green-800', 'overdue' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-gray-100 text-gray-800'][$status] ?? 'bg-gray-100 text-gray-800';
                                        $label = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal'][$status] ?? $status;
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badge ?>"><?= $label ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= !empty($p['paid_at']) ? date('d.m.Y', strtotime($p['paid_at'])) : '–' ?></td>
                                    <td class="px-4 py-3">
                                        <?php if (($p['status'] ?? '') === 'pending' || ($p['status'] ?? '') === 'overdue'): ?>
                                            <a href="/odemeler?payment=<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">Ödeme al</a>
                                        <?php else: ?>
                                            <a href="/odemeler/<?= htmlspecialchars($p['id'] ?? '') ?>" class="text-gray-500 hover:text-gray-700 text-sm">Detay</a>
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

    <!-- Sağ: Özet kart (opsiyonel) -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <h3 class="font-bold text-gray-900 mb-2">Özet</h3>
            <p class="text-sm text-gray-600">Sözleşme sayısı: <strong><?= count($contracts) ?></strong></p>
            <p class="text-sm text-gray-600 mt-1">Toplam borç: <strong class="text-amber-700"><?= number_format($debt, 2, ',', '.') ?> ₺</strong></p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
