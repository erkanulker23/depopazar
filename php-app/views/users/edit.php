<?php
$currentPage = 'kullanicilar';
$profile = $profile ?? [];
$companies = $companies ?? [];
$roleLabels = $roleLabels ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
ob_start();
?>
<div class="mb-6">
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/kullanicilar" class="text-emerald-600 dark:text-emerald-400 hover:underline">Kullanıcılar</a>
        <span class="mx-1">/</span>
        <a href="/kullanicilar/<?= htmlspecialchars($profile['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars(trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''))) ?></a>
        <span class="mx-1">/</span>
        <span class="text-gray-700 dark:text-gray-300">Düzenle</span>
    </nav>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Kullanıcı Düzenle</h1>
</div>

<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-lg">
    <form method="post" action="/kullanicilar/guncelle" class="space-y-4">
        <input type="hidden" name="id" value="<?= htmlspecialchars($profile['id'] ?? '') ?>">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad <span class="text-red-500">*</span></label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soyad <span class="text-red-500">*</span></label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yeni şifre (boş bırakırsanız değişmez)</label>
            <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol</label>
            <select name="role" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <option value="company_staff" <?= ($profile['role'] ?? '') === 'company_staff' ? 'selected' : '' ?>>Personel</option>
                <option value="company_owner" <?= ($profile['role'] ?? '') === 'company_owner' ? 'selected' : '' ?>>Şirket Sahibi</option>
                <option value="data_entry" <?= ($profile['role'] ?? '') === 'data_entry' ? 'selected' : '' ?>>Veri Girişi</option>
                <option value="accounting" <?= ($profile['role'] ?? '') === 'accounting' ? 'selected' : '' ?>>Muhasebe</option>
                <?php if (!empty($currentUserIsSuperAdmin) || ($profile['role'] ?? '') === 'super_admin'): ?>
                <option value="super_admin" <?= ($profile['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Süper Admin</option>
                <?php endif; ?>
            </select>
        </div>
        <?php if (!empty($companies)): ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şirket</label>
            <select name="company_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <option value="">—</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>" <?= ($profile['company_id'] ?? '') === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" <?= !empty($profile['is_active']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
        </label>
        <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-600">
            <a href="/kullanicilar/<?= htmlspecialchars($profile['id'] ?? '') ?>" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</a>
            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Güncelle</button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
