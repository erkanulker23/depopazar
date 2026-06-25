<?php
class PermissionsController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        Auth::requireRoles(['super_admin']);

        $roles = [];
        foreach (RolePermissions::STAFF_ROLES as $roleId) {
            $roles[] = [
                'id' => $roleId,
                'name' => RolePermissions::roleLabels()[$roleId] ?? $roleId,
                'desc' => RolePermissions::roleDescriptions()[$roleId] ?? '',
            ];
        }

        $modules = RolePermissions::modules();
        $actionLabels = RolePermissions::actionLabels();
        $matrix = RolePermissions::matrix();

        $selectedRole = isset($_GET['role']) && in_array($_GET['role'], RolePermissions::STAFF_ROLES, true)
            ? $_GET['role']
            : 'company_staff';

        $roleSummary = RolePermissions::summarizeForRole($selectedRole);

        $staff = $this->fetchStaffUsers();
        $companies = $this->fetchCompanies();

        $pageTitle = 'Kullanıcı Yetkileri';
        require __DIR__ . '/../../views/permissions/index.php';
    }

    private function fetchStaffUsers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.is_active, u.company_id, c.name AS company_name
             FROM users u
             LEFT JOIN companies c ON c.id = u.company_id AND c.deleted_at IS NULL
             WHERE u.deleted_at IS NULL
             AND u.role IN (\'super_admin\', \'company_owner\', \'company_staff\', \'data_entry\', \'accounting\')
             ORDER BY u.role, u.first_name, u.last_name'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['role_label'] = RolePermissions::roleLabels()[$row['role'] ?? ''] ?? ($row['role'] ?? '');
            $row['allowed_modules'] = RolePermissions::countAllowedModules($row['role'] ?? '');
            $row['permissions'] = RolePermissions::summarizeForRole($row['role'] ?? '');
        }
        unset($row);
        return $rows;
    }

    private function fetchCompanies(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
