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
import { paths } from './paths';

function ProtectedRoute({ children, allowedRoles }: { children: JSX.Element; allowedRoles: string[] }) {
  const { user, isAuthenticated } = useAuthStore();

  if (!isAuthenticated) {
    return <Navigate to={paths.giris} />;
  }

  if (user && !allowedRoles.includes(user.role)) {
    if (user.role === 'customer') {
      return <Navigate to={paths.musteri.genelBakis} />;
    }
    return <Navigate to={paths.genelBakis} />;
  }

  return children;
}

export function AppRoutes() {
  const { isAuthenticated, user } = useAuthStore();

  const getRedirectPath = () => {
    if (!isAuthenticated) return paths.giris;
    if (user?.role === 'customer') return paths.musteri.genelBakis;
    return paths.genelBakis;
  };

  return (
    <Routes>
      <Route path="/login" element={<Navigate to={paths.giris} replace />} />
      <Route path="/dashboard" element={<Navigate to={paths.genelBakis} replace />} />
      <Route
        path={paths.giris}
        element={!isAuthenticated ? <LoginPage /> : <Navigate to={getRedirectPath()} />}
      />

      {/* Müşteri paneli */}
      <Route
        path="/musteri"
        element={
          <ProtectedRoute allowedRoles={['customer']}>
            <CustomerLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to={paths.musteri.genelBakis} />} />
        <Route path="genel-bakis" element={<CustomerDashboardPage />} />
        <Route path="sozlesmeler" element={<CustomerContractsPage />} />
        <Route path="sozlesmeler/:id" element={<ContractDetailPage />} />
        <Route path="odemeler" element={<CustomerPaymentsPage />} />
      </Route>

      {/* Admin/Personel paneli */}
      <Route
        path="/"
        element={
          <ProtectedRoute allowedRoles={['super_admin', 'company_owner', 'company_staff', 'data_entry', 'accounting']}>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to={paths.genelBakis} />} />
        <Route path="genel-bakis" element={<DashboardPage />} />
        <Route path="depolar" element={<WarehousesPage />} />
        <Route path="odalar" element={<RoomsPage />} />
        <Route path="odalar/:id" element={<RoomDetailPage />} />
        <Route path="musteriler" element={<CustomersPage />} />
        <Route path="musteriler/:id" element={<CustomerDetailPage />} />
        <Route path="girisler" element={<ContractsPage />} />
        <Route path="girisler/yeni" element={<ContractsPage />} />
        <Route path="girisler/:id" element={<ContractDetailPage />} />
        <Route path="odemeler" element={<PaymentsPage />} />
        <Route path="odemeler/basarili" element={<PaymentSuccessPage />} />
        <Route path="odemeler/hata" element={<PaymentFailPage />} />
        <Route path="kullanicilar" element={<StaffPage />} />
        <Route path="kullanicilar/:id" element={<StaffDetailPage />} />
        <Route path="yetkiler" element={<PermissionsPage />} />
        <Route path="nakliye-isler" element={<TransportationJobsPage />} />
        <Route path="hizmetler" element={<ServicesPage />} />
        <Route path="teklifler" element={<ProposalsPage />} />
        <Route path="teklifler/yeni" element={<CreateProposalPage />} />
        <Route path="teklifler/:id/duzenle" element={<CreateProposalPage />} />
        <Route path="raporlar" element={<ReportsPage />} />
        <Route path="raporlar/banka-hesaplari" element={<BankAccountPaymentsPage />} />
        <Route path="ayarlar" element={<SettingsPage />} />
      </Route>
    </Routes>
  );
}
