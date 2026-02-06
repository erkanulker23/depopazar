<?php
$currentPage = 'musteriler';
$pageTitle = 'Müşteriler Excel İçe Aktar';
ob_start();
?>
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/musteriler" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Müşteriler</a>
        <i class="bi bi-chevron-right"></i>
        <span class="text-gray-700 dark:text-gray-300 font-medium">Excel İçe Aktar</span>
    </div>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Excel ile İçe Aktar</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">CSV dosyası yükleyerek toplu müşteri ekleyebilirsiniz.</p>
</div>

<?php if (isset($flashSuccess) && $flashSuccess): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (isset($flashError) && $flashError): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-2xl">
    <form method="post" action="/musteriler/excel-ice-aktar" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CSV / Excel dosyası</label>
            <input type="file" name="csv_file" accept=".csv" required class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">CSV dosyası yükleyin (Excel’de “CSV (Ayırıcı: noktalı virgül)” olarak kaydedebilirsiniz). Sütunlar: Ad; Soyad; E-posta; Telefon; TC Kimlik No; Adres; Notlar; Aktif (Evet/Hayır). UTF-8 önerilir.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
                <i class="bi bi-upload"></i> Yükle ve İçe Aktar
            </button>
            <a href="/musteriler/excel-sablon" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i class="bi bi-download"></i> Örnek şablon indir
            </a>
            <a href="/musteriler" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">İptal</a>
        </div>
    </form>
</div>

<div class="mt-6 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Örnek CSV (ilk satır başlık, sonraki satırlar veri)</h3>
    <pre class="text-xs text-gray-600 dark:text-gray-400 overflow-x-auto p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">Ad;Soyad;E-posta;Telefon;TC Kimlik No;Adres;Notlar;Aktif
Ahmet;Yılmaz;ahmet@ornek.com;05551234567;;İstanbul;Aktif müşteri;Evet
Ayşe;Demir;ayse@ornek.com;;12345678901;Ankara;;Evet</pre>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
