<?php
/** Depo listesi / detay için ortak yardımcılar */
if (!function_exists('warehouseEditPayload')) {
    function warehouseEditPayload(array $w): array
    {
        return [
            'id' => $w['id'],
            'name' => $w['name'],
            'address' => $w['address'] ?? '',
            'city' => $w['city'] ?? '',
            'district' => $w['district'] ?? '',
            'total_floors' => (int) ($w['total_floors'] ?? 0),
            'description' => $w['description'] ?? '',
            'is_active' => !empty($w['is_active']),
            'monthly_base_fee' => $w['monthly_base_fee'] ?? null,
            'phone' => $w['phone'] ?? '',
            'whatsapp_number' => $w['whatsapp_number'] ?? '',
            'email' => $w['email'] ?? '',
            'website' => $w['website'] ?? '',
            'logo_url' => warehouseLogoHref($w) ?: '',
        ];
    }
}

if (!function_exists('warehouseHasContact')) {
    function warehouseHasContact(array $w): bool
    {
        return trim((string) ($w['phone'] ?? '')) !== ''
            || trim((string) ($w['whatsapp_number'] ?? '')) !== ''
            || trim((string) ($w['email'] ?? '')) !== ''
            || trim((string) ($w['website'] ?? '')) !== '';
    }
}

if (!function_exists('warehouseLocationLine')) {
    function warehouseLocationLine(array $w): string
    {
        $parts = array_filter([
            trim((string) ($w['address'] ?? '')),
            trim(implode(' / ', array_filter([
                trim((string) ($w['district'] ?? '')),
                trim((string) ($w['city'] ?? '')),
            ], fn($p) => $p !== ''))),
        ], fn($p) => $p !== '');
        return $parts !== [] ? implode(' · ', $parts) : '-';
    }
}
