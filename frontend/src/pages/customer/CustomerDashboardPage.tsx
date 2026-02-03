import { useEffect, useState } from 'react';
import { useAuthStore } from '../../stores/authStore';
import { customersApi } from '../../services/api/customersApi';
import { contractsApi } from '../../services/api/contractsApi';
import { paymentsApi } from '../../services/api/paymentsApi';
import {
  DocumentTextIcon,
  CreditCardIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  CalendarIcon,
} from '@heroicons/react/24/outline';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import { useNavigate } from 'react-router-dom';
import { paths } from '../../routes/paths';

export function CustomerDashboardPage() {
  const { user } = useAuthStore();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [customerData, setCustomerData] = useState<any>(null);
  const [contracts, setContracts] = useState<any[]>([]);
  const [payments, setPayments] = useState<any[]>([]);

  useEffect(() => {
    const fetchData = async () => {
      if (!user?.id) return;
      
      try {
        setLoading(true);
        
        // Müşteri bilgilerini al (email ile)
        const allCustomers = await customersApi.getAll({ limit: 100 });
        const customer = allCustomers.data.find((c: any) => c.email === user.email);
        
        if (customer) {
          // Müşteri borç bilgilerini al
          const debtInfo = await customersApi.getDebtInfo(customer.id);
          setCustomerData(debtInfo);
          
          // Müşterinin sözleşmelerini al
          const contractsRes = await contractsApi.getAll({ limit: 100 });
          const customerContracts = contractsRes.data.filter(
            (c: any) => c.customer_id === customer.id && c.is_active
          );
          setContracts(customerContracts);
          
          // Müşterinin ödemelerini al
          const paymentsRes = await paymentsApi.getAll();
          const customerPayments = paymentsRes.filter((p: any) => 
            customerContracts.some((c: any) => c.id === p.contract_id)
          );
          setPayments(customerPayments);
        }
      } catch (error) {
        console.error('Error fetching customer data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [user]);

  if (loading) {
    return (
      <div className="modern-card p-8 text-center">
        <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
      </div>
    );
  }

  if (!customerData) {
    return (
      <div className="modern-card p-8 text-center">
        <p className="text-gray-600 dark:text-gray-400">Müşteri bilgileri bulunamadı.</p>
      </div>
    );
  }

  const activeContracts = contracts.filter((c: any) => c.is_active);
  const pendingPayments = payments.filter(
    (p: any) => p.status === 'pending' || p.status === 'overdue'
  );
  const overduePayments = payments.filter((p: any) => p.status === 'overdue');
  const paidPayments = payments.filter((p: any) => p.status === 'paid');

  const totalDebt = pendingPayments.reduce((sum, p) => sum + Number(p.amount || 0), 0);
  const totalPaid = paidPayments.reduce((sum, p) => sum + Number(p.amount || 0), 0);

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold gradient-text mb-2">
          Hoş Geldiniz, {customerData.first_name} {customerData.last_name}
        </h1>
        <p className="text-gray-600 dark:text-gray-400">Depo yönetim paneliniz</p>
      </div>

      {/* Özet Kartlar */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="modern-card p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Sözleşmeler</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                {activeContracts.length}
              </p>
            </div>
            <DocumentTextIcon className="h-12 w-12 text-primary-500" />
          </div>
        </div>

        <div className="modern-card p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Borç</p>
              <p className="text-2xl font-bold text-red-600 dark:text-red-400 mt-2">
                {formatTurkishCurrency(totalDebt)}
              </p>
            </div>
            <CreditCardIcon className="h-12 w-12 text-red-500" />
          </div>
        </div>

        <div className="modern-card p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Geciken Ödemeler</p>
              <p className="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                {overduePayments.length}
              </p>
            </div>
            <ExclamationTriangleIcon className="h-12 w-12 text-orange-500" />
          </div>
        </div>

        <div className="modern-card p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Ödenen</p>
              <p className="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">
                {formatTurkishCurrency(totalPaid)}
              </p>
            </div>
            <CheckCircleIcon className="h-12 w-12 text-green-500" />
          </div>
        </div>
      </div>

      {/* Aktif Sözleşmeler */}
      <div className="modern-card mb-8">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
            <DocumentTextIcon className="h-6 w-6 mr-2 text-primary-500" />
            Aktif Sözleşmelerim
          </h2>
        </div>
        <div className="p-6">
          {activeContracts.length === 0 ? (
            <p className="text-gray-600 dark:text-gray-400 text-center py-8">
              Aktif sözleşmeniz bulunmamaktadır.
            </p>
          ) : (
            <div className="space-y-4">
              {activeContracts.map((contract: any) => {
                const contractPayments = payments.filter((p: any) => p.contract_id === contract.id);
                const unpaid = contractPayments.filter(
                  (p: any) => p.status === 'pending' || p.status === 'overdue'
                );
                const contractDebt = unpaid.reduce((sum, p) => sum + Number(p.amount || 0), 0);

                return (
                  <div
                    key={contract.id}
                    className="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors"
                    onClick={() => navigate(paths.musteri.sozlesmeDetay(contract.id))}
                  >
                    <div className="flex items-center justify-between">
                      <div>
                        <h3 className="font-semibold text-gray-900 dark:text-white">
                          {contract.contract_number}
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                          Oda: {contract.room?.room_number || 'N/A'}
                        </p>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Aylık: {formatTurkishCurrency(contract.monthly_price)}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Borç</p>
                        <p className="text-lg font-bold text-red-600 dark:text-red-400">
                          {formatTurkishCurrency(contractDebt)}
                        </p>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>

      {/* Bekleyen Ödemeler */}
      {pendingPayments.length > 0 && (
        <div className="modern-card">
          <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
              <CalendarIcon className="h-6 w-6 mr-2 text-orange-500" />
              Bekleyen Ödemelerim
            </h2>
          </div>
          <div className="p-6">
            <div className="space-y-4">
              {pendingPayments
                .sort((a, b) => new Date(a.due_date).getTime() - new Date(b.due_date).getTime())
                .map((payment: any) => {
                  const isOverdue = payment.status === 'overdue';
                  const dueDate = new Date(payment.due_date);
                  
                  return (
                    <div
                      key={payment.id}
                      className={`p-4 border rounded-lg ${
                        isOverdue
                          ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20'
                          : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50'
                      }`}
                    >
                      <div className="flex items-center justify-between">
                        <div>
                          <h3 className="font-semibold text-gray-900 dark:text-white">
                            {payment.payment_number}
                          </h3>
                          <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Vade: {dueDate.toLocaleDateString('tr-TR')}
                          </p>
                          {isOverdue && (
                            <p className="text-sm text-red-600 dark:text-red-400 mt-1">
                              {payment.days_overdue} gün gecikmiş
                            </p>
                          )}
                        </div>
                        <div className="text-right">
                          <p className="text-lg font-bold text-gray-900 dark:text-white">
                            {formatTurkishCurrency(payment.amount)}
                          </p>
                          {isOverdue && (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 mt-1">
                              Gecikmiş
                            </span>
                          )}
                        </div>
                      </div>
                    </div>
                  );
                })}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
