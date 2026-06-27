<?php
if (!function_exists('fmtPrice')) {
    function fmtPrice($n) {
        if ($n === null || $n === '') return '';
        $f = (float) $n;
        return ($f == (int)$f ? number_format((int)$f, 0, '', '.') : number_format($f, 2, ',', '.')) . ' ₺';
    }
}

/** Oda numarası eşleştirme anahtarı (1 = 01 = 001) */
if (!function_exists('normalizeRoomNumberKey')) {
    function normalizeRoomNumberKey(?string $roomNumber): string
    {
        $value = trim((string) $roomNumber);
        if ($value === '') {
            return '';
        }
        if (ctype_digit($value)) {
            return (string) (int) $value;
        }
        return mb_strtolower($value);
    }
}

/** Oda numarası ekranda (ham değer; 1 ve 01 karıştırılmasın diye pad yok) */
if (!function_exists('fmtRoomNumber')) {
    function fmtRoomNumber(?string $roomNumber): string
    {
        $value = trim((string) $roomNumber);
        return $value !== '' ? $value : '–';
    }
}

/** UUID biçiminde mi (müşteri içe aktarma) */
if (!function_exists('isUuidString')) {
    function isUuidString(?string $value): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }
}

/** Müşteri CSV başlık satırı mı */
if (!function_exists('isCustomerCsvHeaderRow')) {
    function isCustomerCsvHeaderRow(array $row): bool
    {
        $c0 = mb_strtolower(trim($row[0] ?? ''));
        $c1 = mb_strtolower(trim($row[1] ?? ''));
        if ($c0 === 'ad' || (str_contains($c0, 'ad') && str_contains($c1, 'soyad'))) {
            return true;
        }
        if (str_contains($c0, 'müşteri') && str_contains($c0, 'id')) {
            return true;
        }
        if (str_contains($c0, 'musteri') && str_contains($c0, 'id')) {
            return true;
        }
        if ($c0 === 'id' || $c0 === 'müşteri id' || $c0 === 'musteri id') {
            return true;
        }
        return false;
    }
}

/**
 * Müşteri CSV satırını ayrıştır.
 * @return array<string, mixed>|null
 */
if (!function_exists('parseCustomerImportRow')) {
    function parseCustomerImportRow(array $row): ?array
    {
        $row = array_map(static fn($v) => trim((string) $v), $row);
        if (count($row) < 2 || isCustomerCsvHeaderRow($row)) {
            return null;
        }

        $customerId = null;
        $externalId = null;
        $isActive = 1;

        if (count($row) >= 9 && isUuidString($row[0] ?? '')) {
            $customerId = $row[0];
            $firstName = $row[1] ?? '';
            $lastName = $row[2] ?? '';
            $email = $row[3] ?? '';
            $phone = ($row[4] ?? '') !== '' ? $row[4] : null;
            $identityNumber = ($row[5] ?? '') !== '' ? $row[5] : null;
            $address = ($row[6] ?? '') !== '' ? $row[6] : null;
            $notes = ($row[7] ?? '') !== '' ? $row[7] : null;
            if (isset($row[8])) {
                $v = trim($row[8]);
                if (stripos($v, 'hayır') !== false || $v === '0' || strtolower($v) === 'no') {
                    $isActive = 0;
                }
            }
        } elseif (count($row) >= 9 && ($row[0] ?? '') === '') {
            $firstName = $row[1] ?? '';
            $lastName = $row[2] ?? '';
            $email = $row[3] ?? '';
            $phone = ($row[4] ?? '') !== '' ? $row[4] : null;
            $identityNumber = ($row[5] ?? '') !== '' ? $row[5] : null;
            $address = ($row[6] ?? '') !== '' ? $row[6] : null;
            $notes = ($row[7] ?? '') !== '' ? $row[7] : null;
            if (isset($row[8])) {
                $v = trim($row[8]);
                if (stripos($v, 'hayır') !== false || $v === '0' || strtolower($v) === 'no') {
                    $isActive = 0;
                }
            }
        } elseif (count($row) >= 9 && ($row[0] ?? '') !== '') {
            $externalId = $row[0];
            $firstName = $row[1] ?? '';
            $lastName = $row[2] ?? '';
            $email = $row[3] ?? '';
            $phone = ($row[4] ?? '') !== '' ? $row[4] : null;
            $identityNumber = ($row[5] ?? '') !== '' ? $row[5] : null;
            $address = ($row[6] ?? '') !== '' ? $row[6] : null;
            $notes = ($row[7] ?? '') !== '' ? $row[7] : null;
            if (isset($row[8])) {
                $v = trim($row[8]);
                if (stripos($v, 'hayır') !== false || $v === '0' || strtolower($v) === 'no') {
                    $isActive = 0;
                }
            }
        } else {
            $firstName = $row[0] ?? '';
            $lastName = $row[1] ?? '';
            $email = $row[2] ?? '';
            $phone = ($row[3] ?? '') !== '' ? $row[3] : null;
            $identityNumber = ($row[4] ?? '') !== '' ? $row[4] : null;
            $address = ($row[5] ?? '') !== '' ? $row[5] : null;
            $notes = ($row[6] ?? '') !== '' ? $row[6] : null;
            if (isset($row[7])) {
                $v = trim($row[7]);
                if (stripos($v, 'hayır') !== false || $v === '0' || strtolower($v) === 'no') {
                    $isActive = 0;
                }
            }
        }

        if ($firstName === '' && $lastName === '') {
            return null;
        }

        return [
            'customer_id' => $customerId,
            'external_id' => $externalId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'identity_number' => $identityNumber,
            'address' => $address,
            'notes' => $notes,
            'is_active' => $isActive,
        ];
    }
}

/** İçe aktarma dosyasında tekrar eşleşmesi için anahtar */
if (!function_exists('customerImportMatchKey')) {
    function customerImportMatchKey(?string $customerId, ?string $externalId, ?string $email, ?string $phone, ?string $identityNumber): ?string
    {
        if ($customerId !== null && $customerId !== '') {
            return 'id:' . strtolower($customerId);
        }
        if ($externalId !== null && $externalId !== '') {
            return 'ext:' . $externalId;
        }
        $email = mb_strtolower(trim((string) $email));
        if ($email !== '') {
            return 'email:' . $email;
        }
        $phoneDigits = normalizePhoneDigits($phone);
        if ($phoneDigits !== null) {
            return 'phone:' . $phoneDigits;
        }
        $identityNumber = trim((string) $identityNumber);
        if ($identityNumber !== '') {
            return 'tc:' . $identityNumber;
        }
        return null;
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

/** Ham telefon → sadece rakamlar (05xxxxxxxxx hedef) */
if (!function_exists('normalizePhoneDigits')) {
    function normalizePhoneDigits(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $v = preg_replace('/\D/', '', $value);
        if ($v === '') {
            return null;
        }
        if (strlen($v) >= 12 && str_starts_with($v, '90')) {
            $v = substr($v, 2);
        }
        if (strlen($v) === 10 && $v[0] === '5') {
            $v = '0' . $v;
        }
        if ($v[0] !== '0') {
            $v = '0' . $v;
        }
        if (strlen($v) > 11) {
            $v = substr($v, 0, 11);
        }
        return $v;
    }
}

/** Türkiye telefon formatı: tam 11 hane 05xxxxxxxxx */
if (!function_exists('validatePhone')) {
    function validatePhone(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }
        $v = normalizePhoneDigits($value);
        return $v !== null && strlen($v) === 11 && $v[0] === '0' && $v[1] === '5';
    }
}

/** Telefon formatına çevir (girişten) - veritabanı için normalize; eksik numara null */
if (!function_exists('formatPhoneInput')) {
    function formatPhoneInput(?string $value): ?string
    {
        $v = normalizePhoneDigits($value);
        if ($v === null) {
            return null;
        }
        if (strlen($v) === 11 && $v[0] === '0' && $v[1] === '5') {
            return $v;
        }
        return null;
    }
}

/** Telefonu ekranda göstermek için maskeli formata çevir (05xx xxx xx xx) */
if (!function_exists('formatPhoneDisplay')) {
    function formatPhoneDisplay(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $v = normalizePhoneDigits($value);
        if ($v === null || strlen($v) !== 11 || $v[0] !== '0' || $v[1] !== '5') {
            return trim($value);
        }
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

/** Depo eşya listesi — satır bazında ürün durumu */
if (!function_exists('itemConditionOptions')) {
    function itemConditionOptions(): array
    {
        return [
            'sifir' => 'Sıfır',
            'koli' => 'Koli',
            'kullanilmis' => 'Kullanılmış',
            'hasarli' => 'Hasarlı',
        ];
    }
}

if (!function_exists('normalizeItemCondition')) {
    function normalizeItemCondition(?string $code): string
    {
        $code = trim((string) $code);
        $legacy = [
            'new' => 'sifir',
            'paketlenmis' => 'koli',
            'ikinci_el' => 'kullanilmis',
        ];
        if (isset($legacy[$code])) {
            return $legacy[$code];
        }
        $allowed = array_keys(itemConditionOptions());
        return in_array($code, $allowed, true) ? $code : 'sifir';
    }
}

if (!function_exists('itemConditionLabel')) {
    function itemConditionLabel(?string $code): string
    {
        if ($code === null || trim($code) === '') {
            return '-';
        }
        return itemConditionOptions()[normalizeItemCondition($code)] ?? $code;
    }
}

if (!function_exists('itemConditionSelectHtml')) {
    function itemConditionSelectHtml(?string $selected = 'sifir'): string
    {
        $selected = normalizeItemCondition($selected ?: 'sifir');
        $class = 'w-full min-w-[110px] px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white';
        $html = '<select name="item_condition[]" required class="' . $class . '">';
        foreach (itemConditionOptions() as $code => $label) {
            $sel = $selected === $code ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($code) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
        }
        return $html . '</select>';
    }
}

/** Sözleşme eşya listesi — form satırlarını normalize eder */
if (!function_exists('parseContractItemsFromRequest')) {
    function parseContractItemsFromRequest(array $post): array
    {
        $names = $post['item_name'] ?? [];
        if (!is_array($names)) {
            return [];
        }
        $quantities = $post['item_quantity'] ?? [];
        $units = $post['item_unit'] ?? [];
        $descriptions = $post['item_description'] ?? [];
        $conditions = $post['item_condition'] ?? [];
        $items = [];
        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $qtyRaw = $quantities[$i] ?? 1;
            $quantity = ($qtyRaw !== '' && $qtyRaw !== null) ? max(1, (int) $qtyRaw) : 1;
            $unit = trim((string) ($units[$i] ?? '')) ?: 'adet';
            $description = trim((string) ($descriptions[$i] ?? ''));
            $condition = normalizeItemCondition($conditions[$i] ?? 'sifir');
            $items[] = [
                'name' => $name,
                'quantity' => $quantity,
                'unit' => $unit,
                'description' => $description !== '' ? $description : null,
                'condition' => $condition,
            ];
        }
        return $items;
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
        $keepParams = array_filter($keepParams, static function ($v, $k) {
            if ($v === null) {
                return false;
            }
            if ($v === '' && $k !== 'borc') {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);

        $url = function (int $p) use ($baseUrl, $keepParams) {
            $params = $keepParams;
            $params['page'] = $p;
            $qs = http_build_query($params);
            return $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . $qs;
        };

        $html = '<nav class="pagination-bar relative z-10 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 py-3 px-4 mt-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50/80 dark:bg-gray-800/80 touch-manipulation" aria-label="Sayfalama">';
        $html .= '<p class="text-sm text-gray-500 dark:text-gray-400 shrink-0">';
        $from = ($currentPage - 1) * $perPage + 1;
        $to = min($currentPage * $perPage, $total);
        $html .= $from . '–' . $to . ' / ' . $total . ' kayıt';
        if ($totalPages > 1) {
            $html .= ' · Sayfa ' . $currentPage . ' / ' . $totalPages;
        }
        $html .= '</p>';
        $html .= '<div class="pagination-links-wrap overflow-x-auto -mx-1 px-1">';
        $html .= '<div class="flex items-center gap-1 flex-nowrap justify-end min-w-max">';

        $linkClass = 'inline-flex items-center justify-center min-w-[2.5rem] min-h-[2.75rem] px-3 py-1.5 rounded-lg text-sm font-medium touch-manipulation';
        $idleClass = $linkClass . ' text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700';
        $activeClass = $linkClass . ' bg-emerald-600 text-white pointer-events-none';

        if ($currentPage > 1) {
            $html .= '<a href="' . htmlspecialchars($url(1)) . '" class="' . $idleClass . '" title="İlk" rel="nofollow"><i class="bi bi-chevron-double-left"></i></a>';
            $html .= '<a href="' . htmlspecialchars($url($currentPage - 1)) . '" class="' . $idleClass . '" title="Önceki" rel="nofollow"><i class="bi bi-chevron-left"></i></a>';
        }

        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        for ($i = $start; $i <= $end; $i++) {
            if ($i === $currentPage) {
                $html .= '<span class="' . $activeClass . '" aria-current="page">' . $i . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars($url($i)) . '" class="' . $idleClass . '" rel="nofollow">' . $i . '</a>';
            }
        }

        if ($currentPage < $totalPages) {
            $html .= '<a href="' . htmlspecialchars($url($currentPage + 1)) . '" class="' . $idleClass . '" title="Sonraki" rel="nofollow"><i class="bi bi-chevron-right"></i></a>';
            $html .= '<a href="' . htmlspecialchars($url($totalPages)) . '" class="' . $idleClass . '" title="Son" rel="nofollow"><i class="bi bi-chevron-double-right"></i></a>';
        }
        $html .= '</div></div></nav>';
        return $html;
    }
}

/** Tümünü sil onay metni */
if (!function_exists('deleteAllConfirmMessage')) {
    function deleteAllConfirmMessage(string $pluralLabel): string
    {
        return 'Tüm ' . $pluralLabel . ' kalıcı olarak tamamen silinecektir; ilişkili kayıtlar da sistemden kaldırılacaktır. Bu işlem geri alınamaz. Devam etmek istiyor musunuz?';
    }
}

/** Kalıcı silme onay metni */
if (!function_exists('deleteConfirmMessage')) {
    function deleteConfirmMessage(string $entityLabel): string
    {
        return 'Bu ' . $entityLabel . ' kalıcı olarak tamamen silinecektir; ilişkili kayıtlar da sistemden kaldırılacaktır. Bu işlem geri alınamaz. Devam etmek istiyor musunuz?';
    }
}

/** public/uploads altındaki dosyayı diskten kaldırır */
if (!function_exists('publicFilePath')) {
    function publicFilePath(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }
        $relativePath = '/' . ltrim($relativePath, '/');
        if (strpos($relativePath, '/uploads/') !== 0) {
            return null;
        }
        $root = defined('APP_ROOT') ? APP_ROOT . '/public' : dirname(__DIR__) . '/public';
        $full = $root . $relativePath;
        return is_file($full) ? $full : null;
    }
}

if (!function_exists('absoluteAppUrl')) {
    function absoluteAppUrl(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            $path = '';
        }
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = 'https';
            if (!empty($_SERVER['REQUEST_SCHEME'])) {
                $scheme = (string) $_SERVER['REQUEST_SCHEME'];
            } elseif (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
                $scheme = 'http';
            }
            return $scheme . '://' . $_SERVER['HTTP_HOST'] . $path;
        }
        return $path;
    }
}

/** public/uploads altındaki dosyayı diskten kaldırır */
if (!function_exists('unlinkPublicFile')) {
    function unlinkPublicFile(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $relativePath = '/' . ltrim($relativePath, '/');
        if (strpos($relativePath, '/uploads/') !== 0) {
            return;
        }
        $root = defined('APP_ROOT') ? APP_ROOT . '/public' : dirname(__DIR__) . '/public';
        $full = $root . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

/** Flash mesajlarını okur ve oturumdan siler */
if (!function_exists('flash_consume')) {
    /** @return array{success: ?string, error: ?string} */
    function flash_consume(): array
    {
        return Auth::consumeFlash();
    }
}

/** Aynı POST'un kısa sürede tekrarlanmasını engeller (çift tıklama / kuyruk) */
if (!function_exists('request_dedupe_hit')) {
    function request_dedupe_hit(string $action, string $key, int $ttlSeconds = 10): ?array
    {
        $last = Auth::getSession('dedupe_' . $action);
        if (!is_array($last) || ($last['key'] ?? '') !== $key) {
            return null;
        }
        if ((time() - (int) ($last['at'] ?? 0)) >= $ttlSeconds) {
            return null;
        }
        return $last;
    }
}

if (!function_exists('request_dedupe_store')) {
    /** @param array<string, mixed> $extra */
    function request_dedupe_store(string $action, string $key, array $extra = []): void
    {
        Auth::setSession('dedupe_' . $action, array_merge(['key' => $key, 'at' => time()], $extra));
    }
}

/** Depo kaydından tam adres metni */
if (!function_exists('formatWarehouseAddress')) {
    function formatWarehouseAddress(array $wh): string
    {
        return trim(implode(', ', array_filter([
            $wh['name'] ?? '',
            $wh['address'] ?? '',
            $wh['district'] ?? '',
            $wh['city'] ?? '',
        ])));
    }
}

/**
 * Kayıtlı nakliye adresinden form alanlarını çıkarır.
 *
 * @param list<array<string, mixed>> $warehouses
 * @return array{source_type: string, warehouse_id: string, address_detail: string, preview: string}
 */
if (!function_exists('parseJobLocationAddress')) {
    function parseJobLocationAddress(?string $address, array $warehouses = []): array
    {
        $address = trim((string) $address);
        if ($address === '') {
            return ['source_type' => 'evden', 'warehouse_id' => '', 'address_detail' => '', 'preview' => ''];
        }
        if (preg_match('/^Depo:\s*(.+)$/us', $address, $m)) {
            $depoText = trim($m[1]);
            $warehouseId = '';
            foreach ($warehouses as $wh) {
                $formatted = formatWarehouseAddress($wh);
                if ($formatted === $depoText || ($wh['name'] ?? '') === $depoText) {
                    $warehouseId = (string) ($wh['id'] ?? '');
                    break;
                }
                if (($wh['name'] ?? '') !== '' && str_contains($depoText, (string) $wh['name'])) {
                    $warehouseId = (string) ($wh['id'] ?? '');
                    break;
                }
            }
            return [
                'source_type' => 'depo',
                'warehouse_id' => $warehouseId,
                'address_detail' => '',
                'preview' => $depoText,
            ];
        }
        if (preg_match('/^Ofisten:\s*(.+)$/us', $address, $m)) {
            return [
                'source_type' => 'ofisten',
                'warehouse_id' => '',
                'address_detail' => trim($m[1]),
                'preview' => trim($m[1]),
            ];
        }
        if (preg_match('/^Evden:\s*(.+)$/us', $address, $m)) {
            return [
                'source_type' => 'evden',
                'warehouse_id' => '',
                'address_detail' => trim($m[1]),
                'preview' => trim($m[1]),
            ];
        }
        return [
            'source_type' => 'evden',
            'warehouse_id' => '',
            'address_detail' => $address,
            'preview' => $address,
        ];
    }
}

/** Yanıtı istemciye gönder; shutdown (push, SMTP vb.) arka planda devam eder. */
if (!function_exists('releaseHttpResponse')) {
    function releaseHttpResponse(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
