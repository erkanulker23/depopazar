<?php
$seoDefaultAppName = 'Depo ve Nakliye Takip';
if (!isset($pageTitle)) $pageTitle = 'Ana Sayfa';
if (!isset($projectName)) $projectName = $_SESSION['company_project_name'] ?? $seoDefaultAppName;
$fullTitle = htmlspecialchars($pageTitle) . ' - ' . htmlspecialchars($projectName);
// SEO description: firma/uygulama bilgisi varsa ona göre, yoksa varsayılan
$companyName = trim($_SESSION['company_name'] ?? '');
$appName = trim($projectName);
if ($companyName !== '' && $appName !== '') {
    $seoDescription = $companyName . ' - ' . $appName . '. Depo ve nakliye yönetimi.';
} elseif ($appName !== '') {
    $seoDescription = $appName . '. Depo ve nakliye işlemlerinizi tek panelden yönetin.';
} else {
    $seoDescription = $seoDefaultAppName . '. Depo ve nakliye işlemlerinizi tek panelden yönetin.';
}
$user = Auth::user();
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
if (($q = strpos($currentPath, '?')) !== false) $currentPath = substr($currentPath, 0, $q);
$navIcons = ['Genel Bakış'=>'house','Depo Girişi Ekle'=>'plus-circle','Ödeme Al'=>'bank','Tüm Sözleşmeler'=>'file-text','Nakliye İşler'=>'truck','Araçlar'=>'car-front','Hizmetler'=>'tag','Teklifler'=>'file-earmark-plus','Personel'=>'person-badge','Kullanıcılar'=>'people','Kullanıcı Yetkileri'=>'shield-check','Depolar'=>'building','Odalar'=>'grid-3x3','Müşteriler'=>'people','Ödemeler'=>'credit-card','Masraflar'=>'wallet2','Raporlar'=>'bar-chart','Bildirimler'=>'bell','Ayarlar'=>'gear'];
$navItems = [
    ['name' => 'Genel Bakış', 'href' => '/genel-bakis', 'active' => $currentPath === '/genel-bakis'],
    ['name' => 'Depo Girişi Ekle', 'href' => '/girisler?newSale=1', 'active' => false],
    ['name' => 'Müşteriler', 'href' => '/musteriler', 'active' => $currentPath === '/musteriler'],
    ['name' => 'Tüm Sözleşmeler', 'href' => '/girisler', 'active' => $currentPath === '/girisler'],
    ['name' => 'Depolar', 'href' => '/depolar', 'active' => $currentPath === '/depolar'],
    ['name' => 'Odalar', 'href' => '/odalar', 'active' => $currentPath === '/odalar'],
    ['name' => 'Ödeme Al', 'href' => '/odemeler?collect=1', 'active' => false],
    ['name' => 'Nakliye İşler', 'href' => '/nakliye-isler', 'active' => $currentPath === '/nakliye-isler'],
    ['name' => 'Araçlar', 'href' => '/araclar', 'active' => $currentPath === '/araclar' || (strpos($currentPath, '/araclar/') === 0)],
    ['name' => 'Hizmetler', 'href' => '/hizmetler', 'active' => $currentPath === '/hizmetler'],
    ['name' => 'Teklifler', 'href' => '/teklifler', 'active' => $currentPath === '/teklifler'],
    ['name' => 'Personel', 'href' => '/personel', 'active' => $currentPath === '/personel'],
    ['name' => 'Kullanıcılar', 'href' => '/kullanicilar', 'active' => $currentPath === '/kullanicilar'],
    ['name' => 'Kullanıcı Yetkileri', 'href' => '/yetkiler', 'active' => $currentPath === '/yetkiler'],
    ['name' => 'Ödemeler', 'href' => '/odemeler', 'active' => $currentPath === '/odemeler'],
    ['name' => 'Masraflar', 'href' => '/masraflar', 'active' => $currentPath === '/masraflar'],
    ['name' => 'Raporlar', 'href' => '/raporlar', 'active' => $currentPath === '/raporlar'],
    ['name' => 'Bildirimler', 'href' => '/bildirimler', 'active' => $currentPath === '/bildirimler'],
    ['name' => 'Ayarlar', 'href' => '/ayarlar', 'active' => $currentPath === '/ayarlar'],
];
$navItems = array_values(array_filter($navItems, fn($item) => Auth::canAccessNav($item['href'])));
$companyLogoUrl = publicUploadHref($_SESSION['company_logo_url'] ?? null);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#059669" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1a1614" media="(prefers-color-scheme: dark)">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($projectName) ?>">
    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta property="og:title" content="<?= $fullTitle ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta property="og:type" content="website">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/icon-180.png">
    <title><?= $fullTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: { 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46' },
                    surface: { 50: '#f8fafc', 100: '#f1f5f9', 800: '#241e1b', 900: '#1a1614' },
                    gray: {
                        50: '#f8fafc',
                        100: '#f1f5f9',
                        200: '#e2e8f0',
                        300: '#cbd5e1',
                        400: '#9a8a7e',
                        500: '#7a6b5e',
                        600: '#4a3f36',
                        700: '#352e28',
                        800: '#2a2320',
                        900: '#1a1614',
                        950: '#120f0d',
                    }
                },
                fontFamily: {
                    sans: ['Plus Jakarta Sans', 'system-ui', '-apple-system', 'sans-serif']
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --safe-top: env(safe-area-inset-top);
            --safe-bottom: env(safe-area-inset-bottom);
            --safe-left: env(safe-area-inset-left);
            --safe-right: env(safe-area-inset-right);
            --mobile-nav-height: calc(4.25rem + var(--safe-bottom));
        }
        html, body, input, select, textarea, button { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif; -webkit-font-smoothing: antialiased; }
        .nav-active { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; box-shadow: 0 4px 14px rgba(5,150,105,.35); }
        .dark .nav-active { background: linear-gradient(135deg, #6b4c3b 0%, #5c4033 100%); box-shadow: 0 4px 14px rgba(92,64,51,.35); }
        .nav-active .nav-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: rgba(255,255,255,.9); border-radius: 0 3px 3px 0; }
        .btn-filter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            min-height: 44px;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.25rem;
            background-color: #2563eb;
            color: #fff;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.35);
            transition: background-color 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-filter:hover { background-color: #1d4ed8; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.4); }
        .dark .btn-filter { background-color: #3b82f6; box-shadow: 0 1px 3px rgba(59, 130, 246, 0.45); }
        .dark .btn-filter:hover { background-color: #2563eb; }
        .filter-field > label,
        .filter-modal-body .filter-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: rgb(55 65 81);
            margin-bottom: 0.375rem;
        }
        .dark .filter-field > label,
        .dark .filter-modal-body .filter-label { color: rgb(209 213 219); }
        .filter-input,
        .filter-modal-body input[type="search"],
        .filter-modal-body input[type="date"],
        .filter-modal-body input[type="text"],
        .filter-modal-body select,
        .filter-modal-body textarea {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            background: #fff;
            color: rgb(17 24 39);
        }
        .dark .filter-input,
        .dark .filter-modal-body input[type="search"],
        .dark .filter-modal-body input[type="date"],
        .dark .filter-modal-body input[type="text"],
        .dark .filter-modal-body select,
        .dark .filter-modal-body textarea {
            border-color: rgb(75 85 99);
            background: rgb(55 65 81);
            color: #fff;
        }
        .filter-modal-body input:focus,
        .filter-modal-body select:focus,
        .filter-modal-body textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.45);
            border-color: rgb(16 185 129);
        }
        .filter-modal-panel { animation: filterModalSlideUp 0.22s ease-out; }
        @keyframes filterModalSlideUp {
            from { transform: translateY(12px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (min-width: 640px) {
            .filter-modal-panel { animation: filterModalFadeIn 0.18s ease-out; }
            @keyframes filterModalFadeIn {
                from { transform: scale(0.97); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
        }
        .sidebar-mobile { transform: translateX(-100%); transition: transform .3s cubic-bezier(0.4,0,0.2,1); will-change: transform; }
        .sidebar-mobile.open { transform: translateX(0); box-shadow: 20px 0 40px rgba(0,0,0,.2); }
        @media (min-width: 768px) { .sidebar-mobile { transform: none; box-shadow: none; } }
        @media (max-width: 767px) {
            #sidebar.sidebar-mobile.open { z-index: 60; }
            #sidebarOverlay:not(.hidden) { z-index: 55; }
            body.sidebar-open #mobileBottomNav {
                visibility: hidden;
                pointer-events: none;
            }
            #sidebar .sidebar-user-footer {
                padding-bottom: max(1.25rem, var(--safe-bottom));
                margin-bottom: 0;
            }
            #sidebar .sidebar-logout-btn {
                min-width: 44px;
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }
        * { -webkit-tap-highlight-color: transparent; }
        html { overflow-x: hidden; scroll-padding-bottom: var(--mobile-nav-height); }
        .main-content-wrap { padding-bottom: env(safe-area-inset-bottom, 0); }
        @media (max-width: 767px) {
            html { scroll-padding-bottom: var(--mobile-nav-height); }
            .main-shell {
                flex: none !important;
                min-height: 0 !important;
                width: 100%;
            }
            body > .flex.min-h-screen {
                min-height: 0;
                align-items: flex-start;
            }
            .main-content-wrap {
                flex: none !important;
                padding-bottom: var(--mobile-nav-height) !important;
            }
            /* Alt menü yüksekliği kadar boşluk — fazla padding kaldırıldı */
            .page-header-actions {
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                gap: 0.5rem;
                padding-bottom: 0.125rem;
            }
            .page-header-actions::-webkit-scrollbar { display: none; }
            .page-header-actions > a,
            .page-header-actions > button,
            .page-header-actions > form {
                flex-shrink: 0;
            }
            .mobile-card {
                border-radius: 1rem;
                overflow: hidden;
            }
            .mobile-card > .overflow-x-auto,
            .mobile-card .table-scroll {
                margin-left: -0.25rem;
                margin-right: -0.25rem;
                padding-left: 0.25rem;
                padding-right: 0.25rem;
            }
            /* Gizli yazdırma / modal blokları yer kaplamasın */
            .hidden,
            .modal-overlay.hidden {
                display: none !important;
            }
        }
        @media (min-width: 768px) {
            .main-content-wrap {
                flex: 1 1 auto;
                min-height: 0;
            }
        }
        @media (max-width: 767px) {
            #sidebar { padding-top: max(1rem, var(--safe-top)); width: min(300px, 88vw); }
            .main-content-wrap {
                padding-bottom: var(--mobile-nav-height) !important;
            }
            .table-responsive,
            .table-scroll,
            .overflow-x-auto {
                -webkit-overflow-scrolling: touch;
                overflow-x: auto;
                max-width: 100%;
            }
            /* Sayfa üstü: filtre + aksiyon çubukları */
            .page-toolbar {
                gap: 0.75rem;
            }
            .page-toolbar > form,
            .page-toolbar > .page-toolbar-form {
                width: 100%;
            }
            .page-toolbar form > div,
            form.page-toolbar > div {
                flex: 1 1 calc(50% - 0.5rem);
                min-width: 140px;
            }
            form.page-toolbar > div:has(input[type="search"]),
            form.page-toolbar > div.w-full {
                flex: 1 1 100%;
            }
            form.page-toolbar button[type="submit"],
            form.page-toolbar .btn-touch,
            form.page-toolbar a.btn-touch {
                flex: 1 1 auto;
                min-width: 44%;
                justify-content: center;
            }
            .page-toolbar .btn-touch,
            .page-toolbar button[type="submit"],
            .page-toolbar a.btn-touch {
                flex: 1 1 auto;
                min-width: 0;
                justify-content: center;
            }
            .page-header {
                gap: 0.75rem;
            }
            .page-header > div:last-child:not(:only-child) {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .page-header > div:last-child:not(:only-child) > a,
            .page-header > div:last-child:not(:only-child) > button {
                flex: 1 1 calc(50% - 0.25rem);
                min-width: 0;
                justify-content: center;
                min-height: 44px;
            }
            .page-header-actions > a,
            .page-header-actions > button,
            .page-header-actions > form {
                flex: 0 0 auto;
                min-width: auto;
                white-space: nowrap;
            }
            /* Form kaydet / gönder — alt menünün üstünde yapışkan */
            .form-submit-bar {
                position: sticky;
                bottom: var(--mobile-nav-height);
                z-index: 35;
                margin-top: 1rem;
                margin-left: -0.25rem;
                margin-right: -0.25rem;
                padding: 0.75rem 0.25rem;
                background: linear-gradient(to top, rgba(249,250,251,0.98) 70%, rgba(249,250,251,0));
                backdrop-filter: blur(6px);
            }
            .dark .form-submit-bar {
                background: linear-gradient(to top, rgba(26,22,20,0.98) 70%, rgba(26,22,20,0));
            }
            .form-submit-bar button[type="submit"],
            .form-submit-bar .btn-submit,
            .form-submit-bar .btn-touch {
                min-height: 48px;
            }
            .form-submit-bar:not(.flex) button[type="submit"],
            .form-submit-bar:not(.flex) .btn-submit {
                width: 100%;
            }
            .form-submit-bar.flex button[type="submit"],
            .form-submit-bar.flex .btn-submit {
                flex: 1 1 auto;
                min-width: 44%;
            }
            /* Uzun formlarda otomatik yapışkan alt çubuk (sınıf eklenmemiş sayfalar) */
            main form:not(.modal-overlay form) > div:last-child:has(> button[type="submit"]:only-child),
            main form:not(.modal-overlay form) > div:last-child:has(> button[type="submit"]:last-child:not(:first-child)) {
                position: sticky;
                bottom: var(--mobile-nav-height);
                z-index: 35;
                padding-top: 0.75rem;
                padding-bottom: 0.25rem;
                background: linear-gradient(to top, rgba(249,250,251,0.98) 75%, rgba(249,250,251,0));
            }
            .dark main form:not(.modal-overlay form) > div:last-child:has(button[type="submit"]) {
                background: linear-gradient(to top, rgba(17,24,39,0.98) 75%, rgba(17,24,39,0));
            }
            main form:not(.modal-overlay form) > div:last-child:has(button[type="submit"]) button[type="submit"] {
                min-height: 48px;
            }
            /* Modallar — tam ekran, alt menünün üstünde */
            .modal-overlay {
                padding: 0;
                z-index: 60 !important;
            }
            .modal-overlay > div.flex {
                min-height: 100%;
                align-items: stretch;
                padding: 0;
            }
            .modal-overlay .relative.bg-white,
            .modal-overlay .relative.dark\:bg-gray-800,
            .modal-overlay .relative[class*="bg-white"],
            .modal-overlay .relative[class*="bg-gray-800"] {
                max-height: none;
                min-height: 100dvh;
                width: 100%;
                max-width: none;
                border-radius: 0;
                display: flex;
                flex-direction: column;
            }
            .modal-overlay form {
                display: flex;
                flex-direction: column;
                flex: 1;
                min-height: 0;
            }
            .modal-overlay .form-submit-bar {
                position: sticky;
                bottom: 0;
                margin-top: auto;
                padding-bottom: max(1rem, var(--safe-bottom));
                background: rgba(255,255,255,0.98);
                border-top: 1px solid rgb(229 231 235);
                z-index: 10;
            }
            body.modal-open #mobileBottomNav {
                visibility: hidden;
                pointer-events: none;
            }
            .dark .modal-overlay .form-submit-bar {
                background: rgba(42,35,32,0.98);
                border-top-color: rgb(74 63 54);
            }
            /* Dokunma hedefleri */
            main button[type="submit"]:not(.inline-flex):not([class*="text-xs"]),
            main .btn-primary-mobile {
                min-height: 44px;
            }
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
                background: rgba(26,22,20,.98); border-top-color: rgb(61 52 46);
                box-shadow: 0 -4px 24px rgba(0,0,0,.35);
            }
        }
        @media (min-width: 768px) { #mobileBottomNav { display: none !important; } }
        @media (max-width: 767px) {
            .nav-link { min-height: 48px; padding: 0.75rem 1rem; -webkit-tap-highlight-color: transparent; }
            .btn-touch { min-height: 44px; min-width: 44px; padding: 0.625rem 1rem; }
        }
        input, select, textarea { font-size: 16px !important; }
        /* Koyu mod: form — etiketler ve input metni okunaklı olsun */
        .dark label { color: #cbd5e1; }
        .dark input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="range"]),
        .dark select,
        .dark textarea {
            background-color: #2a2320;
            color: #f1f5f9;
            border-color: #4a3f36;
        }
        .dark input::placeholder,
        .dark textarea::placeholder {
            color: #9a8a7e;
            opacity: 1;
        }
        .dark input[type="date"],
        .dark input[type="datetime-local"],
        .dark input[type="time"],
        .dark input[type="month"] {
            color-scheme: dark;
        }
        .dark select option {
            background-color: #2a2320;
            color: #f1f5f9;
        }
        .touch-manipulation { touch-action: manipulation; -webkit-user-select: none; user-select: none; }
        #pushBanner, #pwaInstallBanner { padding-left: max(1rem, env(safe-area-inset-left)); padding-right: max(1rem, env(safe-area-inset-right)); }
        @media (max-width: 767px) {
            #pushBanner, #pwaInstallBanner { flex-direction: column; align-items: stretch; text-align: center; gap: 0.75rem; padding: 0.75rem max(1rem, env(safe-area-inset-left)) 0.75rem max(1rem, env(safe-area-inset-right)); }
            #pushBanner .push-banner-text, #pwaInstallBanner .pwa-banner-text { flex: none; min-width: 0; width: 100%; word-wrap: break-word; overflow-wrap: break-word; }
            #pushBanner .push-banner-btns, #pwaInstallBanner .pwa-banner-btns { flex-wrap: nowrap; justify-content: center; align-items: center; gap: 0.5rem; width: 100%; }
            #pushBanner .push-banner-btns button { min-width: 0; }
            #pushBanner #pushBannerAllow { flex: 1; max-width: 200px; }
            #pushBanner #pushBannerLater { flex: 0 0 auto; }
        }
        .page-title { font-size: 1.5rem; line-height: 1.3; }
        @media (min-width: 768px) { .page-title { font-size: 1.875rem; } }
        .page-subtitle { color: rgb(107 114 128); }
        .dark .page-subtitle { color: rgb(154 138 126); }
        .gradient-title { background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .dark .gradient-title { background: linear-gradient(135deg, #d4a574 0%, #c4956a 50%, #a67c52 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .card-modern { border-radius: 1rem; border: 1px solid rgb(229 231 235); background: white; box-shadow: 0 1px 3px rgba(0,0,0,.05); transition: all .2s; overflow: visible; }
        .dark .card-modern { border-color: rgb(74 63 54); background: rgb(42 35 32); }
        .card-modern:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .dark .card-modern:hover { box-shadow: 0 4px 12px rgba(0,0,0,.25); }
        .stat-card { border-radius: 1rem; border: 1px solid rgb(229 231 235); background: white; padding: 1.25rem; transition: all .2s; }
        .dark .stat-card { border-color: rgb(74 63 54); background: rgb(42 35 32); }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(5,150,105,.1); }
        /* Koyu mod: tablo, modal ve aksiyon butonları okunaklılığı */
        .dark main table td.text-gray-600,
        .dark main table tbody .text-gray-600,
        .dark .modal-overlay .text-gray-600 {
            color: #cbd5e1;
        }
        .dark main table td.text-gray-700,
        .dark .modal-overlay .text-gray-700 {
            color: #e2e8f0;
        }
        .dark main a.bg-emerald-50,
        .dark main button.bg-emerald-50 {
            background-color: rgba(6, 78, 59, 0.28);
            color: #6ee7b7;
        }
        .dark main a.bg-gray-100,
        .dark main button.bg-gray-100 {
            background-color: rgba(74, 63, 54, 0.65);
            color: #e2e8f0;
        }
        .dark main a.bg-red-50,
        .dark main button.bg-red-50 {
            background-color: rgba(127, 29, 29, 0.28);
            color: #fca5a5;
        }
        .dark main a.hover\:bg-emerald-100:hover { background-color: rgba(6, 78, 59, 0.42); }
        .dark main a.hover\:bg-gray-200:hover,
        .dark main button.hover\:bg-gray-200:hover { background-color: rgba(92, 64, 51, 0.85); }
        .dark main a.hover\:bg-red-100:hover { background-color: rgba(127, 29, 29, 0.42); }
        .dark .modal-overlay .border-gray-300 { border-color: #4a3f36; }
        .modal-overlay { -webkit-overflow-scrolling: touch; }
        .modal-overlay .relative { max-height: min(90vh, 600px); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen text-gray-900 dark:text-gray-100 antialiased" style="touch-action: manipulation;" data-auth="<?= ($user && !empty($user['id'])) ? '1' : '0' ?>" data-app-name="<?= htmlspecialchars($projectName) ?>">
    <div class="flex min-h-screen">
        <div id="sidebarOverlay" class="md:hidden fixed inset-0 bg-black/50 z-30 hidden transition-opacity" aria-hidden="true"></div>
        <aside id="sidebar" class="sidebar-mobile fixed md:static inset-y-0 left-0 z-40 w-72 flex flex-col pt-6 bg-white dark:bg-[#241e1b] border-r border-gray-200/50 dark:border-[#3d342e] overflow-y-auto overflow-x-hidden">
            <div class="flex items-center flex-shrink-0 px-6 mb-6">
                <div class="flex items-center gap-3">
                    <?php if (!empty($companyLogoUrl)): ?>
                        <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="" class="h-10 w-auto object-contain flex-shrink-0" aria-hidden="true">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <i class="bi bi-building text-white text-lg"></i>
                        </div>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <?php if (empty($companyLogoUrl)): ?><h1 class="text-lg font-bold bg-gradient-to-r from-emerald-600 to-emerald-500 bg-clip-text text-transparent truncate"><?= htmlspecialchars($projectName) ?></h1><?php endif; ?>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Sistem</p>
                    </div>
                </div>
            </div>
            <nav class="mt-2 flex-1 px-3 overflow-y-auto min-h-0 space-y-0.5">
                <?php foreach ($navItems as $item):
                    $icon = $navIcons[$item['name']] ?? 'circle';
                ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="nav-link group relative flex items-center px-4 py-3 text-xs font-semibold uppercase tracking-wide rounded-xl transition-all duration-200 <?= !empty($item['active']) ? 'nav-active' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>">
                        <?php if (!empty($item['active'])): ?><div class="nav-bar"></div><?php endif; ?>
                        <i class="bi bi-<?= $icon ?> mr-3 flex-shrink-0 h-5 w-5"></i>
                        <span class="flex-1 truncate"><?= htmlspecialchars($item['name']) ?></span>
                        <?php if (($item['href'] ?? '') === '/odemeler?collect=1'): ?>
                            <span id="collectPaymentBadge" class="ml-2 min-w-[1.25rem] px-1.5 py-0.5 text-[10px] font-bold leading-none bg-red-500 text-white rounded-full text-center hidden" title="Tahsil edilecek ödeme"></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-user-footer flex-shrink-0 border-t border-gray-200/50 dark:border-gray-700 p-4 mx-3 mb-4">
                <div class="flex items-center gap-3 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <?php $userRow = $user; $size = 'sm'; require __DIR__ . '/partials/user_avatar.php'; ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    </div>
                    <a href="/cikis" class="sidebar-logout-btn p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 flex-shrink-0" title="Çıkış" aria-label="Çıkış"><i class="bi bi-box-arrow-right text-lg"></i></a>
                </div>
            </div>
        </aside>
        <main class="main-shell flex-1 min-w-0 flex flex-col md:min-h-screen">
            <div class="flex-shrink-0 flex items-center justify-between md:justify-end gap-2 pl-4 pr-3 py-3 md:pl-6 md:px-6 lg:px-8 border-b border-gray-200 dark:border-[#3d342e] bg-white/95 dark:bg-[#241e1b]/95 backdrop-blur sticky top-0 z-20 min-h-[3.5rem]" style="padding-top: max(0.75rem, var(--safe-top));">
                <div class="flex items-center gap-2 min-w-0 md:mr-auto">
                    <?php if (!empty($companyLogoUrl)): ?>
                        <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="" class="h-8 w-auto object-contain flex-shrink-0 md:h-9" aria-hidden="true">
                    <?php endif; ?>
                    <span class="md:hidden text-sm font-semibold text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($projectName) ?></span>
                </div>
                <div class="flex items-center gap-1">
                    <?php if ($user && !empty($user['id'])): ?>
                    <div class="relative" id="notificationWrap">
                        <button type="button" id="notificationBell" class="relative p-3 md:p-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors min-h-[44px] min-w-[44px] md:min-h-0 md:min-w-0 flex items-center justify-center" title="Bildirimler" aria-expanded="false" aria-haspopup="true">
                            <i class="bi bi-bell text-xl md:text-lg" aria-hidden="true"></i>
                            <span id="notificationBadge" class="absolute top-1.5 right-1.5 md:top-1 md:right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center hidden">0</span>
                        </button>
                        <div id="notificationDropdown" class="hidden absolute right-0 top-full mt-2 w-[min(90vw,380px)] max-h-[min(70vh,420px)] flex flex-col rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-xl z-50 overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50">
                                <span class="font-semibold text-gray-900 dark:text-white">Bildirimler</span>
                                <div class="flex items-center gap-1">
                                    <button type="button" id="notificationMarkAllRead" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600" title="Tümünü okundu işaretle"><i class="bi bi-check-all"></i></button>
                                    <button type="button" id="notificationDeleteAll" class="p-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" title="Tümünü sil"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <div id="notificationListWrap" class="flex-1 overflow-y-auto min-h-[120px]">
                                <div id="notificationListLoading" class="hidden p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Yükleniyor…</div>
                                <div id="notificationListEmpty" class="hidden p-6 text-center text-gray-500 dark:text-gray-400 text-sm"><i class="bi bi-bell text-3xl block mb-2 opacity-50"></i>Bildirim yok</div>
                                <ul id="notificationList" class="divide-y divide-gray-200 dark:divide-gray-600"></ul>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-600 px-4 py-2 bg-gray-50 dark:bg-gray-700/50">
                                <a href="/bildirimler" class="block text-center text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">Tüm bildirimler</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <button type="button" id="themeToggle" class="p-3 md:p-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors min-h-[44px] min-w-[44px] md:min-h-0 md:min-w-0 flex items-center justify-center" title="Koyu / Açık mod">
                        <i class="bi bi-moon-stars text-xl md:text-lg dark:hidden" aria-hidden="true"></i>
                        <i class="bi bi-sun text-xl md:text-lg hidden dark:inline" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <?php if ($user && !empty($user['id'])): ?>
            <div id="pwaInstallBanner" class="hidden mx-4 md:mx-6 lg:mx-8 mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3 text-sm text-emerald-900 dark:text-emerald-100">
                <div class="pwa-banner-text flex items-center gap-2 min-w-0">
                    <i class="bi bi-phone text-lg flex-shrink-0"></i>
                    <span>Uygulamayı telefonunuza ekleyerek tek dokunuşla panele girin.</span>
                </div>
                <div class="pwa-banner-btns flex gap-2 flex-shrink-0">
                    <button type="button" id="pwaInstallBtn" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 text-sm">Yükle</button>
                    <button type="button" id="pwaInstallDismiss" class="px-3 py-2 rounded-xl text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-sm">Kapat</button>
                </div>
            </div>
            <div id="pushBanner" class="hidden mx-4 md:mx-6 lg:mx-8 mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-4 py-3 text-sm text-blue-900 dark:text-blue-100">
                <div class="push-banner-text flex items-center gap-2 min-w-0">
                    <i class="bi bi-bell text-lg flex-shrink-0"></i>
                    <span>Her işlemde anlık bildirim almak için açın (telefon + e-posta). iPhone: önce uygulamayı ana ekrana ekleyin.</span>
                </div>
                <div class="push-banner-btns flex gap-2 flex-shrink-0">
                    <button type="button" id="pushBannerAllow" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 text-sm">Bildirimleri Aç</button>
                    <button type="button" id="pushBannerLater" class="px-3 py-2 rounded-xl text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-sm">Sonra</button>
                </div>
            </div>
            <?php endif; ?>
            <div class="p-4 md:p-6 lg:p-8 md:pb-6 main-content-wrap md:flex-1 md:min-h-0">
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
        function closeSidebar(){ if(s) s.classList.remove('open'); if(o) o.classList.add('hidden'); document.body.style.overflow = ''; document.body.classList.remove('sidebar-open'); }
        function openSidebar(){ if(s) s.classList.add('open'); if(o) o.classList.remove('hidden'); document.body.style.overflow = 'hidden'; document.body.classList.add('sidebar-open'); }
        if(t&&s){ t.addEventListener('click',function(){ s.classList.toggle('open'); if(o) o.classList.toggle('hidden'); var isOpen = s.classList.contains('open'); document.body.style.overflow = isOpen ? 'hidden' : ''; document.body.classList.toggle('sidebar-open', isOpen); }); }
        if(mobileMenuBtn&&s){ mobileMenuBtn.addEventListener('click', openSidebar); }
        if(o&&s){ o.addEventListener('click', closeSidebar); }
        document.querySelectorAll('.nav-link').forEach(function(el){ el.addEventListener('click', closeSidebar); });
    })();
    (function(){
        function updateModalState() {
            var any = document.querySelector('.modal-overlay:not(.hidden)');
            document.body.classList.toggle('modal-open', !!any);
            document.body.style.overflow = any ? 'hidden' : '';
        }
        var obs = new MutationObserver(updateModalState);
        document.querySelectorAll('.modal-overlay').forEach(function(el) {
            obs.observe(el, { attributes: true, attributeFilter: ['class'] });
        });
        updateModalState();
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
    })();
    (function(){
        var wrap = document.getElementById('notificationWrap');
        if (!wrap) return;
        var bell = document.getElementById('notificationBell');
        var badge = document.getElementById('notificationBadge');
        var dropdown = document.getElementById('notificationDropdown');
        var listEl = document.getElementById('notificationList');
        var listLoading = document.getElementById('notificationListLoading');
        var listEmpty = document.getElementById('notificationListEmpty');
        var markAllBtn = document.getElementById('notificationMarkAllRead');
        var deleteAllBtn = document.getElementById('notificationDeleteAll');

        function setBadge(n) {
            if (!badge) return;
            n = parseInt(n, 10) || 0;
            badge.textContent = n > 99 ? '99+' : n;
            badge.classList.toggle('hidden', n === 0);
        }

        function fetchNotifications(cb) {
            fetch('/api/bildirimler', { credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(data){
                var list = data.notifications || [];
                var unread = data.unread_count || 0;
                setBadge(unread);
                if (typeof cb === 'function') cb(list);
            }).catch(function(){ setBadge(0); if (typeof cb === 'function') cb([]); });
        }

        function renderList(list) {
            listLoading.classList.add('hidden');
            listEmpty.classList.toggle('hidden', list.length > 0);
            listEl.innerHTML = '';
            var typeIcons = { payment: 'credit-card', contract: 'file-text', proposal: 'file-earmark-plus', customer: 'people', warehouse: 'building', room: 'grid-3x3', bank: 'bank', expense: 'wallet2', vehicle: 'car-front', default: 'bell' };
            list.forEach(function(n){
                var icon = typeIcons[n.type] || typeIcons.default;
                var read = n.is_read == 1;
                var li = document.createElement('li');
                li.className = 'px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 ' + (read ? '' : 'bg-emerald-50/50 dark:bg-emerald-900/10');
                var time = n.created_at ? new Date(n.created_at).toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
                var actor = (n.metadata && n.metadata.actor_name) ? ' · ' + n.metadata.actor_name : '';
                li.innerHTML = '<div class="flex items-start gap-3"><div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center ' + (read ? 'bg-gray-100 dark:bg-gray-600 text-gray-500' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400') + '"><i class="bi bi-' + icon + '"></i></div><div class="flex-1 min-w-0"><p class="font-medium text-gray-900 dark:text-white text-sm">' + (n.title || '').replace(/</g,'&lt;') + '</p><p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">' + (n.message || '').replace(/</g,'&lt;').substring(0,120) + (n.message && n.message.length > 120 ? '…' : '') + actor + '</p><p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">' + time + '</p></div></div>';
                listEl.appendChild(li);
            });
        }

        function openDropdown() {
            dropdown.classList.remove('hidden');
            bell.setAttribute('aria-expanded', 'true');
            listEl.innerHTML = '';
            listLoading.classList.remove('hidden');
            listEmpty.classList.add('hidden');
            fetchNotifications(function(list){ renderList(list); });
        }

        function closeDropdown() {
            dropdown.classList.add('hidden');
            bell.setAttribute('aria-expanded', 'false');
        }

        bell.addEventListener('click', function(e){
            e.stopPropagation();
            if (dropdown.classList.contains('hidden')) openDropdown(); else closeDropdown();
        });

        wrap.addEventListener('click', function(e){ e.stopPropagation(); });
        document.documentElement.addEventListener('click', function(){ closeDropdown(); });

        if (markAllBtn) markAllBtn.addEventListener('click', function(){
            var csrf = document.querySelector('input[name="_token"]');
            fetch('/bildirimler/okundu', { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body: '_token=' + (csrf ? encodeURIComponent(csrf.value) : '') }).then(function(){
                fetchNotifications(function(list){ renderList(list); });
            });
        });

        if (deleteAllBtn) deleteAllBtn.addEventListener('click', function(){
            if (!confirm(<?= json_encode(deleteAllConfirmMessage('bildirimler')) ?>)) return;
            var csrf = document.querySelector('input[name="_token"]');
            fetch('/bildirimler/tumunu-sil', { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body: '_token=' + (csrf ? encodeURIComponent(csrf.value) : '') }).then(function(){
                setBadge(0);
                renderList([]);
            });
        });

        fetchNotifications();
    })();
    (function(){
        var badge = document.getElementById('collectPaymentBadge');
        if (!badge) return;
        fetch('/api/tahsil-edilebilir-sayisi', { credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                var n = parseInt(data.count, 10) || 0;
                if (n <= 0) return;
                badge.textContent = n > 99 ? '99+' : String(n);
                badge.title = n + ' tahsil edilecek ödeme';
                badge.classList.remove('hidden');
            })
            .catch(function(){});
    })();
    (function(){
        window.openFilterModal = function(id) {
            var el = document.getElementById(id || 'pageFilterModal');
            if (!el) return;
            el.classList.remove('hidden');
            el.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            var first = el.querySelector('input:not([type="hidden"]), select, textarea');
            if (first) setTimeout(function(){ first.focus(); }, 80);
        };
        window.closeFilterModal = function(id) {
            var el = document.getElementById(id || 'pageFilterModal');
            if (!el) return;
            el.classList.add('hidden');
            el.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.filter-modal-overlay:not(.hidden), .modal-overlay:not(.hidden)')) {
                document.body.style.overflow = '';
            }
        };
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            var open = document.querySelector('.filter-modal-overlay:not(.hidden)');
            if (open) {
                e.preventDefault();
                closeFilterModal(open.id);
            }
        });
    })();
    </script>
    <script src="/turkish-search.js"></script>
    <script src="/phone-mask.js" defer></script>
    <script src="/delete-confirm.js"></script>
    <script src="/form-guard.js" defer></script>
    <?php if ($user && !empty($user['id'])): ?><script src="/pwa.js" defer></script><?php endif; ?>
</body>
</html>
