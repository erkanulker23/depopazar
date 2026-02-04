<?php
$currentPage = 'kullanicilar';
$profile = $profile ?? [];
$roleLabels = $roleLabels ?? [];
$fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
ob_start();
?>
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/kullanicilar" class="text-emerald-600 hover:text-emerald-700 font-medium">Kullanıcılar</a>
        <i class="bi bi-chevron-right"></i>
        <span class="text-gray-700 dark:text-gray-300 font-medium"><?= htmlspecialchars($fullName) ?></span>
    </div>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Kullanıcı Profili</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($fullName) ?></p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-2xl">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="bi bi-person text-emerald-600"></i> Bilgiler
    </h2>
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ad Soyad</dt>
            <dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($fullName ?: '-') ?></dd>
        </div>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">E-posta</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($profile['email'] ?? '-') ?></dd>
        </div>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Telefon</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($profile['phone'] ?? '-') ?></dd>
        </div>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Rol</dt>
            <dd class="mt-1 text-gray-900 dark:text-white"><?= htmlspecialchars($roleLabels[$profile['role'] ?? ''] ?? $profile['role'] ?? '-') ?></dd>
        </div>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Şirket</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($companyName ?? '-') ?></dd>
        </div>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</dt>
            <dd class="mt-1"><?= !empty($profile['is_active']) ? '<span class="text-green-600 dark:text-green-400 font-medium">Aktif</span>' : '<span class="text-gray-500 dark:text-gray-400">Pasif</span>' ?></dd>
        </div>
        <?php if (!empty($profile['last_login_at'])): ?>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Son Giriş</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= date('d.m.Y H:i', strtotime($profile['last_login_at'])) ?></dd>
        </div>
        <?php endif; ?>
    </dl>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
