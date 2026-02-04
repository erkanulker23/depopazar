<?php
$currentPage = 'kullanicilar';
$roleLabels = $roleLabels ?? [];
$staff = $staff ?? [];
$companies = $companies ?? [];
$canManageUsers = $canManageUsers ?? false;
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Kullanıcılar</h1>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Sistem kullanıcıları</p>
</div>

<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<?php if ($canManageUsers): ?>
<div class="mb-4">
    <button type="button" onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
        <i class="bi bi-plus-lg mr-2"></i> Kullanıcı Ekle
    </button>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($staff)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Henüz kullanıcı kaydı yok.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ad Soyad</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">E-posta</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Rol</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Durum</th>
                        <?php if ($canManageUsers): ?><th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">İşlem</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <?php foreach ($staff as $s): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><a href="/kullanicilar/<?= htmlspecialchars($s['id'] ?? '') ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700"><?= htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?></a></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($s['email'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($roleLabels[$s['role'] ?? ''] ?? $s['role'] ?? '') ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($s['is_active'])): ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManageUsers): ?>
                            <td class="px-4 py-3 text-right">
                                <a href="/kullanicilar/<?= htmlspecialchars($s['id']) ?>/duzenle" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 mr-1" title="Düzenle"><i class="bi bi-pencil"></i></a>
                                <button type="button" onclick="openChangePasswordModal('<?= htmlspecialchars($s['id']) ?>', '<?= htmlspecialchars(addslashes(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))) ?>')" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 mr-1" title="Şifre Değiştir"><i class="bi bi-key"></i></button>
                                <form method="post" action="/kullanicilar/sil" class="inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($s['id']) ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100" title="Sil"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($canManageUsers): ?>
<!-- Modal: Kullanıcı Ekle -->
<div id="addUserModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addUserModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Kullanıcı Ekle</h3>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="/kullanicilar/ekle" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soyad <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şifre <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="password" name="password" id="addUser_password" required class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <button type="button" onclick="generatePassword()" class="px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-500">Otomatik Üret</button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol</label>
                    <select name="role" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <option value="company_staff">Personel</option>
                        <option value="company_owner">Şirket Sahibi</option>
                        <option value="data_entry">Veri Girişi</option>
                        <option value="accounting">Muhasebe</option>
                    </select>
                </div>
                <?php if (!empty($companies)): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şirket (Süper admin)</label>
                    <select name="company_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Seçin</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function generatePassword() {
    var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
    var pwd = '';
    for (var i = 0; i < 12; i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    var el = document.getElementById('addUser_password');
    if (el) { el.value = pwd; el.type = 'text'; setTimeout(function() { el.type = 'password'; }, 2000); }
}
</script>
<!-- Modal: Şifre Değiştir -->
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
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('changePasswordModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Kaydet</button>
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
