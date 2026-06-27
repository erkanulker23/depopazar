<?php
class DashboardController
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

        $warehousesCount = 0;
        $roomsCount = 0;
        $occupiedRooms = 0;
        $emptyRooms = 0;
        $customersCount = 0;
        $activeContracts = 0;
        $monthlyRevenue = 0.0;
        $pendingPayments = 0;
        $overduePayments = 0;
        $debtOverdue = 0.0;      // vadesi geçmiş borç (tutar)
        $debtDueThisMonth = 0.0; // vadesi gelmiş borç – bu ay (tutar)
        $totalDebt = 0.0;        // toplam borç (tüm ödenmemiş)

        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            $warehousesCount = count($warehouses);
            $rooms = Room::findAll($this->pdo, null);
            $companyRooms = array_filter($rooms, fn($r) => ($r['company_id'] ?? '') === $companyId);
            $roomsCount = count($companyRooms);
            $occupiedRooms = count(array_filter($companyRooms, fn($r) => ($r['status'] ?? '') === 'occupied'));
            $emptyRooms = count(array_filter($companyRooms, fn($r) => ($r['status'] ?? '') === 'empty'));
            $customersCount = Customer::count($this->pdo, $companyId);
            $activeContracts = Contract::countActiveByCompany($this->pdo, $companyId);
            $monthlyRevenue = Payment::sumPaidThisMonthByCompany($this->pdo, $companyId);
            $pendingPayments = Payment::countByStatus($this->pdo, $companyId, 'pending');
            $overduePayments = Payment::countOverdueByDueDate($this->pdo, $companyId);
            $debtOverdue = Payment::sumOverdueByCompany($this->pdo, $companyId);
            $debtDueThisMonth = Payment::sumDueThisMonthByCompany($this->pdo, $companyId);
            $totalDebt = Payment::sumUnpaidByCompany($this->pdo, $companyId);
            try {
                $totalDebt += CustomerCharge::sumUnpaidByCompany($this->pdo, $companyId);
            } catch (Throwable $e) {
            }
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehousesCount = Warehouse::countAll($this->pdo);
            $rooms = Room::findAll($this->pdo, null);
            $roomsCount = count($rooms);
            $occupiedRooms = count(array_filter($rooms, fn($r) => ($r['status'] ?? '') === 'occupied'));
            $emptyRooms = count(array_filter($rooms, fn($r) => ($r['status'] ?? '') === 'empty'));
            $customersCount = Customer::count($this->pdo, null);
            $activeContracts = Contract::countActiveGlobal($this->pdo);
            $monthlyRevenue = Payment::sumPaidThisMonthGlobal($this->pdo);
            $pendingPayments = Payment::countByStatusGlobal($this->pdo, 'pending');
            $overduePayments = Payment::countOverdueByDueDateGlobal($this->pdo);
            $debtOverdue = Payment::sumOverdueGlobal($this->pdo);
            $debtDueThisMonth = Payment::sumDueThisMonthGlobal($this->pdo);
            $totalDebt = Payment::sumUnpaidGlobal($this->pdo);
            try {
                $stmt = $this->pdo->query('SELECT COALESCE(SUM(amount), 0) FROM customer_charges WHERE deleted_at IS NULL AND status = \'pending\'');
                $totalDebt += (float) $stmt->fetchColumn();
            } catch (Throwable $e) {
            }
        }

        $upcomingPayments = [];
        $expiringContracts = [];
        $customersWithUnpaid = [];
        $weekRange = Payment::currentWeekRange();
        $weekOverdueList = [];
        $weekDueList = [];
        $weekPaidList = [];
        $weekOverdueCount = 0;
        $weekOverdueSum = 0.0;
        $weekNewOverdueCount = 0;
        $weekNewOverdueSum = 0.0;
        $weekDueCount = 0;
        $weekDueSum = 0.0;
        $weekPaidSum = 0.0;
        $showWeekPanel = false;
        $earlyPaymentsList = [];
        $prepaidContracts = [];
        $earlyPaymentsCount = 0;
        $earlyPaymentsSum = 0.0;

        if ($companyId || ($user['role'] ?? '') === 'super_admin') {
            $cid = $companyId;
            $upcomingPayments = Payment::findUpcoming($this->pdo, $cid, 10);
            $expiringContracts = Contract::findExpiringSoon($this->pdo, $cid, 30);
            $customersWithUnpaid = Payment::findCustomersWithUnpaidPayments($this->pdo, $cid, 50);
            $weekOverdueList = Payment::findOverdueList($this->pdo, $cid, 12);
            $weekDueList = Payment::findDueThisWeek($this->pdo, $cid, 12);
            $weekPaidList = Payment::findPaidThisWeek($this->pdo, $cid, 8);
            $weekOverdueCount = $cid
                ? Payment::countOverdueByDueDate($this->pdo, $cid)
                : Payment::countOverdueByDueDateGlobal($this->pdo);
            $weekOverdueSum = $cid
                ? Payment::sumOverdueByCompany($this->pdo, $cid)
                : Payment::sumOverdueGlobal($this->pdo);
            $weekNewOverdueCount = Payment::countOverdueDueThisWeek($this->pdo, $cid);
            $weekNewOverdueSum = Payment::sumOverdueDueThisWeek($this->pdo, $cid);
            $weekDueCount = Payment::countDueThisWeek($this->pdo, $cid);
            $weekDueSum = Payment::sumDueThisWeek($this->pdo, $cid);
            $weekPaidSum = Payment::sumPaidThisWeek($this->pdo, $cid);
            $showWeekPanel = true;
            $earlyPaymentsList = Payment::findEarlyPayments($this->pdo, $cid, 8);
            $prepaidContracts = Payment::findFullyPrepaidContracts($this->pdo, $cid, 6);
            $earlyPaymentsCount = Payment::countEarlyPayments($this->pdo, $cid);
            $earlyPaymentsSum = Payment::sumEarlyPayments($this->pdo, $cid);
        } else {
            $earlyPaymentsList = [];
            $prepaidContracts = [];
            $earlyPaymentsCount = 0;
            $earlyPaymentsSum = 0.0;
        }

        $config = require __DIR__ . '/../../config/config.php';
        $brand = $companyId ? Company::findOne($this->pdo, $companyId) : null;
        $projectName = $brand['project_name'] ?? $config['app_name'];

        // Kurulum rehberi: yeni projede tamamlanmamış adımlar (her biri yapıldıkça kalkar)
        $setupSteps = [
            'company'   => ['done' => false, 'label' => 'Firma bilgilerini güncelleyiniz', 'href' => '/ayarlar?tab=firma', 'icon' => 'bi-building-gear'],
            'warehouses' => ['done' => false, 'label' => 'Depolarınızı ekleyin', 'href' => '/depolar', 'icon' => 'bi-building'],
            'rooms'     => ['done' => false, 'label' => 'Odalarınızı ekleyin', 'href' => '/odalar', 'icon' => 'bi-grid-3x3'],
            'staff'     => ['done' => false, 'label' => 'Saha personeli ekleyin', 'href' => '/personel', 'icon' => 'bi-person-badge'],
            'vehicles'  => ['done' => false, 'label' => 'Araçlarınızı ekleyin', 'href' => '/araclar', 'icon' => 'bi-truck'],
            'services'  => ['done' => false, 'label' => 'Hizmetlerinizi ekleyin', 'href' => '/hizmetler', 'icon' => 'bi-list-check'],
        ];
        $isSuperAdmin = ($user['role'] ?? '') === 'super_admin';
        try {
            if ($isSuperAdmin) {
                $setupSteps['company']['done'] = true;
                $setupSteps['warehouses']['done'] = Warehouse::countAll($this->pdo) > 0;
                $setupSteps['rooms']['done'] = count(Room::findAll($this->pdo, null)) > 0;
                $setupSteps['staff']['done'] = Personnel::tableExists($this->pdo) && count(Personnel::findAll($this->pdo, null, null, null, '1')) >= 1;
                $setupSteps['vehicles']['done'] = count(Vehicle::findAll($this->pdo, null)) > 0;
                $setupSteps['services']['done'] = count(Service::findAll($this->pdo, null)) > 0;
            } elseif ($companyId) {
                if ($brand) {
                    $name = trim($brand['name'] ?? '');
                    $projectNameVal = trim($brand['project_name'] ?? '');
                    $hasContact = trim($brand['email'] ?? '') !== '' || trim($brand['phone'] ?? '') !== '' || trim($brand['address'] ?? '') !== '';
                    $setupSteps['company']['done'] = $hasContact || $name !== 'DepoPazar' || $projectNameVal !== 'DepoPazar';
                }
                $setupSteps['warehouses']['done'] = $warehousesCount > 0;
                $setupSteps['rooms']['done'] = $roomsCount > 0;
                $setupSteps['staff']['done'] = Personnel::tableExists($this->pdo) && count(Personnel::findActiveForCompany($this->pdo, $companyId)) >= 1;
                $vehicles = Vehicle::findAll($this->pdo, $companyId);
                $setupSteps['vehicles']['done'] = count($vehicles) > 0;
                $services = Service::findAll($this->pdo, $companyId);
                $setupSteps['services']['done'] = count($services) > 0;
            }
        } catch (Throwable $e) {
            // Tablo/class eksikse sayfa kırılmasın; rehber tüm adımları gösterir
        }
        $setupComplete = true;
        foreach ($setupSteps as $s) {
            if (!$s['done']) {
                $setupComplete = false;
                break;
            }
        }

        require __DIR__ . '/../../views/dashboard/index.php';
    }
}
