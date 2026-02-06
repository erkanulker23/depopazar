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
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- BAKİYE DURUM kartı -->
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
    <div class="bg-rose-500/10 dark:bg-rose-500/20 border-b border-rose-200/50 dark:border-rose-800/50 px-4 py-3">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-bar-chart-fill text-rose-600 dark:text-rose-400"></i> BAKİYE DURUM
        </h2>
    </div>
    <div class="p-4 space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($customerName) ?></span>
            <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400" title="Düzenle (yakında)"><i class="bi bi-pencil"></i></a>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                <span class="font-semibold text-gray-900 dark:text-white"><?= number_format((float)$debt, 2, ',', '.') ?> ₺</span>
                <?php if ($debt > 0): ?><span class="text-red-600 dark:text-red-400">(Müşteri Borçlu)</span><?php endif; ?>
            </p>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Son Ödeme</p>
            <?php if ($lastPayment): ?>
            <p class="text-sm <?= (time() - strtotime($lastPayment['paid_at'])) > 90 * 86400 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' ?>">
                <?= htmlspecialchars(timeAgoTr($lastPayment['paid_at'])) ?>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Tarih: <?= date('d-m-Y', strtotime($lastPayment['paid_at'])) ?> · Tutar: <?= fmtPrice($lastPayment['amount'] ?? 0) ?></p>
            <?php else: ?>
            <p class="text-sm text-red-600 dark:text-red-400">Ödeme kaydı yok.</p>
            <?php endif; ?>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Aylık Kira Tutarı</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= number_format((float)$monthlyRent, 2, ',', '.') ?> ₺</p>
        </div>
        <?php if ($primaryWarehouse !== null): ?>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300"><?= htmlspecialchars($primaryWarehouse) ?></span>
            <?php if ($exitDone): ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">Çıkış İşlemi Yapıldı</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/borclandir" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                <i class="bi bi-currency-dollar"></i> Borçlandır
            </a>
            <a href="/odemeler?collect=1&customer=<?= htmlspecialchars($customer['id']) ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
                <i class="bi bi-currency-dollar"></i> Ödeme Gir
            </a>
            <a href="/girisler?newSale=1&newCustomerId=<?= htmlspecialchars($customer['id']) ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition-colors">
                <i class="bi bi-bag-plus"></i> Depo Girişi Ekle
            </a>
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/belge-ekle" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                <i class="bi bi-file-earmark-plus"></i> Belge Ekle
            </a>
            <a href="/musteriler/<?= htmlspecialchars($customer['id']) ?>/cikis-belgesi" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                <i class="bi bi-download"></i> Çıkış Belgesi Oluştur
            </a>
            <button type="button" onclick="document.getElementById('noteModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                <i class="bi bi-chat-left-text"></i> Bilgi Notu Ekle
            </button>
            <button type="button" onclick="document.getElementById('smsModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                <i class="bi bi-chat-dots"></i> SMS Gönder
            </button>
        </div>
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
        <?php $documents = $documents ?? []; if (!empty($documents)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <i class="bi bi-file-earmark text-emerald-600"></i> Belgeler
            </h3>
            <ul class="space-y-2">
                <?php foreach ($documents as $doc): ?>
                <li class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <a href="<?= htmlspecialchars(strpos($doc['file_path'] ?? '', '/') === 0 ? $doc['file_path'] : '/' . $doc['file_path']) ?>" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium"><?= htmlspecialchars($doc['name'] ?? 'Belge') ?></a>
                    <form method="post" action="/musteriler/belge-sil" class="inline" onsubmit="return confirm('Bu belgeyi silmek istediğinize emin misiniz?');">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($doc['id']) ?>">
                        <input type="hidden" name="redirect" value="/musteriler/<?= htmlspecialchars($customer['id']) ?>">
                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Sil</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Bilgi Notu -->
<div id="noteModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('noteModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><i class="bi bi-chat-left-text text-emerald-600 mr-2"></i> Bilgi Notu</h3>
                <button type="button" onclick="document.getElementById('noteModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/musteriler/<?= htmlspecialchars($customer['id']) ?>/not-guncelle" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                    <textarea name="notes" rows="5" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="Müşteri hakkında not..."><?= htmlspecialchars($customer['notes'] ?? '') ?></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('noteModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: SMS Gönder -->
<div id="smsModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('smsModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><i class="bi bi-chat-dots text-emerald-600 mr-2"></i> SMS Gönder</h3>
                <button type="button" onclick="document.getElementById('smsModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">SMS, Ayarlar &rarr; SMS (Netgsm) bölümündeki ayarlara göre gönderilir. Alıcı: <strong><?= htmlspecialchars($customer['phone'] ?? '') ?></strong></p>
            <form method="post" action="/musteriler/<?= htmlspecialchars($customer['id']) ?>/sms-gonder" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mesaj <span class="text-red-500">*</span></label>
                    <textarea name="message" required rows="4" maxlength="160" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="SMS metnini yazın (tek mesaj 160 karakter)"></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tek SMS en fazla 160 karakter. Uzun metinler birden fazla SMS olarak gönderilir.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('smsModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
