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
        <div id="propBulkBar" class="hidden flex items-center justify-between gap-3 px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600 flex-wrap">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><span id="propBulkCount">0</span> teklif seçildi</span>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="printSelectedProposals()" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200">Yazdır / PDF</button>
                <button type="button" onclick="emailSelectedProposals()" class="px-3 py-1.5 rounded-lg text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100">E-posta Gönder</button>
                <form method="post" action="/teklifler/sil" id="propBulkDeleteForm" class="inline">
                    <div id="propBulkIdsContainer"></div>
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Toplu Sil</button>
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" id="selectAllProposals" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" title="Tümünü seç"></label></th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Başlık</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Müşteri</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tutar</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($proposals as $p): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 proposal-row" data-id="<?= htmlspecialchars($p['id'] ?? '') ?>" data-title="<?= htmlspecialchars($p['title'] ?? '') ?>" data-customer="<?= htmlspecialchars(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? '')) ?>" data-amount="<?= htmlspecialchars(fmtPrice($p['total_amount'] ?? 0)) ?>" data-status="<?= htmlspecialchars($statusLabels[$p['status'] ?? 'draft'] ?? $p['status']) ?>" data-email="<?= htmlspecialchars($p['customer_email'] ?? '') ?>">
                            <td class="px-4 py-3"><label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="proposal-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="<?= htmlspecialchars($p['id'] ?? '') ?>"></label></td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($p['title'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? '') ?: '-') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= fmtPrice($p['total_amount'] ?? 0) ?> <?= htmlspecialchars($p['currency'] ?? 'TRY') ?></td>
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
                                <a href="/teklifler/<?= htmlspecialchars($p['id'] ?? '') ?>/yazdir" target="_blank" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 mr-1" title="Görüntüle / Yazdır / PDF"><i class="bi bi-eye mr-1"></i> Detay</a>
                                <a href="/teklifler/<?= htmlspecialchars($p['id'] ?? '') ?>/duzenle" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 mr-1" title="Düzenle"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="/teklifler/sil" class="inline" onsubmit="return confirm('Bu teklifi silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="ids[]" value="<?= htmlspecialchars($p['id'] ?? '') ?>">
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
<script>
(function() {
    var bulkBar = document.getElementById('propBulkBar');
    var bulkCountEl = document.getElementById('propBulkCount');
    var selectAll = document.getElementById('selectAllProposals');
    var form = document.getElementById('propBulkDeleteForm');
    var container = document.getElementById('propBulkIdsContainer');
    function updateBulkBar() {
        var cbs = document.querySelectorAll('.proposal-cb:checked');
        var n = cbs.length;
        if (bulkCountEl) bulkCountEl.textContent = n;
        if (bulkBar) bulkBar.classList.toggle('hidden', n === 0);
        if (selectAll) selectAll.checked = n > 0 && document.querySelectorAll('.proposal-cb').length === n;
    }
    function getSelectedIds() {
        var cbs = document.querySelectorAll('.proposal-cb:checked');
        return Array.prototype.map.call(cbs, function(cb) { return cb.value; });
    }
    window.printSelectedProposals = function() {
        var ids = getSelectedIds();
        if (ids.length === 0) { alert('Lütfen en az bir teklif seçin.'); return; }
        var q = ids.map(function(id) { return 'id[]=' + encodeURIComponent(id); }).join('&');
        var w = window.open('/teklifler/yazdir?' + q, 'teklifler_yazdir', 'width=900,height=700,scrollbars=yes');
        if (w) w.focus();
    };
    window.emailSelectedProposals = function() {
        var ids = getSelectedIds();
        if (ids.length === 0) { alert('Lütfen en az bir teklif seçin.'); return; }
        var rows = document.querySelectorAll('.proposal-row');
        var emails = [];
        ids.forEach(function(id) {
            var row = document.querySelector('.proposal-row[data-id="' + id.replace(/"/g, '\\"') + '"]');
            if (row && row.getAttribute('data-email')) {
                var e = row.getAttribute('data-email').trim();
                if (e && emails.indexOf(e) === -1) emails.push(e);
            }
        });
        if (emails.length === 0) {
            alert('Seçili tekliflerde e-posta adresi bulunamadı. Müşteri bilgilerine e-posta ekleyin.');
            return;
        }
        var subject = encodeURIComponent('Teklifleriniz');
        var body = encodeURIComponent('Merhaba,\n\nSeçili teklifleriniz ektedir veya aşağıdaki linklerden ulaşabilirsiniz.\n\nİyi günler.');
        window.location.href = 'mailto:' + emails.join(',') + '?subject=' + subject + '&body=' + body;
    };
    if (form) form.addEventListener('submit', function(e) {
        var cbs = document.querySelectorAll('.proposal-cb:checked');
        if (cbs.length === 0) { e.preventDefault(); return; }
        if (!confirm('Seçili ' + cbs.length + ' teklifi silmek istediğinize emin misiniz?')) { e.preventDefault(); return; }
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
    document.querySelectorAll('.proposal-cb').forEach(function(cb) { cb.addEventListener('change', updateBulkBar); });
    if (selectAll) selectAll.addEventListener('change', function() { document.querySelectorAll('.proposal-cb').forEach(function(cb) { cb.checked = selectAll.checked; }); updateBulkBar(); });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
