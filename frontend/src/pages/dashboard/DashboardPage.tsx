import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
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
} from '@heroicons/react/24/outline';
import { formatTurkishCurrency } from '../../utils/inputFormatters';

export function DashboardPage() {
  const navigate = useNavigate();
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
        const [warehouses, rooms, customersRes, payments, contractsRes] = await Promise.all([
          warehousesApi.getAll(),
          roomsApi.getAll(),
          customersApi.getAll({ limit: 100 }),
          paymentsApi.getAll(),
          contractsApi.getAll({ limit: 100 }),
        ]);
        const customers = customersRes.data;
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
      <div className="mb-8">
        <h1 className="text-3xl md:text-4xl font-bold gradient-text mb-2">Dashboard</h1>
        <p className="text-gray-600 dark:text-gray-400">Sistem özeti ve istatistikler</p>
      </div>

      {/* Ana İstatistikler - Modern Design */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div className="modern-card-gradient p-6 group hover:scale-[1.02] transition-transform duration-300">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                Toplam Depo
              </p>
              <p className="text-3xl font-bold text-gray-900 dark:text-white">
                {stats.loading ? '...' : stats.warehouses}
              </p>
            </div>
            <div className="p-3 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl shadow-lg">
              <BuildingOfficeIcon className="h-8 w-8 text-white" />
            </div>
          </div>
        </div>

        <div className="modern-card-gradient p-6 group hover:scale-[1.02] transition-transform duration-300">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                Toplam Oda
              </p>
              <p className="text-3xl font-bold text-gray-900 dark:text-white">
                {stats.loading ? '...' : stats.rooms}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                <span className="font-semibold text-green-600 dark:text-green-400">{stats.occupiedRooms}</span> Dolu / <span className="font-semibold text-gray-400">{stats.emptyRooms}</span> Boş
              </p>
            </div>
            <div className="p-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl shadow-lg">
              <CubeIcon className="h-8 w-8 text-white" />
            </div>
          </div>
        </div>

        <div className="modern-card-gradient p-6 group hover:scale-[1.02] transition-transform duration-300">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                Toplam Müşteri
              </p>
              <p className="text-3xl font-bold text-gray-900 dark:text-white">
                {stats.loading ? '...' : stats.customers}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                <span className="font-semibold text-primary-600 dark:text-primary-400">{stats.activeContracts}</span> Aktif Sözleşme
              </p>
            </div>
            <div className="p-3 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl shadow-lg">
              <UsersIcon className="h-8 w-8 text-white" />
            </div>
          </div>
        </div>

        <div className="modern-card-gradient p-6 group hover:scale-[1.02] transition-transform duration-300">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                Bu Ay Gelir
              </p>
              <p className="text-3xl font-bold text-gray-900 dark:text-white">
                {stats.loading ? '...' : formatTurkishCurrency(stats.monthlyRevenue)}
              </p>
            </div>
            <div className="p-3 bg-gradient-to-br from-green-400 to-green-600 rounded-xl shadow-lg">
              <CurrencyDollarIcon className="h-8 w-8 text-white" />
            </div>
          </div>
        </div>
      </div>

      {/* Ödeme Durumları - Modern Design */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <button
          onClick={() => navigate('/payments?status=pending')}
          className="modern-card-gradient p-6 border-l-4 border-yellow-500 group hover:scale-[1.02] transition-transform duration-300 cursor-pointer w-full text-left"
        >
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                Bekleyen Ödeme
              </p>
              <p className="text-3xl font-bold text-gray-900 dark:text-white">
                {stats.loading ? '...' : stats.pendingPayments}
              </p>
            </div>
            <div className="p-3 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl shadow-lg">
              <CreditCardIcon className="h-8 w-8 text-white" />
            </div>
          </div>
        </button>

        <button
          onClick={() => navigate('/payments?status=overdue')}
          className="modern-card-gradient p-6 border-l-4 border-red-500 group hover:scale-[1.02] transition-transform duration-300 cursor-pointer w-full text-left"
        >
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                Geciken Ödeme
              </p>
              <p className="text-3xl font-bold text-gray-900 dark:text-white">
                {stats.loading ? '...' : stats.overduePayments}
              </p>
            </div>
            <div className="p-3 bg-gradient-to-br from-red-400 to-red-600 rounded-xl shadow-lg">
              <ExclamationTriangleIcon className="h-8 w-8 text-white" />
            </div>
          </div>
        </button>

        <button
          onClick={() => navigate('/payments?status=unpaid')}
          className="modern-card-gradient p-6 border-l-4 border-red-500 group hover:scale-[1.02] transition-transform duration-300 cursor-pointer w-full text-left sm:col-span-2 lg:col-span-1"
        >
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                Toplam Borç
              </p>
              <p className="text-3xl font-bold text-red-600 dark:text-red-400">
                {stats.loading ? '...' : formatTurkishCurrency(stats.totalDebt)}
              </p>
            </div>
            <div className="p-3 bg-gradient-to-br from-red-400 to-red-600 rounded-xl shadow-lg">
              <ChartBarIcon className="h-8 w-8 text-white" />
            </div>
          </div>
        </button>
      </div>

      {/* 5 Gün İçinde Kalan Ödemeler - Modern Design */}
      {stats.paymentsIn5Days.length > 0 && (
        <div className="modern-card-gradient p-6 mb-6 border-l-4 border-red-500 animate-fade-in">
          <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <div className="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg mr-3">
              <ExclamationTriangleIcon className="h-6 w-6 text-red-600 dark:text-red-400" />
            </div>
            Acil Ödemeler (5 Gün İçinde)
          </h2>
          {renderMobileList(stats.paymentsIn5Days, 'payment')}
          <div className="hidden md:block overflow-x-auto">
            <table className="table-modern">
              <thead className="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Müşteri</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tutar</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vade Tarihi</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kalan Gün</th>
                </tr>
              </thead>
              <tbody>
                {stats.paymentsIn5Days.map((payment: any) => (
                  <tr key={payment.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {payment.contract?.customer?.first_name} {payment.contract?.customer?.last_name}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {formatTurkishCurrency(Number(payment.amount))}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {new Date(payment.due_date).toLocaleDateString('tr-TR')}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                        payment.daysLeft <= 1 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
                      }`}>
                        {payment.daysLeft} Gün
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
        <div className="modern-card-gradient p-6 mb-6 animate-fade-in border-l-4 border-orange-500">
          <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <div className="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg mr-3">
              <ExclamationTriangleIcon className="h-6 w-6 text-orange-600 dark:text-orange-400" />
            </div>
            Bitiş Tarihi Yaklaşan Sözleşmeler (30 Gün İçinde)
          </h2>
          {renderMobileList(stats.expiringContracts, 'contract')}
          <div className="hidden md:block overflow-x-auto">
            <table className="table-modern">
              <thead>
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sözleşme No</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Müşteri</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Oda</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bitiş Tarihi</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kalan Gün</th>
                </tr>
              </thead>
              <tbody>
                {stats.expiringContracts.map((contract: any) => {
                  const endDate = new Date(contract.end_date);
                  const today = new Date();
                  today.setHours(0, 0, 0, 0);
                  const daysLeft = Math.ceil((endDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
                  return (
                    <tr key={contract.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{contract.contract_number}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{contract.customer?.first_name} {contract.customer?.last_name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{contract.room?.room_number || '-'}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{endDate.toLocaleDateString('tr-TR')}</td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                          daysLeft <= 7 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : daysLeft <= 15 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                        }`}>{daysLeft} Gün</span>
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
