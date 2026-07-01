<style>
.print-only { display: none; }
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm 8mm;
    }
    html, body {
        background: #fff !important;
        color: #111 !important;
        font-size: 9pt;
        width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    #appShell,
    body > .flex.min-h-screen,
    .main-shell,
    .main-content-wrap,
    main,
    #report-content {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
    }
    #sidebar,
    #sidebarOverlay,
    #appTopBar,
    #mobileBottomNavHost,
    #mobileBottomNav,
    #notificationBackdrop,
    #notificationWrap,
    #notificationDropdown,
    #pwaInstallBanner,
    #pushBanner,
    .screen-only,
    .no-print,
    .page-toolbar,
    .page-filter-modal,
    .filter-modal-overlay,
    .modal-overlay,
    .col-print-hide {
        display: none !important;
    }
    .print-only { display: block !important; }
    .print-only span,
    .print-only.inline { display: inline !important; }
    .overflow-x-auto,
    .table-scroll,
    .table-responsive,
    .mobile-card,
    .report-print-table-wrap {
        overflow: visible !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    .report-print-header {
        border-bottom: 2px solid #047857;
        padding-bottom: 8px;
        margin-bottom: 10px;
        break-inside: avoid;
        break-after: avoid;
        page-break-inside: avoid;
        page-break-after: avoid;
    }
    .report-print-brand h1 {
        font-size: 14pt;
        color: #047857;
        margin: 0 0 2px;
        font-weight: 700;
        line-height: 1.2;
    }
    .report-print-company {
        font-size: 9pt;
        color: #374151;
        margin: 0;
    }
    .report-print-meta {
        margin-top: 6px;
        font-size: 8pt;
        color: #4b5563;
        line-height: 1.4;
        display: flex;
        flex-wrap: wrap;
        gap: 2px 12px;
    }
    .report-print-meta p { margin: 0; }
    .report-print-summary {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        font-size: 8pt;
        table-layout: fixed;
    }
    .report-print-summary th,
    .report-print-summary td {
        border: 1px solid #d1d5db;
        padding: 4px 6px;
        text-align: center;
        word-wrap: break-word;
    }
    .report-print-summary th {
        background: #ecfdf5 !important;
        color: #047857;
        font-weight: 700;
    }
    .report-print-section {
        margin-bottom: 10px;
        break-inside: auto;
        page-break-inside: auto;
    }
    .report-print-section h2 {
        font-size: 10pt;
        color: #047857;
        margin: 0 0 6px;
        font-weight: 700;
        break-after: avoid;
        page-break-after: avoid;
    }
    .report-print-table-wrap,
    .report-print-section table {
        box-shadow: none !important;
        border: none !important;
    }
    .report-data-table {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 7pt;
        line-height: 1.25;
    }
    .report-data-table thead {
        display: table-header-group;
    }
    .report-data-table tfoot {
        display: table-footer-group;
    }
    .report-data-table th {
        background: #047857 !important;
        color: #fff !important;
        padding: 4px 3px;
        border: 1px solid #065f46;
        text-align: left;
        font-weight: 700;
        font-size: 6.5pt;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    .report-data-table td {
        border: 1px solid #d1d5db;
        padding: 3px;
        vertical-align: top;
        color: #111 !important;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    .report-data-table tbody tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .report-data-table tbody tr:nth-child(even) td {
        background: #f9fafb !important;
    }
    .report-data-table tfoot td {
        border-top: 2px solid #047857;
        background: #ecfdf5 !important;
        font-weight: 700;
    }
    .report-data-table .rounded-full {
        border-radius: 0 !important;
        padding: 0 !important;
        background: transparent !important;
        color: #111 !important;
        border: none !important;
        font-size: inherit !important;
        font-weight: inherit !important;
    }
    .report-data-table a {
        color: #111 !important;
        text-decoration: none !important;
    }
    .mobile-card,
    .card-modern,
    .stat-card {
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
    }
    .report-page-break {
        break-before: page;
        page-break-before: always;
    }
}
</style>
