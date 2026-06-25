<?php
if (!function_exists('fmtPrice')) {
    function fmtPrice($n) {
        if ($n === null || $n === '') return '';
        $f = (float) $n;
        return ($f == (int)$f ? number_format((int)$f, 0, '', '.') : number_format($f, 2, ',', '.')) . ' ₺';
    }
}

/** Tarih gösterimi (vade, sözleşme dönemi vb.) */
if (!function_exists('fmtDate')) {
    function fmtDate(?string $value, string $fallback = '–'): string
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }
        $ts = strtotime(trim($value));
        return $ts !== false ? date('d.m.Y', $ts) : $fallback;
    }
}

/** Tarih + saat gösterimi (kayıt, tahsilat, işlem zamanı) */
if (!function_exists('fmtDateTime')) {
    function fmtDateTime(?string $value, string $fallback = '–'): string
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }
        $ts = strtotime(trim($value));
        return $ts !== false ? date('d.m.Y H:i', $ts) : $fallback;
    }
}

/** datetime-local input varsayılan değeri */
if (!function_exists('fmtDateTimeLocalInput')) {
    function fmtDateTimeLocalInput(?string $value = null): string
    {
        $ts = ($value !== null && trim($value) !== '') ? strtotime(trim($value)) : time();
        return $ts !== false ? date('Y-m-d\TH:i', $ts) : date('Y-m-d\TH:i');
    }
}

/** TC Kimlik No / Müşteri numarası: en fazla 11 haneli rakam (boş bırakılabilir) */
if (!function_exists('validateTcIdentity')) {
    function validateTcIdentity(?string $value): bool {
        if ($value === null || $value === '') return true;
        $v = preg_replace('/\s/', '', $value);
        return preg_match('/^\d{1,11}$/', $v) === 1;
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

/** Telefon formatına çevir (girişten) - veritabanı için normalize */
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

/** Telefonu ekranda göstermek için maskeli formata çevir (05xx xxx xx xx) */
if (!function_exists('formatPhoneDisplay')) {
    function formatPhoneDisplay(?string $value): string {
        if ($value === null || $value === '') return '';
        $v = preg_replace('/\D/', '', $value);
        if (strlen($v) === 10 && $v[0] === '5') $v = '0' . $v;
        if (strlen($v) === 11 && substr($v, 0, 2) === '90') $v = '0' . substr($v, 2);
        if (strlen($v) !== 11 || $v[0] !== '0' || $v[1] !== '5') return $value;
        return $v[0] . ' ' . substr($v, 1, 3) . ' ' . substr($v, 4, 3) . ' ' . substr($v, 7, 2) . ' ' . substr($v, 9, 2);
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

/** Ödeme yöntemi etiketi (DB değerinden okunabilir metin) */
if (!function_exists('paymentMethodLabel')) {
    function paymentMethodLabel(?string $method): string {
        $m = strtolower(trim((string) $method));
        if ($m === '' ) return '–';
        if (in_array($m, ['cash', 'nakit', 'bank_transfer', 'havale', 'banka'], true)) {
            return 'Havale / EFT';
        }
        if (str_contains($m, 'kredi') || $m === 'credit_card') {
            return 'Kredi Kartı';
        }
        return $method;
    }
}

/**
 * Ödeme vadesinden önce tahsil edilmiş mi (paid_at < due_date).
 */
if (!function_exists('paymentIsEarly')) {
    function paymentIsEarly(array $payment): bool
    {
        if (($payment['status'] ?? '') !== 'paid') {
            return false;
        }
        $paidRaw = trim((string) ($payment['paid_at'] ?? ''));
        $dueRaw = trim((string) ($payment['due_date'] ?? ''));
        if ($paidRaw === '' || $dueRaw === '') {
            return false;
        }
        $paidDay = strtotime(explode(' ', $paidRaw)[0]);
        $dueDay = strtotime(explode(' ', $dueRaw)[0]);
        return $paidDay !== false && $dueDay !== false && $paidDay < $dueDay;
    }
}

/** Vadeden kaç gün önce ödendi (erken değilse 0) */
if (!function_exists('paymentDaysEarly')) {
    function paymentDaysEarly(array $payment): int
    {
        if (!paymentIsEarly($payment)) {
            return 0;
        }
        $paidDay = strtotime(explode(' ', trim((string) ($payment['paid_at'] ?? '')))[0]);
        $dueDay = strtotime(explode(' ', trim((string) ($payment['due_date'] ?? '')))[0]);
        return (int) max(0, ($dueDay - $paidDay) / 86400);
    }
}

/**
 * Ödeme durumu etiketi ve rozet sınıfı (vade tarihine göre; DB overdue status kullanılmaz).
 * @return array{label: string, badge: string, collectible: bool, early?: bool, days_early?: int}
 */
if (!function_exists('paymentStatusDisplay')) {
    function paymentStatusDisplay(array $payment): array
    {
        $status = $payment['status'] ?? 'pending';
        $dueRaw = trim((string) ($payment['due_date'] ?? ''));
        $dueDay = $dueRaw !== '' ? strtotime(explode(' ', $dueRaw)[0]) : false;
        $todayStart = strtotime(date('Y-m-d'));

        if ($status === 'paid') {
            $early = paymentIsEarly($payment);
            $daysEarly = $early ? paymentDaysEarly($payment) : 0;
            return [
                'label' => $early ? ('Erken ödendi' . ($daysEarly > 0 ? ' (' . $daysEarly . ' gün)' : '')) : 'Ödendi',
                'badge' => $early
                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                    : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                'collectible' => false,
                'early' => $early,
                'days_early' => $daysEarly,
            ];
        }
        if ($status === 'cancelled') {
            return [
                'label' => 'İptal',
                'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300',
                'collectible' => false,
            ];
        }
        if ($status === 'overdue' || ($status === 'pending' && $dueDay !== false && $dueDay < $todayStart)) {
            return [
                'label' => 'Vadesi geçmiş',
                'badge' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                'collectible' => true,
            ];
        }
        if ($status === 'pending' && $dueDay !== false && $dueDay > $todayStart) {
            return [
                'label' => 'Vadesi gelmemiş',
                'badge' => 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-300',
                'collectible' => true,
            ];
        }
        return [
            'label' => 'Bekliyor',
            'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'collectible' => true,
        ];
    }
}

/** Tahsil edilebilir mi (pending ve vadesi gelmemiş dahil) */
if (!function_exists('paymentIsCollectible')) {
    function paymentIsCollectible(array $payment): bool
    {
        return paymentStatusDisplay($payment)['collectible'];
    }
}

/** Liste filtresi: status GET parametresine göre ödeme eşleşir mi */
if (!function_exists('paymentMatchesStatusFilter')) {
    function paymentMatchesStatusFilter(array $payment, string $filter): bool
    {
        $display = paymentStatusDisplay($payment);
        $dbStatus = $payment['status'] ?? 'pending';
        return match ($filter) {
            'paid' => $dbStatus === 'paid',
            'early' => paymentIsEarly($payment),
            'cancelled' => $dbStatus === 'cancelled',
            'overdue' => $display['label'] === 'Vadesi geçmiş',
            'pending' => in_array($display['label'], ['Bekliyor', 'Vadesi gelmemiş'], true),
            'unpaid' => $display['collectible'],
            default => true,
        };
    }
}

/** Depo girişi — girilen eşyanın durumu seçenekleri */
if (!function_exists('storedItemsConditionOptions')) {
    function storedItemsConditionOptions(): array
    {
        return [
            'sifir' => 'Sıfır',
            'paketlenmis' => 'Paketlenmiş',
            'ikinci_el' => 'İkinci el',
            'hasarli' => 'Hasarlı',
        ];
    }
}

if (!function_exists('storedItemsConditionLabel')) {
    function storedItemsConditionLabel(?string $code): string
    {
        if ($code === null || $code === '') {
            return '-';
        }
        return storedItemsConditionOptions()[$code] ?? $code;
    }
}

/**
 * POST'tan ürün durumu doğrular.
 * @return array{0: ?string, 1: ?string, 2: ?string} [condition, note, errorMessage]
 */
if (!function_exists('parseStoredItemsConditionFromRequest')) {
    function parseStoredItemsConditionFromRequest(array $post, bool $required = true): array
    {
        $allowed = array_keys(storedItemsConditionOptions());
        $condition = trim((string) ($post['stored_items_condition'] ?? ''));
        if ($condition === '') {
            if ($required) {
                return [null, null, 'Giriş yapılan ürün durumu seçilmelidir.'];
            }
            return [null, null, null];
        }
        if (!in_array($condition, $allowed, true)) {
            return [null, null, 'Geçersiz ürün durumu.'];
        }
        $note = trim((string) ($post['stored_items_condition_note'] ?? ''));
        if ($condition === 'hasarli' && $note === '') {
            return [null, null, 'Hasarlı ürünler için hasar notu zorunludur.'];
        }
        if ($condition !== 'hasarli') {
            $note = '';
        }
        return [$condition, $note !== '' ? $note : null, null];
    }
}

/** Manuel borç (customer_charges) durum etiketi */
if (!function_exists('chargeStatusDisplay')) {
    function chargeStatusDisplay(array $charge): array
    {
        $status = $charge['status'] ?? 'pending';
        $dueRaw = trim((string) ($charge['due_date'] ?? ''));
        $dueDay = $dueRaw !== '' ? strtotime($dueRaw) : false;
        $todayStart = strtotime(date('Y-m-d'));

        if ($status === 'paid') {
            return ['label' => 'Ödendi', 'badge' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', 'collectible' => false];
        }
        if ($status === 'cancelled') {
            return ['label' => 'İptal', 'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300', 'collectible' => false];
        }
        if ($dueDay !== false && $dueDay < $todayStart) {
            return ['label' => 'Vadesi geçmiş', 'badge' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', 'collectible' => true];
        }
        if ($dueDay !== false && $dueDay > $todayStart) {
            return ['label' => 'Vadesi gelmemiş', 'badge' => 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-300', 'collectible' => true];
        }
        return ['label' => 'Bekliyor', 'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300', 'collectible' => true];
    }
}

/** POST paid_at (Y-m-d, datetime-local veya datetime) → MySQL datetime; boşsa şimdi */
if (!function_exists('normalizePaidAt')) {
    function normalizePaidAt(?string $paidAt): string
    {
        $paidAt = trim((string) $paidAt);
        if ($paidAt === '') {
            return date('Y-m-d H:i:s');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $paidAt)) {
            return $paidAt . ' ' . date('H:i:s');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $paidAt)) {
            $paidAt = str_replace('T', ' ', $paidAt);
            if (strlen($paidAt) === 16) {
                $paidAt .= ':00';
            }
        }
        $ts = strtotime($paidAt);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
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
