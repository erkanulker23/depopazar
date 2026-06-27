<?php
$currentPage = 'kullanicilar';
$profile = $profile ?? [];
$companies = $companies ?? [];
$roleLabels = $roleLabels ?? [];
$formRoleOptions = $formRoleOptions ?? RolePermissions::formRoleOptions(false);
$warehouses = $warehouses ?? [];
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
    <form method="post" action="/kullanicilar/guncelle" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" value="<?= htmlspecialchars($profile['id'] ?? '') ?>">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profil fotoğrafı</label>
            <div class="flex items-center gap-4">
                <div class="shrink-0">
                    <?php $userRow = $profile; $size = 'lg'; require __DIR__ . '/../partials/user_avatar.php'; ?>
                </div>
                <div class="flex-1 min-w-0 space-y-2">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300 hover:file:bg-emerald-100">
                    <p class="text-xs text-gray-500 dark:text-gray-400">JPG, PNG, GIF veya WebP</p>
                    <?php if (!empty(userPhotoHref($profile))): ?>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-red-600 dark:text-red-400">
                        <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                        Fotoğrafı kaldır
                    </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
            <select name="role" id="editUser_role" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                <?php foreach ($formRoleOptions as $roleKey => $roleLabel): ?>
                    <option value="<?= htmlspecialchars($roleKey) ?>" <?= ($profile['role'] ?? '') === $roleKey ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
        $fieldPrefix = 'editUser';
        $selectedWarehouseId = $profile['managed_warehouse_id'] ?? null;
        $isSuperAdminCompanySelect = !empty($companies);
        require __DIR__ . '/../partials/user_managed_warehouse_field.php';
        ?>
        <?php if (!empty($companies)): ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şirket</label>
            <select name="company_id" id="editUser_company_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
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
        <label class="inline-flex items-start gap-2 cursor-pointer">
            <input type="checkbox" name="receive_email_notifications" value="1" <?= !empty($profile['receive_email_notifications']) ? 'checked' : '' ?> class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">
                Bildirim e-postası alsın
                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Paneldeki işlem bildirimleri (sözleşme, ödeme, kullanıcı vb.) bu adrese gider. Kapalıysa süper admin dahil e-posta gitmez.</span>
            </span>
        </label>
        <div class="form-submit-bar flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-600">
            <a href="/kullanicilar/<?= htmlspecialchars($profile['id'] ?? '') ?>" class="btn-touch px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</a>
            <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Güncelle</button>
        </div>
    </form>
</div>
<script>
(function() {
    function sync(prefix) {
        var roleEl = document.getElementById(prefix + '_role');
        var wrap = document.getElementById(prefix + '_managed_warehouse_wrap');
        var select = document.getElementById(prefix + '_managed_warehouse_id');
        if (!wrap || !select || !roleEl) return;
        var isWhManager = roleEl.value === 'warehouse_manager';
        wrap.classList.toggle('hidden', !isWhManager);
        select.required = isWhManager;
        if (!isWhManager) select.value = '';
        var emailCb = document.querySelector('form[action="/kullanicilar/guncelle"] input[name="receive_email_notifications"]');
        if (isWhManager && emailCb && !emailCb.checked) emailCb.checked = true;
    }
    function filterByCompany(prefix) {
        var companyEl = document.getElementById(prefix + '_company_id');
        var select = document.getElementById(prefix + '_managed_warehouse_id');
        if (!select || !companyEl) return;
        var companyId = companyEl.value;
        Array.prototype.forEach.call(select.options, function(opt) {
            if (!opt.value) return;
            var optCompany = opt.getAttribute('data-company-id') || '';
            opt.hidden = companyId !== '' && optCompany !== companyId;
        });
        if (select.selectedOptions[0] && select.selectedOptions[0].hidden) select.value = '';
    }
    var roleEl = document.getElementById('editUser_role');
    if (roleEl) roleEl.addEventListener('change', function() { sync('editUser'); });
    var companyEl = document.getElementById('editUser_company_id');
    if (companyEl) companyEl.addEventListener('change', function() { filterByCompany('editUser'); sync('editUser'); });
    filterByCompany('editUser');
    sync('editUser');
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
