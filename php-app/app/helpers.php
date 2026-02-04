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
