<?php
if (!function_exists('fmtPrice')) {
    function fmtPrice($n) {
        if ($n === null || $n === '') return '';
        $f = (float) $n;
        return ($f == (int)$f ? number_format((int)$f, 0, '', '.') : number_format($f, 2, ',', '.')) . ' ₺';
    }
}

/** TC Kimlik No: 11 haneli rakam */
if (!function_exists('validateTcIdentity')) {
    function validateTcIdentity(?string $value): bool {
        if ($value === null || $value === '') return true;
        $v = preg_replace('/\s/', '', $value);
        return preg_match('/^\d{11}$/', $v) === 1;
    }
}

/** Türkiye telefon formatı: 05xx xxx xx xx veya +90 5xx xxx xx xx */
if (!function_exists('validatePhone')) {
    function validatePhone(?string $value): bool {
        if ($value === null || $value === '') return true;
        $v = preg_replace('/[\s\-\(\)\.]/', '', $value);
        return preg_match('/^(\+90|0)?5\d{9}$/', $v) === 1;
    }
}

/** Telefon formatına çevir (girişten) */
if (!function_exists('formatPhoneInput')) {
    function formatPhoneInput(?string $value): ?string {
        if ($value === null || $value === '') return null;
        $v = preg_replace('/\D/', '', $value);
        if (strlen($v) === 10 && $v[0] === '5') return '0' . $v;
        if (strlen($v) === 11 && substr($v, 0, 2) === '90') return '0' . substr($v, 2);
        if (strlen($v) >= 10) return $v;
        return $value;
    }
}

/** E-posta formatı */
if (!function_exists('validateEmail')) {
    function validateEmail(?string $value): bool {
        if ($value === null || $value === '') return true;
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/** Tarih için "X yıl Y ay Z gün önce" Türkçe metni */
if (!function_exists('timeAgoTr')) {
    function timeAgoTr(?string $datetime): string {
        if ($datetime === null || $datetime === '') return '';
        $ts = strtotime($datetime);
        if ($ts === false) return '';
        $diff = time() - $ts;
        if ($diff < 60) return 'az önce';
        if ($diff < 3600) return floor($diff / 60) . ' dakika önce';
        if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
        $days = (int) floor($diff / 86400);
        if ($days < 30) return $days . ' gün önce';
        $months = (int) floor($days / 30);
        if ($months < 12) return $months . ' ay önce';
        $years = (int) floor($months / 12);
        $remainMonths = $months % 12;
        $remainDays = $days % 30;
        $parts = [];
        if ($years > 0) $parts[] = $years . ' yıl';
        if ($remainMonths > 0) $parts[] = $remainMonths . ' ay';
        if ($remainDays > 0 && $years < 2) $parts[] = $remainDays . ' gün';
        return implode(' ', $parts) . ' önce';
    }
}

/** Sayfalama çubuğu HTML üretir. $keepParams: sayfa değişirken korunacak GET parametreleri (örn. ['q' => 'arama']) */
if (!function_exists('renderPagination')) {
    function renderPagination(int $total, int $perPage, int $currentPage, string $baseUrl, array $keepParams = []): string
    {
        if ($total <= $perPage) {
            return '';
        }
        $totalPages = (int) ceil($total / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $keepParams = array_filter($keepParams, fn($v) => $v !== '' && $v !== null);

        $url = function (int $p) use ($baseUrl, $keepParams) {
            $params = $keepParams;
            $params['page'] = $p;
            $qs = http_build_query($params);
            return $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . $qs;
        };

        $html = '<nav class="flex items-center justify-between gap-2 py-3 px-4 border-t border-gray-200 dark:border-gray-600" aria-label="Sayfalama">';
        $html .= '<p class="text-sm text-gray-500 dark:text-gray-400">';
        $from = ($currentPage - 1) * $perPage + 1;
        $to = min($currentPage * $perPage, $total);
        $html .= $from . '–' . $to . ' / ' . $total . ' kayıt</p>';
        $html .= '<div class="flex items-center gap-1 flex-wrap justify-end">';

        if ($currentPage > 1) {
            $html .= '<a href="' . htmlspecialchars($url(1)) . '" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="İlk"><i class="bi bi-chevron-double-left"></i></a>';
            $html .= '<a href="' . htmlspecialchars($url($currentPage - 1)) . '" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="Önceki"><i class="bi bi-chevron-left"></i></a>';
        }

        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $currentPage;
            $html .= '<a href="' . htmlspecialchars($url($i)) . '" class="px-3 py-1.5 rounded-lg text-sm font-medium ' . ($active ? 'bg-emerald-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700') . '">' . $i . '</a>';
        }

        if ($currentPage < $totalPages) {
            $html .= '<a href="' . htmlspecialchars($url($currentPage + 1)) . '" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="Sonraki"><i class="bi bi-chevron-right"></i></a>';
            $html .= '<a href="' . htmlspecialchars($url($totalPages)) . '" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="Son"><i class="bi bi-chevron-double-right"></i></a>';
        }
        $html .= '</div></nav>';
        return $html;
    }
}
