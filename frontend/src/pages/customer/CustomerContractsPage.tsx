import { useEffect, useState } from 'react';
import { useAuthStore } from '../../stores/authStore';
import { customersApi } from '../../services/api/customersApi';
import { contractsApi } from '../../services/api/contractsApi';
import { paymentsApi } from '../../services/api/paymentsApi';
import {
  DocumentTextIcon,
  CalendarIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import toast from 'react-hot-toast';

export function CustomerContractsPage() {
  const { user } = useAuthStore();
  const [loading, setLoading] = useState(true);
  const [customerData, setCustomerData] = useState<any>(null);
  const [contracts, setContracts] = useState<any[]>([]);
  const [expandedContract, setExpandedContract] = useState<string | null>(null);

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
        }
      } catch (error) {
        console.error('Error fetching contracts:', error);
        toast.error('Sözleşmeler yüklenirken bir hata oluştu');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [user]);

  const handlePayment = async (paymentId: string, _amount: number) => {
    try {
      await paymentsApi.markAsPaid(paymentId, 'online', undefined, 'Müşteri tarafından online ödeme');
      toast.success('Ödeme başarıyla tamamlandı');
      // Sayfayı yenile
      window.location.reload();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Ödeme yapılırken bir hata oluştu');
    }
  };

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

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold gradient-text mb-2">Sözleşmelerim</h1>
        <p className="text-gray-600 dark:text-gray-400">Aktif depolama sözleşmeleriniz</p>
      </div>

      {contracts.length === 0 ? (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">Aktif sözleşmeniz bulunmamaktadır.</p>
        </div>
      ) : (
        <div className="space-y-6">
          {contracts.map((contract: any) => {
            const contractPayments = contract.payments || [];
            const unpaidPayments = contractPayments.filter(
              (p: any) => p.status === 'pending' || p.status === 'overdue'
            );
            const contractDebt = unpaidPayments.reduce((sum: number, p: any) => sum + Number(p.amount || 0), 0);
            const isExpanded = expandedContract === contract.id;

            // Ödemeleri aya göre grupla
            const paymentsByMonth: Record<string, any[]> = {};
            contractPayments.forEach((payment: any) => {
              const dueDate = new Date(payment.due_date);
              const monthKey = `${dueDate.getFullYear()}-${String(dueDate.getMonth() + 1).padStart(2, '0')}`;
              if (!paymentsByMonth[monthKey]) {
                paymentsByMonth[monthKey] = [];
              }
              paymentsByMonth[monthKey].push(payment);
            });

            const monthNames = [
              'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
              'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
            ];

            return (
              <div key={contract.id} className="modern-card">
                <div
                  className="p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                  onClick={() => setExpandedContract(isExpanded ? null : contract.id)}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                      <DocumentTextIcon className="h-8 w-8 text-primary-500" />
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                          {contract.contract_number}
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                          Oda: {contract.room?.room_number || 'N/A'} | 
                          Aylık: {formatTurkishCurrency(contract.monthly_price)}
                        </p>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Başlangıç: {new Date(contract.start_date).toLocaleDateString('tr-TR')} | 
                          Bitiş: {new Date(contract.end_date).toLocaleDateString('tr-TR')}
                        </p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-sm text-gray-600 dark:text-gray-400">Toplam Borç</p>
                      <p className="text-xl font-bold text-red-600 dark:text-red-400">
                        {formatTurkishCurrency(contractDebt)}
                      </p>
                      {unpaidPayments.length > 0 && (
                        <p className="text-xs text-orange-600 dark:text-orange-400 mt-1">
                          {unpaidPayments.length} ödeme bekliyor
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                {isExpanded && (
                  <div className="border-t border-gray-200 dark:border-gray-700 p-6">
                    <h4 className="font-semibold text-gray-900 dark:text-white mb-4">
                      Ödeme Geçmişi ve Bekleyen Ödemeler
                    </h4>
                    
                    <div className="space-y-4">
                      {Object.keys(paymentsByMonth)
                        .sort()
                        .reverse()
                        .map((monthKey) => {
                          const [year, month] = monthKey.split('-');
                          const monthPayments = paymentsByMonth[monthKey];
                          const unpaidInMonth = monthPayments.filter(
                            (p: any) => p.status === 'pending' || p.status === 'overdue'
                          );

                          return (
                            <div
                              key={monthKey}
                              className="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                            >
                              <div className="flex items-center justify-between mb-3">
                                <h5 className="font-semibold text-gray-900 dark:text-white">
                                  {monthNames[Number.parseInt(month) - 1]} {year}
                                </h5>
                                {unpaidInMonth.length > 0 && (
                                  <span className="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 rounded">
                                    {unpaidInMonth.length} ödeme bekliyor
                                  </span>
                                )}
                              </div>
                              
                              <div className="space-y-2">
                                {monthPayments.map((payment: any) => {
                                  const dueDate = new Date(payment.due_date);
                                  const isOverdue = payment.status === 'overdue';
                                  const isPaid = payment.status === 'paid';

                                  return (
                                    <div
                                      key={payment.id}
                                      className={`flex items-center justify-between p-3 rounded-lg ${
                                        isOverdue
                                          ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
                                          : isPaid
                                          ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
                                          : 'bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700'
                                      }`}
                                    >
                                      <div className="flex items-center space-x-3">
                                        {isPaid ? (
                                          <CheckCircleIcon className="h-5 w-5 text-green-500" />
                                        ) : isOverdue ? (
                                          <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />
                                        ) : (
                                          <CalendarIcon className="h-5 w-5 text-gray-400" />
                                        )}
                                        <div>
                                          <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            {payment.payment_number}
                                          </p>
                                          <p className="text-xs text-gray-600 dark:text-gray-400">
                                            Vade: {dueDate.toLocaleDateString('tr-TR')}
                                            {isOverdue && (
                                              <span className="ml-2 text-red-600 dark:text-red-400">
                                                ({payment.days_overdue} gün gecikmiş)
                                              </span>
                                            )}
                                            {isPaid && payment.paid_at && (
                                              <span className="ml-2 text-green-600 dark:text-green-400">
                                                (Ödendi: {new Date(payment.paid_at).toLocaleDateString('tr-TR')})
                                              </span>
                                            )}
                                          </p>
                                        </div>
                                      </div>
                                      <div className="flex items-center space-x-4">
                                        <div className="text-right">
                                          <p className="text-sm font-semibold text-gray-900 dark:text-white">
                                            {formatTurkishCurrency(payment.amount)}
                                          </p>
                                          {isPaid && payment.payment_method && (
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                              {payment.payment_method === 'credit_card' && 'Kredi Kartı'}
                                              {payment.payment_method === 'bank_transfer' && 'Banka Transferi'}
                                              {payment.payment_method === 'cash' && 'Nakit'}
                                              {payment.payment_method === 'online' && 'Online'}
                                            </p>
                                          )}
                                        </div>
                                        {!isPaid && (
                                          <button
                                            onClick={(e) => {
                                              e.stopPropagation();
                                              handlePayment(payment.id, payment.amount);
                                            }}
                                            className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium"
                                          >
                                            Öde
                                          </button>
                                        )}
                                      </div>
                                    </div>
                                  );
                                })}
                              </div>
                            </div>
                          );
                        })}
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
