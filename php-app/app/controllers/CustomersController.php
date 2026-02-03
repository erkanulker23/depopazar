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
            } elseif ($redirectTo === 'new_job') {
                header('Location: /nakliye-isler');
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
        $name = trim($firstName . ' ' . $lastName);
        Notification::createForCompany($this->pdo, $companyId, 'customer', 'Müşteri eklendi', $name . ' müşterisi eklendi.', ['customer_id' => $customer['id']]);
        $redirectTo = $_POST['redirect_to'] ?? '';
        if ($redirectTo === 'new_sale') {
            $_SESSION['flash_success'] = 'Müşteri eklendi. Yeni satış formunda seçebilirsiniz.';
            header('Location: /girisler?newSale=1&newCustomerId=' . urlencode($customer['id']));
        } elseif ($redirectTo === 'new_job') {
            $_SESSION['flash_success'] = 'Müşteri eklendi. Nakliye formunda seçebilirsiniz.';
            header('Location: /nakliye-isler?newCustomerId=' . urlencode($customer['id']));
        } else {
            $_SESSION['flash_success'] = 'Müşteri eklendi.';
            header('Location: /musteriler');
        }
        exit;
    }

    /** Excel (CSV) dışa aktar – mevcut filtreye göre müşteri listesini indirir */
    public function exportCsv(): void
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

        $filename = 'musteriler_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel için)
        $headers = ['Ad', 'Soyad', 'E-posta', 'Telefon', 'TC Kimlik No', 'Adres', 'Notlar', 'Aktif'];
        fputcsv($out, $headers, ';');

        foreach ($customers as $c) {
            fputcsv($out, [
                $c['first_name'] ?? '',
                $c['last_name'] ?? '',
                $c['email'] ?? '',
                $c['phone'] ?? '',
                $c['identity_number'] ?? '',
                $c['address'] ?? '',
                $c['notes'] ?? '',
                !empty($c['is_active']) ? 'Evet' : 'Hayır',
            ], ';');
        }
        fclose($out);
        exit;
    }

    /** Excel (CSV) şablonu indir – sadece başlık satırı */
    public function downloadTemplate(): void
    {
        Auth::requireStaff();
        $filename = 'musteri_sablonu.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Ad', 'Soyad', 'E-posta', 'Telefon', 'TC Kimlik No', 'Adres', 'Notlar', 'Aktif'], ';');
        fputcsv($out, ['Ahmet', 'Yılmaz', 'ahmet@ornek.com', '05551234567', '', 'İstanbul', 'Not', 'Evet'], ';');
        fputcsv($out, ['Ayşe', 'Demir', 'ayse@ornek.com', '', '12345678901', 'Ankara', '', 'Evet'], ';');
        fclose($out);
        exit;
    }

    /** Excel (CSV) içe aktar – form göster */
    public function importForm(): void
    {
        Auth::requireStaff();
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $customers = [];
        $currentPage = 'musteriler';
        $q = '';
        require __DIR__ . '/../../views/customers/import.php';
    }

    /** Excel (CSV) içe aktar – dosyayı işle */
    public function importCsv(): void
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
            header('Location: /musteriler/excel-ice-aktar');
            exit;
        }

        $file = $_FILES['csv_file'] ?? null;
        if (!$file || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Lütfen bir CSV dosyası seçin veya yükleme hatası oluştu.';
            header('Location: /musteriler/excel-ice-aktar');
            exit;
        }

        $tmp = $file['tmp_name'];
        $handle = @fopen($tmp, 'rb');
        if (!$handle) {
            $_SESSION['flash_error'] = 'Dosya okunamadı.';
            header('Location: /musteriler/excel-ice-aktar');
            exit;
        }

        // İlk satır BOM veya başlık olabilir
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $first = fgetcsv($handle, 0, ';');
        if ($first === false) {
            $first = fgetcsv($handle, 0, ',');
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            $first = fgetcsv($handle, 0, ',');
        }
        $delimiter = ';';
        if ($first !== false && count($first) === 1 && strpos($first[0], ',') !== false) {
            $delimiter = ',';
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            $first = fgetcsv($handle, 0, ',');
        }

        $added = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_map('trim', $row);
            if (count($row) < 2) {
                continue;
            }
            $firstName = $row[0] ?? '';
            $lastName = $row[1] ?? '';
            if ($firstName === '' && $lastName === '') {
                continue;
            }
            if (stripos($firstName, 'ad') !== false && stripos($lastName, 'soyad') !== false) {
                continue; // başlık satırı
            }
            $email = $row[2] ?? '';
            $phone = isset($row[3]) ? trim($row[3]) : null;
            $identityNumber = isset($row[4]) && $row[4] !== '' ? trim($row[4]) : null;
            $address = isset($row[5]) && $row[5] !== '' ? trim($row[5]) : null;
            $notes = isset($row[6]) && $row[6] !== '' ? trim($row[6]) : null;
            $isActive = 1;
            if (isset($row[7])) {
                $v = trim($row[7]);
                if (stripos($v, 'hayır') !== false || $v === '0' || strtolower($v) === 'no') {
                    $isActive = 0;
                }
            }

            try {
                $data = [
                    'company_id'      => $companyId,
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'email'           => $email,
                    'phone'           => $phone ?: null,
                    'identity_number' => $identityNumber,
                    'address'         => $address,
                    'notes'           => $notes,
                    'is_active'       => $isActive,
                ];
                Customer::create($this->pdo, $data);
                $added++;
            } catch (Throwable $e) {
                $errors[] = $firstName . ' ' . $lastName . ': ' . $e->getMessage();
                $skipped++;
            }
        }
        fclose($handle);

        if ($added > 0) {
            Notification::createForCompany($this->pdo, $companyId, 'customer', 'Toplu müşteri ekleme', $added . ' müşteri Excel ile eklendi.' . ($skipped > 0 ? ' ' . $skipped . ' kayıt atlandı.' : ''));
            $_SESSION['flash_success'] = $added . ' müşteri eklendi.';
            if ($skipped > 0) {
                $_SESSION['flash_success'] .= ' ' . $skipped . ' kayıt atlandı.';
            }
        }
        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' …' : '');
        } elseif ($added === 0 && $skipped === 0) {
            $_SESSION['flash_error'] = 'İşlenecek geçerli satır bulunamadı. CSV formatı: Ad; Soyad; E-posta; Telefon; TC Kimlik No; Adres; Notlar; Aktif';
        }

        header('Location: /musteriler/excel-ice-aktar');
        exit;
    }
}
