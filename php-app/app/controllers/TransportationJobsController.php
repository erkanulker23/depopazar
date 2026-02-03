<?php
class TransportationJobsController
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
        $customerSearch = isset($_GET['q']) ? trim($_GET['q']) : null;
        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int) $_GET['year'] : null;
        $month = isset($_GET['month']) && $_GET['month'] !== '' ? (int) $_GET['month'] : null;
        if ($companyId) {
            $jobs = TransportationJob::findAll($this->pdo, $companyId, $customerSearch, $year, $month);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $jobs = TransportationJob::findAll($this->pdo, null, $customerSearch, $year, $month);
        } else {
            $jobs = [];
        }
        $years = [];
        $stmt = $this->pdo->query('SELECT DISTINCT YEAR(job_date) AS y FROM transportation_jobs WHERE deleted_at IS NULL AND job_date IS NOT NULL ORDER BY y DESC');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $years[] = (int) $row['y'];
        }
        $customers = [];
        $services = [];
        $staff = [];
        if ($companyId) {
            $customers = Customer::findAll($this->pdo, $companyId);
            $services = Service::findAll($this->pdo, $companyId);
            $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE company_id = ? AND deleted_at IS NULL AND role IN (\'company_staff\', \'company_owner\') ORDER BY first_name, last_name');
            $stmt->execute([$companyId]);
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $customers = Customer::findAll($this->pdo, null);
            $services = Service::findAll($this->pdo, null);
            $stmt = $this->pdo->query('SELECT id, first_name, last_name, email FROM users WHERE deleted_at IS NULL AND role IN (\'company_staff\', \'company_owner\') ORDER BY first_name, last_name');
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/transportation_jobs/index.php';
    }

    public function edit(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /nakliye-isler');
            exit;
        }
        $job = TransportationJob::findOne($this->pdo, $id);
        if (!$job) {
            $_SESSION['flash_error'] = 'Nakliye işi bulunamadı.';
            header('Location: /nakliye-isler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($job['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu işe erişim yetkiniz yok.';
            header('Location: /nakliye-isler');
            exit;
        }
        if ($companyId) {
            $customers = Customer::findAll($this->pdo, $companyId);
            $services = Service::findAll($this->pdo, $companyId);
            $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE company_id = ? AND deleted_at IS NULL AND role IN (\'company_staff\', \'company_owner\') ORDER BY first_name, last_name');
            $stmt->execute([$companyId]);
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $customers = Customer::findAll($this->pdo, null);
            $services = Service::findAll($this->pdo, null);
            $stmt = $this->pdo->query('SELECT id, first_name, last_name, email FROM users WHERE deleted_at IS NULL AND role IN (\'company_staff\', \'company_owner\') ORDER BY first_name, last_name');
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/transportation_jobs/edit.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /nakliye-isler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId && ($user['role'] ?? '') !== 'super_admin') {
            $_SESSION['flash_error'] = 'Şirket bilgisi gerekli.';
            header('Location: /nakliye-isler');
            exit;
        }
        if (!$companyId) {
            $stmt = $this->pdo->query('SELECT id FROM companies WHERE deleted_at IS NULL LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $companyId = $row ? $row['id'] : null;
        }
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bulunamadı.';
            header('Location: /nakliye-isler');
            exit;
        }
        $customerId = trim($_POST['customer_id'] ?? '');
        if (!$customerId) {
            $_SESSION['flash_error'] = 'Müşteri seçin.';
            header('Location: /nakliye-isler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $customerId);
        if (!$customer || ($companyId && ($customer['company_id'] ?? '') !== $companyId)) {
            $_SESSION['flash_error'] = 'Geçersiz müşteri.';
            header('Location: /nakliye-isler');
            exit;
        }
        $jobType = trim($_POST['job_type'] ?? '') ?: null;
        try {
            TransportationJob::create($this->pdo, [
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'job_type' => $jobType,
                'pickup_address' => trim($_POST['pickup_address'] ?? '') ?: null,
                'pickup_floor_status' => trim($_POST['pickup_floor_status'] ?? '') ?: null,
                'pickup_elevator_status' => trim($_POST['pickup_elevator_status'] ?? '') ?: null,
                'pickup_room_count' => trim($_POST['pickup_room_count'] ?? '') !== '' ? (int) $_POST['pickup_room_count'] : null,
                'delivery_address' => trim($_POST['delivery_address'] ?? '') ?: null,
                'delivery_floor_status' => trim($_POST['delivery_floor_status'] ?? '') ?: null,
                'delivery_elevator_status' => trim($_POST['delivery_elevator_status'] ?? '') ?: null,
                'delivery_room_count' => trim($_POST['delivery_room_count'] ?? '') !== '' ? (int) $_POST['delivery_room_count'] : null,
                'price' => trim($_POST['price'] ?? '') !== '' ? (float) str_replace(',', '.', $_POST['price']) : null,
                'vat_rate' => trim($_POST['vat_rate'] ?? '') !== '' ? (float) str_replace(',', '.', $_POST['vat_rate']) : 20,
                'price_includes_vat' => isset($_POST['price_includes_vat']) && $_POST['price_includes_vat'] === '1',
                'job_date' => trim($_POST['job_date'] ?? '') ?: null,
                'status' => trim($_POST['status'] ?? '') ?: 'pending',
                'is_paid' => isset($_POST['is_paid']) && $_POST['is_paid'] === '1',
                'notes' => trim($_POST['notes'] ?? '') ?: null,
                'staff_ids' => isset($_POST['staff_ids']) && is_array($_POST['staff_ids']) ? array_filter($_POST['staff_ids']) : [],
            ]);
            $_SESSION['flash_success'] = 'Nakliye işi eklendi.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Kayıt oluşturulamadı: ' . $e->getMessage();
        }
        header('Location: /nakliye-isler');
        exit;
    }

    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /nakliye-isler');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if (!$id) {
            header('Location: /nakliye-isler');
            exit;
        }
        $job = TransportationJob::findOne($this->pdo, $id);
        if (!$job) {
            $_SESSION['flash_error'] = 'Nakliye işi bulunamadı.';
            header('Location: /nakliye-isler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($job['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu işe erişim yetkiniz yok.';
            header('Location: /nakliye-isler');
            exit;
        }
        try {
            TransportationJob::update($this->pdo, $id, [
                'job_type' => trim($_POST['job_type'] ?? '') ?: null,
                'pickup_address' => trim($_POST['pickup_address'] ?? '') ?: null,
                'pickup_floor_status' => trim($_POST['pickup_floor_status'] ?? '') ?: null,
                'pickup_elevator_status' => trim($_POST['pickup_elevator_status'] ?? '') ?: null,
                'pickup_room_count' => trim($_POST['pickup_room_count'] ?? '') !== '' ? (int) $_POST['pickup_room_count'] : null,
                'delivery_address' => trim($_POST['delivery_address'] ?? '') ?: null,
                'delivery_floor_status' => trim($_POST['delivery_floor_status'] ?? '') ?: null,
                'delivery_elevator_status' => trim($_POST['delivery_elevator_status'] ?? '') ?: null,
                'delivery_room_count' => trim($_POST['delivery_room_count'] ?? '') !== '' ? (int) $_POST['delivery_room_count'] : null,
                'price' => trim($_POST['price'] ?? '') !== '' ? (float) str_replace(',', '.', $_POST['price']) : null,
                'vat_rate' => trim($_POST['vat_rate'] ?? '') !== '' ? (float) str_replace(',', '.', $_POST['vat_rate']) : 20,
                'price_includes_vat' => isset($_POST['price_includes_vat']) && $_POST['price_includes_vat'] === '1',
                'job_date' => trim($_POST['job_date'] ?? '') ?: null,
                'status' => trim($_POST['status'] ?? '') ?: 'pending',
                'is_paid' => isset($_POST['is_paid']) && $_POST['is_paid'] === '1',
                'notes' => trim($_POST['notes'] ?? '') ?: null,
                'staff_ids' => isset($_POST['staff_ids']) && is_array($_POST['staff_ids']) ? array_filter($_POST['staff_ids']) : [],
            ]);
            $_SESSION['flash_success'] = 'Nakliye işi güncellendi.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Güncellenemedi: ' . $e->getMessage();
        }
        header('Location: /nakliye-isler');
        exit;
    }

    public function delete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /nakliye-isler');
            exit;
        }
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_filter(array_map('trim', $_POST['ids'])) : [];
        if (empty($ids)) {
            $id = trim($_POST['id'] ?? '');
            if ($id !== '') $ids = [$id];
        }
        if (empty($ids)) {
            $_SESSION['flash_error'] = 'Nakliye işi seçilmedi.';
            header('Location: /nakliye-isler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $deleted = 0;
        foreach ($ids as $id) {
            $job = TransportationJob::findOne($this->pdo, $id);
            if (!$job) continue;
            if ($companyId && ($job['company_id'] ?? '') !== $companyId) continue;
            TransportationJob::remove($this->pdo, $id);
            $deleted++;
        }
        if ($deleted > 0) {
            $_SESSION['flash_success'] = $deleted === 1 ? 'Nakliye işi silindi.' : $deleted . ' nakliye işi silindi.';
        } else {
            $_SESSION['flash_error'] = 'Silinecek nakliye işi bulunamadı veya yetkiniz yok.';
        }
        header('Location: /nakliye-isler');
        exit;
    }
}
