<?php
class PaymentsController
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
        $collectMode = isset($_GET['collect']) && $_GET['collect'] !== '0';
        $preselectedCustomerId = isset($_GET['customer']) ? trim((string) $_GET['customer']) : '';
        $statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'paid', 'overdue', 'cancelled', 'unpaid', 'early'], true) ? $_GET['status'] : '';
        $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';
        $searchQ = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $listCompanyId = $companyId;
        if (!$listCompanyId && ($user['role'] ?? '') !== 'super_admin') {
            $listCompanyId = null;
            $payments = [];
        } else {
            $payments = Payment::findForList(
                $this->pdo,
                $listCompanyId,
                $statusFilter !== '' ? $statusFilter : null,
                $searchQ !== '' ? $searchQ : null,
                $dateFrom !== '' ? $dateFrom : null,
                $dateTo !== '' ? $dateTo : null,
                $collectMode && $statusFilter === ''
            );
        }
        $payments = array_values($payments);
        $unpaid = array_filter($payments, fn($p) => paymentIsCollectible($p));
        $unpaidPaymentsByCustomer = [];
        foreach ($unpaid as $p) {
            $cid = $p['customer_id'] ?? '';
            if ($cid === '') continue;
            if (!isset($unpaidPaymentsByCustomer[$cid])) $unpaidPaymentsByCustomer[$cid] = [];
            $unpaidPaymentsByCustomer[$cid][] = $p;
        }
        $customersWithDebt = [];
        foreach ($unpaidPaymentsByCustomer as $cid => $list) {
            $first = $list[0];
            $customersWithDebt[] = ['id' => $cid, 'customer_first_name' => $first['customer_first_name'] ?? '', 'customer_last_name' => $first['customer_last_name'] ?? '', 'payments' => $list];
        }
        if ($preselectedCustomerId !== '') {
            $customersWithDebt = array_values(array_filter(
                $customersWithDebt,
                fn($c) => (string) ($c['id'] ?? '') === $preselectedCustomerId
            ));
        }
        usort($customersWithDebt, function ($a, $b) {
            $totalA = array_sum(array_map(fn($p) => (float) ($p['amount'] ?? 0), $a['payments'] ?? []));
            $totalB = array_sum(array_map(fn($p) => (float) ($p['amount'] ?? 0), $b['payments'] ?? []));
            if ($totalA !== $totalB) {
                return $totalB <=> $totalA;
            }
            $na = trim(($a['customer_first_name'] ?? '') . ' ' . ($a['customer_last_name'] ?? ''));
            $nb = trim(($b['customer_first_name'] ?? '') . ' ' . ($b['customer_last_name'] ?? ''));
            return strcasecmp($na, $nb);
        });
        // Müşteri bazlı liste: tüm ödemeleri müşteriye göre grupla (dropdown için)
        $paymentsByCustomer = [];
        foreach ($payments as $p) {
            $cid = $p['customer_id'] ?? '';
            if ($cid === '') continue;
            if (!isset($paymentsByCustomer[$cid])) {
                $paymentsByCustomer[$cid] = [
                    'id' => $cid,
                    'customer_first_name' => $p['customer_first_name'] ?? '',
                    'customer_last_name' => $p['customer_last_name'] ?? '',
                    'customer_email' => $p['customer_email'] ?? '',
                    'payments' => [],
                ];
            }
            $paymentsByCustomer[$cid]['payments'][] = $p;
        }
        // Her müşterinin ödemelerini vade tarihine göre sırala (eskiden yeniye)
        foreach ($paymentsByCustomer as $cid => $row) {
            usort($paymentsByCustomer[$cid]['payments'], function ($a, $b) {
                $da = strtotime($a['due_date'] ?? '');
                $db = strtotime($b['due_date'] ?? '');
                return $da <=> $db;
            });
        }
        // Müşteri adına göre sırala
        uasort($paymentsByCustomer, function ($a, $b) {
            $na = trim(($a['customer_first_name'] ?? '') . ' ' . ($a['customer_last_name'] ?? ''));
            $nb = trim(($b['customer_first_name'] ?? '') . ' ' . ($b['customer_last_name'] ?? ''));
            return strcasecmp($na, $nb);
        });
        $paymentsByCustomer = array_values($paymentsByCustomer);
        $totalPayments = count($payments);
        $totalPages = 1;
        $page = 1;
        $hasActiveFilters = $statusFilter !== '' || $searchQ !== '' || $dateFrom !== '' || $dateTo !== '';
        $payStatus = $statusFilter;
        $payQ = $searchQ;
        $statusLabels = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal', 'unpaid' => 'Bekleyen / Gecikmiş', 'early' => 'Erken ödendi'];
        $bankAccounts = [];
        if ($companyId) {
            $stmt = $this->pdo->prepare('SELECT * FROM bank_accounts WHERE company_id = ? AND deleted_at IS NULL AND is_active = 1 ORDER BY bank_name');
            $stmt->execute([$companyId]);
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT * FROM bank_accounts WHERE deleted_at IS NULL AND is_active = 1 ORDER BY bank_name');
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        $paymentsByCustomer = $paymentsByCustomer ?? [];
        $totalPayments = $totalPayments ?? 0;
        $preselectedCustomerId = $preselectedCustomerId ?? '';
        require __DIR__ . '/../../views/payments/index.php';
    }

    public function markPaid(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /odemeler');
            exit;
        }
        $paymentIds = isset($_POST['payment_ids']) && is_array($_POST['payment_ids']) ? array_filter($_POST['payment_ids']) : (isset($_POST['payment_id']) && $_POST['payment_id'] !== '' ? [$_POST['payment_id']] : []);
        $chargeIds = isset($_POST['charge_ids']) && is_array($_POST['charge_ids']) ? array_filter($_POST['charge_ids']) : [];
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $bankAccountId = trim($_POST['bank_account_id'] ?? '') ?: null;
        $transactionId = trim($_POST['transaction_id'] ?? '') ?: null;
        $notes = trim($_POST['notes'] ?? '') ?: null;
        $paidAt = trim($_POST['paid_at'] ?? '') ?: null;
        if (empty($paymentIds) && empty($chargeIds)) {
            Auth::setSession('flash_error', 'Geçersiz istek. Ödeme/borç seçin.');
            header('Location: /odemeler');
            exit;
        }
        if (!empty($paymentIds) && !in_array($paymentMethod, ['bank_transfer', 'credit_card'], true)) {
            Auth::setSession('flash_error', 'Geçersiz istek. Ödeme yöntemini belirleyin (Havale veya Kredi Kartı).');
            header('Location: /odemeler');
            exit;
        }
        if (!empty($paymentIds) && $paymentMethod === 'bank_transfer' && !$bankAccountId) {
            Auth::setSession('flash_error', 'Havale ile ödeme için banka hesabı seçin.');
            header('Location: /odemeler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        foreach ($paymentIds as $pid) {
            $payment = Payment::findOne($this->pdo, $pid);
            if (!$payment) continue;
            if ($companyId && ($payment['company_id'] ?? '') !== $companyId) {
                Auth::setSession('flash_error', 'Bu ödemeye erişim yetkiniz yok.');
                header('Location: /odemeler');
                exit;
            }
        }
        foreach ($chargeIds as $cid) {
            $charge = CustomerCharge::findOne($this->pdo, $cid);
            if (!$charge) continue;
            if ($companyId && ($charge['company_id'] ?? '') !== $companyId) {
                Auth::setSession('flash_error', 'Bu borç kaydına erişim yetkiniz yok.');
                header('Location: /odemeler');
                exit;
            }
        }
        if (count($paymentIds) > 1 && empty($_POST['confirm_multi_period'])) {
            $dueDays = [];
            $byContract = [];
            foreach ($paymentIds as $pid) {
                $p = Payment::findOne($this->pdo, $pid);
                if (!$p) {
                    continue;
                }
                if (!empty($p['due_date'])) {
                    $dueDays[] = substr((string) $p['due_date'], 0, 10);
                }
                $cid = $p['contract_id'] ?? '';
                if ($cid !== '') {
                    $byContract[$cid][] = $pid;
                }
            }
            $needsConfirm = false;
            foreach ($byContract as $ids) {
                if (count($ids) > 1) {
                    $needsConfirm = true;
                    break;
                }
            }
            if (!$needsConfirm && count($dueDays) > 1) {
                $minDue = min($dueDays);
                $maxDue = max($dueDays);
                $spanDays = (int) ((strtotime($maxDue) - strtotime($minDue)) / 86400);
                if ($spanDays >= 28) {
                    $needsConfirm = true;
                }
            }
            if ($needsConfirm) {
                Auth::setSession('flash_error', count($paymentIds) . ' farklı taksit aynı anda ödendi işaretlenecek. Yalnızca gerçekten tahsil ettiğiniz taksit(ler)i seçin ve onaylayın.');
                $redirect = trim($_POST['redirect'] ?? '');
                header('Location: ' . ($redirect !== '' && preg_match('#^/[a-z0-9/\-]+$#i', $redirect) ? $redirect : '/odemeler'));
                exit;
            }
        }
        try {
            $paidByUserId = !empty($user['id']) ? (string) $user['id'] : null;
            if (count($paymentIds) === 1 && empty($chargeIds)) {
                Payment::markAsPaid($this->pdo, $paymentIds[0], $paymentMethod, $transactionId, $notes, $bankAccountId, $paidAt, $paidByUserId);
                $firstPayment = Payment::findOne($this->pdo, $paymentIds[0]);
                if ($firstPayment && !empty($firstPayment['contract_id'])) {
                    $contract = Contract::findOne($this->pdo, $firstPayment['contract_id']);
                    if ($contract) {
                        $whId = $contract['warehouse_id'] ?? null;
                        Notification::createForCompanyAndWarehouse($this->pdo, $contract['company_id'] ?? null, $whId, 'payment', 'Ödeme alındı', 'Sözleşme ' . ($contract['contract_number'] ?? '') . ' için ödeme alındı.', ['contract_id' => $firstPayment['contract_id'], 'warehouse_id' => $whId]);
                        $this->sendPaymentReceivedEmails($firstPayment);
                    }
                }
            } elseif (!empty($paymentIds)) {
                Payment::markManyAsPaid($this->pdo, $paymentIds, $paymentMethod, $transactionId, $notes, $bankAccountId, $paidAt, $paidByUserId);
                $notifiedWarehouses = [];
                foreach ($paymentIds as $pid) {
                    $p = Payment::findOne($this->pdo, $pid);
                    if ($p) {
                        $this->sendPaymentReceivedEmails($p);
                        if (!empty($p['contract_id'])) {
                            $c = Contract::findOne($this->pdo, $p['contract_id']);
                            $whId = $c['warehouse_id'] ?? null;
                            if ($whId && !isset($notifiedWarehouses[$whId])) {
                                $notifiedWarehouses[$whId] = true;
                                Notification::createForWarehouse($this->pdo, $whId, $companyId, 'payment', 'Ödemeler alındı', count($paymentIds) . ' adet ödeme kaydedildi.', ['warehouse_id' => $whId]);
                            }
                        }
                    }
                }
                Notification::createForCompany($this->pdo, $companyId, 'payment', 'Ödemeler alındı', count($paymentIds) . ' adet ödeme kaydedildi.');
            }
            if (!empty($chargeIds)) {
                CustomerCharge::markManyAsPaid($this->pdo, $chargeIds, $notes, $paidAt);
            }
            Auth::setSession('flash_success', count($paymentIds) > 1
                ? count($paymentIds) . ' ödeme kaydedildi.'
                : 'Ödeme kaydedildi.');
        } catch (Exception $e) {
            Auth::setSession('flash_error', 'Kayıt yapılamadı: ' . $e->getMessage());
        }
        $redirect = trim($_POST['redirect'] ?? '');
        if ($redirect !== '' && preg_match('#^/[a-z0-9/\-]+$#i', $redirect)) {
            header('Location: ' . $redirect);
        } else {
            header('Location: /odemeler');
        }
        exit;
    }

    /** Ödenmiş kaydın tahsilat tarihini güncelle */
    public function updatePaidAt(array $params): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /odemeler');
            exit;
        }
        $id = $params['id'] ?? '';
        if (!$id) {
            Auth::setSession('flash_error', 'Ödeme bulunamadı.');
            header('Location: /odemeler');
            exit;
        }
        $payment = Payment::findOne($this->pdo, $id);
        if (!$payment) {
            Auth::setSession('flash_error', 'Ödeme kaydı bulunamadı.');
            header('Location: /odemeler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($payment['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu ödemeyi düzenleme yetkiniz yok.');
            header('Location: /odemeler');
            exit;
        }
        if (($payment['status'] ?? '') !== 'paid') {
            Auth::setSession('flash_error', 'Sadece ödenmiş ödemelerin tarihi değiştirilebilir.');
            header('Location: /odemeler/' . $id);
            exit;
        }
        $paidAtRaw = trim($_POST['paid_at'] ?? '');
        if ($paidAtRaw === '') {
            Auth::setSession('flash_error', 'Tahsilat tarihi zorunludur.');
            header('Location: /odemeler/' . $id);
            exit;
        }
        Payment::updatePaidAt($this->pdo, $id, $paidAtRaw);
        Auth::setSession('flash_success', 'Tahsilat tarihi güncellendi.');
        header('Location: /odemeler/' . $id);
        exit;
    }

    public function show(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /odemeler');
            exit;
        }
        $payment = Payment::findOne($this->pdo, $id);
        if (!$payment) {
            Auth::setSession('flash_error', 'Ödeme kaydı bulunamadı.');
            header('Location: /odemeler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($payment['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu ödemeye erişim yetkiniz yok.');
            header('Location: /odemeler');
            exit;
        }
        $statusLabels = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal'];
        $company = !empty($payment['company_id']) ? Company::findOne($this->pdo, $payment['company_id']) : null;
        $pageTitle = 'Ödeme: ' . ($payment['payment_number'] ?? $id);
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        require __DIR__ . '/../../views/payments/detail.php';
    }

    /** Ödemeyi iptal et (yanlış işlem geri alınsın) */
    public function cancel(array $params): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /odemeler');
            exit;
        }
        $id = $params['id'] ?? '';
        if (!$id) {
            Auth::setSession('flash_error', 'Ödeme bulunamadı.');
            header('Location: /odemeler');
            exit;
        }
        $payment = Payment::findOne($this->pdo, $id);
        if (!$payment) {
            Auth::setSession('flash_error', 'Ödeme kaydı bulunamadı.');
            header('Location: /odemeler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($payment['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu ödemeyi iptal etme yetkiniz yok.');
            header('Location: /odemeler');
            exit;
        }
        if (($payment['status'] ?? '') !== 'paid') {
            Auth::setSession('flash_error', 'Sadece ödenmiş ödemeler iptal edilebilir.');
            header('Location: /odemeler/' . $id);
            exit;
        }
        if (Payment::cancel($this->pdo, $id)) {
            Auth::setSession('flash_success', 'Ödeme iptal edildi.');
        } else {
            Auth::setSession('flash_error', 'İptal işlemi yapılamadı.');
        }
        $redirect = isset($_POST['redirect']) ? trim($_POST['redirect']) : '';
        if ($redirect !== '' && preg_match('#^/[a-z0-9/\-]+$#i', $redirect)) {
            header('Location: ' . $redirect);
        } else {
            header('Location: /odemeler/' . $id);
        }
        exit;
    }

    public function printPage(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /odemeler');
            exit;
        }
        $payment = Payment::findOne($this->pdo, $id);
        if (!$payment) {
            Auth::setSession('flash_error', 'Ödeme kaydı bulunamadı.');
            header('Location: /odemeler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($payment['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu ödemeye erişim yetkiniz yok.');
            header('Location: /odemeler');
            exit;
        }
        $statusLabels = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal'];
        $company = !empty($payment['company_id']) ? Company::findOne($this->pdo, $payment['company_id']) : null;
        require __DIR__ . '/../../views/payments/print.php';
    }

    /**
     * Ödeme alındı bildirim e-postaları (müşteri + yönetici). Modern HTML şablon kullanır.
     */
    private function sendPaymentReceivedEmails(array $payment): void
    {
        $companyId = $payment['company_id'] ?? null;
        if (!$companyId) {
            return;
        }
        $mail = Company::getMailSettings($this->pdo, $companyId);
        if (!$mail || empty($mail['smtp_host']) || empty($mail['is_active'])) {
            return;
        }
        $config = require defined('APP_ROOT') ? APP_ROOT . '/config/config.php' : __DIR__ . '/../../config/config.php';
        $appName = $config['app_name'] ?? 'Depo ve Nakliye Takip';
        $musteriAdi = trim(($payment['customer_first_name'] ?? '') . ' ' . ($payment['customer_last_name'] ?? ''));
        $tutar = number_format((float) ($payment['amount'] ?? 0), 2, ',', '.') . ' ₺';
        $sozlesmeNo = $payment['contract_number'] ?? '';
        $paidAt = $payment['paid_at'] ?? null;
        $odemeTarihi = $paidAt ? date('d.m.Y H:i', strtotime($paidAt)) : date('d.m.Y H:i');
        $pm = $payment['payment_method'] ?? '';
        $odemeYontemi = in_array($pm, ['cash', 'nakit'], true) ? 'Havale/EFT' : ($pm === 'bank_transfer' || $pm === 'havale' ? 'Havale/EFT' : ($pm === 'credit_card' || $pm === 'kredi_karti' ? 'Kredi Kartı' : ($pm !== '' ? $pm : 'Belirtilmedi')));
        $hesapAdi = '';
        if (!empty($payment['bank_account_id'])) {
            $stmt = $this->pdo->prepare('SELECT bank_name, account_holder_name FROM bank_accounts WHERE id = ? AND deleted_at IS NULL LIMIT 1');
            $stmt->execute([$payment['bank_account_id']]);
            $ba = $stmt->fetch(PDO::FETCH_ASSOC);
            $hesapAdi = $ba ? trim(($ba['bank_name'] ?? '') . ' - ' . ($ba['account_holder_name'] ?? '')) : '';
        }
        if ($hesapAdi === '') {
            $hesapAdi = 'Belirtilmedi';
        }
        $defaultCustomer = "Sayın {musteri_adi},\n\n{tutar} tutarındaki ödemeniz alınmıştır.\n\nTeşekkür ederiz.";
        $defaultAdmin = "Ödeme bildirimi:\n\n{musteri_adi} müşterisi adına {tutar} tutarında ödeme alındı.\nSözleşme: {sozlesme_no}\nÖdeme tarihi: {odeme_tarihi}\nÖdeme yöntemi: {odeme_yontemi}\nHesap: {hesap_adi}";
        $tplCustomer = !empty(trim($mail['payment_received_template'] ?? '')) ? $mail['payment_received_template'] : $defaultCustomer;
        $tplAdmin = !empty(trim($mail['admin_payment_received_template'] ?? '')) ? $mail['admin_payment_received_template'] : $defaultAdmin;
        $replace = [
            '{musteri_adi}' => $musteriAdi,
            '{tutar}' => $tutar,
            '{sozlesme_no}' => $sozlesmeNo,
            '{odeme_tarihi}' => $odemeTarihi,
            '{odeme_yontemi}' => $odemeYontemi,
            '{hesap_adi}' => $hesapAdi,
        ];

        $user = Auth::user();
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $emailContext = [
            'actor_name' => $actorName,
            'acted_at' => $paidAt ?? date('Y-m-d H:i:s'),
            'action_title' => 'Ödeme alındı',
        ];

        if (!empty($mail['notify_customer_on_payment'])) {
            $customerEmail = trim($payment['customer_email'] ?? '');
            if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $bodyPlain = str_replace(array_keys($replace), array_values($replace), $tplCustomer);
                MailService::sendTemplated(
                    $mail,
                    $customerEmail,
                    $appName . ' – Ödeme Alındı',
                    'Ödeme Alındı',
                    $bodyPlain,
                    $tutar,
                    $emailContext
                );
            }
        }

        if (!empty($mail['notify_admin_on_payment'])) {
            $staff = User::findStaff($this->pdo, $companyId);
            $adminBodyPlain = str_replace(array_keys($replace), array_values($replace), $tplAdmin);
            $adminContext = array_merge($emailContext, ['action_title' => 'Ödeme bildirimi']);
            foreach ($staff as $u) {
                $email = trim($u['email'] ?? '');
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    MailService::sendTemplated(
                        $mail,
                        $email,
                        $appName . ' – Ödeme alındı: ' . $musteriAdi,
                        'Ödeme Bildirimi',
                        $adminBodyPlain,
                        $musteriAdi . ' – ' . $tutar,
                        $adminContext
                    );
                }
            }
        }
    }

    /** Menü rozeti: tahsil edilebilir ödeme sayısı (async yükleme) */
    public function apiCollectibleCount(): void
    {
        Auth::requireStaff();
        header('Content-Type: application/json; charset=utf-8');
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId) {
            $count = Payment::countCollectible($this->pdo, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $count = Payment::countCollectible($this->pdo, null);
        } else {
            $count = 0;
        }
        echo json_encode(['count' => $count]);
    }
}
