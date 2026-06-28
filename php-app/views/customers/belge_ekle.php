<?php
$customer = $customer ?? null;
$customerName = $customerName ?? '';
$customerId = $customer['id'] ?? '';
$uploadMaxLabel = uploadMaxBytesLabel();
$uploadMaxBytes = uploadMaxBytes();
$uploadChunkBytes = uploadChunkByteSize();
ob_start();
?>
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="/musteriler" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium">Müşteriler</a>
        <i class="bi bi-chevron-right"></i>
        <a href="/musteriler/<?= htmlspecialchars($customerId) ?>" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium"><?= htmlspecialchars($customerName) ?></a>
        <i class="bi bi-chevron-right"></i>
        <span class="text-gray-700 dark:text-gray-300 font-medium">Belge Ekle</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Belge Ekle</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($customerName) ?> için belge yükleyin (PDF, resim, Word).</p>
</div>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 max-w-lg">
    <form id="belgeEkleForm" method="post" action="/musteriler/belge-ekle" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customerId) ?>">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Belge adı</label>
            <input type="text" name="name" id="belge_name" placeholder="Örn: Kimlik fotokopisi" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Boş bırakılırsa dosya adı kullanılır.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dosya <span class="text-red-500">*</span></label>
            <input type="file" name="document" id="belge_file" required accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx" data-max-bytes="<?= (int) $uploadMaxBytes ?>" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PDF, JPG, PNG, GIF, WebP, DOC, DOCX · En fazla <?= htmlspecialchars($uploadMaxLabel) ?></p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Not</label>
            <textarea name="notes" id="belge_notes" rows="2" class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700 dark:text-white"></textarea>
        </div>
        <p id="belge_upload_status" class="hidden text-sm text-emerald-700 dark:text-emerald-300"></p>
        <div class="form-submit-bar flex flex-wrap gap-2 pt-2">
            <button type="submit" id="belge_submit_btn" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors disabled:opacity-60">
                <i class="bi bi-upload"></i> <span id="belge_submit_label">Yükle</span>
            </button>
            <a href="/musteriler/<?= htmlspecialchars($customerId) ?>" class="btn-touch inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">İptal</a>
        </div>
    </form>
</div>
<script>
(function() {
    var form = document.getElementById('belgeEkleForm');
    var fileInput = document.getElementById('belge_file');
    var submitBtn = document.getElementById('belge_submit_btn');
    var submitLabel = document.getElementById('belge_submit_label');
    var statusEl = document.getElementById('belge_upload_status');
    var CHUNK_SIZE = <?= (int) $uploadChunkBytes ?>;
    var MAX_BYTES = <?= (int) $uploadMaxBytes ?>;
    var customerId = <?= json_encode($customerId) ?>;

    function newUploadId() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'up' + Date.now().toString(36) + Math.random().toString(36).slice(2, 12);
    }

    function setUploading(active, text) {
        if (submitBtn) submitBtn.disabled = active;
        if (statusEl) {
            statusEl.classList.toggle('hidden', !active);
            statusEl.textContent = text || '';
        }
        if (submitLabel) submitLabel.textContent = active ? (text || 'Yükleniyor...') : 'Yükle';
    }

    if (!form || !fileInput) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!fileInput.files || !fileInput.files[0]) return;
        var file = fileInput.files[0];
        if (MAX_BYTES > 0 && file.size > MAX_BYTES) {
            alert('Dosya boyutu <?= htmlspecialchars($uploadMaxLabel) ?> sınırını aşıyor.');
            return;
        }
        var uploadId = newUploadId();
        var totalChunks = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));
        var nameEl = document.getElementById('belge_name');
        var notesEl = document.getElementById('belge_notes');

        (async function() {
            setUploading(true, 'Yükleniyor 0/' + totalChunks + '...');
            for (var i = 0; i < totalChunks; i++) {
                setUploading(true, 'Yükleniyor ' + (i + 1) + '/' + totalChunks + '...');
                var start = i * CHUNK_SIZE;
                var end = Math.min(file.size, start + CHUNK_SIZE);
                var blob = file.slice(start, end);
                var fd = new FormData();
                fd.append('upload_id', uploadId);
                fd.append('chunk_index', String(i));
                fd.append('total_chunks', String(totalChunks));
                fd.append('customer_id', customerId);
                fd.append('original_name', file.name);
                fd.append('name', nameEl ? nameEl.value : '');
                fd.append('notes', notesEl ? notesEl.value : '');
                fd.append('chunk', blob, file.name);
                var res;
                try {
                    res = await fetch('/musteriler/belge-ekle-parca', { method: 'POST', body: fd, credentials: 'same-origin' });
                } catch (err) {
                    setUploading(false);
                    alert('Bağlantı hatası. İnternet bağlantınızı kontrol edip tekrar deneyin.');
                    return;
                }
                if (res.status === 413) {
                    setUploading(false);
                    alert('Sunucu dosya boyutu sınırı çok düşük. Yöneticiden nginx ayarının güncellenmesini isteyin (client_max_body_size 20M).');
                    return;
                }
                var data;
                try {
                    data = await res.json();
                } catch (parseErr) {
                    setUploading(false);
                    alert('Sunucu yanıtı işlenemedi. Tekrar deneyin.');
                    return;
                }
                if (!data.ok) {
                    setUploading(false);
                    alert(data.error || 'Yükleme başarısız.');
                    return;
                }
                if (data.done && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
            }
            setUploading(false);
            alert('Yükleme tamamlanamadı. Tekrar deneyin.');
        })();
    });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
