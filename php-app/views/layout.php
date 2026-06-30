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
$navIcons = ['Genel Bakış'=>'house','Yeni Depo Sözleşmesi Ekle'=>'plus-circle','Ödeme Al'=>'bank','Tüm Sözleşmeler'=>'file-text','Nakliye İşler'=>'truck','Araçlar'=>'car-front','Hizmetler'=>'tag','Teklifler'=>'file-earmark-plus','Personel'=>'person-badge','Kullanıcılar'=>'people','Kullanıcı Yetkileri'=>'shield-check','Depolar'=>'building','Odalar'=>'grid-3x3','Müşteriler'=>'people','Ödemeler'=>'credit-card','Masraflar'=>'wallet2','Raporlar'=>'bar-chart','Bildirimler'=>'bell','Ayarlar'=>'gear'];
$navItems = [
    ['name' => 'Genel Bakış', 'href' => '/genel-bakis', 'active' => $currentPath === '/genel-bakis'],
    ['name' => 'Yeni Depo Sözleşmesi Ekle', 'href' => '/girisler?newSale=1', 'active' => false],
    ['name' => 'Müşteriler', 'href' => '/musteriler', 'active' => $currentPath === '/musteriler'],
    ['name' => 'Tüm Sözleşmeler', 'href' => '/girisler', 'active' => $currentPath === '/girisler'],
    ['name' => 'Depolar', 'href' => '/depolar', 'active' => $currentPath === '/depolar'],
    ['name' => 'Odalar', 'href' => '/odalar', 'active' => $currentPath === '/odalar' || strpos($currentPath, '/odalar/') === 0],
    ['name' => 'Ödeme Al', 'href' => '/odemeler?collect=1', 'active' => false],
    ['name' => 'Nakliye İşler', 'href' => '/nakliye-isler', 'active' => $currentPath === '/nakliye-isler'],
    ['name' => 'Araçlar', 'href' => '/araclar', 'active' => $currentPath === '/araclar' || (strpos($currentPath, '/araclar/') === 0)],
    ['name' => 'Hizmetler', 'href' => '/hizmetler', 'active' => $currentPath === '/hizmetler'],
    ['name' => 'Teklifler', 'href' => '/teklifler', 'active' => $currentPath === '/teklifler'],
    ['name' => 'Personel', 'href' => '/personel', 'active' => $currentPath === '/personel' || strpos($currentPath, '/personel/') === 0],
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
            --mobile-nav-height: calc(4.75rem + max(0.5rem, var(--safe-bottom)));
            --mobile-page-bg: #f8fafc;
            --mobile-nav-bg: rgba(255, 255, 255, 0.97);
        }
        .dark:root {
            --mobile-page-bg: #1a1614;
            --mobile-nav-bg: rgba(26, 22, 20, 0.98);
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
        /* Kompakt sayfa aksiyon butonları (Düzenle, Yazdır, PDF, Sil vb.) */
        .page-header-actions {
            gap: 0.375rem;
        }
        .page-header-actions > a,
        .page-header-actions > button,
        .page-header-actions > form,
        .page-header-actions > form > button,
        .page-header-actions > form > a {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem !important;
            font-size: 0.8125rem !important;
            line-height: 1.25rem !important;
            font-weight: 500 !important;
            border-radius: 0.625rem !important;
            white-space: nowrap;
        }
        .page-header-actions i[class*="bi-"] {
            font-size: 0.875rem;
            line-height: 1;
        }
        .page-header-actions .mr-2 {
            margin-right: 0 !important;
        }
        @media (min-width: 768px) {
            main .btn-touch {
                min-height: 36px !important;
                min-width: auto !important;
                padding: 0.375rem 0.875rem !important;
                font-size: 0.8125rem !important;
                border-radius: 0.625rem !important;
            }
            main .page-toolbar .btn-touch,
            main .page-toolbar a.inline-flex[class*="px-"],
            main .page-toolbar button.inline-flex[class*="px-"] {
                padding: 0.375rem 0.875rem !important;
                font-size: 0.8125rem !important;
                min-height: 36px !important;
            }
        }
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
            body.sidebar-open #mobileBottomNavHost {
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
        html {
            overflow-x: hidden;
            scroll-padding-bottom: var(--mobile-nav-height);
            background-color: var(--mobile-page-bg);
        }
        .main-content-wrap { padding-bottom: env(safe-area-inset-bottom, 0); }
        @media (max-width: 767px) {
            html {
                height: auto;
                min-height: 100%;
                scroll-padding-bottom: var(--mobile-nav-height);
                overscroll-behavior-y: none;
            }
            body {
                overflow-x: hidden;
                max-width: 100vw;
                min-height: 0;
                min-height: -webkit-fill-available;
                background-color: var(--mobile-page-bg) !important;
                overscroll-behavior-y: none;
            }
            body.min-h-screen {
                min-height: -webkit-fill-available !important;
            }
            #appShell,
            body > .flex.min-h-screen {
                display: block;
                min-height: 0 !important;
                width: 100%;
            }
            .main-shell {
                flex: none !important;
                min-height: 0 !important;
                width: 100%;
                display: block;
            }
            .main-content-wrap {
                flex: none !important;
                padding-bottom: var(--mobile-nav-height) !important;
            }
            .mobile-card {
                border-radius: 1rem;
                overflow-x: clip;
                overflow-y: visible;
            }
            .mobile-card.overflow-visible {
                overflow-x: clip;
                overflow-y: visible;
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
            /* Sayfa üstü: filtre + aksiyon çubukları */
            .page-toolbar {
                gap: 0.75rem;
                flex-direction: column;
                align-items: stretch;
            }
            .page-toolbar-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.5rem;
                width: 100%;
            }
            .page-toolbar-actions > a,
            .page-toolbar-actions > button,
            .page-toolbar-actions > form {
                min-width: 0;
            }
            .page-toolbar-actions .btn-touch,
            .page-toolbar-actions a,
            .page-toolbar-actions button {
                width: 100%;
                justify-content: center;
            }
            .page-toolbar-actions .col-span-2 {
                grid-column: span 2;
            }
            .page-toolbar-actions > *:only-child {
                grid-column: 1 / -1;
            }
            .card-modern {
                overflow: visible;
                max-width: 100%;
            }
            .settings-tabs-wrap {
                max-width: 100%;
                scrollbar-width: none;
            }
            .settings-tabs-wrap::-webkit-scrollbar {
                display: none;
            }
            .settings-page .card-modern > div {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .settings-page .settings-tabs-wrap nav a {
                padding: 0.625rem 0.75rem;
                font-size: 0.8125rem;
                gap: 0.375rem;
            }
            .settings-page .settings-list-item {
                flex-direction: column;
                align-items: stretch;
            }
            .settings-page .settings-list-item > .flex.items-center.gap-2 {
                width: 100%;
                justify-content: flex-end;
                padding-top: 0.5rem;
                border-top: 1px solid rgb(229 231 235);
            }
            .dark .settings-page .settings-list-item > .flex.items-center.gap-2 {
                border-top-color: rgb(55 65 81);
            }
            .settings-page .form-submit-bar .btn-submit,
            .settings-page .form-submit-bar button[type="submit"] {
                width: 100%;
            }
            .settings-page .form-submit-bar.flex .btn-touch:not(.btn-submit) {
                flex: 1 1 auto;
            }
            /* Sayfa kartları — mobilde fixed modalları kırmasın */
            main .card-modern,
            main .user-profile-page .profile-card {
                overflow: visible;
                max-width: 100%;
            }
            /* Filtre modalı — mobil alt sayfa */
            .page-filter-modal:not(.hidden) {
                position: fixed !important;
                inset: 0 !important;
                width: 100%;
                max-width: 100vw;
                height: 100%;
                max-height: 100dvh;
            }
            .page-filter-modal > div.flex {
                min-height: 100%;
                align-items: stretch;
                padding: 0;
            }
            .page-filter-modal .filter-modal-backdrop {
                position: absolute !important;
                inset: 0 !important;
            }
            .page-filter-modal .filter-modal-panel {
                max-height: 100dvh;
                min-height: 100dvh;
                width: 100%;
                max-width: none;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            .mobile-data-card {
                padding: 1rem;
            }
            .mobile-data-card + .mobile-data-card {
                border-top: 1px solid rgb(229 231 235);
            }
            .dark .mobile-data-card + .mobile-data-card {
                border-top-color: rgb(55 65 81);
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
                min-height: 40px;
            }
            /* Form kaydet / gönder — alt menünün üstünde yapışkan */
            .form-submit-bar {
                position: static;
                bottom: auto;
                z-index: auto;
                margin-top: 1rem;
                margin-left: 0;
                margin-right: 0;
                padding: 0.75rem 0 0.25rem;
                background: transparent;
                backdrop-filter: none;
            }
            .dark .form-submit-bar {
                background: transparent;
            }
            .card-modern .form-submit-bar {
                padding-bottom: 0.5rem;
            }
            /* Uzun formlarda otomatik yapışkan alt çubuk (form-submit-bar olmayan formlar) */
            main form:not(.modal-overlay form) > div:last-child:not(.form-submit-bar):has(> button[type="submit"]:only-child),
            main form:not(.modal-overlay form) > div:last-child:not(.form-submit-bar):has(> button[type="submit"]:last-child:not(:first-child)) {
                position: sticky;
                bottom: var(--mobile-nav-height);
                z-index: 35;
                padding-top: 0.75rem;
                padding-bottom: 0.25rem;
                background: linear-gradient(to top, rgba(249,250,251,0.98) 75%, rgba(249,250,251,0));
            }
            .dark main form:not(.modal-overlay form) > div:last-child:not(.form-submit-bar):has(button[type="submit"]) {
                background: linear-gradient(to top, rgba(17,24,39,0.98) 75%, rgba(17,24,39,0));
            }
            main form:not(.modal-overlay form) > div:last-child:not(.form-submit-bar):has(button[type="submit"]) button[type="submit"] {
                min-height: 48px;
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
            /* Modallar — tam ekran, alt menünün üstünde */
            .modal-overlay:not(.hidden) {
                position: fixed !important;
                inset: 0 !important;
                width: 100%;
                max-width: 100vw;
                height: 100%;
                max-height: 100dvh;
            }
            .modal-overlay {
                padding: 0;
                z-index: 70 !important;
            }
            .modal-overlay > div.flex > .fixed.inset-0 {
                position: absolute !important;
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
            body.modal-open #mobileBottomNavHost {
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
        #mobileBottomNavHost {
            display: none;
        }
        #mobileBottomNav {
            padding-bottom: max(0.5rem, var(--safe-bottom));
        }
        @media (max-width: 767px) {
            #mobileBottomNavHost {
                display: block;
                position: fixed !important;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                max-width: 100vw;
                z-index: 9990;
                pointer-events: none;
                padding: 0;
                margin: 0;
                transform: translate3d(0, 0, 0);
                -webkit-transform: translate3d(0, 0, 0);
                will-change: bottom, left, width;
            }
            #mobileBottomNav {
                position: relative !important;
                left: auto;
                right: auto;
                bottom: auto;
                width: 100%;
                max-width: 100vw;
                z-index: 1;
                margin: 0;
                padding-top: 0.625rem;
                padding-left: max(0.5rem, var(--safe-left));
                padding-right: max(0.5rem, var(--safe-right));
                padding-bottom: max(0.5rem, var(--safe-bottom));
                background: var(--mobile-nav-bg);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border-top: 1px solid rgb(229 231 235);
                box-shadow: 0 -8px 32px rgba(0,0,0,.08);
                pointer-events: auto;
                transform: translate3d(0, 0, 0);
                -webkit-transform: translate3d(0, 0, 0);
                backface-visibility: hidden;
                -webkit-backface-visibility: hidden;
            }
            #mobileBottomNav .mobile-nav-bar {
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                align-items: end;
                gap: 0.375rem;
                width: 100%;
                min-height: 3.25rem;
            }
            #mobileBottomNav .mobile-nav-group {
                display: flex;
                align-items: flex-end;
                justify-content: space-around;
                gap: 0.125rem;
                min-width: 0;
            }
            #mobileBottomNav .mobile-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.125rem;
                min-width: 0;
                flex: 1 1 0;
                max-width: 4.75rem;
                min-height: 3rem;
                padding: 0.25rem 0.125rem;
                border: none;
                background: transparent;
                -webkit-tap-highlight-color: transparent;
            }
            #mobileBottomNav .mobile-nav-item i {
                font-size: 1.25rem;
                line-height: 1;
            }
            #mobileBottomNav .mobile-nav-item span {
                font-size: 0.5625rem;
                font-weight: 700;
                line-height: 1.1;
                text-align: center;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
            }
            #mobileBottomNav .mobile-nav-fab {
                position: relative;
                top: -0.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 3.25rem;
                height: 3.25rem;
                margin: 0 auto 0.125rem;
                border-radius: 1rem;
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                color: #fff;
                box-shadow: 0 8px 20px rgba(5, 150, 105, 0.4);
                flex-shrink: 0;
            }
            #mobileBottomNav .mobile-nav-fab:active {
                transform: scale(0.94);
            }
            .dark #mobileBottomNav {
                background: var(--mobile-nav-bg);
                border-top-color: rgb(61 52 46);
                box-shadow: 0 -8px 32px rgba(0,0,0,.4);
            }
            /* Mobil bildirim paneli */
            #notificationBackdrop {
                position: fixed;
                inset: 0;
                z-index: 110;
                background: rgba(15, 23, 42, 0.45);
                backdrop-filter: blur(2px);
                -webkit-backdrop-filter: blur(2px);
            }
            body.notification-panel-open {
                overflow: hidden;
            }
            body.notification-panel-open #appTopBar {
                z-index: 111;
            }
            body.notification-panel-open #mobileBottomNavHost {
                visibility: hidden;
                pointer-events: none;
            }
            #notificationDropdown.notification-panel-mobile {
                position: fixed !important;
                left: max(0.75rem, var(--safe-left)) !important;
                right: max(0.75rem, var(--safe-right)) !important;
                top: calc(max(0.75rem, var(--safe-top)) + 3.25rem) !important;
                bottom: calc(var(--mobile-nav-height) + 0.5rem) !important;
                width: auto !important;
                max-height: none !important;
                margin: 0 !important;
                z-index: 112 !important;
                display: flex !important;
                border-radius: 1rem;
                box-shadow: 0 24px 48px rgba(0, 0, 0, 0.18);
            }
            #notificationDropdown.notification-panel-mobile #notificationListWrap {
                min-height: 0;
                flex: 1 1 auto;
            }
            #notificationDropdown .notification-item {
                padding: 0.875rem 1rem;
            }
            #notificationDropdown .notification-item-clickable {
                cursor: pointer;
                -webkit-tap-highlight-color: transparent;
            }
            #notificationDropdown .notification-item-clickable:active {
                background-color: rgba(243, 244, 246, 0.95);
            }
            .dark #notificationDropdown .notification-item-clickable:active {
                background-color: rgba(55, 65, 81, 0.55);
            }
            #notificationDropdown .notification-item-icon {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.75rem;
            }
            #notificationDropdown .notification-item-title {
                font-size: 0.875rem;
                font-weight: 600;
                line-height: 1.35;
            }
            #notificationDropdown .notification-item-message {
                font-size: 0.8125rem;
                line-height: 1.45;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            #notificationBell {
                touch-action: manipulation;
            }
        }
        @media (min-width: 768px) { #mobileBottomNavHost, #mobileBottomNav { display: none !important; } }
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
    <div id="appShell" class="flex min-h-screen">
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
            <div id="appTopBar" class="flex-shrink-0 flex items-center justify-between md:justify-end gap-2 pl-4 pr-3 py-3 md:pl-6 md:px-6 lg:px-8 border-b border-gray-200 dark:border-[#3d342e] bg-white/95 dark:bg-[#241e1b]/95 backdrop-blur sticky top-0 z-20 min-h-[3.5rem]" style="padding-top: max(0.75rem, var(--safe-top));">
                <div class="flex items-center gap-2 min-w-0 md:mr-auto">
                    <?php if (!empty($companyLogoUrl)): ?>
                        <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="" class="h-8 w-auto object-contain flex-shrink-0 md:h-9" aria-hidden="true">
                    <?php endif; ?>
                    <span class="md:hidden text-sm font-semibold text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($projectName) ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($user && !empty($user['id'])): ?>
                    <div class="relative" id="notificationWrap">
                        <button type="button" id="notificationBell" class="relative p-3 md:p-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors min-h-[44px] min-w-[44px] md:min-h-0 md:min-w-0 flex items-center justify-center" title="Bildirimler" aria-expanded="false" aria-haspopup="true">
                            <i class="bi bi-bell text-xl md:text-lg" aria-hidden="true"></i>
                            <span id="notificationBadge" class="absolute top-1.5 right-1.5 md:top-1 md:right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center hidden">0</span>
                        </button>
                        <div id="notificationDropdown" class="hidden absolute right-0 top-full mt-2 w-[min(90vw,380px)] max-h-[min(70vh,420px)] flex flex-col rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-xl z-50 overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 shrink-0">
                                <span class="font-semibold text-gray-900 dark:text-white">Bildirimler</span>
                                <div class="flex items-center gap-1">
                                    <button type="button" id="notificationMarkAllRead" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 min-h-[44px] min-w-[44px] flex items-center justify-center" title="Tümünü okundu işaretle"><i class="bi bi-check-all"></i></button>
                                    <button type="button" id="notificationCloseMobile" class="md:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 min-h-[44px] min-w-[44px] flex items-center justify-center" title="Kapat" aria-label="Kapat"><i class="bi bi-x-lg"></i></button>
                                    <button type="button" id="notificationDeleteAll" class="p-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 min-h-[44px] min-w-[44px] flex items-center justify-center" title="Tümünü sil"><i class="bi bi-trash"></i></button>
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
                    <?php
                    $headerUserName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                    $headerUserLink = '/kullanicilar/' . ($user['id'] ?? '');
                    ?>
                    <a href="<?= htmlspecialchars($headerUserLink) ?>" class="flex items-center justify-center p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px] min-w-[44px] md:min-h-0 md:min-w-0 shrink-0" title="<?= htmlspecialchars($headerUserName) ?>" aria-label="Profilim">
                        <?php $userRow = $user; $size = 'sm'; require __DIR__ . '/partials/user_avatar.php'; ?>
                    </a>
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
    </div>

    <div id="notificationBackdrop" class="hidden md:hidden" aria-hidden="true"></div>

    <div id="mobileBottomNavHost" class="md:hidden" aria-hidden="false">
    <nav id="mobileBottomNav" aria-label="Hızlı erişim">
        <div class="mobile-nav-bar">
            <div class="mobile-nav-group">
                <a href="/genel-bakis" class="mobile-nav-item <?= $currentPath === '/genel-bakis' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' ?>">
                    <i class="bi bi-house"></i>
                    <span>Ana Sayfa</span>
                </a>
                <a href="/musteriler" class="mobile-nav-item <?= $currentPath === '/musteriler' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' ?>">
                    <i class="bi bi-people"></i>
                    <span>Müşteriler</span>
                </a>
            </div>
            <a href="/girisler?newSale=1" class="mobile-nav-fab" aria-label="Yeni depo sözleşmesi ekle">
                <i class="bi bi-plus-lg text-2xl"></i>
            </a>
            <div class="mobile-nav-group">
                <a href="/odemeler" class="mobile-nav-item <?= (strpos($currentPath, '/odemeler') === 0) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' ?>">
                    <i class="bi bi-credit-card"></i>
                    <span>Ödemeler</span>
                </a>
                <button type="button" id="mobileMenuOpenBtn" class="mobile-nav-item text-gray-500 dark:text-gray-400" aria-label="Menüyü aç">
                    <i class="bi bi-list"></i>
                    <span>Menü</span>
                </button>
            </div>
        </div>
    </nav>
    </div>
    <script>
    (function(){
        var host = document.getElementById('mobileBottomNavHost');
        var nav = document.getElementById('mobileBottomNav');
        if (!host || !nav) return;
        function isMobile() { return window.matchMedia('(max-width: 767px)').matches; }
        function pinMobileNav() {
            if (!isMobile()) return;
            if (host.parentNode !== document.body) {
                document.body.appendChild(host);
            }
        }
        function syncMobileNavViewport() {
            if (!isMobile()) {
                host.style.bottom = '';
                host.style.left = '';
                host.style.width = '';
                return;
            }
            if (window.visualViewport) {
                var vv = window.visualViewport;
                var bottomGap = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
                host.style.bottom = bottomGap + 'px';
                host.style.left = vv.offsetLeft + 'px';
                host.style.width = vv.width + 'px';
            } else {
                host.style.bottom = '0';
                host.style.left = '0';
                host.style.width = '100%';
            }
        }
        function syncMobileNavHeight() {
            if (!isMobile() || !nav) return;
            syncMobileNavViewport();
            var h = Math.ceil(host.getBoundingClientRect().height);
            if (h > 0 && h <= 160) {
                document.documentElement.style.setProperty('--mobile-nav-height', h + 'px');
            }
        }
        pinMobileNav();
        syncMobileNavHeight();
        window.addEventListener('resize', function() { pinMobileNav(); syncMobileNavHeight(); });
        window.addEventListener('orientationchange', function() {
            setTimeout(function() { pinMobileNav(); syncMobileNavHeight(); }, 120);
        });
        window.addEventListener('scroll', syncMobileNavViewport, { passive: true });
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', syncMobileNavHeight);
            window.visualViewport.addEventListener('scroll', syncMobileNavViewport);
        }
        if (typeof ResizeObserver !== 'undefined') {
            try {
                var ro = new ResizeObserver(syncMobileNavHeight);
                ro.observe(nav);
            } catch (e) {}
        }
    })();
    </script>
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
        function restoreModal(el) {
            if (!el._mountedToBody || !el._modalHome) return;
            if (el._modalNext && el._modalNext.parentNode === el._modalHome) {
                el._modalHome.insertBefore(el, el._modalNext);
            } else {
                el._modalHome.appendChild(el);
            }
            el._mountedToBody = false;
        }
        function mountModal(el) {
            if (el._mountedToBody || el.classList.contains('hidden')) return;
            if (!window.matchMedia('(max-width: 767px)').matches) return;
            if (el.parentNode === document.body) {
                el._mountedToBody = true;
                return;
            }
            el._modalHome = el.parentNode;
            el._modalNext = el.nextSibling;
            document.body.appendChild(el);
            el._mountedToBody = true;
        }
        function updateModalState() {
            document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(mountModal);
            document.querySelectorAll('.modal-overlay.hidden').forEach(restoreModal);
            var any = document.querySelector('.modal-overlay:not(.hidden)');
            var sidebarOpen = document.body.classList.contains('sidebar-open');
            document.body.classList.toggle('modal-open', !!any);
            if (any) {
                document.body.style.overflow = 'hidden';
            } else if (!sidebarOpen) {
                document.body.style.overflow = '';
            }
        }
        window.updateModalState = updateModalState;
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
        var closeMobileBtn = document.getElementById('notificationCloseMobile');
        var backdrop = document.getElementById('notificationBackdrop');
        var mobileMq = window.matchMedia('(max-width: 767px)');
        var dropdownHome = wrap;

        function isMobilePanel() {
            return mobileMq.matches;
        }

        function mountMobilePanel() {
            if (!isMobilePanel()) return;
            if (dropdown.parentNode !== document.body) {
                document.body.appendChild(dropdown);
            }
            if (backdrop && backdrop.parentNode !== document.body) {
                document.body.appendChild(backdrop);
            }
        }

        function unmountMobilePanel() {
            if (dropdown.parentNode === document.body && dropdownHome) {
                dropdownHome.appendChild(dropdown);
            }
        }

        function escHtml(s) {
            return String(s || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

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
                var goHref = n.id ? ('/bildirimler/' + encodeURIComponent(n.id) + '/git') : (n.url || '/bildirimler');
                li.className = 'notification-item notification-item-clickable border-b border-gray-100 dark:border-gray-700/80 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 ' + (read ? '' : 'bg-emerald-50/60 dark:bg-emerald-900/15');
                li.dataset.href = goHref;
                li.setAttribute('role', 'link');
                li.setAttribute('tabindex', '0');
                var time = n.created_at ? new Date(n.created_at).toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
                var actor = (n.metadata && n.metadata.actor_name) ? ' · ' + n.metadata.actor_name : '';
                var iconBg = read ? 'bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-300' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400';
                li.innerHTML =
                    '<div class="flex items-start gap-3">' +
                        '<div class="notification-item-icon flex-shrink-0 flex items-center justify-center ' + iconBg + '"><i class="bi bi-' + icon + ' text-base"></i></div>' +
                        '<div class="flex-1 min-w-0">' +
                            '<p class="notification-item-title text-gray-900 dark:text-white">' + escHtml(n.title) + '</p>' +
                            '<p class="notification-item-message text-gray-600 dark:text-gray-400 mt-1">' + escHtml(n.message) + escHtml(actor) + '</p>' +
                            '<p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5">' + escHtml(time) + '</p>' +
                        '</div>' +
                        (read ? '' : '<span class="flex-shrink-0 mt-1.5 w-2 h-2 rounded-full bg-emerald-500" aria-hidden="true"></span>') +
                    '</div>';
                listEl.appendChild(li);
            });
        }

        function followNotificationLink(li) {
            if (!li || !li.dataset.href) return;
            closeDropdown();
            window.location.assign(li.dataset.href);
        }

        function onNotificationItemActivate(e) {
            var li = e.target.closest('li[data-href]');
            if (!li) return;
            e.preventDefault();
            followNotificationLink(li);
        }

        listEl.addEventListener('click', onNotificationItemActivate);
        listEl.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var li = e.target.closest('li[data-href]');
            if (!li) return;
            e.preventDefault();
            followNotificationLink(li);
        });

        function openDropdown() {
            mountMobilePanel();
            dropdown.classList.remove('hidden');
            bell.setAttribute('aria-expanded', 'true');
            if (isMobilePanel()) {
                dropdown.classList.add('notification-panel-mobile');
                document.body.classList.add('notification-panel-open');
                if (backdrop) {
                    backdrop.classList.remove('hidden');
                    backdrop.setAttribute('aria-hidden', 'false');
                }
            }
            listEl.innerHTML = '';
            listLoading.classList.remove('hidden');
            listEmpty.classList.add('hidden');
            fetchNotifications(function(list){ renderList(list); });
        }

        function closeDropdown() {
            dropdown.classList.add('hidden');
            dropdown.classList.remove('notification-panel-mobile');
            document.body.classList.remove('notification-panel-open');
            if (backdrop) {
                backdrop.classList.add('hidden');
                backdrop.setAttribute('aria-hidden', 'true');
            }
            bell.setAttribute('aria-expanded', 'false');
            unmountMobilePanel();
        }

        function toggleDropdown(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if (dropdown.classList.contains('hidden')) openDropdown();
            else closeDropdown();
        }

        bell.addEventListener('click', toggleDropdown);

        wrap.addEventListener('click', function(e){ e.stopPropagation(); });
        document.documentElement.addEventListener('click', function(){
            if (!isMobilePanel()) closeDropdown();
        });

        if (backdrop) backdrop.addEventListener('click', closeDropdown);
        if (closeMobileBtn) closeMobileBtn.addEventListener('click', function(e){
            e.stopPropagation();
            closeDropdown();
        });

        mobileMq.addEventListener('change', function(){
            if (!mobileMq.matches) closeDropdown();
        });

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
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            if (window.matchMedia('(max-width: 767px)').matches && el.parentNode !== document.body && !el._mountedToBody) {
                el._modalHome = el.parentNode;
                el._modalNext = el.nextSibling;
                document.body.appendChild(el);
                el._mountedToBody = true;
            }
            var first = el.querySelector('input:not([type="hidden"]), select, textarea');
            if (first) setTimeout(function(){ first.focus(); }, 80);
        };
        window.closeFilterModal = function(id) {
            var el = document.getElementById(id || 'pageFilterModal');
            if (!el) return;
            el.classList.add('hidden');
            el.setAttribute('aria-hidden', 'true');
            if (el._mountedToBody && el._modalHome) {
                if (el._modalNext && el._modalNext.parentNode === el._modalHome) {
                    el._modalHome.insertBefore(el, el._modalNext);
                } else {
                    el._modalHome.appendChild(el);
                }
                el._mountedToBody = false;
            }
            if (window.updateModalState) {
                window.updateModalState();
            } else if (!document.querySelector('.filter-modal-overlay:not(.hidden), .modal-overlay:not(.hidden)') && !document.body.classList.contains('sidebar-open')) {
                document.body.classList.remove('modal-open');
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
