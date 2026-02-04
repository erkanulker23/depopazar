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
    <title>Giriş - <?= htmlspecialchars($projectName ?? 'DepoPazar') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: { sans: ['Ubuntu', 'system-ui', '-apple-system', 'sans-serif'] }
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
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body, input, button { font-family: 'Ubuntu', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        body { padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left); }
        .login-card { max-width: 400px; margin-left: auto; margin-right: auto; }
        input { font-size: 16px !important; min-height: 48px; }
        .btn-submit { min-height: 48px; font-size: 1rem; }
    </style>
</head>
<body class="min-vh-100 flex items-center justify-center py-4 px-3 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
    <div class="w-full login-card relative">
        <button type="button" id="themeToggle" class="absolute top-0 right-0 p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700" title="Koyu / Açık mod" aria-label="Tema değiştir">
            <i class="bi bi-moon-stars text-xl dark:hidden"></i>
            <i class="bi bi-sun text-xl hidden dark:inline"></i>
        </button>
        <div class="text-center mb-4">
            <div class="inline-flex items-center justify-center rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 mb-3 w-16 h-16">
                <i class="bi bi-box-seam text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($projectName ?? 'DepoPazar') ?></h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold mt-1">Depo yönetim sistemi</p>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg shadow-gray-200/50 dark:shadow-none p-6">
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="/giris" class="space-y-4">
                <div>
                    <label for="email" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">E-posta</label>
                    <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="ornek@email.com" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <label for="password" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Şifre</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-submit w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition-colors">Giriş Yap</button>
            </form>
        </div>
        <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">© <?= date('Y') ?> <?= htmlspecialchars($projectName ?? 'DepoPazar') ?></p>
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
