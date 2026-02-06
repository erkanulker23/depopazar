<?php
$currentPage = 'araclar';
$vehicle = $vehicle ?? [];
$trafficInsurances = $trafficInsurances ?? [];
$kaskos = $kaskos ?? [];
$accidents = $accidents ?? [];
$trafficInsuranceDocs = $trafficInsuranceDocs ?? [];
$kaskoDocs = $kaskoDocs ?? [];
$accidentDocs = $accidentDocs ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
$pageTitle = $pageTitle ?? 'Araç Detay';
$vid = $vehicle['id'] ?? '';
if (!function_exists('fmtMoney')) {
    function fmtMoney($n) {
        if ($n === null || $n === '') return '–';
        $f = (float) $n;
        return number_format($f, 2, ',', '.') . ' ₺';
    }
}
ob_start();
?>
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="/araclar" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Araçlar</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-gray-700 dark:text-gray-300 font-medium"><?= htmlspecialchars($vehicle['plate'] ?? '') ?></span>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-1">Araç Detayı</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold"><?= htmlspecialchars($vehicle['plate'] ?? '') ?></p>
    </div>
    <a href="/araclar" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
        <i class="bi bi-arrow-left mr-2"></i> Araç listesine dön
    </a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<!-- Sekmeli alan: Araç Bilgileri / Trafik Sigortaları / Kaskolar -->
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
    <div class="flex border-b border-gray-200 dark:border-gray-600" role="tablist">
        <button type="button" role="tab" id="tab-bilgi" aria-selected="true" aria-controls="panel-bilgi" class="vehicle-tab active flex items-center gap-2 px-5 py-4 text-sm font-medium border-b-2 border-emerald-600 text-emerald-600 dark:text-emerald-400 bg-white dark:bg-gray-800">
            <i class="bi bi-car-front"></i> Araç Bilgileri
        </button>
        <button type="button" role="tab" id="tab-trafik" aria-selected="false" aria-controls="panel-trafik" class="vehicle-tab flex items-center gap-2 px-5 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-500">
            <i class="bi bi-shield-check"></i> Trafik Sigortaları
        </button>
        <button type="button" role="tab" id="tab-kasko" aria-selected="false" aria-controls="panel-kasko" class="vehicle-tab flex items-center gap-2 px-5 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-500">
            <i class="bi bi-shield-fill-check"></i> Kaskolar
        </button>
        <button type="button" role="tab" id="tab-kaza" aria-selected="false" aria-controls="panel-kaza" class="vehicle-tab flex items-center gap-2 px-5 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-500">
            <i class="bi bi-exclamation-triangle"></i> Kaza Bilgileri
        </button>
    </div>
    <div class="p-6">
        <!-- Panel: Araç Bilgileri -->
        <div id="panel-bilgi" role="tabpanel" aria-labelledby="tab-bilgi" class="vehicle-panel">
            <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Plaka</dt><dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($vehicle['plate'] ?? '–') ?></dd></div>
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Model Yılı</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($vehicle['model_year'] ?? '–') ?></dd></div>
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Muayene Tarihi</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= !empty($vehicle['inspection_date']) ? date('d.m.Y', strtotime($vehicle['inspection_date'])) : '–' ?></dd></div>
                <div><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Kasa (m³)</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= $vehicle['cargo_volume_m3'] !== null && $vehicle['cargo_volume_m3'] !== '' ? htmlspecialchars($vehicle['cargo_volume_m3']) : '–' ?></dd></div>
                <?php if (!empty($vehicle['notes'])): ?>
                <div class="sm:col-span-2"><dt class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Not</dt><dd class="mt-1 text-gray-600 dark:text-gray-400"><?= nl2br(htmlspecialchars($vehicle['notes'])) ?></dd></div>
                <?php endif; ?>
            </dl>
        </div>
        <!-- Panel: Trafik Sigortaları -->
        <div id="panel-trafik" role="tabpanel" aria-labelledby="tab-trafik" class="vehicle-panel hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Trafik sigortası kayıtları</span>
                <button type="button" onclick="openAddTrafficInsurance()" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm bg-emerald-600 text-white hover:bg-emerald-700">
                    <i class="bi bi-plus-lg mr-1"></i> Ekle
                </button>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($trafficInsurances)): ?>
                    <div class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Henüz trafik sigortası kaydı yok.</div>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Poliçe No</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sigorta Şirketi</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Başlangıç</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Bitiş</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Belgeler (PDF / resim)</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($trafficInsurances as $ti):
                                $tiDocs = $trafficInsuranceDocs[$ti['id']] ?? [];
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($ti['policy_number'] ?? '–') ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($ti['insurer_name'] ?? '–') ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= !empty($ti['start_date']) ? date('d.m.Y', strtotime($ti['start_date'])) : '–' ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= !empty($ti['end_date']) ? date('d.m.Y', strtotime($ti['end_date'])) : '–' ?></td>
                                <td class="px-4 py-2 text-sm">
                                    <?php foreach ($tiDocs as $d): ?>
                                        <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline block"><?= htmlspecialchars($d['file_name'] ?? 'Belge') ?></a>
                                        <form method="post" action="/araclar/trafik-sigortasi/belge-sil" class="inline" onsubmit="return confirm('Bu belgeyi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                                            <button type="submit" class="text-red-500 hover:underline text-xs">sil</button>
                                        </form>
                                    <?php endforeach; ?>
                                    <button type="button" onclick="openAddTrafficInsuranceDoc('<?= htmlspecialchars($ti['id']) ?>')" class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm mt-1"><i class="bi bi-plus"></i> Belge ekle</button>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <button type="button" onclick='openEditTrafficInsurance(<?= json_encode($ti) ?>)' class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm mr-2">Düzenle</button>
                                    <form method="post" action="/araclar/trafik-sigortasi/sil" class="inline" onsubmit="return confirm('Bu trafik sigortasını silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($ti['id']) ?>">
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <!-- Panel: Kaskolar -->
        <div id="panel-kasko" role="tabpanel" aria-labelledby="tab-kasko" class="vehicle-panel hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Kasko kayıtları</span>
                <button type="button" onclick="openAddKasko()" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm bg-emerald-600 text-white hover:bg-emerald-700">
                    <i class="bi bi-plus-lg mr-1"></i> Ekle
                </button>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($kaskos)): ?>
                    <div class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Henüz kasko kaydı yok.</div>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Poliçe No</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Sigorta Şirketi</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Başlangıç / Bitiş</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Prim</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Belgeler (PDF / resim)</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($kaskos as $k):
                                $kDocs = $kaskoDocs[$k['id']] ?? [];
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($k['policy_number'] ?? '–') ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($k['insurer_name'] ?? '–') ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= !empty($k['start_date']) ? date('d.m.Y', strtotime($k['start_date'])) : '–' ?> – <?= !empty($k['end_date']) ? date('d.m.Y', strtotime($k['end_date'])) : '–' ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= fmtMoney($k['premium_amount'] ?? null) ?></td>
                                <td class="px-4 py-2 text-sm">
                                    <?php foreach ($kDocs as $d): ?>
                                        <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline block"><?= htmlspecialchars($d['file_name'] ?? 'Belge') ?></a>
                                        <form method="post" action="/araclar/kasko/belge-sil" class="inline" onsubmit="return confirm('Bu belgeyi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                                            <button type="submit" class="text-red-500 hover:underline text-xs">sil</button>
                                        </form>
                                    <?php endforeach; ?>
                                    <button type="button" onclick="openAddKaskoDoc('<?= htmlspecialchars($k['id']) ?>')" class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm mt-1"><i class="bi bi-plus"></i> Belge ekle</button>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <button type="button" onclick='openEditKasko(<?= json_encode($k) ?>)' class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm mr-2">Düzenle</button>
                                    <form method="post" action="/araclar/kasko/sil" class="inline" onsubmit="return confirm('Bu kaskoyu silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($k['id']) ?>">
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <!-- Panel: Kaza Bilgileri -->
        <div id="panel-kaza" role="tabpanel" aria-labelledby="tab-kaza" class="vehicle-panel hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">Kaza kayıtları</span>
                <button type="button" onclick="openAddAccident()" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm bg-emerald-600 text-white hover:bg-emerald-700">
                    <i class="bi bi-plus-lg mr-1"></i> Ekle
                </button>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($accidents)): ?>
                    <div class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Henüz kaza kaydı yok.</div>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Hasar</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tamir Tutarı</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Belgeler (ruhsat, kimlik, kaza foto)</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($accidents as $a):
                                $aDocs = $accidentDocs[$a['id']] ?? [];
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white"><?= !empty($a['accident_date']) ? date('d.m.Y', strtotime($a['accident_date'])) : '–' ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars(mb_substr($a['description'] ?? '–', 0, 80)) ?><?= mb_strlen($a['description'] ?? '') > 80 ? '…' : '' ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars(mb_substr($a['damage_info'] ?? '–', 0, 50)) ?><?= mb_strlen($a['damage_info'] ?? '') > 50 ? '…' : '' ?></td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?= fmtMoney($a['repair_cost'] ?? null) ?></td>
                                <td class="px-4 py-2 text-sm">
                                    <?php foreach ($aDocs as $d): ?>
                                        <span class="text-gray-500 dark:text-gray-400 text-xs"><?= htmlspecialchars(VehicleAccidentDocument::kindLabel($d['document_kind'] ?? 'diger')) ?>:</span>
                                        <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline"><?= htmlspecialchars($d['file_name'] ?? 'Belge') ?></a>
                                        <form method="post" action="/araclar/kaza/belge-sil" class="inline" onsubmit="return confirm('Bu belgeyi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                                            <button type="submit" class="text-red-500 hover:underline text-xs">sil</button>
                                        </form>
                                    <?php endforeach; ?>
                                    <button type="button" onclick="openAddAccidentDoc('<?= htmlspecialchars($a['id']) ?>')" class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm mt-1 block"><i class="bi bi-plus"></i> Belge ekle (ruhsat / kimlik / kaza foto)</button>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <button type="button" onclick='openEditAccident(<?= json_encode($a) ?>)' class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm mr-2">Düzenle</button>
                                    <form method="post" action="/araclar/kaza/sil" class="inline" onsubmit="return confirm('Bu kaza kaydını silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($a['id']) ?>">
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Trafik Sigortası Ekle -->
<div id="addTrafficInsuranceModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('addTrafficInsuranceModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Trafik Sigortası Ekle</h3>
            <form method="post" action="/araclar/trafik-sigortasi/ekle" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Poliçe No</label>
                        <input type="text" name="policy_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sigorta Şirketi</label>
                        <input type="text" name="insurer_name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç</label>
                            <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
                            <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Belge (PDF veya resim)</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('addTrafficInsuranceModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Trafik Sigortası Belge Ekle -->
<div id="addTrafficInsuranceDocModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('addTrafficInsuranceDocModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Trafik Sigortası Belge Ekle</h3>
            <form method="post" action="/araclar/trafik-sigortasi/belge-ekle" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <input type="hidden" name="traffic_insurance_id" id="ati_doc_traffic_insurance_id" value="">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PDF veya resim</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('addTrafficInsuranceDocModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Yükle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Trafik Sigortası Düzenle -->
<div id="editTrafficInsuranceModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('editTrafficInsuranceModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Trafik Sigortası Düzenle</h3>
            <form method="post" action="/araclar/trafik-sigortasi/guncelle">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <input type="hidden" name="id" id="eti_id">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Poliçe No</label>
                        <input type="text" name="policy_number" id="eti_policy_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sigorta Şirketi</label>
                        <input type="text" name="insurer_name" id="eti_insurer_name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç</label>
                            <input type="date" name="start_date" id="eti_start_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
                            <input type="date" name="end_date" id="eti_end_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" id="eti_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('editTrafficInsuranceModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kasko Ekle -->
<div id="addKaskoModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('addKaskoModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kasko Ekle</h3>
            <form method="post" action="/araclar/kasko/ekle" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Poliçe No</label>
                        <input type="text" name="policy_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sigorta Şirketi</label>
                        <input type="text" name="insurer_name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç</label>
                            <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
                            <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prim (₺)</label>
                        <input type="text" name="premium_amount" placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Belge (PDF veya resim)</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('addKaskoModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kasko Belge Ekle -->
<div id="addKaskoDocModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('addKaskoDocModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kasko Belge Ekle</h3>
            <form method="post" action="/araclar/kasko/belge-ekle" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <input type="hidden" name="kasko_id" id="akasko_doc_kasko_id" value="">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PDF veya resim</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('addKaskoDocModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Yükle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kasko Düzenle -->
<div id="editKaskoModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('editKaskoModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kasko Düzenle</h3>
            <form method="post" action="/araclar/kasko/guncelle">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <input type="hidden" name="id" id="ek_id">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Poliçe No</label>
                        <input type="text" name="policy_number" id="ek_policy_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sigorta Şirketi</label>
                        <input type="text" name="insurer_name" id="ek_insurer_name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç</label>
                            <input type="date" name="start_date" id="ek_start_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
                            <input type="date" name="end_date" id="ek_end_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prim (₺)</label>
                        <input type="text" name="premium_amount" id="ek_premium_amount" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" id="ek_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('editKaskoModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kaza Belge Ekle -->
<div id="addAccidentDocModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('addAccidentDocModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kaza Belgesi Ekle</h3>
            <form method="post" action="/araclar/kaza/belge-ekle" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <input type="hidden" name="accident_id" id="akaza_doc_accident_id" value="">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Belge türü</label>
                        <select name="document_kind" id="akaza_doc_kind" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                            <option value="ruhsat">Ruhsat (kazaya karışan aracın)</option>
                            <option value="kimlik">Kimlik (sürücü / ilgili kişi)</option>
                            <option value="kaza_foto">Kaza fotoğrafı</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PDF veya resim</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('addAccidentDocModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Yükle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kaza Ekle -->
<div id="addAccidentModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('addAccidentModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kaza Kaydı Ekle</h3>
            <form method="post" action="/araclar/kaza/ekle">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kaza Tarihi</label>
                        <input type="date" name="accident_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" placeholder="Kazanın özeti"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasar Bilgisi</label>
                        <textarea name="damage_info" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" placeholder="Hasar detayı"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tamir Tutarı (₺)</label>
                        <input type="text" name="repair_cost" placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('addAccidentModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kaza Düzenle -->
<div id="editAccidentModal" class="modal-overlay hidden fixed inset-0 z-50 overflow-y-auto" onclick="if(!event.target.closest('.modal-content')) this.classList.add('hidden')">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 z-0 bg-black/50" onclick="document.getElementById('editAccidentModal').classList.add('hidden')"></div>
        <div class="modal-content relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kaza Kaydı Düzenle</h3>
            <form method="post" action="/araclar/kaza/guncelle">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vid) ?>">
                <input type="hidden" name="id" id="ea_id">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kaza Tarihi</label>
                        <input type="date" name="accident_date" id="ea_accident_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                        <textarea name="description" id="ea_description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasar Bilgisi</label>
                        <textarea name="damage_info" id="ea_damage_info" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tamir Tutarı (₺)</label>
                        <input type="text" name="repair_cost" id="ea_repair_cost" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
                        <textarea name="notes" id="ea_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('editAccidentModal').classList.add('hidden')" class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">İptal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var tabs = document.querySelectorAll('.vehicle-tab');
    var panels = document.querySelectorAll('.vehicle-panel');
    tabs.forEach(function(tab, i) {
        tab.addEventListener('click', function() {
            tabs.forEach(function(t) {
                t.setAttribute('aria-selected', 'false');
                t.classList.remove('active', 'border-emerald-600', 'text-emerald-600', 'dark:text-emerald-400');
                t.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            });
            panels.forEach(function(p) { p.classList.add('hidden'); });
            tab.setAttribute('aria-selected', 'true');
            tab.classList.add('active', 'border-emerald-600', 'text-emerald-600', 'dark:text-emerald-400');
            tab.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            var panelId = tab.getAttribute('aria-controls');
            if (panelId) document.getElementById(panelId).classList.remove('hidden');
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(function(el) {
        el.addEventListener('keydown', function(e) { if (e.key === 'Escape') { e.preventDefault(); el.classList.add('hidden'); } });
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var openModal = document.querySelector('.modal-overlay:not(.hidden)');
            if (openModal) { e.preventDefault(); openModal.classList.add('hidden'); }
        }
    });
})();
function openAddTrafficInsurance() { document.getElementById('addTrafficInsuranceModal').classList.remove('hidden'); }
function openAddTrafficInsuranceDoc(trafficInsuranceId) {
    document.getElementById('ati_doc_traffic_insurance_id').value = trafficInsuranceId;
    document.getElementById('addTrafficInsuranceDocModal').classList.remove('hidden');
}
function openEditTrafficInsurance(row) {
    document.getElementById('eti_id').value = row.id || '';
    document.getElementById('eti_policy_number').value = row.policy_number || '';
    document.getElementById('eti_insurer_name').value = row.insurer_name || '';
    document.getElementById('eti_start_date').value = row.start_date || '';
    document.getElementById('eti_end_date').value = row.end_date || '';
    document.getElementById('eti_notes').value = row.notes || '';
    document.getElementById('editTrafficInsuranceModal').classList.remove('hidden');
}
function openAddKasko() { document.getElementById('addKaskoModal').classList.remove('hidden'); }
function openAddKaskoDoc(kaskoId) {
    document.getElementById('akasko_doc_kasko_id').value = kaskoId;
    document.getElementById('addKaskoDocModal').classList.remove('hidden');
}
function openEditKasko(row) {
    document.getElementById('ek_id').value = row.id || '';
    document.getElementById('ek_policy_number').value = row.policy_number || '';
    document.getElementById('ek_insurer_name').value = row.insurer_name || '';
    document.getElementById('ek_start_date').value = row.start_date || '';
    document.getElementById('ek_end_date').value = row.end_date || '';
    document.getElementById('ek_premium_amount').value = row.premium_amount != null && row.premium_amount !== '' ? row.premium_amount : '';
    document.getElementById('ek_notes').value = row.notes || '';
    document.getElementById('editKaskoModal').classList.remove('hidden');
}
function openAddAccident() { document.getElementById('addAccidentModal').classList.remove('hidden'); }
function openAddAccidentDoc(accidentId) {
    document.getElementById('akaza_doc_accident_id').value = accidentId;
    document.getElementById('addAccidentDocModal').classList.remove('hidden');
}
function openEditAccident(row) {
    document.getElementById('ea_id').value = row.id || '';
    document.getElementById('ea_accident_date').value = row.accident_date || '';
    document.getElementById('ea_description').value = row.description || '';
    document.getElementById('ea_damage_info').value = row.damage_info || '';
    document.getElementById('ea_repair_cost').value = row.repair_cost != null && row.repair_cost !== '' ? row.repair_cost : '';
    document.getElementById('ea_notes').value = row.notes || '';
    document.getElementById('editAccidentModal').classList.remove('hidden');
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
