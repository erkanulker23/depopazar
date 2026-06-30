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
$layout = $layout ?? 'stack';
$textCls = $compact ? 'text-xs text-gray-600 dark:text-gray-400' : 'text-sm text-gray-600 dark:text-gray-400';
$chipCls = 'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors';
?>
<?php if ($hasAny): ?>
<?php if ($layout === 'chips'): ?>
<div class="flex flex-wrap gap-1.5">
    <?php if ($whPhone !== ''): ?>
        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $whPhone)) ?>" class="<?= $chipCls ?>"><i class="bi bi-telephone"></i><span class="truncate max-w-[8rem]"><?= htmlspecialchars($whPhone) ?></span></a>
    <?php endif; ?>
    <?php if ($whWhatsapp !== ''): ?>
        <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D+/', '', $whWhatsapp)) ?>" target="_blank" rel="noopener" class="<?= $chipCls ?>"><i class="bi bi-whatsapp"></i><span>WA</span></a>
    <?php endif; ?>
    <?php if ($whEmail !== ''): ?>
        <a href="mailto:<?= htmlspecialchars($whEmail) ?>" class="<?= $chipCls ?>"><i class="bi bi-envelope"></i><span class="truncate max-w-[10rem]"><?= htmlspecialchars($whEmail) ?></span></a>
    <?php endif; ?>
    <?php if ($whWebsite !== ''): ?>
        <?php $webHref = normalizeWebsiteUrl($whWebsite) ?? $whWebsite; ?>
        <a href="<?= htmlspecialchars($webHref) ?>" target="_blank" rel="noopener" class="<?= $chipCls ?>"><i class="bi bi-globe"></i><span class="truncate max-w-[8rem]"><?= htmlspecialchars(websiteDisplayUrl($whWebsite)) ?></span></a>
    <?php endif; ?>
</div>
<?php else: ?>
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
<?php endif; ?>
