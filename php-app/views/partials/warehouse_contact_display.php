<?php
/** Depo iletişim bilgisi gösterimi (liste, detay, etiket) */
/** @var array $warehouse */
$warehouse = $warehouse ?? [];
$compact = !empty($compact);
$whPhone = trim((string) ($warehouse['phone'] ?? ''));
$whWhatsapp = trim((string) ($warehouse['whatsapp_number'] ?? ''));
$whEmail = trim((string) ($warehouse['email'] ?? ''));
$whWebsite = trim((string) ($warehouse['website'] ?? ''));
$hasAny = $whPhone !== '' || $whWhatsapp !== '' || $whEmail !== '' || $whWebsite !== '';
$textCls = $compact ? 'text-xs text-gray-600 dark:text-gray-400' : 'text-sm text-gray-600 dark:text-gray-400';
?>
<?php if ($hasAny): ?>
<div class="<?= $compact ? 'mt-1 space-y-0.5' : 'space-y-1' ?>">
    <?php if ($whPhone !== ''): ?>
        <p class="<?= $textCls ?>"><i class="bi bi-telephone mr-1 opacity-70"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $whPhone)) ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400"><?= htmlspecialchars($whPhone) ?></a></p>
    <?php endif; ?>
    <?php if ($whWhatsapp !== ''): ?>
        <p class="<?= $textCls ?>"><i class="bi bi-whatsapp mr-1 opacity-70"></i><a href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D+/', '', $whWhatsapp)) ?>" target="_blank" rel="noopener" class="hover:text-emerald-600 dark:hover:text-emerald-400"><?= htmlspecialchars($whWhatsapp) ?></a></p>
    <?php endif; ?>
    <?php if ($whEmail !== ''): ?>
        <p class="<?= $textCls ?>"><i class="bi bi-envelope mr-1 opacity-70"></i><a href="mailto:<?= htmlspecialchars($whEmail) ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400"><?= htmlspecialchars($whEmail) ?></a></p>
    <?php endif; ?>
    <?php if ($whWebsite !== ''): ?>
        <?php $webHref = normalizeWebsiteUrl($whWebsite) ?? $whWebsite; ?>
        <p class="<?= $textCls ?>"><i class="bi bi-globe mr-1 opacity-70"></i><a href="<?= htmlspecialchars($webHref) ?>" target="_blank" rel="noopener" class="hover:text-emerald-600 dark:hover:text-emerald-400"><?= htmlspecialchars(websiteDisplayUrl($whWebsite)) ?></a></p>
    <?php endif; ?>
</div>
<?php endif; ?>
