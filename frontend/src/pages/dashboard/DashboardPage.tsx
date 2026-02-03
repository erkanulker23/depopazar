import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { paths } from '../../routes/paths';
import { useAuthStore } from '../../stores/authStore';
import { warehousesApi } from '../../services/api/warehousesApi';
import { roomsApi } from '../../services/api/roomsApi';
import { customersApi } from '../../services/api/customersApi';
import { paymentsApi } from '../../services/api/paymentsApi';
import { contractsApi } from '../../services/api/contractsApi';
import {
  BuildingOfficeIcon,
  CubeIcon,
  UsersIcon,
  CreditCardIcon,
  CurrencyDollarIcon,
  ExclamationTriangleIcon,
  ChartBarIcon,
  DocumentTextIcon,
} from '@heroicons/react/24/outline';
import { formatTurkishCurrency } from '../../utils/inputFormatters';

export function DashboardPage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [stats, setStats] = useState({
    warehouses: 0,
    rooms: 0,
    customers: 0,
    pendingPayments: 0,
    overduePayments: 0,
    totalDebt: 0,
    monthlyRevenue: 0,
    occupiedRooms: 0,
    emptyRooms: 0,
    activeContracts: 0,
    contractsByMonth: [] as any[],
    upcomingPayments: [] as any[],
    paymentsIn5Days: [] as any[],
    paymentsIn10Days: [] as any[],
    expiringContracts: [] as any[],
    loading: true,
  });

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const canViewFinancials = user?.role === 'super_admin' || user?.role === 'company_owner' || user?.role === 'accounting';

        const [warehouses, rooms, customersRes, paymentsRes, contractsRes] = await Promise.all([
          warehousesApi.getAll(),
          roomsApi.getAll(),
          customersApi.getAll({ limit: 100 }),
          canViewFinancials ? paymentsApi.getAll() : Promise.resolve([]),
          contractsApi.getAll({ limit: 100 }),
        ]);
        const customers = customersRes.data;
        const payments = Array.isArray(paymentsRes) ? paymentsRes : [];
        const contracts = contractsRes.data;

        const pendingPayments = payments.filter((p: any) => p.status === 'pending');
        const overduePayments = payments.filter((p: any) => p.status === 'overdue');
        const totalDebt = [...pendingPayments, ...overduePayments].reduce(
          (sum, p) => sum + Number(p.amount),
          0,
        );

        const paidPayments = payments.filter((p: any) => p.status === 'paid');
        const thisMonth = new Date().getMonth();
        const thisYear = new Date().getFullYear();
        const monthlyRevenue = paidPayments
          .filter((p: any) => {
            const paidDate = new Date(p.paid_at);
            return paidDate.getMonth() === thisMonth && paidDate.getFullYear() === thisYear;
          })
          .reduce((sum: number, p: any) => sum + Number(p.amount), 0);

        const occupiedRooms = rooms.filter((r: any) => r.status === 'occupied').length;
        const emptyRooms = rooms.filter((r: any) => r.status === 'empty').length;
        const activeContracts = contracts.filter((c: any) => c.is_active).length;

        // Sözleşme süreleri hesaplama
        const contractsByMonth = contracts
          .filter((c: any) => c.is_active)
          .map((c: any) => {
            const start = new Date(c.start_date);
            const end = new Date(c.end_date);
            const months = Math.round(
              (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24 * 30),
            );
            return { contract: c, months };
          });

        // Yaklaşan ödemeler (5 gün, 10 gün ve 7 gün içinde)
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const fiveDaysLater = new Date(today);
        fiveDaysLater.setDate(today.getDate() + 5);
        
        const tenDaysLater = new Date(today);
        tenDaysLater.setDate(today.getDate() + 10);
        
        const sevenDaysLater = new Date(today);
        sevenDaysLater.setDate(today.getDate() + 7);
        
        // 5 gün içinde kalan ödemeler
        const paymentsIn5Days = payments
          .filter((p: any) => {
            const dueDate = new Date(p.due_date);
            dueDate.setHours(0, 0, 0, 0);
            return (
              (p.status === 'pending' || p.status === 'overdue') &&
              dueDate >= today &&
              dueDate <= fiveDaysLater
            );
          })
          .map((p: any) => {
            const dueDate = new Date(p.due_date);
            const daysLeft = Math.ceil((dueDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
            return { ...p, daysLeft };
          })
          .sort((a: any, b: any) => a.daysLeft - b.daysLeft);
        
        // 10 gün içinde kalan ödemeler (5 günden fazla)
        const paymentsIn10Days = payments
          .filter((p: any) => {
            const dueDate = new Date(p.due_date);
            dueDate.setHours(0, 0, 0, 0);
            return (
              (p.status === 'pending' || p.status === 'overdue') &&
              dueDate > fiveDaysLater &&
              dueDate <= tenDaysLater
            );
          })
          .map((p: any) => {
            const dueDate = new Date(p.due_date);
            const daysLeft = Math.ceil((dueDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
            return { ...p, daysLeft };
          })
          .sort((a: any, b: any) => a.daysLeft - b.daysLeft);
        
        const upcomingPayments = payments
          .filter((p: any) => {
            const dueDate = new Date(p.due_date);
            return (
              p.status === 'pending' &&
              dueDate >= today &&
              dueDate <= sevenDaysLater
            );
          })
          .sort((a: any, b: any) => new Date(a.due_date).getTime() - new Date(b.due_date).getTime())
          .slice(0, 10);

        // Bitiş tarihi yaklaşan sözleşmeler (30 gün içinde)
        const thirtyDaysLater = new Date(today);
        thirtyDaysLater.setDate(today.getDate() + 30);
        const expiringContracts = contracts
          .filter((c: any) => {
            const endDate = new Date(c.end_date);
            return c.is_active && endDate >= today && endDate <= thirtyDaysLater;
          })
          .sort((a: any, b: any) => new Date(a.end_date).getTime() - new Date(b.end_date).getTime())
          .slice(0, 10);

        setStats({
          warehouses: warehouses.length || 0,
          rooms: rooms.length || 0,
          customers: customers.length || 0,
          pendingPayments: pendingPayments.length,
          overduePayments: overduePayments.length,
          totalDebt,
          monthlyRevenue,
          occupiedRooms,
          emptyRooms,
          activeContracts,
          contractsByMonth,
          upcomingPayments,
          paymentsIn5Days,
          paymentsIn10Days,
          expiringContracts,
          loading: false,
        });
      } catch (error) {
        console.error('Error fetching stats:', error);
        setStats((prev) => ({ ...prev, loading: false }));
      }
    };

    fetchStats();
  }, []);

  const renderMobileList = (items: any[], type: 'payment' | 'contract') => (
    <div className="grid grid-cols-1 gap-3 md:hidden">
      {items.map((item: any) => (
        <div key={item.id} className="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
          {type === 'payment' ? (
            <>
              <div className="flex justify-between items-start mb-2">
                <span className="text-sm font-bold text-gray-900 dark:text-white">
                  {item.contract?.customer?.first_name} {item.contract?.customer?.last_name}
                </span>
                <span className="text-sm font-bold text-primary-600 dark:text-primary-400">
                  {formatTurkishCurrency(Number(item.amount))}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-xs text-gray-500 dark:text-gray-400">
                  Vade: {new Date(item.due_date).toLocaleDateString('tr-TR')}
                </span>
                <span className={`px-2 py-0.5 text-[10px] font-bold rounded-full ${
                  item.daysLeft <= 1 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'
                }`}>
                  {item.daysLeft} GÜN KALDI
                </span>
              </div>
            </>
          ) : (
            <>
              <div className="flex justify-between items-start mb-2">
                <span className="text-sm font-bold text-gray-900 dark:text-white">
                  {item.contract_number}
                </span>
                <span className="text-xs font-medium text-gray-500 dark:text-gray-400">
                  Oda: {item.room?.room_number || '-'}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-xs text-gray-500 dark:text-gray-400 truncate mr-2">
                  {item.customer?.first_name} {item.customer?.last_name}
                </span>
                <span className="text-xs font-bold text-gray-700 dark:text-gray-300">
                  {new Date(item.end_date).toLocaleDateString('tr-TR')}
                </span>
              </div>
            </>
          )}
        </div>
      ))}
    </div>
  );

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-zinc-100 mb-1">Genel Bakış</h1>
        <p className="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-widest font-bold">Sistem özeti</p>
      </div>

      {/* Ana İstatistikler - Modern Design */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div className="modern-card p-5 group hover:scale-[1.01] transition-all duration-300">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5">
                Toplam Depo
              </p>
              <p className="text-2xl font-bold text-gray-900 dark:text-zinc-100">
                {stats.loading ? '...' : stats.warehouses}
              </p>
            </div>
            <div className="p-2.5 bg-emerald-50 dark:bg-emerald-500/10 rounded-xl">
              <BuildingOfficeIcon className="h-6 w-6 text-emerald-600 dark:text-emerald-500" />
            </div>
          </div>
        </div>

        <div className="modern-card p-5 group hover:scale-[1.01] transition-all duration-300">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5">
                Toplam Oda
              </p>
              <p className="text-2xl font-bold text-gray-900 dark:text-zinc-100">
                {stats.loading ? '...' : stats.rooms}
              </p>
              <p className="text-[10px] text-gray-500 dark:text-zinc-500 mt-1.5 font-bold">
                <span className="text-emerald-600 dark:text-emerald-500">{stats.occupiedRooms}</span> Dolu / {stats.emptyRooms} Boş
              </p>
            </div>
            <div className="p-2.5 bg-blue-50 dark:bg-blue-500/10 rounded-xl">
              <CubeIcon className="h-6 w-6 text-blue-600 dark:text-blue-500" />
            </div>
          </div>
        </div>

        <div className="modern-card p-5 group hover:scale-[1.01] transition-all duration-300">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5">
                Toplam Müşteri
              </p>
              <p className="text-2xl font-bold text-gray-900 dark:text-zinc-100">
                {stats.loading ? '...' : stats.customers}
              </p>
              <p className="text-[10px] text-gray-500 dark:text-zinc-500 mt-1.5 font-bold">
                <span className="text-emerald-600 dark:text-emerald-500">{stats.activeContracts}</span> Aktif Sözleşme
              </p>
            </div>
            <div className="p-2.5 bg-purple-50 dark:bg-purple-500/10 rounded-xl">
              <UsersIcon className="h-6 w-6 text-purple-600 dark:text-purple-500" />
            </div>
          </div>
        </div>

        {(user?.role === 'super_admin' || user?.role === 'company_owner' || user?.role === 'accounting') && (
          <div className="modern-card p-5 group hover:scale-[1.01] transition-all duration-300">
            <div className="flex items-center justify-between">
              <div className="flex-1">
                <p className="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5">
                  Bu Ay Gelir
                </p>
                <p className="text-2xl font-bold text-gray-900 dark:text-zinc-100">
                  {stats.loading ? '...' : formatTurkishCurrency(stats.monthlyRevenue)}
                </p>
              </div>
              <div className="p-2.5 bg-emerald-50 dark:bg-emerald-500/10 rounded-xl">
                <CurrencyDollarIcon className="h-6 w-6 text-emerald-600 dark:text-emerald-500" />
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Ödeme Durumları - Modern Design */}
      {(user?.role === 'super_admin' || user?.role === 'company_owner' || user?.role === 'accounting') && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
          <button
            onClick={() => navigate(`${paths.odemeler}?status=pending`)}
            className="modern-card p-5 border-l-4 border-yellow-500 group hover:scale-[1.01] transition-all duration-300 cursor-pointer w-full text-left"
          >
            <div className="flex items-center justify-between">
              <div className="flex-1">
                <p className="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5">
                  Bekleyen Ödeme
                </p>
                <p className="text-2xl font-bold text-gray-900 dark:text-zinc-100">
                  {stats.loading ? '...' : stats.pendingPayments}
                </p>
              </div>
              <div className="p-2.5 bg-yellow-50 dark:bg-yellow-500/10 rounded-xl">
                <CreditCardIcon className="h-6 w-6 text-yellow-600 dark:text-yellow-500" />
              </div>
            </div>
          </button>

          <button
            onClick={() => navigate(`${paths.odemeler}?status=overdue`)}
            className="modern-card p-5 border-l-4 border-red-500 group hover:scale-[1.01] transition-all duration-300 cursor-pointer w-full text-left"
          >
            <div className="flex items-center justify-between">
              <div className="flex-1">
                <p className="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5">
                  Geciken Ödeme
                </p>
                <p className="text-2xl font-bold text-gray-900 dark:text-zinc-100">
                  {stats.loading ? '...' : stats.overduePayments}
                </p>
              </div>
              <div className="p-2.5 bg-red-50 dark:bg-red-500/10 rounded-xl">
                <ExclamationTriangleIcon className="h-6 w-6 text-red-600 dark:text-red-500" />
              </div>
            </div>
          </button>

          <button
            onClick={() => navigate(`${paths.odemeler}?status=unpaid`)}
            className="modern-card p-5 border-l-4 border-zinc-900 dark:border-zinc-100 group hover:scale-[1.01] transition-all duration-300 cursor-pointer w-full text-left sm:col-span-2 lg:col-span-1"
          >
            <div className="flex items-center justify-between">
              <div className="flex-1">
                <p className="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5">
                  Toplam Borç
                </p>
                <p className="text-2xl font-bold text-red-600">
                  {stats.loading ? '...' : formatTurkishCurrency(stats.totalDebt)}
                </p>
              </div>
              <div className="p-2.5 bg-zinc-50 dark:bg-zinc-500/10 rounded-xl">
                <ChartBarIcon className="h-6 w-6 text-zinc-600 dark:text-zinc-400" />
              </div>
            </div>
          </button>
        </div>
      )}

      {/* 5 Gün İçinde Kalan Ödemeler - Modern Design */}
      {(user?.role === 'super_admin' || user?.role === 'company_owner' || user?.role === 'accounting') && stats.paymentsIn5Days.length > 0 && (
        <div className="modern-card mb-6 border-l-4 border-red-500 animate-in fade-in slide-in-from-bottom-2 duration-500 overflow-hidden">
          <div className="p-5 border-b border-gray-100 dark:border-[#27272a]/50 flex items-center justify-between">
            <h2 className="text-sm font-bold text-gray-900 dark:text-zinc-100 flex items-center">
              <ExclamationTriangleIcon className="h-5 w-5 text-red-500 mr-2" />
              Acil Ödemeler (5 Gün İçinde)
            </h2>
            <span className="text-[10px] font-bold bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400 px-2 py-0.5 rounded-full uppercase tracking-widest">
              Önemli
            </span>
          </div>
          {renderMobileList(stats.paymentsIn5Days, 'payment')}
          <div className="hidden md:block">
            <table className="table-modern">
              <thead className="bg-gray-50/50 dark:bg-zinc-900/50">
                <tr>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Müşteri</th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Tutar</th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Vade Tarihi</th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest text-right">Durum</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 dark:divide-[#27272a]/50">
                {stats.paymentsIn5Days.map((payment: any) => (
                  <tr key={payment.id} className="hover:bg-gray-50/50 dark:hover:bg-zinc-900/50 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap text-xs font-bold text-gray-700 dark:text-zinc-300">
                      {payment.contract?.customer?.first_name} {payment.contract?.customer?.last_name}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-xs font-bold text-gray-900 dark:text-zinc-100">
                      {formatTurkishCurrency(Number(payment.amount))}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-zinc-500">
                      {new Date(payment.due_date).toLocaleDateString('tr-TR')}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right">
                      <span className={`px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full ${
                        payment.daysLeft <= 1 ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400'
                      }`}>
                        {payment.daysLeft} GÜN KALDI
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Bitiş Tarihi Yaklaşan Sözleşmeler */}
      {stats.expiringContracts.length > 0 && (
        <div className="modern-card mb-6 border-l-4 border-emerald-500 animate-in fade-in slide-in-from-bottom-2 duration-500 overflow-hidden">
          <div className="p-5 border-b border-gray-100 dark:border-[#27272a]/50 flex items-center justify-between">
            <h2 className="text-sm font-bold text-gray-900 dark:text-zinc-100 flex items-center">
              <DocumentTextIcon className="h-5 w-5 text-emerald-500 mr-2" />
              Bitiş Tarihi Yaklaşan Sözleşmeler (30 Gün İçinde)
            </h2>
          </div>
          {renderMobileList(stats.expiringContracts, 'contract')}
          <div className="hidden md:block">
            <table className="table-modern">
              <thead className="bg-gray-50/50 dark:bg-zinc-900/50">
                <tr>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Sözleşme</th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Müşteri</th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest text-right">Kalan Gün</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 dark:divide-[#27272a]/50">
                {stats.expiringContracts.map((contract: any) => {
                  const endDate = new Date(contract.end_date);
                  const today = new Date();
                  today.setHours(0, 0, 0, 0);
                  const daysLeft = Math.ceil((endDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
                  return (
                    <tr key={contract.id} className="hover:bg-gray-50/50 dark:hover:bg-zinc-900/50 transition-colors">
                      <td className="px-6 py-4 whitespace-nowrap text-xs font-bold text-gray-900 dark:text-zinc-100">
                        {contract.contract_number}
                        <div className="text-[10px] text-gray-400 dark:text-zinc-500 font-medium">Oda: {contract.room?.room_number || '-'}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-xs font-bold text-gray-700 dark:text-zinc-300">{contract.customer?.first_name} {contract.customer?.last_name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-right">
                        <span className={`px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full ${
                          daysLeft <= 7 ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' : daysLeft <= 15 ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'
                        }`}>{daysLeft} GÜN</span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
