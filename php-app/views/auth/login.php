<?php
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="data:,">
    <title>Giriş - <?= htmlspecialchars($projectName ?? 'DepoPazar') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left); }
        .login-card { max-width: 400px; margin-left: auto; margin-right: auto; }
        @media (max-width: 480px) { .login-card .card-body { padding: 1.25rem !important; } }
        input.form-control { font-size: 16px !important; min-height: 48px; }
        .btn-submit { min-height: 48px; font-size: 1rem; }
    </style>
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center py-4 px-3">
    <div class="w-100 login-card">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success bg-opacity-10 text-success mb-3" style="width:64px;height:64px;">
                <i class="bi bi-box-seam fs-2"></i>
            </div>
            <h1 class="h3 fw-bold"><?= htmlspecialchars($projectName ?? 'DepoPazar') ?></h1>
            <p class="text-muted small text-uppercase">Depo yönetim sistemi</p>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" action="/giris" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label small text-uppercase fw-bold text-muted">E-posta</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="ornek@email.com" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label small text-uppercase fw-bold text-muted">Şifre</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold btn-submit">Giriş Yap</button>
                </form>
            </div>
        </div>
        <p class="text-center text-muted small mt-4">© <?= date('Y') ?> <?= htmlspecialchars($projectName ?? 'DepoPazar') ?></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
