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

        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        $canEditSelectedRole = in_array($selectedRole, RolePermissions::EDITABLE_ROLES, true);
        $hasCustomOverrides = RolePermissions::hasCustomOverrides($selectedRole);

        $pageTitle = 'Kullanıcı Yetkileri';
        require __DIR__ . '/../../views/permissions/index.php';
    }

    public function update(): void
    {
        Auth::requireRoles(['super_admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /yetkiler');
            exit;
        }
        $role = trim($_POST['role'] ?? '');
        if (!in_array($role, RolePermissions::EDITABLE_ROLES, true)) {
            Auth::setSession('flash_error', 'Bu rol düzenlenemez.');
            header('Location: /yetkiler');
            exit;
        }

        $actions = ['nav', 'view', 'create', 'edit', 'delete', 'export', 'print'];
        $rolePerms = [];
        $posted = $_POST['perm'] ?? [];
        foreach (RolePermissions::modules() as $module) {
            $moduleId = $module['id'];
            $rolePerms[$moduleId] = [];
            foreach ($actions as $action) {
                $rolePerms[$moduleId][$action] = !empty($posted[$moduleId][$action]);
            }
        }

        $overrides = RolePermissions::loadOverrides();
        $overrides[$role] = $rolePerms;
        RolePermissions::saveOverrides($overrides);

        Auth::setSession('flash_success', (RolePermissions::roleLabels()[$role] ?? $role) . ' rolü için yetkiler kaydedildi.');
        header('Location: /yetkiler?role=' . urlencode($role) . '#rol-matrisi');
        exit;
    }

    public function resetRole(): void
    {
        Auth::requireRoles(['super_admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /yetkiler');
            exit;
        }
        $role = trim($_POST['role'] ?? '');
        if (!in_array($role, RolePermissions::EDITABLE_ROLES, true)) {
            Auth::setSession('flash_error', 'Bu rol sıfırlanamaz.');
            header('Location: /yetkiler');
            exit;
        }
        RolePermissions::resetRoleOverrides($role);
        Auth::setSession('flash_success', (RolePermissions::roleLabels()[$role] ?? $role) . ' rolü varsayılan yetkilere döndürüldü.');
        header('Location: /yetkiler?role=' . urlencode($role) . '#rol-matrisi');
        exit;
    }

    private function fetchStaffUsers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.is_active, u.company_id, c.name AS company_name
             FROM users u
             LEFT JOIN companies c ON c.id = u.company_id AND c.deleted_at IS NULL
             WHERE u.deleted_at IS NULL
             AND u.role IN (' . RolePermissions::sqlStaffRoles() . ')
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
