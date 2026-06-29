<?php
/** Ortak depo iletişim form alanları (ekle / düzenle modalları) */
/** @var string $prefix '' veya 'edit_' */
$prefix = $prefix ?? '';
$idPhone = $prefix . 'phone';
$idWhatsapp = $prefix . 'whatsapp_number';
$idEmail = $prefix . 'email';
$idWebsite = $prefix . 'website';
$namePhone = 'phone';
$nameWhatsapp = 'whatsapp_number';
$nameEmail = 'email';
$nameWebsite = 'website';
?>
<div class="border-t border-gray-200 dark:border-gray-600 pt-4 mt-2">
    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">İletişim Bilgileri</h4>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="<?= htmlspecialchars($idPhone) ?>">Telefon</label>
                <input type="text" name="<?= htmlspecialchars($namePhone) ?>" id="<?= htmlspecialchars($idPhone) ?>" placeholder="0212 000 00 00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="<?= htmlspecialchars($idWhatsapp) ?>">WhatsApp</label>
                <input type="text" name="<?= htmlspecialchars($nameWhatsapp) ?>" id="<?= htmlspecialchars($idWhatsapp) ?>" placeholder="905551234567" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="<?= htmlspecialchars($idEmail) ?>">E-posta</label>
            <input type="email" name="<?= htmlspecialchars($nameEmail) ?>" id="<?= htmlspecialchars($idEmail) ?>" placeholder="depo@firma.com" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="<?= htmlspecialchars($idWebsite) ?>">Web Sitesi</label>
            <input type="text" name="<?= htmlspecialchars($nameWebsite) ?>" id="<?= htmlspecialchars($idWebsite) ?>" placeholder="www.firma.com" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
        </div>
    </div>
</div>
