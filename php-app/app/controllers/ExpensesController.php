<?php
class ExpensesController
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
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $categories = $this->safeExpenseCategoriesFindAll($companyId);
        $bankAccounts = BankAccount::findAll($this->pdo, $companyId);
        $creditCards = $this->safeCreditCardsFindAll($companyId);
        $categoryId = isset($_GET['category_id']) ? trim($_GET['category_id']) : null;
        $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-t');
        $paymentSourceType = isset($_GET['payment_source_type']) ? trim($_GET['payment_source_type']) : null;
        $paymentSourceId = isset($_GET['payment_source_id']) ? trim($_GET['payment_source_id']) : null;
        $expenses = $this->safeExpensesFindAll($companyId, $categoryId ?: null, $startDate, $endDate, $paymentSourceType ?: null, $paymentSourceId ?: null);
        $totalAmount = array_sum(array_map(fn($e) => (float) ($e['amount'] ?? 0), $expenses));
        $expensesMigrationOk = $this->checkExpensesMigration();
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $pageTitle = 'Masraflar';
        require __DIR__ . '/../../views/expenses/index.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /masraflar');
            exit;
        }
        if (!$this->checkExpensesMigration()) {
            $_SESSION['flash_error'] = 'Masraflar modülü şu an kullanılamıyor.';
            header('Location: /masraflar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $categoryId = trim($_POST['category_id'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));
        $paymentSourceType = trim($_POST['payment_source_type'] ?? 'bank_account');
        $paymentSourceId = trim($_POST['payment_source_id'] ?? '');
        if (!$categoryId || $amount <= 0 || !$paymentSourceId) {
            $_SESSION['flash_error'] = 'Kategori, tutar ve ödeme kaynağı zorunludur.';
            header('Location: /masraflar');
            exit;
        }
        if (!in_array($paymentSourceType, ['bank_account', 'credit_card'], true)) {
            $_SESSION['flash_error'] = 'Geçersiz ödeme kaynağı.';
            header('Location: /masraflar');
            exit;
        }
        Expense::create($this->pdo, [
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'expense_date' => $expenseDate,
            'payment_source_type' => $paymentSourceType,
            'payment_source_id' => $paymentSourceId,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_by_user_id' => $user['id'] ?? null,
        ]);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $companyId, 'expense', 'Masraf kaydedildi', number_format($amount, 2, ',', '.') . ' ₺ tutarında masraf ' . ($actorName ?: 'sistem') . ' tarafından kaydedildi.', ['actor_name' => $actorName]);
        $_SESSION['flash_success'] = 'Masraf kaydedildi.';
        header('Location: /masraflar');
        exit;
    }

    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /masraflar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if (!$id) {
            header('Location: /masraflar');
            exit;
        }
        $categoryId = trim($_POST['category_id'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));
        $paymentSourceType = trim($_POST['payment_source_type'] ?? 'bank_account');
        $paymentSourceId = trim($_POST['payment_source_id'] ?? '');
        if (!$categoryId || $amount <= 0 || !$paymentSourceId) {
            $_SESSION['flash_error'] = 'Kategori, tutar ve ödeme kaynağı zorunludur.';
            header('Location: /masraflar');
            exit;
        }
        Expense::update($this->pdo, $id, [
            'category_id' => $categoryId,
            'amount' => $amount,
            'expense_date' => $expenseDate,
            'payment_source_type' => $paymentSourceType,
            'payment_source_id' => $paymentSourceId,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ], $companyId);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $companyId, 'expense', 'Masraf güncellendi', number_format($amount, 2, ',', '.') . ' ₺ tutarında masraf güncellendi.', ['actor_name' => $actorName]);
        $_SESSION['flash_success'] = 'Masraf güncellendi.';
        header('Location: /masraflar');
        exit;
    }

    public function delete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /masraflar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if ($id) {
            Expense::remove($this->pdo, $id, $companyId);
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $companyId, 'expense', 'Masraf silindi', 'Masraf kaydı silindi.', ['actor_name' => $actorName]);
            $_SESSION['flash_success'] = 'Masraf silindi.';
        }
        header('Location: /masraflar');
        exit;
    }

    public function addCategory(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /masraflar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['flash_error'] = 'Kategori adı zorunludur.';
            header('Location: /masraflar');
            exit;
        }
        ExpenseCategory::create($this->pdo, [
            'company_id' => $companyId,
            'name' => $name,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $companyId, 'expense', 'Masraf kategorisi eklendi', $name . ' masraf kategorisi eklendi.', ['actor_name' => $actorName]);
        $_SESSION['flash_success'] = 'Masraf kategorisi eklendi.';
        header('Location: /masraflar');
        exit;
    }

    public function updateCategory(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /masraflar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if (!$id || $name === '') {
            $_SESSION['flash_error'] = 'Kategori adı zorunludur.';
            header('Location: /masraflar');
            exit;
        }
        ExpenseCategory::update($this->pdo, $id, [
            'name' => $name,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ], $companyId);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $companyId, 'expense', 'Masraf kategorisi güncellendi', $name . ' masraf kategorisi güncellendi.', ['actor_name' => $actorName]);
        $_SESSION['flash_success'] = 'Masraf kategorisi güncellendi.';
        header('Location: /masraflar');
        exit;
    }

    public function deleteCategory(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /masraflar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if ($id) {
            if (ExpenseCategory::hasExpenses($this->pdo, $id)) {
                $_SESSION['flash_error'] = 'Bu kategoriye bağlı masraflar var, silinemez.';
            } else {
                ExpenseCategory::remove($this->pdo, $id, $companyId);
                $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                Notification::createForCompany($this->pdo, $companyId, 'expense', 'Masraf kategorisi silindi', 'Masraf kategorisi silindi.', ['actor_name' => $actorName]);
                $_SESSION['flash_success'] = 'Masraf kategorisi silindi.';
            }
        }
        header('Location: /masraflar');
        exit;
    }

    private function safeExpenseCategoriesFindAll(string $companyId): array
    {
        try {
            return ExpenseCategory::findAll($this->pdo, $companyId);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function safeCreditCardsFindAll(string $companyId): array
    {
        try {
            return CreditCard::findAll($this->pdo, $companyId);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function safeExpensesFindAll(string $companyId, ?string $categoryId, string $startDate, string $endDate, ?string $paymentSourceType, ?string $paymentSourceId): array
    {
        try {
            return Expense::findAll($this->pdo, $companyId, $categoryId, $startDate, $endDate, $paymentSourceType, $paymentSourceId);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function checkExpensesMigration(): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM credit_cards LIMIT 1');
            $stmt = $this->pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND COLUMN_NAME = 'transportation_job_id' LIMIT 1");
            return $stmt && $stmt->fetch();
        } catch (Throwable $e) {
            return false;
        }
    }
}
