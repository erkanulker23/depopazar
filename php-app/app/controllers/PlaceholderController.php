<?php
class PlaceholderController
{
    public static function page(string $title, string $message = 'Bu sayfa henüz hazırlanıyor.'): void
    {
        Auth::requireLogin();
        $config = require __DIR__ . '/../../config/config.php';
        $projectName = $config['app_name'];
        $currentPage = '';
        ob_start();
        ?>
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1"><?= htmlspecialchars($title) ?></h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Sayfa</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-8 md:p-12 text-center text-gray-500 dark:text-gray-400">
                <i class="bi bi-tools text-5xl mb-4 block text-gray-300 dark:text-gray-600"></i>
                <p class="mb-0"><?= htmlspecialchars($message) ?></p>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layout.php';
    }
}
