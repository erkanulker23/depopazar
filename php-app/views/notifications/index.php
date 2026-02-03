<?php
$currentPage = 'bildirimler';
$notifications = $notifications ?? [];
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
ob_start();
?>
<div class="mb-6">
    <h1 class="page-title gradient-title">Bildirimler</h1>
    <p class="page-subtitle uppercase tracking-widest font-bold">Tüm işlem bildirimleri</p>
</div>

<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm flex items-center justify-between">
        <span><?= htmlspecialchars($flashSuccess) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm flex items-center justify-between">
        <span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>

<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
    <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($notifications) ?> bildirim</span>
    <div class="flex flex-wrap gap-2">
        <form method="post" action="/bildirimler/okundu" class="inline">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
                <i class="bi bi-check-all mr-2"></i> Tümü Okundu
            </button>
        </form>
        <form method="post" action="/bildirimler/tumunu-sil" class="inline" onsubmit="return confirm('Tüm bildirimleri silmek istediğinize emin misiniz?');">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20">
                <i class="bi bi-trash mr-2"></i> Tümünü Sil
            </button>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($notifications)): ?>
        <div class="p-12 text-center text-gray-500 dark:text-gray-400">
            <i class="bi bi-bell text-5xl text-gray-300 dark:text-gray-600 block mb-3"></i>
            <p>Henüz bildirim yok.</p>
        </div>
    <?php else: ?>
        <ul class="divide-y divide-gray-200 dark:divide-gray-600">
            <?php foreach ($notifications as $n): ?>
                <li class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 <?= empty($n['is_read']) ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : '' ?>">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center <?= empty($n['is_read']) ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400' ?>">
                            <i class="bi bi-<?= $n['type'] === 'payment' ? 'credit-card' : ($n['type'] === 'contract' ? 'file-text' : 'bell') ?> text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($n['title']) ?></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= date('d.m.Y H:i', strtotime($n['created_at'] ?? '')) ?></p>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
