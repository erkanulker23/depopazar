<?php

/**
 * Sözleşme aylık kira dönemleri: giriş tarihi = 1. vade, sonraki vadeler aynı gün (ay yıl dönümü).
 * Örn. başlangıç 28.06.2026 → vadeler 28.06.2026, 28.07.2026, 28.08.2026 …
 */
class ContractBilling
{
    public static function normalizeDate(?string $date): string
    {
        if ($date === null || trim($date) === '') {
            return '';
        }
        return substr(trim($date), 0, 10);
    }

    /** Giriş tarihine N ay ekler; hedef ayda gün yoksa ayın son günü kullanılır (31 Ocak → 28/29 Şubat). */
    public static function addMonths(string $anchorDate, int $months): string
    {
        $anchor = self::normalizeDate($anchorDate);
        if ($anchor === '') {
            return '';
        }
        $day = (int) substr($anchor, 8, 2);
        $year = (int) substr($anchor, 0, 4);
        $month = (int) substr($anchor, 5, 2);

        $totalMonths = ($year * 12 + ($month - 1)) + $months;
        $targetYear = intdiv($totalMonths, 12);
        $targetMonth = ($totalMonths % 12) + 1;

        $lastDay = (int) (new DateTime(sprintf('%04d-%02d-01', $targetYear, $targetMonth)))->format('t');
        $targetDay = min($day, $lastDay);

        return sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, $targetDay);
    }

    /**
     * @return list<array{index: int, key: string, due_date: string, label: string}>
     */
    public static function periods(string $startDate, string $endDate): array
    {
        $start = self::normalizeDate($startDate);
        $end = self::normalizeDate($endDate);
        if ($start === '' || $end === '' || $end < $start) {
            return [];
        }

        $periods = [];
        $index = 0;
        while (true) {
            $dueDate = self::addMonths($start, $index);
            if ($dueDate === '' || $dueDate > $end) {
                break;
            }
            $periods[] = [
                'index' => $index,
                'key' => $dueDate,
                'due_date' => $dueDate . ' 00:00:00',
                'label' => self::formatPeriodLabel($dueDate),
            ];
            $index++;
        }

        return $periods;
    }

    public static function periodKeyFromDueDate(?string $dueDate): string
    {
        return self::normalizeDate($dueDate);
    }

    public static function formatPeriodLabel(string $dateYmd): string
    {
        $dateYmd = self::normalizeDate($dateYmd);
        if ($dateYmd === '') {
            return '';
        }
        $ts = strtotime($dateYmd);
        return $ts !== false ? date('d.m.Y', $ts) : $dateYmd;
    }

    /** Form / eski Y-m anahtarları ile fiyat çözümleme */
    public static function resolvePriceForPeriod(string $periodKey, float $defaultPrice, array $monthlyPricesPost): float
    {
        if (isset($monthlyPricesPost[$periodKey]) && $monthlyPricesPost[$periodKey] !== '') {
            return (float) str_replace(',', '.', (string) $monthlyPricesPost[$periodKey]);
        }
        $legacyYm = strlen($periodKey) >= 7 ? substr($periodKey, 0, 7) : $periodKey;
        if ($legacyYm !== $periodKey && isset($monthlyPricesPost[$legacyYm]) && $monthlyPricesPost[$legacyYm] !== '') {
            return (float) str_replace(',', '.', (string) $monthlyPricesPost[$legacyYm]);
        }
        return $defaultPrice;
    }

    /** contract_monthly_prices satırından fiyat (Y-m-d veya eski Y-m anahtarı) */
    public static function priceFromExistingRows(string $periodKey, array $existingByMonth, float $defaultPrice): float
    {
        if (isset($existingByMonth[$periodKey])) {
            return (float) ($existingByMonth[$periodKey]['price'] ?? $defaultPrice);
        }
        $legacyYm = substr($periodKey, 0, 7);
        if (isset($existingByMonth[$legacyYm])) {
            return (float) ($existingByMonth[$legacyYm]['price'] ?? $defaultPrice);
        }
        return $defaultPrice;
    }

    public static function isPaidPeriodKey(string $periodKey, array $paidPeriodKeys): bool
    {
        if (in_array($periodKey, $paidPeriodKeys, true)) {
            return true;
        }
        $legacyYm = substr($periodKey, 0, 7);
        return in_array($legacyYm, $paidPeriodKeys, true);
    }
}
