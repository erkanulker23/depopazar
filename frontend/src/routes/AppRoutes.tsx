import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuthStore } from '../stores/authStore';
import { LoginPage } from '../pages/auth/LoginPage';
import { DashboardLayout } from '../layouts/DashboardLayout';
import { CustomerLayout } from '../layouts/CustomerLayout';
import { DashboardPage } from '../pages/dashboard/DashboardPage';
import { WarehousesPage } from '../pages/warehouses/WarehousesPage';
import { RoomsPage } from '../pages/rooms/RoomsPage';
import { CustomersPage } from '../pages/customers/CustomersPage';
import { CustomerDetailPage } from '../pages/customers/CustomerDetailPage';
import { RoomDetailPage } from '../pages/rooms/RoomDetailPage';
import { ContractsPage } from '../pages/contracts/ContractsPage';
import { ContractDetailPage } from '../pages/contracts/ContractDetailPage';
import { PaymentsPage } from '../pages/payments/PaymentsPage';
import { PaymentSuccessPage } from '../pages/payments/PaymentSuccessPage';
import { PaymentFailPage } from '../pages/payments/PaymentFailPage';
import { StaffPage } from '../pages/staff/StaffPage';
import { StaffDetailPage } from '../pages/staff/StaffDetailPage';
import { PermissionsPage } from '../pages/permissions/PermissionsPage';
import { SettingsPage } from '../pages/settings/SettingsPage';
import { ReportsPage } from '../pages/reports/ReportsPage';
import { BankAccountPaymentsPage } from '../pages/reports/BankAccountPaymentsPage';
import { CustomerDashboardPage } from '../pages/customer/CustomerDashboardPage';
import { CustomerContractsPage } from '../pages/customer/CustomerContractsPage';
import { CustomerPaymentsPage } from '../pages/customer/CustomerPaymentsPage';
import { TransportationJobsPage } from '../pages/transportation-jobs/TransportationJobsPage';
import { ServicesPage } from '../pages/services/ServicesPage';
import { ProposalsPage } from '../pages/proposals/ProposalsPage';
import { CreateProposalPage } from '../pages/proposals/CreateProposalPage';

// Role-based route wrapper
function ProtectedRoute({ children, allowedRoles }: { children: JSX.Element; allowedRoles: string[] }) {
  const { user, isAuthenticated } = useAuthStore();
  
  if (!isAuthenticated) {
    return <Navigate to="/login" />;
  }
  
  if (user && !allowedRoles.includes(user.role)) {
    // Redirect to appropriate dashboard based on role
    if (user.role === 'customer') {
      return <Navigate to="/customer/dashboard" />;
    }
    return <Navigate to="/dashboard" />;
  }
  
  return children;
}

export function AppRoutes() {
  const { isAuthenticated, user } = useAuthStore();

  // Determine redirect path based on role
  const getRedirectPath = () => {
    if (!isAuthenticated) return '/login';
    if (user?.role === 'customer') return '/customer/dashboard';
    return '/dashboard';
  };

  return (
    <Routes>
      <Route 
        path="/login" 
        element={!isAuthenticated ? <LoginPage /> : <Navigate to={getRedirectPath()} />} 
      />
      
      {/* Customer Routes */}
      <Route
        path="/customer"
        element={
          <ProtectedRoute allowedRoles={['customer']}>
            <CustomerLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/customer/dashboard" />} />
        <Route path="dashboard" element={<CustomerDashboardPage />} />
        <Route path="contracts" element={<CustomerContractsPage />} />
        <Route path="payments" element={<CustomerPaymentsPage />} />
      </Route>

      {/* Admin/Staff Routes */}
      <Route
        path="/"
        element={
          <ProtectedRoute allowedRoles={['super_admin', 'company_owner', 'company_staff', 'data_entry', 'accounting']}>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/dashboard" />} />
        <Route path="dashboard" element={<DashboardPage />} />
        <Route path="warehouses" element={<WarehousesPage />} />
        <Route path="rooms" element={<RoomsPage />} />
        <Route path="rooms/:id" element={<RoomDetailPage />} />
        <Route path="customers" element={<CustomersPage />} />
        <Route path="customers/:id" element={<CustomerDetailPage />} />
        <Route path="contracts" element={<ContractsPage />} />
        <Route path="contracts/:id" element={<ContractDetailPage />} />
        <Route path="payments" element={<PaymentsPage />} />
        <Route path="payments/success" element={<PaymentSuccessPage />} />
        <Route path="payments/fail" element={<PaymentFailPage />} />
        <Route path="staff" element={<StaffPage />} />
        <Route path="staff/:id" element={<StaffDetailPage />} />
        <Route path="permissions" element={<PermissionsPage />} />
        <Route path="transportation-jobs" element={<TransportationJobsPage />} />
        <Route path="services" element={<ServicesPage />} />
        <Route path="proposals" element={<ProposalsPage />} />
        <Route path="proposals/new" element={<CreateProposalPage />} />
        <Route path="proposals/:id/edit" element={<CreateProposalPage />} />
        <Route path="reports" element={<ReportsPage />} />
        <Route path="reports/bank-accounts" element={<BankAccountPaymentsPage />} />
        <Route path="settings" element={<SettingsPage />} />
      </Route>
    </Routes>
  );
}
