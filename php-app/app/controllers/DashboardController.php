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
        $reservedRooms = 0;
        $lockedRooms = 0;
        $customersCount = 0;
        $activeContracts = 0;
        $monthlyRevenue = 0.0;
        $paidTodaySum = 0.0;
        $paidThisWeekSum = 0.0;
        $weekRange = Payment::currentWeekRange();
        $pendingPayments = 0;
        $overduePayments = 0;
        $debtOverdue = 0.0;      // vadesi geçmiş borç (tutar)
        $debtDueThisMonth = 0.0; // vadesi gelmiş borç – bu ay (tutar)
        $totalDebt = 0.0;        // toplam borç (tüm ödenmemiş)
        $companyDebtSummary = null;

        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            $warehousesCount = count($warehouses);
            $roomStatusCounts = Room::statusCounts($this->pdo, $companyId);
            $roomsCount = Room::countAll($this->pdo, $companyId);
            $occupiedRooms = (int) ($roomStatusCounts['occupied'] ?? 0);
            $emptyRooms = (int) ($roomStatusCounts['empty'] ?? 0);
            $reservedRooms = (int) ($roomStatusCounts['reserved'] ?? 0);
            $lockedRooms = (int) ($roomStatusCounts['locked'] ?? 0);
            $customersCount = Customer::count($this->pdo, $companyId);
            $activeContracts = Contract::countActiveByCompany($this->pdo, $companyId);
            $monthlyRevenue = Payment::sumPaidThisMonthByCompany($this->pdo, $companyId);
            $paidTodaySum = Payment::sumPaidToday($this->pdo, $companyId) + CustomerCharge::sumPaidToday($this->pdo, $companyId);
            $paidThisWeekSum = Payment::sumPaidThisWeek($this->pdo, $companyId) + CustomerCharge::sumPaidThisWeek($this->pdo, $companyId);
            $pendingPayments = Payment::countByStatus($this->pdo, $companyId, 'pending');
            $overduePayments = Payment::countOverdueByDueDate($this->pdo, $companyId);
            $companyDebtSummary = computeCompanyDebtSummary($this->pdo, $companyId);
            $debtOverdue = $companyDebtSummary['overdue'];
            $debtDueThisMonth = $companyDebtSummary['due_this_month'];
            $totalDebt = $companyDebtSummary['total'];
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehousesCount = Warehouse::countAll($this->pdo);
            $roomStatusCounts = Room::statusCounts($this->pdo, null);
            $roomsCount = Room::countAll($this->pdo, null);
            $occupiedRooms = (int) ($roomStatusCounts['occupied'] ?? 0);
            $emptyRooms = (int) ($roomStatusCounts['empty'] ?? 0);
            $reservedRooms = (int) ($roomStatusCounts['reserved'] ?? 0);
            $lockedRooms = (int) ($roomStatusCounts['locked'] ?? 0);
            $customersCount = Customer::count($this->pdo, null);
            $activeContracts = Contract::countActiveGlobal($this->pdo);
            $monthlyRevenue = Payment::sumPaidThisMonthGlobal($this->pdo);
            $paidTodaySum = Payment::sumPaidToday($this->pdo, null) + CustomerCharge::sumPaidToday($this->pdo, null);
            $paidThisWeekSum = Payment::sumPaidThisWeek($this->pdo, null) + CustomerCharge::sumPaidThisWeek($this->pdo, null);
            $pendingPayments = Payment::countByStatusGlobal($this->pdo, 'pending');
            $overduePayments = Payment::countOverdueByDueDateGlobal($this->pdo);
            $companyDebtSummary = computeCompanyDebtSummary($this->pdo, null);
            $debtOverdue = $companyDebtSummary['overdue'];
            $debtDueThisMonth = $companyDebtSummary['due_this_month'];
            $totalDebt = $companyDebtSummary['total'];
        }

        $upcomingPayments = [];
        $expiringContracts = [];
        $customersWithUnpaid = [];
        $monthRange = Payment::currentMonthRange();
        $monthOverdueList = [];
        $monthDueList = [];
        $monthPaidList = [];
        $monthNewOverdueCount = 0;
        $monthNewOverdueSum = 0.0;
        $monthDueCount = 0;
        $monthDueSum = 0.0;
        $monthPaidSum = 0.0;
        $showMonthPanel = false;
        $earlyPaymentsList = [];
        $prepaidContracts = [];
        $earlyPaymentsCount = 0;
        $earlyPaymentsSum = 0.0;
        $topSellers = [];
        $topPersonnelByJobs = [];

        if ($companyId || ($user['role'] ?? '') === 'super_admin') {
            $cid = $companyId;
            $upcomingPayments = Payment::findUpcoming($this->pdo, $cid, 10);
            $expiringContracts = Contract::findExpiringSoon($this->pdo, $cid, 30);
            if ($companyDebtSummary === null) {
                $companyDebtSummary = computeCompanyDebtSummary($this->pdo, $cid);
            }
            foreach (array_slice($companyDebtSummary['by_customer'] ?? [], 0, 50, true) as $customerId => $row) {
                if ((float) ($row['total'] ?? 0) <= 0.009) {
                    continue;
                }
                $customersWithUnpaid[] = [
                    'customer_id' => $customerId,
                    'customer_first_name' => $row['customer_first_name'] ?? '',
                    'customer_last_name' => $row['customer_last_name'] ?? '',
                    'total_debt' => (float) ($row['total'] ?? 0),
                    'payment_count' => 0,
                ];
            }
            $monthOverdueList = Payment::findOverdueDueThisMonth($this->pdo, $cid, 12);
            $monthDueList = Payment::findDueThisMonth($this->pdo, $cid, 12);
            $monthPaidList = Payment::findPaidThisMonth($this->pdo, $cid, 8);
            $monthNewOverdueCount = Payment::countOverdueDueThisMonth($this->pdo, $cid);
            $monthNewOverdueSum = Payment::sumOverdueDueThisMonth($this->pdo, $cid);
            $monthDueCount = Payment::countDueThisMonth($this->pdo, $cid);
            $monthDueSum = Payment::sumDueThisMonth($this->pdo, $cid);
            $monthPaidSum = Payment::sumPaidThisMonth($this->pdo, $cid);
            $showMonthPanel = true;
            $earlyPaymentsList = Payment::findEarlyPayments($this->pdo, $cid, 8);
            $prepaidContracts = Payment::findFullyPrepaidContracts($this->pdo, $cid, 6);
            $earlyPaymentsCount = Payment::countEarlyPayments($this->pdo, $cid);
            $earlyPaymentsSum = Payment::sumEarlyPayments($this->pdo, $cid);
            try {
                $topSellers = Contract::findTopSellers(
                    $this->pdo,
                    $cid,
                    $monthRange['start'],
                    $monthRange['end'],
                    5
                );
                $topPersonnelByJobs = Personnel::findTopByJobCount(
                    $this->pdo,
                    $cid,
                    $monthRange['start'],
                    $monthRange['end'],
                    5
                );
            } catch (Throwable $e) {
            }
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
                $setupSteps['rooms']['done'] = Room::countAll($this->pdo, null) > 0;
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
