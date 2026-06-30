<?php
class PersonnelController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $tableExists = Personnel::tableExists($this->pdo);
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $search = $search !== '' ? $search : null;
        $jobType = isset($_GET['job_type']) && $_GET['job_type'] !== '' ? trim((string) $_GET['job_type']) : null;
        $activeFilter = isset($_GET['is_active']) && in_array($_GET['is_active'], ['0', '1'], true) ? $_GET['is_active'] : null;

        $personnel = [];
        if ($tableExists) {
            if (($user['role'] ?? '') === 'super_admin') {
                $personnel = Personnel::findAll($this->pdo, null, $search, $jobType, $activeFilter);
            } elseif ($companyId) {
                $personnel = Personnel::findAll($this->pdo, $companyId, $search, $jobType, $activeFilter);
            }
        }

        $jobTypeLabels = Personnel::jobTypeLabels();
        $canManage = in_array($user['role'] ?? '', ['super_admin', 'company_owner', 'company_staff', 'warehouse_manager'], true);
        $companies = [];
        if (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name');
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        require __DIR__ . '/../../views/personnel/index.php';
    }

    public function detail(array $params): void
    {
        Auth::requireStaff();
        $id = trim($params['id'] ?? '');
        if (!$id || !Personnel::tableExists($this->pdo)) {
            header('Location: /personel');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $isSuperAdmin = ($user['role'] ?? '') === 'super_admin';
        $personnelRow = Personnel::findOne($this->pdo, $id, $isSuperAdmin ? null : $companyId);
        if (!$personnelRow) {
            Auth::setSession('flash_error', 'Personel bulunamadı.');
            header('Location: /personel');
            exit;
        }
        $personnelCompanyId = $personnelRow['company_id'] ?? null;
        $contracts = Personnel::findContractsForPersonnel($this->pdo, $id, $personnelCompanyId);
        $payments = Personnel::findPaymentsCollectedForPersonnel($this->pdo, $id, $personnelCompanyId);
        $stats = Personnel::getDetailStats($contracts, $payments);
        $jobTypeLabels = Personnel::jobTypeLabels();
        $canManage = in_array($user['role'] ?? '', ['super_admin', 'company_owner', 'company_staff', 'warehouse_manager'], true);
        $companyName = null;
        if ($isSuperAdmin && $personnelCompanyId) {
            $stmt = $this->pdo->prepare('SELECT name FROM companies WHERE id = ? AND deleted_at IS NULL LIMIT 1');
            $stmt->execute([$personnelCompanyId]);
            $companyName = $stmt->fetchColumn() ?: null;
        }
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        require __DIR__ . '/../../views/personnel/detail.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /personel');
            exit;
        }
        if (!Personnel::tableExists($this->pdo)) {
            Auth::setSession('flash_error', 'Personel tablosu henüz oluşturulmamış. Deploy sonrası migration çalıştırın.');
            header('Location: /personel');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (($user['role'] ?? '') === 'super_admin') {
            $companyId = trim($_POST['company_id'] ?? '') ?: $companyId;
        }
        if (!$companyId) {
            Auth::setSession('flash_error', 'Personel eklemek için şirket gerekli.');
            header('Location: /personel');
            exit;
        }
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if ($firstName === '' || $lastName === '') {
            Auth::setSession('flash_error', 'Ad ve soyad zorunludur.');
            header('Location: /personel');
            exit;
        }
        try {
            $id = Personnel::create($this->pdo, [
                'company_id' => $companyId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => trim($_POST['phone'] ?? ''),
                'job_type' => $_POST['job_type'] ?? 'diger',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'notes' => trim($_POST['notes'] ?? ''),
            ]);
            $photoUrl = storePersonnelPhotoUpload($_FILES['photo'] ?? null, $id);
            if ($photoUrl) {
                try {
                    Personnel::updatePhotoUrl($this->pdo, $id, $photoUrl, $companyId);
                } catch (Throwable $e) {
                }
            }
            Notification::createForCompany($this->pdo, $companyId, 'personnel', 'Personel eklendi', trim($firstName . ' ' . $lastName) . ' saha personeli olarak eklendi.');
            Auth::setSession('flash_success', 'Personel eklendi.');
        } catch (Throwable $e) {
            Auth::setSession('flash_error', 'Personel eklenirken hata oluştu.');
        }
        header('Location: /personel');
        exit;
    }

    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /personel');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        $row = $id ? Personnel::findOne($this->pdo, $id) : null;
        if (!$row) {
            Auth::setSession('flash_error', 'Personel bulunamadı.');
            header('Location: /personel');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($row['company_id'] ?? '') !== $companyId) {
            header('Location: /personel');
            exit;
        }
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if ($firstName === '' || $lastName === '') {
            Auth::setSession('flash_error', 'Ad ve soyad zorunludur.');
            header('Location: /personel');
            exit;
        }
        Personnel::update($this->pdo, $id, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim($_POST['phone'] ?? ''),
            'job_type' => $_POST['job_type'] ?? 'diger',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'notes' => trim($_POST['notes'] ?? ''),
        ], $companyId ?: null);

        if (!empty($_POST['remove_photo'])) {
            if (!empty($row['photo_url'])) {
                unlinkPublicFile($row['photo_url']);
            }
            try {
                Personnel::updatePhotoUrl($this->pdo, $id, null, $companyId ?: null);
            } catch (Throwable $e) {
            }
        } else {
            $photoUrl = storePersonnelPhotoUpload($_FILES['photo'] ?? null, $id);
            if ($photoUrl) {
                if (!empty($row['photo_url'])) {
                    unlinkPublicFile($row['photo_url']);
                }
                try {
                    Personnel::updatePhotoUrl($this->pdo, $id, $photoUrl, $companyId ?: null);
                } catch (Throwable $e) {
                }
            }
        }

        Notification::createForCompany($this->pdo, $row['company_id'] ?? null, 'personnel', 'Personel güncellendi', trim($firstName . ' ' . $lastName) . ' bilgileri güncellendi.');
        Auth::setSession('flash_success', 'Personel güncellendi.');
        $redirect = trim($_POST['redirect'] ?? '');
        header('Location: ' . ($redirect !== '' && preg_match('#^/personel/[a-f0-9\-]+$#i', $redirect) ? $redirect : '/personel'));
        exit;
    }

    public function delete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /personel');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        $row = $id ? Personnel::findOne($this->pdo, $id) : null;
        if (!$row) {
            Auth::setSession('flash_error', 'Personel bulunamadı.');
            header('Location: /personel');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($row['company_id'] ?? '') !== $companyId) {
            header('Location: /personel');
            exit;
        }
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if (!empty($row['photo_url'])) {
            unlinkPublicFile($row['photo_url']);
        }
        Personnel::delete($this->pdo, $id, $companyId ?: null);
        Notification::createForCompany($this->pdo, $row['company_id'] ?? null, 'personnel', 'Personel silindi', $name . ' silindi.');
        Auth::setSession('flash_success', 'Personel silindi.');
        header('Location: /personel');
        exit;
    }
}
