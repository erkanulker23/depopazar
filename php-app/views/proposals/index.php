<?php
$currentPage = 'teklifler';
$proposals = $proposals ?? [];
$statusLabels = ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul', 'rejected' => 'Red'];
$durumGet = isset($_GET['durum']) ? $_GET['durum'] : '';
ob_start();
?>
<div class="mb-6">
    <h1 class="page-title gradient-title">Teklifler</h1>
    <p class="page-subtitle uppercase tracking-widest font-bold">Teklif listesi</p>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
    <form method="get" action="/teklifler" class="flex flex-wrap items-center gap-2">
        <select name="durum" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm dark:bg-gray-700 dark:text-white">
            <option value="">Tüm durumlar</option>
            <?php foreach ($statusLabels as $val => $l): ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= $durumGet === $val ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium">Filtrele</button>
    </form>
    <a href="/teklifler/yeni" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
        <i class="bi bi-plus-lg mr-2"></i> Yeni Teklif
    </a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($proposals)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz teklif yok<?= $durumGet !== '' ? ' veya filtreye uygun kayıt yok.' : '.' ?></div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Başlık</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($proposals as $p): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($p['title'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? '') ?: '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= number_format((float)($p['total_amount'] ?? 0), 2, ',', '.') ?> <?= htmlspecialchars($p['currency'] ?? 'TRY') ?></td>
                            <td class="px-4 py-3">
                                <form method="post" action="/teklifler/durum" class="inline">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id'] ?? '') ?>">
                                    <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1 dark:bg-gray-700 dark:text-white">
                                        <?php foreach ($statusLabels as $val => $l): ?>
                                            <option value="<?= htmlspecialchars($val) ?>" <?= ($p['status'] ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="/teklifler/<?= htmlspecialchars($p['id'] ?? '') ?>/duzenle" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 mr-1">Düzenle</a>
                                <form method="post" action="/teklifler/sil" class="inline" onsubmit="return confirm('Bu teklifi silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id'] ?? '') ?>">
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
