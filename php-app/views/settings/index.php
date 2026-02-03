<?php
$currentPage = 'ayarlar';
$tabs = [
    'firma' => ['label' => 'Firma Bilgileri', 'icon' => 'building'],
    'paytr' => ['label' => 'PayTR', 'icon' => 'credit-card'],
    'banka' => ['label' => 'Banka Hesapları', 'icon' => 'bank'],
    'eposta' => ['label' => 'E-posta Ayarları', 'icon' => 'envelope'],
];
$activeTab = $activeTab ?? 'firma';
ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">Ayarlar</h1>
    <p class="text-xs text-gray-500 uppercase tracking-widest font-bold">Firma ve entegrasyon ayarları</p>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-800 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-800 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<!-- Sekmeler - mobilde yatay scroll -->
<div class="border-b border-gray-200 dark:border-gray-600 mb-6 -mx-4 px-4 md:mx-0 md:px-0 overflow-x-auto">
    <nav class="flex gap-1 -mb-px min-w-max md:min-w-0" aria-label="Ayarlar sekmeleri">
        <?php foreach ($tabs as $key => $t): ?>
            <a href="/ayarlar?tab=<?= $key ?>" class="inline-flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors <?= $activeTab === $key ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-500' ?>">
                <i class="bi bi-<?= $t['icon'] ?>"></i>
                <?= htmlspecialchars($t['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<div class="card-modern overflow-hidden">
    <?php if ($activeTab === 'firma'): ?>
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-building text-emerald-600"></i> Firma Bilgileri</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Proje adı uygulama başlığında ve SEO’da kullanılır.</p>
            <form method="post" action="/ayarlar/firma-guncelle" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Firma Adı</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($company['name'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Proje Adı (uygulama başlığı)</label>
                        <input type="text" name="project_name" value="<?= htmlspecialchars($company['project_name'] ?? '') ?>" placeholder="Örn: DepoPazar" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">E-posta</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Telefon</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($company['phone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">WhatsApp</label>
                        <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($company['whatsapp_number'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Mersis No</label>
                        <input type="text" name="mersis_number" value="<?= htmlspecialchars($company['mersis_number'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Vergi Dairesi</label>
                        <input type="text" name="tax_office" value="<?= htmlspecialchars($company['tax_office'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Adres</label>
                        <textarea name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    <?php elseif ($activeTab === 'paytr'): ?>
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-credit-card text-emerald-600"></i> PayTR Entegrasyonu</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Online kredi kartı ile ödeme almak için PayTR bilgilerinizi girin.</p>
            <form method="post" action="/ayarlar/paytr-guncelle" class="space-y-4 max-w-2xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Merchant ID</label>
                        <input type="text" name="merchant_id" value="<?= htmlspecialchars($paytrSettings['merchant_id'] ?? '') ?>" placeholder="Örn: 123456" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Merchant Key</label>
                        <input type="password" name="merchant_key" value="" placeholder="<?= !empty($paytrSettings['merchant_key']) ? '•••••••• (değiştirmek için yazın)' : 'Girin' ?>" autocomplete="new-password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Merchant Salt</label>
                        <input type="password" name="merchant_salt" value="" placeholder="<?= !empty($paytrSettings['merchant_salt']) ? '•••••••• (değiştirmek için yazın)' : 'Girin' ?>" autocomplete="new-password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div class="flex flex-wrap gap-6 pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?= !empty($paytrSettings['is_active']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="test_mode" value="1" <?= !empty($paytrSettings['test_mode']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Test Modu</span>
                    </label>
                </div>
                <div class="pt-2">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    <?php elseif ($activeTab === 'banka'): ?>
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-bank text-emerald-600"></i> Banka Hesapları</h2>
            <div class="mb-6">
                <button type="button" onclick="document.getElementById('addBankAccountModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">
                    <i class="bi bi-plus-lg mr-2"></i> Banka Hesabı Ekle
                </button>
            </div>
            <?php if (empty($bankAccounts)): ?>
                <p class="text-gray-500 dark:text-gray-400">Henüz banka hesabı eklenmemiş. Yukarıdaki butonla ekleyebilirsiniz.</p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($bankAccounts as $ba): ?>
                        <li class="border border-gray-100 dark:border-gray-600 rounded-xl p-4 flex flex-wrap items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($ba['bank_name'] ?? '') ?></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Hesap sahibi: <?= htmlspecialchars($ba['account_holder_name'] ?? '') ?></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Hesap no: <?= htmlspecialchars($ba['account_number'] ?? '') ?></div>
                                <?php if (!empty($ba['iban'])): ?><div class="text-sm text-gray-600 dark:text-gray-400">IBAN: <?= htmlspecialchars($ba['iban']) ?></div><?php endif; ?>
                                <?php if (!empty($ba['branch_name'])): ?><div class="text-sm text-gray-500 dark:text-gray-500">Şube: <?= htmlspecialchars($ba['branch_name']) ?></div><?php endif; ?>
                                <span class="inline-block mt-2 px-2 py-0.5 text-xs rounded-full <?= !empty($ba['is_active']) ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300' ?>"><?= !empty($ba['is_active']) ? 'Aktif' : 'Pasif' ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick='openEditBank(<?= json_encode($ba) ?>)' class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500">Düzenle</button>
                                <form method="post" action="/ayarlar/banka-sil" class="inline" onsubmit="return confirm('Bu banka hesabını silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($ba['id']) ?>">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100">Sil</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <!-- Modal: Banka hesabı ekle -->
        <div id="addBankAccountModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addBankAccountModal').classList.add('hidden')"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Banka Hesabı Ekle</h3>
                        <button type="button" onclick="document.getElementById('addBankAccountModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <form method="post" action="/ayarlar/banka-ekle" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Banka Adı <span class="text-red-500">*</span></label>
                            <input type="text" name="bank_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hesap Sahibi <span class="text-red-500">*</span></label>
                            <input type="text" name="account_holder_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hesap Numarası <span class="text-red-500">*</span></label>
                            <input type="text" name="account_number" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IBAN</label>
                            <input type="text" name="iban" placeholder="TR00 0000 0000 0000 0000 0000 00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şube</label>
                            <input type="text" name="branch_name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('addBankAccountModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Modal: Banka hesabı düzenle -->
        <div id="editBankAccountModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('editBankAccountModal').classList.add('hidden')"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Banka Hesabı Düzenle</h3>
                        <button type="button" onclick="document.getElementById('editBankAccountModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <form method="post" action="/ayarlar/banka-guncelle" class="space-y-3">
                        <input type="hidden" name="id" id="edit_bank_id">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Banka Adı <span class="text-red-500">*</span></label>
                            <input type="text" name="bank_name" id="edit_bank_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hesap Sahibi <span class="text-red-500">*</span></label>
                            <input type="text" name="account_holder_name" id="edit_account_holder_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hesap Numarası <span class="text-red-500">*</span></label>
                            <input type="text" name="account_number" id="edit_account_number" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IBAN</label>
                            <input type="text" name="iban" id="edit_iban" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şube</label>
                            <input type="text" name="branch_name" id="edit_branch_name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" id="edit_bank_is_active" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('editBankAccountModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">İptal</button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
        function openEditBank(ba) {
            document.getElementById('edit_bank_id').value = ba.id || '';
            document.getElementById('edit_bank_name').value = ba.bank_name || '';
            document.getElementById('edit_account_holder_name').value = ba.account_holder_name || '';
            document.getElementById('edit_account_number').value = ba.account_number || '';
            document.getElementById('edit_iban').value = ba.iban || '';
            document.getElementById('edit_branch_name').value = ba.branch_name || '';
            document.getElementById('edit_bank_is_active').checked = !!ba.is_active;
            document.getElementById('editBankAccountModal').classList.remove('hidden');
        }
        </script>
    <?php elseif ($activeTab === 'eposta'): ?>
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bi bi-envelope text-emerald-600"></i> E-posta Ayarları</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Sözleşme ve ödeme bildirimleri için SMTP bilgilerinizi girin.</p>
            <form method="post" action="/ayarlar/eposta-guncelle" class="space-y-4 max-w-2xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">SMTP Sunucu</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars($mailSettings['smtp_host'] ?? '') ?>" placeholder="Örn: smtp.gmail.com" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">SMTP Port</label>
                        <input type="number" name="smtp_port" value="<?= htmlspecialchars($mailSettings['smtp_port'] ?? '587') ?>" placeholder="587" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">SMTP Kullanıcı Adı</label>
                        <input type="text" name="smtp_username" value="<?= htmlspecialchars($mailSettings['smtp_username'] ?? '') ?>" placeholder="E-posta adresi" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">SMTP Şifre</label>
                        <input type="password" name="smtp_password" value="" placeholder="<?= !empty($mailSettings['smtp_password']) ? '•••••••• (değiştirmek için yazın)' : 'Girin' ?>" autocomplete="new-password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Gönderen E-posta</label>
                        <input type="email" name="from_email" value="<?= htmlspecialchars($mailSettings['from_email'] ?? '') ?>" placeholder="bildirim@firma.com" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Gönderen Adı</label>
                        <input type="text" name="from_name" value="<?= htmlspecialchars($mailSettings['from_name'] ?? '') ?>" placeholder="DepoPazar" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="smtp_secure" value="1" <?= !empty($mailSettings['smtp_secure']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">TLS/SSL kullan</span>
                    </label>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Bildirim Tercihleri</p>
                    <div class="space-y-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="notify_customer_on_contract" value="1" <?= !empty($mailSettings['notify_customer_on_contract']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Müşteriye sözleşme oluşturulunca bildir</span>
                        </label><br>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="notify_customer_on_payment" value="1" <?= !empty($mailSettings['notify_customer_on_payment']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Müşteriye ödeme alınınca bildir</span>
                        </label><br>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="notify_customer_on_overdue" value="1" <?= !empty($mailSettings['notify_customer_on_overdue']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Gecikme hatırlatması gönder</span>
                        </label><br>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="notify_admin_on_contract" value="1" <?= !empty($mailSettings['notify_admin_on_contract']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Yöneticiye sözleşme bildirimi</span>
                        </label><br>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="notify_admin_on_payment" value="1" <?= !empty($mailSettings['notify_admin_on_payment']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Yöneticiye ödeme bildirimi</span>
                        </label>
                    </div>
                </div>
                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?= !empty($mailSettings['is_active']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">E-posta bildirimleri aktif</span>
                    </label>
                </div>
                <div class="pt-4">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700">Kaydet</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
