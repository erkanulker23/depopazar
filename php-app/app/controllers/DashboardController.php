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
        $totalDebt = 0.0;

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
            $overduePayments = Payment::countByStatus($this->pdo, $companyId, 'overdue');
            $totalDebt = Payment::sumUnpaidByCompany($this->pdo, $companyId);
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
            $overduePayments = Payment::countByStatusGlobal($this->pdo, 'overdue');
            $totalDebt = Payment::sumUnpaidGlobal($this->pdo);
        }

        $upcomingPayments = [];
        $expiringContracts = [];
        if ($companyId) {
            $upcomingPayments = Payment::findUpcoming($this->pdo, $companyId, 10);
            $expiringContracts = Contract::findExpiringSoon($this->pdo, $companyId, 30);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $upcomingPayments = Payment::findUpcoming($this->pdo, null, 10);
            $expiringContracts = Contract::findExpiringSoon($this->pdo, null, 30);
        }

        $config = require __DIR__ . '/../../config/config.php';
        $brand = $companyId ? Company::findOne($this->pdo, $companyId) : null;
        $projectName = $brand['project_name'] ?? $config['app_name'];
        require __DIR__ . '/../../views/dashboard/index.php';
    }
}
