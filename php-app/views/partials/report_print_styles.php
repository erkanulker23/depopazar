<style>
.print-only { display: none; }
@media print {
    @page {
        size: A4 landscape;
        margin: 12mm 14mm;
    }
    html, body {
        background: #fff !important;
        color: #111 !important;
        font-size: 10pt;
    }
    #appShell { display: block !important; }
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
    .modal-overlay {
        display: none !important;
    }
    .print-only { display: block !important; }
    .print-only span,
    .print-only.inline { display: inline !important; }
    .main-content-wrap {
        padding: 0 !important;
        margin: 0 !important;
    }
    #report-content { padding: 0; }
    .report-print-header {
        border-bottom: 2px solid #047857;
        padding-bottom: 14px;
        margin-bottom: 18px;
        break-inside: avoid;
    }
    .report-print-brand h1 {
        font-size: 20pt;
        color: #047857;
        margin: 0 0 4px;
        font-weight: 700;
        line-height: 1.2;
    }
    .report-print-company {
        font-size: 11pt;
        color: #374151;
        margin: 0;
    }
    .report-print-meta {
        margin-top: 10px;
        font-size: 9.5pt;
        color: #4b5563;
        line-height: 1.55;
        display: flex;
        flex-wrap: wrap;
        gap: 4px 18px;
    }
    .report-print-meta p { margin: 0; }
    .report-print-summary {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
        font-size: 9.5pt;
    }
    .report-print-summary th,
    .report-print-summary td {
        border: 1px solid #d1d5db;
        padding: 7px 10px;
        text-align: center;
    }
    .report-print-summary th {
        background: #ecfdf5 !important;
        color: #047857;
        font-weight: 700;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .report-print-section {
        margin-bottom: 16px;
        break-inside: avoid;
    }
    .report-print-section h2 {
        font-size: 11pt;
        color: #047857;
        margin: 0 0 8px;
        font-weight: 700;
    }
    .report-print-table-wrap,
    .report-print-section table {
        box-shadow: none !important;
        border: none !important;
        break-inside: auto;
    }
    .report-data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
    }
    .report-data-table th {
        background: #047857 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        padding: 7px 5px;
        border: 1px solid #065f46;
        text-align: left;
        font-weight: 700;
    }
    .report-data-table td {
        border: 1px solid #d1d5db;
        padding: 5px;
        vertical-align: top;
        color: #111 !important;
    }
    .report-data-table tbody tr:nth-child(even) td {
        background: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .report-data-table tfoot td {
        border-top: 2px solid #047857;
        background: #ecfdf5 !important;
        font-weight: 700;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .report-data-table .rounded-full {
        border-radius: 0 !important;
        padding: 1px 4px !important;
        background: transparent !important;
        color: #111 !important;
        border: 1px solid #9ca3af;
    }
    .mobile-card,
    .card-modern,
    .stat-card {
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
    }
}
</style>
