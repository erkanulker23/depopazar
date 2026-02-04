<?php
if (!isset($pageTitle)) $pageTitle = 'DepoPazar';
if (!isset($projectName)) $projectName = $_SESSION['company_project_name'] ?? 'DepoPazar';
$user = Auth::user();
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
if (($q = strpos($currentPath, '?')) !== false) $currentPath = substr($currentPath, 0, $q);
$navIcons = ['Dashboard'=>'house','Depo Girişi Ekle'=>'plus-circle','Ödeme Al'=>'bank','Tüm Girişler'=>'file-text','Nakliye İşler'=>'truck','Hizmetler'=>'tag','Teklifler'=>'file-earmark-plus','Kullanıcılar'=>'people','Kullanıcı Yetkileri'=>'shield-check','Depolar'=>'building','Odalar'=>'grid-3x3','Müşteriler'=>'people','Ödemeler'=>'credit-card','Masraflar'=>'wallet2','Bildirimler'=>'bell','Raporlar'=>'bar-chart','Ayarlar'=>'gear'];
$navItems = [
    ['name' => 'Dashboard', 'href' => '/genel-bakis', 'active' => $currentPath === '/genel-bakis'],
    ['name' => 'Depo Girişi Ekle', 'href' => '/girisler?newSale=1', 'active' => false],
    ['name' => 'Ödeme Al', 'href' => '/odemeler?collect=1', 'highlight' => true, 'active' => false],
    ['name' => 'Tüm Girişler', 'href' => '/girisler', 'active' => $currentPath === '/girisler'],
    ['name' => 'Nakliye İşler', 'href' => '/nakliye-isler', 'active' => $currentPath === '/nakliye-isler'],
    ['name' => 'Hizmetler', 'href' => '/hizmetler', 'active' => $currentPath === '/hizmetler'],
    ['name' => 'Teklifler', 'href' => '/teklifler', 'active' => $currentPath === '/teklifler'],
    ['name' => 'Kullanıcılar', 'href' => '/kullanicilar', 'active' => $currentPath === '/kullanicilar'],
    ['name' => 'Kullanıcı Yetkileri', 'href' => '/yetkiler', 'active' => $currentPath === '/yetkiler'],
    ['name' => 'Depolar', 'href' => '/depolar', 'active' => $currentPath === '/depolar'],
    ['name' => 'Odalar', 'href' => '/odalar', 'active' => $currentPath === '/odalar'],
    ['name' => 'Müşteriler', 'href' => '/musteriler', 'active' => $currentPath === '/musteriler'],
    ['name' => 'Ödemeler', 'href' => '/odemeler', 'active' => $currentPath === '/odemeler'],
    ['name' => 'Masraflar', 'href' => '/masraflar', 'active' => $currentPath === '/masraflar'],
    ['name' => 'Bildirimler', 'href' => '/bildirimler', 'active' => $currentPath === '/bildirimler'],
    ['name' => 'Raporlar', 'href' => '/raporlar', 'active' => $currentPath === '/raporlar'],
    ['name' => 'Ayarlar', 'href' => '/ayarlar', 'active' => $currentPath === '/ayarlar'],
];
$initials = strtoupper(mb_substr($user['first_name'] ?? 'A', 0, 1) . mb_substr($user['last_name'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#059669" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="data:,">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($projectName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: { 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46' },
                    surface: { 50: '#f8fafc', 100: '#f1f5f9', 800: '#1e293b', 900: '#0f172a' }
                },
                fontFamily: {
                    sans: ['Ubuntu', 'system-ui', '-apple-system', 'sans-serif']
                }
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
        :root { --safe-top: env(safe-area-inset-top); --safe-bottom: env(safe-area-inset-bottom); --safe-left: env(safe-area-inset-left); --safe-right: env(safe-area-inset-right); }
        html, body, input, select, textarea, button { font-family: 'Ubuntu', system-ui, -apple-system, BlinkMacSystemFont, sans-serif; -webkit-font-smoothing: antialiased; }
        .nav-active { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; box-shadow: 0 4px 14px rgba(5,150,105,.35); }
        .nav-active .nav-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: rgba(255,255,255,.9); border-radius: 0 3px 3px 0; }
        .sidebar-mobile { transform: translateX(-100%); transition: transform .3s cubic-bezier(0.4,0,0.2,1); will-change: transform; }
        .sidebar-mobile.open { transform: translateX(0); box-shadow: 20px 0 40px rgba(0,0,0,.2); }
        @media (min-width: 768px) { .sidebar-mobile { transform: none; box-shadow: none; } }
        * { -webkit-tap-highlight-color: transparent; }
        html { overflow-x: hidden; }
        .main-content-wrap { padding-bottom: env(safe-area-inset-bottom, 0); }
        @media (max-width: 767px) {
            #sidebar { padding-top: max(1rem, var(--safe-top)); width: min(300px, 88vw); }
            .main-content-wrap { padding-bottom: calc(5rem + var(--safe-bottom)); }
            .table-responsive { -webkit-overflow-scrolling: touch; overflow-x: auto; margin: 0 calc(var(--safe-left) * -1); padding: 0 var(--safe-left); }
        }
        #mobileBottomNav {
            padding-bottom: max(0.5rem, var(--safe-bottom));
        }
        @media (max-width: 767px) {
            #mobileBottomNav {
                position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
                background: rgba(255,255,255,.95); backdrop-filter: blur(12px);
                border-top: 1px solid rgb(229 231 235);
                box-shadow: 0 -4px 24px rgba(0,0,0,.06);
            }
            .dark #mobileBottomNav {
                background: rgba(30,41,59,.98); border-top-color: rgb(51 65 85);
                box-shadow: 0 -4px 24px rgba(0,0,0,.3);
            }
        }
        @media (min-width: 768px) { #mobileBottomNav { display: none !important; } }
        @media (max-width: 767px) {
            .nav-link { min-height: 48px; padding: 0.75rem 1rem; -webkit-tap-highlight-color: transparent; }
            .btn-touch { min-height: 44px; min-width: 44px; padding: 0.625rem 1rem; }
        }
        input, select, textarea { font-size: 16px !important; }
        .touch-manipulation { touch-action: manipulation; -webkit-user-select: none; user-select: none; }
        #pushBanner { padding-left: max(1rem, env(safe-area-inset-left)); padding-right: max(1rem, env(safe-area-inset-right)); }
        @media (max-width: 767px) {
            #pushBanner { flex-direction: column; align-items: stretch; text-align: center; gap: 0.75rem; padding: 0.75rem max(1rem, env(safe-area-inset-left)) 0.75rem max(1rem, env(safe-area-inset-right)); }
            #pushBanner .push-banner-text { flex: none; min-width: 0; width: 100%; word-wrap: break-word; overflow-wrap: break-word; }
            #pushBanner .push-banner-btns { flex-wrap: nowrap; justify-content: center; align-items: center; gap: 0.5rem; width: 100%; }
            #pushBanner .push-banner-btns button { min-width: 0; }
            #pushBanner #pushBannerAllow { flex: 1; max-width: 200px; }
            #pushBanner #pushBannerLater { flex: 0 0 auto; }
        }
        .page-title { font-size: 1.5rem; line-height: 1.3; }
        @media (min-width: 768px) { .page-title { font-size: 1.875rem; } }
        .page-subtitle { color: rgb(107 114 128); }
        .dark .page-subtitle { color: rgb(156 163 175); }
        .gradient-title { background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .dark .gradient-title { background: linear-gradient(135deg, #34d399 0%, #10b981 50%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .card-modern { border-radius: 1rem; border: 1px solid rgb(229 231 235); background: white; box-shadow: 0 1px 3px rgba(0,0,0,.05); transition: all .2s; }
        .dark .card-modern { border-color: rgb(75 85 99); background: rgb(31 41 55); }
        .card-modern:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .dark .card-modern:hover { box-shadow: 0 4px 12px rgba(0,0,0,.2); }
        .stat-card { border-radius: 1rem; border: 1px solid rgb(229 231 235); background: white; padding: 1.25rem; transition: all .2s; }
        .dark .stat-card { border-color: rgb(75 85 99); background: rgb(31 41 55); }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(5,150,105,.1); }
        .modal-overlay { -webkit-overflow-scrolling: touch; }
        .modal-overlay .relative { max-height: min(90vh, 600px); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen text-gray-900 dark:text-gray-100 antialiased" style="touch-action: manipulation;">
    <div class="flex min-h-screen">
        <div id="sidebarOverlay" class="md:hidden fixed inset-0 bg-black/50 z-30 hidden transition-opacity" aria-hidden="true"></div>
        <aside id="sidebar" class="sidebar-mobile fixed md:static inset-y-0 left-0 z-40 w-72 flex flex-col pt-6 bg-white dark:bg-gray-800 border-r border-gray-200/50 dark:border-gray-700 overflow-y-auto overflow-x-hidden">
            <div class="flex items-center flex-shrink-0 px-6 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <i class="bi bi-building text-white text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold bg-gradient-to-r from-emerald-600 to-emerald-500 bg-clip-text text-transparent truncate"><?= htmlspecialchars($projectName) ?></h1>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Sistem</p>
                    </div>
                </div>
            </div>
            <nav class="mt-2 flex-1 px-3 overflow-y-auto min-h-0 space-y-0.5">
                <?php foreach ($navItems as $item):
                    $icon = $navIcons[$item['name']] ?? 'circle';
                ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="nav-link group relative flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= !empty($item['active']) ? 'nav-active' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>">
                        <?php if (!empty($item['active'])): ?><div class="nav-bar"></div><?php endif; ?>
                        <i class="bi bi-<?= $icon ?> mr-3 flex-shrink-0 h-5 w-5"></i>
                        <span class="flex-1 truncate"><?= htmlspecialchars($item['name']) ?></span>
                        <?php if (!empty($item['highlight'])): ?><span class="ml-2 px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 rounded-full">Yeni</span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="flex-shrink-0 border-t border-gray-200/50 dark:border-gray-700 p-4 mx-3 mb-4">
                <div class="flex items-center gap-3 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <div class="w-9 h-9 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"><?= htmlspecialchars($initials) ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    </div>
                    <a href="/cikis" class="p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 flex-shrink-0" title="Çıkış"><i class="bi bi-box-arrow-right text-lg"></i></a>
                </div>
            </div>
        </aside>
        <main class="flex-1 min-w-0 flex flex-col min-h-screen">
            <?php $companyLogoUrl = $_SESSION['company_logo_url'] ?? null; ?>
            <div class="flex-shrink-0 flex items-center justify-between md:justify-end gap-2 pl-4 pr-3 py-3 md:pl-6 md:px-6 lg:px-8 border-b border-gray-200 dark:border-gray-700 bg-white/95 dark:bg-gray-800/95 backdrop-blur sticky top-0 z-20 min-h-[3.5rem]" style="padding-top: max(0.75rem, var(--safe-top));">
                <div class="flex items-center gap-2 min-w-0 md:mr-auto">
                    <?php if (!empty($companyLogoUrl)): ?>
                        <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="" class="h-8 w-auto object-contain flex-shrink-0 md:h-9" aria-hidden="true">
                    <?php endif; ?>
                    <span class="md:hidden text-sm font-semibold text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($projectName) ?></span>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" id="themeToggle" class="p-3 md:p-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors min-h-[44px] min-w-[44px] md:min-h-0 md:min-w-0 flex items-center justify-center" title="Koyu / Açık mod">
                        <i class="bi bi-moon-stars text-xl md:text-lg dark:hidden" aria-hidden="true"></i>
                        <i class="bi bi-sun text-xl md:text-lg hidden dark:inline" aria-hidden="true"></i>
                    </button>
                    <div class="relative">
                        <button type="button" id="notifBtn" class="p-3 md:p-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors relative min-h-[44px] min-w-[44px] md:min-h-0 md:min-w-0 flex items-center justify-center" title="Bildirimler" aria-expanded="false" aria-haspopup="true">
                            <i class="bi bi-bell text-xl md:text-lg"></i>
                            <span id="notifBadge" class="hidden absolute top-2 right-2 md:top-1.5 md:right-1.5 w-2.5 h-2.5 md:w-2 md:h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <div id="notifDropdown" class="hidden absolute right-0 top-full mt-1 w-[min(22rem,calc(100vw-2rem))] rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-xl z-50 py-0 max-h-[70vh] overflow-hidden flex flex-col" onclick="event.stopPropagation()">
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
                                <span class="font-semibold text-gray-900 dark:text-white">Bildirimler</span>
                                <a href="/bildirimler" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">Tümü</a>
                            </div>
                            <div id="notifList" class="flex-1 overflow-y-auto px-4 py-3 text-sm text-gray-500 dark:text-gray-400 min-h-[4rem]">Yükleniyor…</div>
                            <div class="flex-shrink-0 border-t border-gray-100 dark:border-gray-700 px-4 py-2 space-y-2 bg-gray-50 dark:bg-gray-700/50">
                                <div class="flex items-center justify-between gap-2">
                                    <form method="post" action="/bildirimler/okundu" class="inline" id="notifMarkAllForm">
                                        <button type="submit" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">Tümü okundu</button>
                                    </form>
                                    <form method="post" action="/bildirimler/tumunu-sil" class="inline" id="notifDeleteAllForm" onsubmit="return confirm('Tüm bildirimleri silmek istediğinize emin misiniz?');">
                                        <button type="submit" class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline">Tümünü sil</button>
                                    </form>
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400" id="pushPromptWrap">
                                    <button type="button" id="pushEnableBtn" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Telefon/cihaz bildirimlerini aç</button> – işlem olduğunda bildirim gider.
                                </p>
                                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 hidden" id="pushEnabledWrap">Cihaz bildirimleri açık.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Push bildirim izni – sayfa ilk açıldığında görünür (mobil dahil); JS izin/dismiss durumuna göre gizler -->
            <div id="pushBanner" class="push-banner-default border-b border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3 flex items-center justify-between gap-3 flex-wrap" role="region" aria-label="Bildirimlere izin verin">
                <p class="push-banner-text text-sm text-gray-800 dark:text-gray-200 flex-1 min-w-0 leading-snug">
                    <i class="bi bi-bell text-emerald-600 dark:text-emerald-400 mr-2 align-middle" aria-hidden="true"></i>
                    <strong>Bildirimlere izin verin</strong> – ödeme, sözleşme ve işlemlerde cihazınıza anlık bildirim gider.
                </p>
                <div class="push-banner-btns flex items-center gap-2 flex-shrink-0">
                    <button type="button" id="pushBannerLater" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 min-h-[44px] touch-manipulation rounded-lg">Sonra</button>
                    <button type="button" id="pushBannerAllow" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 active:bg-emerald-800 min-h-[44px] min-w-[120px] md:min-w-[140px] touch-manipulation shrink-0" aria-label="Bildirimlere izin ver">Bildirimlere izin ver</button>
                </div>
                <p id="pushBannerHint" class="hidden w-full mt-2 text-xs text-amber-700 dark:text-amber-300 text-left break-words"></p>
            </div>
            <div class="flex-1 p-4 md:p-6 lg:p-8 pb-8 md:pb-6 min-h-0 main-content-wrap">
                <?= $content ?? '' ?>
            </div>
        </main>

        <nav id="mobileBottomNav" class="md:hidden flex items-center justify-around pt-2 px-2" aria-label="Hızlı erişim">
            <a href="/genel-bakis" class="flex flex-col items-center justify-center flex-1 min-h-[3.5rem] gap-0.5 <?= $currentPath === '/genel-bakis' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' ?>">
                <i class="bi bi-house text-xl"></i>
                <span class="text-[9px] font-bold">Ana Sayfa</span>
            </a>
            <a href="/musteriler" class="flex flex-col items-center justify-center flex-1 min-h-[3.5rem] gap-0.5 <?= $currentPath === '/musteriler' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' ?>">
                <i class="bi bi-people text-xl"></i>
                <span class="text-[9px] font-bold">Müşteriler</span>
            </a>
            <a href="/girisler?newSale=1" class="relative -mt-6 flex-shrink-0 flex items-center justify-center w-14 h-14 bg-emerald-600 rounded-2xl text-white shadow-xl shadow-emerald-500/30 active:scale-95 transition-transform" aria-label="Yeni depo girişi">
                <i class="bi bi-plus-lg text-2xl"></i>
            </a>
            <a href="/odemeler" class="flex flex-col items-center justify-center flex-1 min-h-[3.5rem] gap-0.5 <?= (strpos($currentPath, '/odemeler') === 0) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' ?>">
                <i class="bi bi-credit-card text-xl"></i>
                <span class="text-[9px] font-bold">Ödemeler</span>
            </a>
            <button type="button" id="mobileMenuOpenBtn" class="flex flex-col items-center justify-center flex-1 min-h-[3.5rem] gap-0.5 text-gray-400 dark:text-gray-500" aria-label="Menüyü aç">
                <i class="bi bi-list text-xl"></i>
                <span class="text-[9px] font-bold">Menü</span>
            </button>
        </nav>
    </div>
    <script>
    (function(){
        var t=document.getElementById('menuToggle'), s=document.getElementById('sidebar'), o=document.getElementById('sidebarOverlay');
        var mobileMenuBtn = document.getElementById('mobileMenuOpenBtn');
        function closeSidebar(){ if(s) s.classList.remove('open'); if(o) o.classList.add('hidden'); document.body.style.overflow = ''; }
        function openSidebar(){ if(s) s.classList.add('open'); if(o) o.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
        if(t&&s){ t.addEventListener('click',function(){ s.classList.toggle('open'); if(o) o.classList.toggle('hidden'); document.body.style.overflow = s.classList.contains('open') ? 'hidden' : ''; }); }
        if(mobileMenuBtn&&s){ mobileMenuBtn.addEventListener('click', openSidebar); }
        if(o&&s){ o.addEventListener('click', closeSidebar); }
        document.querySelectorAll('.nav-link').forEach(function(el){ el.addEventListener('click', closeSidebar); });
    })();
    (function(){
        var themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                var html = document.documentElement;
                var isDark = html.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        }
        var notifBtn = document.getElementById('notifBtn'), notifDrop = document.getElementById('notifDropdown'), notifList = document.getElementById('notifList'), notifBadge = document.getElementById('notifBadge');
        function loadNotifs() {
            if (!notifList) return;
            fetch('/api/bildirimler').then(function(r) { return r.json(); }).then(function(data) {
                var n = data.notifications || [];
                var unread = data.unread_count || 0;
                if (notifBadge) { notifBadge.classList.toggle('hidden', unread === 0); }
                if (n.length === 0) { notifList.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Bildirim bulunmuyor.</p>'; return; }
                var html = '';
                n.forEach(function(item) {
                    var icon = item.type === 'payment' ? 'credit-card' : (item.type === 'contract' ? 'file-text' : (item.type === 'warehouse' ? 'building' : (item.type === 'room' ? 'grid-3x3' : (item.type === 'user' ? 'person' : (item.type === 'transport' ? 'truck' : (item.type === 'bank' ? 'bank' : 'bell'))))));
                    var unreadClass = item.is_read ? '' : ' bg-emerald-50/50 dark:bg-emerald-900/10';
                    var dateStr = item.created_at ? new Date(item.created_at).toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
                    var actorStr = (item.metadata && item.metadata.actor_name) ? '<span class="text-gray-500 dark:text-gray-400 text-[10px]"> · ' + String(item.metadata.actor_name).replace(/</g, '&lt;') + '</span>' : '';
                    html += '<div class="py-2.5 border-b border-gray-100 dark:border-gray-700 last:border-0' + unreadClass + '"><p class="font-medium text-gray-900 dark:text-white text-sm">' + (item.title || '').replace(/</g, '&lt;') + '</p><p class="text-gray-600 dark:text-gray-400 text-xs mt-0.5">' + (item.message || '').replace(/</g, '&lt;').replace(/\n/g, '<br>') + actorStr + '</p><p class="text-gray-400 dark:text-gray-500 text-[10px] mt-1">' + dateStr + '</p></div>';
                });
                notifList.innerHTML = html;
            }).catch(function() { if (notifList) notifList.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Bildirimler yüklenemedi.</p>'; });
        }
        if (notifBtn && notifDrop) {
            notifBtn.addEventListener('click', function(e) { e.stopPropagation(); notifDrop.classList.toggle('hidden'); notifBtn.setAttribute('aria-expanded', notifDrop.classList.contains('hidden') ? 'false' : 'true'); if (!notifDrop.classList.contains('hidden')) loadNotifs(); });
            document.addEventListener('click', function() { notifDrop.classList.add('hidden'); notifBtn.setAttribute('aria-expanded', 'false'); });
        }
        loadNotifs();
        setInterval(loadNotifs, 60000);
    })();
    (function(){
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return;
        var pushPromptWrap = document.getElementById('pushPromptWrap');
        var pushEnabledWrap = document.getElementById('pushEnabledWrap');
        var pushEnableBtn = document.getElementById('pushEnableBtn');
        var pushBanner = document.getElementById('pushBanner');
        var pushBannerAllow = document.getElementById('pushBannerAllow');
        var pushBannerLater = document.getElementById('pushBannerLater');
        var forceShowBanner = /[?&]push_banner=1/.test(location.search || '');
        if (!forceShowBanner) {
            if (Notification.permission === 'granted') {
                if (pushBanner) pushBanner.classList.add('hidden');
                showEnabled();
                registerAndSubscribe();
            } else if (Notification.permission === 'denied') {
                if (pushBanner) pushBanner.classList.add('hidden');
            } else {
                try { if (sessionStorage.getItem('pushBannerDismissed') === '1') { if (pushBanner) pushBanner.classList.add('hidden'); } } catch (e) {}
            }
        }
        function showEnabled() {
            if (pushPromptWrap) pushPromptWrap.classList.add('hidden');
            if (pushEnabledWrap) pushEnabledWrap.classList.remove('hidden');
            if (pushBanner) pushBanner.classList.add('hidden');
        }
        function showPrompt() {
            if (pushPromptWrap) pushPromptWrap.classList.remove('hidden');
            if (pushEnabledWrap) pushEnabledWrap.classList.add('hidden');
        }
        function hidePushRow() {
            if (pushPromptWrap) pushPromptWrap.classList.add('hidden');
            if (pushEnabledWrap) pushEnabledWrap.classList.add('hidden');
            if (pushBanner) pushBanner.classList.add('hidden');
        }
        function hideBanner() {
            if (pushBanner) pushBanner.classList.add('hidden');
            try { sessionStorage.setItem('pushBannerDismissed', '1'); } catch (e) {}
        }
        if (Notification.permission === 'denied') {
            if (pushPromptWrap) pushPromptWrap.innerHTML = '<span class="text-gray-400">Bildirimler tarayıcıda engelli.</span>';
        }
        var swRegistration = null;
        navigator.serviceWorker.register('/sw.js').then(function(reg) { swRegistration = reg; }).catch(function() {});
        function registerAndSubscribe() {
            var regPromise = swRegistration ? Promise.resolve(swRegistration) : navigator.serviceWorker.register('/sw.js');
            regPromise.then(function(reg) {
                if (!swRegistration) swRegistration = reg;
                fetch('/api/push-vapid-public').then(function(r) { return r.json(); }).then(function(data) {
                    var publicKey = data.publicKey;
                    if (!publicKey) {
                        hidePushRow();
                        return;
                    }
                    reg.pushManager.getSubscription().then(function(sub) {
                        if (sub) {
                            sendSubscriptionToServer(sub);
                            showEnabled();
                            return;
                        }
                        return reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(publicKey)
                        });
                    }).then(function(sub) {
                        if (sub && !sub.endpoint) return;
                        if (sub) sendSubscriptionToServer(sub);
                    }).catch(function() { showPrompt(); });
                });
            }).catch(function() {});
        }
        function sendSubscriptionToServer(subscription) {
            var body = JSON.stringify({
                subscription: {
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''),
                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
                    }
                }
            });
            fetch('/api/push-subscribe', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, credentials: 'same-origin' }).then(function(r) {
                if (r.ok) showEnabled();
            });
        }
        function urlBase64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - base64String.length % 4) % 4);
            var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            var rawData = window.atob(base64);
            var outputArray = new Uint8Array(rawData.length);
            for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
            return outputArray;
        }
        var pushBannerHint = document.getElementById('pushBannerHint');
        function requestPermissionAndSubscribe() {
            if (Notification.permission === 'granted') { registerAndSubscribe(); return; }
            if (Notification.permission === 'denied') {
                if (pushBannerHint) { pushBannerHint.textContent = 'Bildirimler tarayıcıda engelli. Ayarlar \u2192 Site ayarları \u2192 Bildirimler ile açabilirsiniz.'; pushBannerHint.classList.remove('hidden'); }
                return;
            }
            function onResult(p) {
                if (p === 'granted') {
                    registerAndSubscribe();
                    hideBanner();
                } else if (p === 'denied' && pushBannerHint) {
                    pushBannerHint.textContent = 'Bildirimler reddedildi. Değiştirmek için tarayıcı/site ayarlarından bildirimlere izin verin.';
                    pushBannerHint.classList.remove('hidden');
                } else if (pushBannerHint && /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
                    pushBannerHint.textContent = 'İzin penceresi açılmadıysa: Safari\/Chrome ayarları \u2192 Bu site \u2192 Bildirimler \u2192 İzin ver.';
                    pushBannerHint.classList.remove('hidden');
                }
            }
            try {
                var p = Notification.requestPermission();
                if (p && typeof p.then === 'function') {
                    p.then(onResult).catch(function() { if (pushBannerHint) { pushBannerHint.textContent = 'İzin alınamadı. Lütfen tarayıcı ayarlarından bildirimlere izin verin.'; pushBannerHint.classList.remove('hidden'); } });
                } else if (typeof p === 'string') {
                    onResult(p);
                }
            } catch (e) {
                if (pushBannerHint) { pushBannerHint.textContent = 'Bu tarayıcı bildirim desteklemiyor veya izin kapalı. Ayarlardan kontrol edin.'; pushBannerHint.classList.remove('hidden'); }
            }
        }
        function bindPushAllow() {
            if (!pushBannerAllow) return;
            var didRequest = false;
            function run() {
                if (didRequest) return;
                didRequest = true;
                requestPermissionAndSubscribe();
                setTimeout(function() { didRequest = false; }, 800);
            }
            pushBannerAllow.addEventListener('click', function(e) { e.preventDefault(); run(); });
            pushBannerAllow.addEventListener('touchend', function(e) { e.preventDefault(); run(); }, { passive: false });
        }
        bindPushAllow();
        if (pushEnableBtn) pushEnableBtn.addEventListener('click', requestPermissionAndSubscribe);
        if (pushBannerLater) pushBannerLater.addEventListener('click', hideBanner);
        fetch('/api/push-vapid-public').then(function(r) { return r.json(); }).then(function(d) {
            if (!d.publicKey) return;
            if (Notification.permission === 'granted') { registerAndSubscribe(); return; }
        }).catch(function() {});
    })();
    </script>
</body>
</html>
