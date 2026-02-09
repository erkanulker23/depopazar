<?php
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f8fafc" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="data:,">
    <title>Giriş - <?= htmlspecialchars($projectName ?? 'Depo ve Nakliye Takip') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: { sans: ['Plus Jakarta Sans', 'system-ui', '-apple-system', 'sans-serif'] }
            }
        }
    };
    (function() {
        var theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches))
            document.documentElement.classList.add('dark');
        else
            document.documentElement.classList.remove('dark');
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body, input, button { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        html { height: 100%; }
        body { min-height: 100%; padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left); }
        .login-bg { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%); }
        .dark .login-bg { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); }
        .login-card { max-width: 420px; width: 100%; }
        input { font-size: 16px !important; min-height: 48px; }
        .btn-submit { min-height: 48px; font-size: 1rem; transition: all 0.2s; }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
        .login-card-inner { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08); }
        .dark .login-card-inner { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4); }
    </style>
</head>
<body class="login-bg min-h-screen flex flex-col items-center justify-center py-8 px-4 text-gray-900 dark:text-gray-100 antialiased">
    <button type="button" id="themeToggle" class="fixed top-4 right-4 md:top-6 md:right-6 p-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-white/80 dark:hover:bg-gray-800/80 transition-colors z-10" title="Koyu / Açık mod" aria-label="Tema değiştir">
        <i class="bi bi-moon-stars text-xl dark:hidden"></i>
        <i class="bi bi-sun text-xl hidden dark:inline"></i>
    </button>

    <div class="login-card flex flex-col items-center justify-center">
        <div class="w-full text-center mb-6">
            <div class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 shadow-lg shadow-emerald-500/25 text-white mb-4 w-20 h-20">
                <i class="bi bi-box-seam text-4xl"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($projectName ?? 'Depo ve Nakliye Takip') ?></h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">Depo yönetim sistemi</p>
        </div>
        <div class="login-card-inner w-full rounded-2xl border border-gray-200/80 dark:border-gray-600/50 bg-white dark:bg-gray-800/95 backdrop-blur p-6 md:p-8">
            <?php if ($error): ?>
                <div class="mb-5 p-4 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm flex items-center gap-2">
                    <i class="bi bi-exclamation-circle flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            <form method="post" action="/giris" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">E-posta</label>
                    <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700/50 dark:text-white transition-shadow" placeholder="ornek@email.com" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Şifre</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700/50 dark:text-white transition-shadow" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-submit w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Giriş Yap</button>
            </form>
        </div>
        <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">© <?= date('Y') ?> <?= htmlspecialchars($projectName ?? 'Depo ve Nakliye Takip') ?></p>
    </div>
    <script>
    (function(){
        var themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                var html = document.documentElement;
                var isDark = html.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        }
    })();
    </script>
</body>
</html>
