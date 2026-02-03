<?php
class ServicesController
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
        if (!$companyId && ($user['role'] ?? '') === 'super_admin') {
            $companyId = null;
        }
        if ($companyId === null && ($user['role'] ?? '') !== 'super_admin') {
            $categories = [];
            $services = [];
        } else {
            $categories = ServiceCategory::findAll($this->pdo, $companyId);
            $categoryFilter = isset($_GET['kategori']) && $_GET['kategori'] !== '' ? trim($_GET['kategori']) : null;
            $services = Service::findAll($this->pdo, $companyId, $categoryFilter);
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/services/index.php';
    }

    public function addCategory(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /hizmetler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) { $_SESSION['flash_error'] = 'Şirket gerekli.'; header('Location: /hizmetler'); exit; }
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { $_SESSION['flash_error'] = 'Kategori adı gerekli.'; header('Location: /hizmetler'); exit; }
        ServiceCategory::create($this->pdo, ['company_id' => $companyId, 'name' => $name, 'description' => trim($_POST['description'] ?? '') ?: null]);
        $_SESSION['flash_success'] = 'Kategori eklendi.';
        header('Location: /hizmetler');
        exit;
    }

    public function updateCategory(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /hizmetler'); exit; }
        $id = trim($_POST['id'] ?? '');
        $cat = $id ? ServiceCategory::findOne($this->pdo, $id) : null;
        if (!$cat) { $_SESSION['flash_error'] = 'Kategori bulunamadı.'; header('Location: /hizmetler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($cat['company_id'] ?? '') !== $companyId) { header('Location: /hizmetler'); exit; }
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { $_SESSION['flash_error'] = 'Kategori adı gerekli.'; header('Location: /hizmetler'); exit; }
        ServiceCategory::update($this->pdo, $id, ['name' => $name, 'description' => trim($_POST['description'] ?? '') ?: null]);
        $_SESSION['flash_success'] = 'Kategori güncellendi.';
        header('Location: /hizmetler');
        exit;
    }

    public function deleteCategory(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /hizmetler'); exit; }
        $id = trim($_POST['id'] ?? '');
        $cat = $id ? ServiceCategory::findOne($this->pdo, $id) : null;
        if (!$cat) { $_SESSION['flash_error'] = 'Kategori bulunamadı.'; header('Location: /hizmetler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($cat['company_id'] ?? '') !== $companyId) { header('Location: /hizmetler'); exit; }
        ServiceCategory::softDelete($this->pdo, $id);
        $_SESSION['flash_success'] = 'Kategori silindi.';
        header('Location: /hizmetler');
        exit;
    }

    public function addService(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /hizmetler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) { $_SESSION['flash_error'] = 'Şirket gerekli.'; header('Location: /hizmetler'); exit; }
        $categoryId = trim($_POST['category_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($categoryId === '' || $name === '') { $_SESSION['flash_error'] = 'Kategori ve hizmet adı gerekli.'; header('Location: /hizmetler'); exit; }
        $cat = ServiceCategory::findOne($this->pdo, $categoryId);
        if (!$cat || ($cat['company_id'] ?? '') !== $companyId) { $_SESSION['flash_error'] = 'Geçersiz kategori.'; header('Location: /hizmetler'); exit; }
        Service::create($this->pdo, [
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $name,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'unit_price' => isset($_POST['unit_price']) && $_POST['unit_price'] !== '' ? (float) str_replace(',', '.', $_POST['unit_price']) : 0,
            'unit' => trim($_POST['unit'] ?? '') ?: null,
        ]);
        $_SESSION['flash_success'] = 'Hizmet eklendi.';
        header('Location: /hizmetler');
        exit;
    }

    public function updateService(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /hizmetler'); exit; }
        $id = trim($_POST['id'] ?? '');
        $svc = $id ? Service::findOne($this->pdo, $id) : null;
        if (!$svc) { $_SESSION['flash_error'] = 'Hizmet bulunamadı.'; header('Location: /hizmetler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($svc['company_id'] ?? '') !== $companyId) { header('Location: /hizmetler'); exit; }
        $categoryId = trim($_POST['category_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { $_SESSION['flash_error'] = 'Hizmet adı gerekli.'; header('Location: /hizmetler'); exit; }
        Service::update($this->pdo, $id, [
            'category_id' => $categoryId ?: $svc['category_id'],
            'name' => $name,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'unit_price' => isset($_POST['unit_price']) && $_POST['unit_price'] !== '' ? (float) str_replace(',', '.', $_POST['unit_price']) : 0,
            'unit' => trim($_POST['unit'] ?? '') ?: null,
        ]);
        $_SESSION['flash_success'] = 'Hizmet güncellendi.';
        header('Location: /hizmetler');
        exit;
    }

    public function deleteService(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /hizmetler'); exit; }
        $id = trim($_POST['id'] ?? '');
        $svc = $id ? Service::findOne($this->pdo, $id) : null;
        if (!$svc) { $_SESSION['flash_error'] = 'Hizmet bulunamadı.'; header('Location: /hizmetler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($svc['company_id'] ?? '') !== $companyId) { header('Location: /hizmetler'); exit; }
        Service::softDelete($this->pdo, $id);
        $_SESSION['flash_success'] = 'Hizmet silindi.';
        header('Location: /hizmetler');
        exit;
    }
}
