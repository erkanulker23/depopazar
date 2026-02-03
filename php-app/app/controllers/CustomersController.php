<?php
class CustomersController
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
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        if ($companyId) {
            $customers = Customer::findAll($this->pdo, $companyId, $search);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $customers = Customer::findAll($this->pdo, null, $search);
        } else {
            $customers = [];
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/customers/index.php';
    }

    public function show(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
            header('Location: /musteriler');
            exit;
        }
        $contracts = Contract::findByCustomerId($this->pdo, $id, $companyId);
        $payments = Payment::findByCustomerId($this->pdo, $id, $companyId);
        $debt = Payment::sumUnpaidByCustomerId($this->pdo, $id, $companyId);
        $pageTitle = 'Müşteri: ' . trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        require __DIR__ . '/../../views/customers/detail.php';
    }

    /** Liste sayfasında satır genişletildiğinde gösterilecek HTML fragment (AJAX) */
    public function rowFragment(array $params): void
    {
        Auth::requireStaff();
        header('Content-Type: text/html; charset=utf-8');
        $id = $params['id'] ?? '';
        if (!$id) {
            echo '';
            return;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            echo '';
            return;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            echo '';
            return;
        }
        $contracts = Contract::findByCustomerId($this->pdo, $id, $companyId);
        $payments = Payment::findByCustomerId($this->pdo, $id, $companyId);
        $debt = Payment::sumUnpaidByCustomerId($this->pdo, $id, $companyId);
        require __DIR__ . '/../../views/customers/_row_fragment.php';
    }

    /** Müşteri depo etiketi (barkod) – yazdırılabilir sayfa */
    public function barcode(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
            header('Location: /musteriler');
            exit;
        }
        $items = Item::findByCustomerId($this->pdo, $id);
        require __DIR__ . '/../../views/customers/barcode.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if ($firstName === '' || $lastName === '') {
            $_SESSION['flash_error'] = 'Ad ve soyad zorunludur.';
            $redirectTo = $_POST['redirect_to'] ?? '';
            if ($redirectTo === 'new_sale') {
                header('Location: /girisler?newSale=1');
            } else {
                header('Location: /musteriler');
            }
            exit;
        }
        $data = [
            'company_id'      => $companyId,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'email'           => trim($_POST['email'] ?? ''),
            'phone'           => trim($_POST['phone'] ?? '') ?: null,
            'identity_number' => trim($_POST['identity_number'] ?? '') ?: null,
            'address'         => trim($_POST['address'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ];
        $customer = Customer::create($this->pdo, $data);
        $redirectTo = $_POST['redirect_to'] ?? '';
        if ($redirectTo === 'new_sale') {
            $_SESSION['flash_success'] = 'Müşteri eklendi. Yeni satış formunda seçebilirsiniz.';
            header('Location: /girisler?newSale=1&newCustomerId=' . urlencode($customer['id']));
        } else {
            $_SESSION['flash_success'] = 'Müşteri eklendi.';
            header('Location: /musteriler');
        }
        exit;
    }
}
