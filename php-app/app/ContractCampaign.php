<?php

/**
 * Sözleşme kampanyaları — ücretsiz ay ödeme taahhüdü olarak uygulanır (son vade 0 ₺).
 *
 * - 6_plus_1: 6 ay öde, 1 ay ücretsiz (toplam 7 vade)
 * - 12_plus_1: 12 ay öde, 1 ay ücretsiz (toplam 13 vade)
 */
class ContractCampaign
{
    public const CODE_6_PLUS_1 = '6_plus_1';
    public const CODE_12_PLUS_1 = '12_plus_1';

    /** @return array<string, array{label: string, paid_months: int, free_months: int, total_periods: int}> */
    public static function all(): array
    {
        return [
            self::CODE_6_PLUS_1 => [
                'label' => '6 ay kalsın, 1 ay ücretsiz',
                'paid_months' => 6,
                'free_months' => 1,
                'total_periods' => 7,
            ],
            self::CODE_12_PLUS_1 => [
                'label' => '1 yıl kalsın, 1 ay ücretsiz',
                'paid_months' => 12,
                'free_months' => 1,
                'total_periods' => 13,
            ],
        ];
    }

    public static function isValid(?string $code): bool
    {
        return $code !== null && $code !== '' && isset(self::all()[$code]);
    }

    public static function label(?string $code): string
    {
        if (!self::isValid($code)) {
            return '';
        }
        return self::all()[$code]['label'];
    }

    public static function totalPeriods(string $code): int
    {
        return self::all()[$code]['total_periods'];
    }

    /** Kampanyaya göre bitiş tarihi (son vade günü). */
    public static function endDateForCampaign(string $startDate, string $code): string
    {
        $def = self::all()[$code];
        return ContractBilling::addMonths(ContractBilling::normalizeDate($startDate), $def['total_periods'] - 1);
    }

    /** Ücretsiz ayın vade anahtarı (Y-m-d). */
    public static function freePeriodKey(string $startDate, string $code): string
    {
        return self::endDateForCampaign($startDate, $code);
    }

    /**
     * Kampanya için aylık fiyat POST dizisini hazırlar (son vade 0).
     *
     * @param array<string, string|float> $monthlyPricesPost
     * @return array<string, string>
     */
    public static function applyToMonthlyPrices(
        string $startDate,
        string $endDate,
        string $code,
        float $defaultPrice,
        array $monthlyPricesPost = []
    ): array {
        if (!self::isValid($code)) {
            return $monthlyPricesPost;
        }
        $periods = ContractBilling::periods($startDate, $endDate);
        if (count($periods) !== self::totalPeriods($code)) {
            return $monthlyPricesPost;
        }
        $freeKey = self::freePeriodKey($startDate, $code);
        $result = $monthlyPricesPost;
        foreach ($periods as $period) {
            $key = $period['key'];
            if ($key === $freeKey) {
                $result[$key] = '0';
            } elseif (!isset($result[$key]) || trim((string) $result[$key]) === '') {
                $result[$key] = number_format($defaultPrice, 2, '.', '');
            }
        }
        return $result;
    }

    /** Tarih aralığı kampanya ile uyumlu mu? */
    public static function matchesPeriodCount(string $startDate, string $endDate, string $code): bool
    {
        if (!self::isValid($code)) {
            return false;
        }
        return count(ContractBilling::periods($startDate, $endDate)) === self::totalPeriods($code);
    }
}
