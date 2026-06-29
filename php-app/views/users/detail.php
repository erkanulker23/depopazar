<?php
$currentPage = 'kullanicilar';
$profile = $profile ?? [];
$roleLabels = $roleLabels ?? [];
$fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
$currentUser = Auth::user();
$canManageProfile = ($currentUser['role'] ?? '') === 'super_admin' || ($currentUser['role'] ?? '') === 'company_owner';
$isOwnProfile = ($currentUser['id'] ?? '') === ($profile['id'] ?? '');
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
ob_start();
?>
<div class="user-profile-page max-w-full min-w-0">
<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>
<div class="page-header mb-6 flex flex-col sm:flex-row sm:flex-wrap sm:items-start sm:justify-between gap-4">
    <div class="flex items-start gap-3 min-w-0">
        <?php $userRow = $profile; $size = 'lg'; require __DIR__ . '/../partials/user_avatar.php'; ?>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="/kullanicilar" class="text-emerald-600 hover:text-emerald-700 font-medium">Kullanıcılar</a>
                <i class="bi bi-chevron-right shrink-0"></i>
                <span class="text-gray-700 dark:text-gray-300 font-medium truncate"><?= htmlspecialchars($fullName) ?></span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Kullanıcı Profili</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold truncate"><?= htmlspecialchars($fullName) ?></p>
        </div>
    </div>
    <?php if ($canManageProfile): ?>
    <div class="page-header-actions flex flex-wrap gap-2 w-full sm:w-auto">
        <a href="/kullanicilar/<?= htmlspecialchars($profile['id'] ?? '') ?>/duzenle" class="btn-touch inline-flex items-center justify-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
            <i class="bi bi-pencil mr-2"></i> Düzenle
        </a>
        <button type="button" onclick="openChangePasswordModal('<?= htmlspecialchars($profile['id'] ?? '') ?>', '<?= htmlspecialchars(addslashes($fullName)) ?>')" class="btn-touch inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-key mr-2"></i> Şifre Değiştir
        </button>
    </div>
    <?php elseif ($isOwnProfile): ?>
    <p class="text-sm text-gray-500 dark:text-gray-400">Profil bilgilerinizi güncellemek için yöneticinize başvurun.</p>
    <?php endif; ?>
</div>

<div class="profile-card card-modern overflow-visible p-6 max-w-2xl">
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
            <dd class="mt-1 text-gray-600 dark:text-gray-300 break-all"><?= htmlspecialchars($profile['email'] ?? '-') ?></dd>
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
        <?php if (($profile['role'] ?? '') === 'warehouse_manager'): ?>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sorumlu Depo</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($managedWarehouseName ?? '—') ?></dd>
        </div>
        <?php endif; ?>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</dt>
            <dd class="mt-1"><?= !empty($profile['is_active']) ? '<span class="text-green-600 dark:text-green-400 font-medium">Aktif</span>' : '<span class="text-gray-500 dark:text-gray-400">Pasif</span>' ?></dd>
        </div>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Bildirim e-postası</dt>
            <dd class="mt-1"><?= !empty($profile['receive_email_notifications']) ? '<span class="text-green-600 dark:text-green-400 font-medium">Açık</span>' : '<span class="text-gray-500 dark:text-gray-400">Kapalı</span>' ?></dd>
        </div>
        <?php if (!empty($profile['last_login_at'])): ?>
        <div>
            <dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Son Giriş</dt>
            <dd class="mt-1 text-gray-600 dark:text-gray-300"><?= date('d.m.Y H:i', strtotime($profile['last_login_at'])) ?></dd>
        </div>
        <?php endif; ?>
    </dl>
</div>
</div>

<?php if ($canManageProfile): ?>
<!-- Modal: Şifre Değiştir (profil sayfası) -->
<div id="changePasswordModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('changePasswordModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Şifre Değiştir</h3>
                <button type="button" onclick="document.getElementById('changePasswordModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/kullanicilar/sifre-degistir">
                <input type="hidden" name="id" id="chpwd_user_id">
                <input type="hidden" name="redirect" value="/kullanicilar/<?= htmlspecialchars($profile['id'] ?? '') ?>">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3" id="chpwd_user_name"></p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yeni Şifre <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <div class="flex-1 relative">
                            <input type="password" name="password" id="chpwd_password" required class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                            <button type="button" onclick="toggleChPwdVisibility()" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-gray-500 hover:text-gray-700" title="Şifreyi göster"><i class="bi bi-eye" id="chpwd_eye_icon"></i></button>
                        </div>
                        <button type="button" onclick="generateChPwd()" class="px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm">Üret</button>
                    </div>
                </div>
                <div class="form-submit-bar flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('changePasswordModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="btn-touch px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openChangePasswordModal(id, name) {
    document.getElementById('chpwd_user_id').value = id || '';
    document.getElementById('chpwd_user_name').textContent = 'Kullanıcı: ' + (name || '');
    document.getElementById('chpwd_password').value = '';
    document.getElementById('changePasswordModal').classList.remove('hidden');
}
function generateChPwd() {
    var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
    var pwd = '';
    for (var i = 0; i < 12; i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    var el = document.getElementById('chpwd_password');
    if (el) { el.value = pwd; el.type = 'text'; var icon = document.getElementById('chpwd_eye_icon'); if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); } }
}
function toggleChPwdVisibility() {
    var el = document.getElementById('chpwd_password');
    var icon = document.getElementById('chpwd_eye_icon');
    if (el && icon) {
        if (el.type === 'password') { el.type = 'text'; icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
        else { el.type = 'password'; icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
    }
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
