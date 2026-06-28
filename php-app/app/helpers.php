<?php
if (!function_exists('fmtPrice')) {
    function fmtPrice($n) {
        if ($n === null || $n === '') return '';
        $f = (float) $n;
        return ($f == (int)$f ? number_format((int)$f, 0, '', '.') : number_format($f, 2, ',', '.')) . ' ₺';
    }
}

/** Form tutar alanı: 5.000,00 / 5000 / 5000,50 → float */
if (!function_exists('parseMoneyInput')) {
    function parseMoneyInput(mixed $value): float
    {
        $val = trim(str_replace(["\xc2\xa0", ' '], '', (string) $value));
        if ($val === '') {
            return 0.0;
        }
        $val = str_replace('₺', '', $val);
        if (str_contains($val, ',')) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        }
        return (float) $val;
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

/** Aynı depoda mevcut oda için kullanıcı mesajı */
if (!function_exists('roomDuplicateMessage')) {
    function roomDuplicateMessage(string $entered, array $existing): string
    {
        $existingNo = fmtRoomNumber($existing['room_number'] ?? '');
        $enteredNo = trim($entered);
        if ($existingNo !== '–' && $existingNo !== $enteredNo) {
            return 'Bu depoda "' . $existingNo . '" numaralı oda zaten kayıtlı (girdiğiniz: "' . $enteredNo . '").';
        }
        return 'Bu depoda "' . $enteredNo . '" numaralı oda zaten kayıtlı.';
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

/** WhatsApp wa.me için uluslararası numara (905xxxxxxxxx) */
if (!function_exists('whatsappIntlPhone')) {
    function whatsappIntlPhone(?string $phone): string
    {
        if ($phone === null || trim($phone) === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) === 10 && $digits[0] === '5') {
            return '90' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '9' . substr($digits, 1);
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '90')) {
            return $digits;
        }
        if (strlen($digits) >= 10) {
            return '90' . ltrim($digits, '0');
        }
        return '';
    }
}

/** Müşteri telefonu (birincil veya ikincil) → WhatsApp uluslararası numara */
if (!function_exists('whatsappIntlPhoneFromCustomerFields')) {
    function whatsappIntlPhoneFromCustomerFields(?string $primary, ?string $secondary = null): string
    {
        foreach ([$primary, $secondary] as $phone) {
            $intl = whatsappIntlPhone($phone);
            if ($intl !== '') {
                return $intl;
            }
        }
        return '';
    }
}

/** wa.me sohbet URL'si (müşteriye doğrudan mesaj) */
if (!function_exists('whatsappMeUrl')) {
    function whatsappMeUrl(?string $phone, string $message = '', ?string $fallbackPhone = null): string
    {
        $intl = whatsappIntlPhoneFromCustomerFields($phone, $fallbackPhone);
        if ($intl === '') {
            return '';
        }
        $url = 'https://wa.me/' . $intl;
        if ($message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }
        return $url;
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

/** Tahsilatı işleyen kullanıcı adı */
if (!function_exists('paymentCollectorName')) {
    function paymentCollectorName(array $payment): string
    {
        $name = trim(($payment['paid_by_first_name'] ?? '') . ' ' . ($payment['paid_by_last_name'] ?? ''));
        return $name;
    }
}

/** Ödeme tahsil edilmiş mi (tek kaynak: status) */
if (!function_exists('paymentIsPaid')) {
    function paymentIsPaid(array $payment): bool
    {
        return ($payment['status'] ?? '') === 'paid';
    }
}

/** Ödeme henüz tahsil edilmemiş mi */
if (!function_exists('paymentIsUnpaid')) {
    function paymentIsUnpaid(array $payment): bool
    {
        $status = $payment['status'] ?? 'pending';
        return in_array($status, ['pending', 'overdue'], true);
    }
}

/**
 * Aylar takvimi: sözleşme giriş/çıkış dönemlerine göre (ContractBilling::periods) ödeme durumu.
 * Sözleşme dışı veya hatalı vade kayıtları takvime yansımaz.
 *
 * @return array{months: array<string, array{status: string, label: string, amount: float, contract_number: string}>, minYear: int, maxYear: int}
 */
if (!function_exists('buildPaymentMonthsCalendar')) {
    function buildPaymentMonthsCalendar(array $payments, array $contracts = []): array
    {
        $paymentsByContractPeriod = [];
        foreach ($payments as $p) {
            if (($p['status'] ?? '') === 'cancelled') {
                continue;
            }
            $cid = $p['contract_id'] ?? '';
            $due = $p['due_date'] ?? '';
            if ($cid === '' || $due === '') {
                continue;
            }
            $periodKey = ContractBilling::periodKeyFromDueDate($due);
            if ($periodKey === '') {
                continue;
            }
            $paymentsByContractPeriod[$cid][$periodKey][] = $p;
        }

        $months = [];
        $minYear = null;
        $maxYear = null;
        $hasPeriods = false;

        foreach ($contracts as $c) {
            $start = ContractBilling::normalizeDate($c['start_date'] ?? null);
            $end = ContractBilling::normalizeDate($c['end_date'] ?? null);
            if ($start === '' || $end === '') {
                continue;
            }
            $cid = $c['id'] ?? '';
            $contractNumber = $c['contract_number'] ?? '';
            $defaultPrice = (float) ($c['monthly_price'] ?? 0);

            foreach (ContractBilling::periods($start, $end) as $period) {
                $hasPeriods = true;
                $periodKey = $period['key'];
                $ym = substr($periodKey, 0, 7);
                $year = (int) substr($ym, 0, 4);
                $minYear = $minYear === null ? $year : min($minYear, $year);
                $maxYear = $maxYear === null ? $year : max($maxYear, $year);

                $matched = ($cid !== '' && isset($paymentsByContractPeriod[$cid][$periodKey]))
                    ? $paymentsByContractPeriod[$cid][$periodKey]
                    : [[
                        'status' => 'pending',
                        'amount' => $defaultPrice,
                        'due_date' => $period['due_date'],
                        'contract_number' => $contractNumber,
                    ]];

                if (!isset($months[$ym])) {
                    $months[$ym] = ['items' => [], 'amount' => 0.0, 'contract_number' => $contractNumber];
                }
                foreach ($matched as $p) {
                    $months[$ym]['items'][] = $p;
                    $months[$ym]['amount'] += (float) ($p['amount'] ?? 0);
                }
                if ($contractNumber !== '' && ($months[$ym]['contract_number'] ?? '') === '') {
                    $months[$ym]['contract_number'] = $contractNumber;
                }
            }
        }

        if (!$hasPeriods) {
            foreach ($payments as $p) {
                $due = $p['due_date'] ?? '';
                if ($due === '' || ($p['status'] ?? '') === 'cancelled') {
                    continue;
                }
                $key = date('Y-m', strtotime($due));
                if (!isset($months[$key])) {
                    $months[$key] = ['items' => [], 'amount' => 0.0, 'contract_number' => $p['contract_number'] ?? ''];
                }
                $months[$key]['items'][] = $p;
                $months[$key]['amount'] += (float) ($p['amount'] ?? 0);
            }
        }

        foreach ($months as $key => $info) {
            $items = $info['items'];
            $paidCount = count(array_filter($items, 'paymentIsPaid'));
            $total = count($items);
            $anyOverdue = false;
            $anyEarly = false;
            foreach ($items as $p) {
                if (!paymentIsPaid($p) && paymentStatusDisplay($p)['label'] === 'Vadesi geçmiş') {
                    $anyOverdue = true;
                }
                if (paymentIsPaid($p) && paymentIsEarly($p)) {
                    $anyEarly = true;
                }
            }
            if ($paidCount === $total && $total > 0) {
                $months[$key]['status'] = 'paid';
                $months[$key]['label'] = $anyEarly ? 'Erken ödendi' : 'Ödendi';
            } elseif ($paidCount > 0) {
                $months[$key]['status'] = 'partial';
                $months[$key]['label'] = 'Kısmi (' . $paidCount . '/' . $total . ')';
            } elseif ($anyOverdue) {
                $months[$key]['status'] = 'overdue';
                $months[$key]['label'] = 'Gecikmede';
            } else {
                $months[$key]['status'] = 'pending';
                $months[$key]['label'] = 'Ödenmedi';
            }
            unset($months[$key]['items']);
        }

        if ($minYear === null) {
            $minYear = (int) date('Y');
            $maxYear = (int) date('Y');
            foreach (array_keys($months) as $ym) {
                $y = (int) substr($ym, 0, 4);
                $minYear = min($minYear, $y);
                $maxYear = max($maxYear, $y);
            }
        }

        return ['months' => $months, 'minYear' => $minYear, 'maxYear' => $maxYear];
    }
}

/**
 * Ödeme listesini sözleşme giriş/çıkış dönemlerindeki vadelerle sınırlar (hatalı/eski kayıtları gizler).
 *
 * @param list<array<string, mixed>> $payments
 * @param list<array<string, mixed>> $contracts
 * @return list<array<string, mixed>>
 */
if (!function_exists('filterPaymentsToValidContractPeriods')) {
    function filterPaymentsToValidContractPeriods(array $payments, array $contracts): array
    {
        $validKeys = [];
        foreach ($contracts as $c) {
            $cid = $c['id'] ?? '';
            $start = ContractBilling::normalizeDate($c['start_date'] ?? null);
            $end = ContractBilling::normalizeDate($c['end_date'] ?? null);
            if ($cid === '' || $start === '' || $end === '') {
                continue;
            }
            foreach (ContractBilling::periods($start, $end) as $period) {
                $validKeys[$cid][$period['key']] = true;
            }
        }
        if ($validKeys === []) {
            return $payments;
        }

        return array_values(array_filter($payments, static function (array $p) use ($validKeys): bool {
            $cid = $p['contract_id'] ?? '';
            $periodKey = ContractBilling::periodKeyFromDueDate($p['due_date'] ?? null);
            if ($cid === '' || $periodKey === '') {
                return false;
            }
            return isset($validKeys[$cid][$periodKey]);
        }));
    }
}

/**
 * Sözleşme dönemlerine göre borç özeti (hatalı / aralık dışı ödemeler hariç).
 *
 * @param array<string, mixed> $contract
 * @param list<array<string, mixed>> $payments
 * @param array<string, float> $monthlyPricesByKey
 * @return array{total: float, overdue: float, future: float, paid: float, contract_total: float, period_count: int}
 */
if (!function_exists('computeContractDebtSummary')) {
    function computeContractDebtSummary(array $contract, array $payments, array $monthlyPricesByKey = []): array
    {
        $start = ContractBilling::normalizeDate($contract['start_date'] ?? null);
        $end = ContractBilling::normalizeDate($contract['end_date'] ?? null);
        $defaultPrice = (float) ($contract['monthly_price'] ?? 0);
        $paymentsByKey = [];
        foreach ($payments as $p) {
            $pk = ContractBilling::periodKeyFromDueDate($p['due_date'] ?? null);
            if ($pk !== '') {
                $paymentsByKey[$pk] = $p;
            }
        }

        $total = 0.0;
        $overdue = 0.0;
        $paid = 0.0;
        $contractTotal = 0.0;
        $today = date('Y-m-d');
        $periods = ($start !== '' && $end !== '') ? ContractBilling::periods($start, $end) : [];

        foreach ($periods as $period) {
            $pk = $period['key'];
            $p = $paymentsByKey[$pk] ?? null;
            $amount = $defaultPrice;
            if ($p !== null) {
                $amount = (float) ($p['amount'] ?? $defaultPrice);
            } elseif (isset($monthlyPricesByKey[$pk])) {
                $amount = (float) $monthlyPricesByKey[$pk];
            }
            $contractTotal += $amount;

            if ($p !== null && ($p['status'] ?? '') === 'paid') {
                $paid += $amount;
                continue;
            }
            if ($p !== null && !in_array($p['status'] ?? '', ['pending', 'overdue'], true)) {
                continue;
            }
            $total += $amount;
            if ($pk < $today) {
                $overdue += $amount;
            }
        }

        return [
            'total' => $total,
            'overdue' => $overdue,
            'future' => max(0.0, $total - $overdue),
            'paid' => $paid,
            'contract_total' => $contractTotal,
            'period_count' => count($periods),
        ];
    }
}

/** Ödeme durumu etiketi ve rozet sınıfı (vade tarihine göre; DB overdue status kullanılmaz).
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

/** Arama metnini Türkçe duyarsız anahtara çevirir (Sami Yiğit → sami yigit) */
if (!function_exists('turkishSearchKey')) {
    function turkishSearchKey(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        $text = str_replace(['İ', 'I'], ['i', 'i'], $text);
        $text = mb_strtolower($text, 'UTF-8');
        return strtr($text, [
            'ı' => 'i',
            'ğ' => 'g',
            'ü' => 'u',
            'ş' => 's',
            'ö' => 'o',
            'ç' => 'c',
        ]);
    }
}

if (!function_exists('turkishLikePattern')) {
    function turkishLikePattern(?string $search): ?string
    {
        $key = turkishSearchKey($search);
        return $key === '' ? null : '%' . $key . '%';
    }
}

/** SQL: sütun değerini Türkçe duyarsız arama anahtarına çevirir */
if (!function_exists('sqlTurkishSearchKey')) {
    function sqlTurkishSearchKey(string $columnExpr): string
    {
        static $pairs = [
            ['İ', 'i'], ['I', 'i'], ['ı', 'i'],
            ['Ş', 's'], ['ş', 's'],
            ['Ğ', 'g'], ['ğ', 'g'],
            ['Ü', 'u'], ['ü', 'u'],
            ['Ö', 'o'], ['ö', 'o'],
            ['Ç', 'c'], ['ç', 'c'],
        ];
        $expr = 'LOWER(COALESCE(' . $columnExpr . ", ''))";
        foreach ($pairs as [$from, $to]) {
            $fromEsc = str_replace("'", "''", $from);
            $expr = "REPLACE($expr, '$fromEsc', '$to')";
        }
        return $expr;
    }
}

/**
 * @param list<string> $columnExprs
 */
if (!function_exists('appendTurkishLikeClause')) {
    function appendTurkishLikeClause(string &$sql, array &$params, array $columnExprs, ?string $search, string $prefix = ' AND '): void
    {
        $pattern = turkishLikePattern($search);
        if ($pattern === null) {
            return;
        }
        $parts = [];
        foreach ($columnExprs as $col) {
            $parts[] = sqlTurkishSearchKey($col) . ' LIKE ?';
            $params[] = $pattern;
        }
        $sql .= $prefix . '(' . implode(' OR ', $parts) . ') ';
    }
}

/** Maksimum dosya yükleme boyutu (bayt) — nginx client_max_body_size ile uyumlu tutun */
if (!function_exists('uploadMaxBytes')) {
    function uploadMaxBytes(): int
    {
        return 20 * 1024 * 1024;
    }
}

if (!function_exists('uploadMaxBytesLabel')) {
    function uploadMaxBytesLabel(): string
    {
        return ((int) (uploadMaxBytes() / (1024 * 1024))) . ' MB';
    }
}

if (!function_exists('uploadErrorMessage')) {
    function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu çok büyük (en fazla ' . uploadMaxBytesLabel() . '). Sunucuda nginx client_max_body_size ayarı da gerekli olabilir.',
            UPLOAD_ERR_PARTIAL => 'Dosya yüklemesi yarım kaldı. Tekrar deneyin.',
            UPLOAD_ERR_NO_FILE => 'Lütfen bir dosya seçin.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucu geçici dizin hatası.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı.',
            UPLOAD_ERR_EXTENSION => 'Sunucu uzantı engeli nedeniyle yükleme reddedildi.',
            default => 'Dosya yüklenemedi.',
        };
    }
}

/**
 * @param list<string> $allowedExt
 */
if (!function_exists('validateUploadedDocument')) {
    function validateUploadedDocument(?array $file, array $allowedExt, ?int $maxBytes = null): ?string
    {
        $maxBytes = $maxBytes ?? uploadMaxBytes();
        if (!$file || empty($file['name'])) {
            return 'Lütfen bir dosya seçin.';
        }
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            return uploadErrorMessage($err);
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            return 'Dosya boyutu ' . uploadMaxBytesLabel() . ' sınırını aşıyor.';
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return 'İzin verilen formatlar: ' . implode(', ', $allowedExt);
        }
        return null;
    }
}

if (!function_exists('validateContractPdfUpload')) {
    function validateContractPdfUpload(?array $file): ?string
    {
        if (!$file || empty($file['name'])) {
            return 'Lütfen bir PDF dosyası seçin.';
        }
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            return uploadErrorMessage($err);
        }
        if (($file['size'] ?? 0) > uploadMaxBytes()) {
            return 'Dosya boyutu ' . uploadMaxBytesLabel() . ' sınırını aşıyor.';
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return 'Sadece PDF dosyası yüklenebilir.';
        }
        return null;
    }
}

/** İmzalı sözleşme PDF yükle; başarılıysa /uploads/contracts/... yolu */
if (!function_exists('storeContractPdfUpload')) {
    function storeContractPdfUpload(?array $file): ?string
    {
        if (!$file || empty($file['name']) || validateContractPdfUpload($file) !== null) {
            return null;
        }
        $uploadDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/contracts' : dirname(__DIR__) . '/public/uploads/contracts';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $filename = 'contract_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $path = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return null;
        }
        return '/uploads/contracts/' . $filename;
    }
}

/** Parçalı yükleme — nginx client_max_body_size sınırını aşmamak için (512 KB parça) */
if (!function_exists('uploadChunkByteSize')) {
    function uploadChunkByteSize(): int
    {
        return 512 * 1024;
    }
}

if (!function_exists('uploadChunksStorageDir')) {
    function uploadChunksStorageDir(): string
    {
        $dir = defined('APP_ROOT') ? APP_ROOT . '/storage/upload_chunks' : dirname(__DIR__) . '/storage/upload_chunks';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }
}

if (!function_exists('sanitizeUploadSessionId')) {
    function sanitizeUploadSessionId(?string $id): ?string
    {
        $id = trim((string) $id);
        if ($id === '' || !preg_match('/^[a-zA-Z0-9\-]{16,64}$/', $id)) {
            return null;
        }
        return $id;
    }
}

if (!function_exists('uploadChunkDir')) {
    function uploadChunkDir(string $uploadId): string
    {
        $dir = uploadChunksStorageDir() . '/' . $uploadId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }
}

if (!function_exists('saveUploadChunkPart')) {
    function saveUploadChunkPart(string $uploadId, int $index, string $tmpPath): bool
    {
        $dest = uploadChunkDir($uploadId) . '/' . sprintf('%05d.part', $index);
        if (@move_uploaded_file($tmpPath, $dest)) {
            return true;
        }
        return @copy($tmpPath, $dest);
    }
}

if (!function_exists('mergeUploadChunkParts')) {
    function mergeUploadChunkParts(string $uploadId, int $totalChunks): ?string
    {
        if ($totalChunks < 1) {
            return null;
        }
        $dir = uploadChunkDir($uploadId);
        $merged = $dir . '/merged.bin';
        $out = fopen($merged, 'wb');
        if (!$out) {
            return null;
        }
        for ($i = 0; $i < $totalChunks; $i++) {
            $part = $dir . '/' . sprintf('%05d.part', $i);
            if (!is_file($part)) {
                fclose($out);
                @unlink($merged);
                return null;
            }
            $in = fopen($part, 'rb');
            if (!$in) {
                fclose($out);
                @unlink($merged);
                return null;
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);
        return $merged;
    }
}

if (!function_exists('removeUploadChunkDir')) {
    function removeUploadChunkDir(string $uploadId): void
    {
        $dir = uploadChunksStorageDir() . '/' . $uploadId;
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }
}

if (!function_exists('documentMimeFromExtension')) {
    function documentMimeFromExtension(string $ext): ?string
    {
        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => null,
        };
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

/** /uploads/... yolunu normalize eder */
if (!function_exists('normalizePublicUploadPath')) {
    function normalizePublicUploadPath(?string $relativePath): ?string
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return null;
        }
        $relativePath = '/' . ltrim(trim($relativePath), '/');
        return strpos($relativePath, '/uploads/') === 0 ? $relativePath : null;
    }
}

/** public/uploads altındaki dosyanın disk yolu (yoksa null) */
if (!function_exists('publicFilePath')) {
    function publicFilePath(?string $relativePath): ?string
    {
        $relativePath = normalizePublicUploadPath($relativePath);
        if ($relativePath === null) {
            return null;
        }
        $root = defined('APP_ROOT') ? APP_ROOT . '/public' : dirname(__DIR__) . '/public';
        $full = $root . $relativePath;
        return is_file($full) ? $full : null;
    }
}

/** Dosya diskte varsa tarayıcı URL'si, yoksa null */
if (!function_exists('publicUploadHref')) {
    function publicUploadHref(?string $relativePath): ?string
    {
        $relativePath = normalizePublicUploadPath($relativePath);
        if ($relativePath === null) {
            return null;
        }
        return publicFilePath($relativePath) !== null ? $relativePath : null;
    }
}

/** Eksik /uploads/ isteği için anlaşılır 404 (Apache/Nginx dosyayı bulamayınca index.php'ye düşer) */
if (!function_exists('respondMissingPublicUpload')) {
    function respondMissingPublicUpload(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (!is_string($uri) || strpos($uri, '/uploads/') !== 0) {
            return false;
        }
        if (publicFilePath($uri) !== null) {
            return false;
        }
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $back = htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/genel-bakis', ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Dosya bulunamadı</title>'
            . '<style>body{font-family:system-ui,sans-serif;max-width:32rem;margin:3rem auto;padding:0 1rem;color:#1f2937}'
            . 'a{color:#059669}</style></head><body>'
            . '<h1>Dosya bulunamadı</h1>'
            . '<p>Bu belge veritabanında kayıtlı ancak sunucuda dosya yok. Deploy öncesi yüklenmiş dosyalar release değişince kaybolmuş olabilir; belgeyi yeniden yükleyin veya gereksiz kaydı silin.</p>'
            . '<p><a href="' . $back . '">← Geri dön</a></p>'
            . '</body></html>';
        return true;
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

/** Personel profil fotoğrafı URL (dosya yoksa null) */
if (!function_exists('personnelPhotoHref')) {
    function personnelPhotoHref(?array $personnel): ?string
    {
        if (!$personnel) {
            return null;
        }
        return publicUploadHref($personnel['photo_url'] ?? null);
    }
}

/** Personel ad soyad baş harfleri (avatar yedek) */
if (!function_exists('personnelInitials')) {
    function personnelInitials(?array $personnel): string
    {
        if (!$personnel) {
            return '?';
        }
        $a = mb_substr(trim($personnel['first_name'] ?? ''), 0, 1);
        $b = mb_substr(trim($personnel['last_name'] ?? ''), 0, 1);
        $init = mb_strtoupper($a . $b);
        return $init !== '' ? $init : '?';
    }
}

/** Personel profil fotoğrafı yükle; başarılıysa /uploads/personnel/... yolu */
if (!function_exists('storePersonnelPhotoUpload')) {
    function storePersonnelPhotoUpload(?array $file, string $personnelId): ?string
    {
        if (!$file || empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }
        $uploadDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/personnel' : dirname(__DIR__) . '/public/uploads/personnel';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $safeId = preg_replace('/[^a-zA-Z0-9\-]/', '', $personnelId) ?: bin2hex(random_bytes(8));
        $filename = 'personnel_' . $safeId . '_' . time() . '.' . $ext;
        $path = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return null;
        }
        return '/uploads/personnel/' . $filename;
    }
}

/** Kullanıcı profil fotoğrafı URL (dosya yoksa null) */
if (!function_exists('userPhotoHref')) {
    function userPhotoHref(?array $userRow): ?string
    {
        if (!$userRow) {
            return null;
        }
        return publicUploadHref($userRow['photo_url'] ?? null);
    }
}

/** Kullanıcı ad soyad baş harfleri (avatar yedek) */
if (!function_exists('userInitials')) {
    function userInitials(?array $userRow): string
    {
        if (!$userRow) {
            return '?';
        }
        $a = mb_substr(trim($userRow['first_name'] ?? ''), 0, 1);
        $b = mb_substr(trim($userRow['last_name'] ?? ''), 0, 1);
        $init = mb_strtoupper($a . $b);
        return $init !== '' ? $init : '?';
    }
}

/** Kullanıcı profil fotoğrafı yükle; başarılıysa /uploads/users/... yolu */
if (!function_exists('storeUserPhotoUpload')) {
    function storeUserPhotoUpload(?array $file, string $userId): ?string
    {
        if (!$file || empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }
        $uploadDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/users' : dirname(__DIR__) . '/public/uploads/users';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $safeId = preg_replace('/[^a-zA-Z0-9\-]/', '', $userId) ?: bin2hex(random_bytes(8));
        $filename = 'user_' . $safeId . '_' . time() . '.' . $ext;
        $path = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return null;
        }
        return '/uploads/users/' . $filename;
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

/** Rapor sayfaları: ay veya özel tarih aralığı */
if (!function_exists('reportPeriodFromRequest')) {
    function reportPeriodFromRequest(array $query): array
    {
        $mode = (($query['period_mode'] ?? '') === 'custom') ? 'custom' : 'month';
        if ($mode === 'custom') {
            $start = trim((string) ($query['start_date'] ?? ''));
            $end = trim((string) ($query['end_date'] ?? ''));
            if ($start === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
                $start = date('Y-m-01');
            }
            if ($end === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                $end = date('Y-m-t');
            }
            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }
            return [
                'mode' => 'custom',
                'start_date' => $start,
                'end_date' => $end,
                'year' => (int) substr($start, 0, 4),
                'month' => (int) substr($start, 5, 2),
                'all_months' => false,
            ];
        }
        $year = isset($query['year']) && $query['year'] !== '' ? (int) $query['year'] : (int) date('Y');
        $monthRaw = isset($query['month']) && $query['month'] !== '' ? (int) $query['month'] : (int) date('n');
        $allMonths = ($monthRaw === 0);
        if ($allMonths) {
            $start = sprintf('%04d-01-01', $year);
            $end = sprintf('%04d-12-31', $year);
        } else {
            $start = sprintf('%04d-%02d-01', $year, $monthRaw);
            $end = date('Y-m-t', strtotime($start));
        }
        return [
            'mode' => 'month',
            'start_date' => $start,
            'end_date' => $end,
            'year' => $year,
            'month' => $monthRaw,
            'all_months' => $allMonths,
        ];
    }
}

if (!function_exists('reportExportUrl')) {
    function reportExportUrl(string $path, array $query = []): string
    {
        $query['export'] = 'csv';
        $qs = http_build_query(array_filter($query, static fn($v) => $v !== null && $v !== ''));
        return $path . ($qs !== '' ? '?' . $qs : '');
    }
}

if (!function_exists('streamCsvDownload')) {
    function streamCsvDownload(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) . '"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }
}

if (!function_exists('duePaymentStatusFilterLabel')) {
    function duePaymentStatusFilterLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Bekleyen',
            'overdue' => 'Vadesi geçmiş',
            'paid' => 'Ödenmiş',
            'unpaid' => 'Ödenmemiş',
            default => 'Tümü',
        };
    }
}

if (!function_exists('streamHtmlExcelReport')) {
    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     * @param list<string> $metaLines
     * @param list<string> $summaryLines
     */
    function streamHtmlExcelReport(string $filename, string $title, array $metaLines, array $headers, array $rows, array $summaryLines = []): never
    {
        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename);
        if (!str_ends_with(strtolower((string) $safeName), '.xls')) {
            $safeName .= '.xls';
        }
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        echo "\xEF\xBB\xBF";
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>';
        echo '<style>
            body { font-family: Calibri, Arial, sans-serif; margin: 24px; color: #111827; }
            h1 { color: #047857; font-size: 20pt; margin: 0 0 4px; }
            .meta { color: #4b5563; font-size: 10pt; margin-bottom: 16px; line-height: 1.6; }
            table { border-collapse: collapse; width: 100%; }
            th { background: #047857; color: #fff; padding: 10px 8px; text-align: left; font-size: 10pt; border: 1px solid #065f46; }
            td { border: 1px solid #d1d5db; padding: 7px 8px; font-size: 10pt; vertical-align: top; }
            tr:nth-child(even) td { background: #f9fafb; }
            .num { text-align: right; mso-number-format:"#,##0.00"; }
            .summary { margin-top: 18px; font-size: 10pt; color: #374151; }
            .summary strong { color: #047857; }
        </style></head><body>';
        echo '<h1>' . htmlspecialchars($title) . '</h1>';
        if ($metaLines !== []) {
            echo '<div class="meta">' . implode('<br>', array_map(static fn($line) => htmlspecialchars($line), $metaLines)) . '</div>';
        }
        echo '<table><thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $i => $cell) {
                $class = ($i === count($row) - 1) ? ' class="num"' : '';
                echo '<td' . $class . '>' . htmlspecialchars((string) $cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        if ($summaryLines !== []) {
            echo '<div class="summary">' . implode('<br>', array_map(static fn($line) => htmlspecialchars($line), $summaryLines)) . '</div>';
        }
        echo '</body></html>';
        exit;
    }
}
