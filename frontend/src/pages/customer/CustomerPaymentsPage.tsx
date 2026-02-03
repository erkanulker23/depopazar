import { useEffect, useState } from 'react';
import { useAuthStore } from '../../stores/authStore';
import { customersApi } from '../../services/api/customersApi';
import { contractsApi } from '../../services/api/contractsApi';
import { paymentsApi } from '../../services/api/paymentsApi';
import { companiesApi } from '../../services/api/companiesApi';
import {
  CreditCardIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  CalendarIcon,
  DocumentTextIcon,
  BuildingLibraryIcon,
} from '@heroicons/react/24/outline';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import { getErrorMessage } from '../../utils/errorMessage';
import toast from 'react-hot-toast';

export function CustomerPaymentsPage() {
  const { user } = useAuthStore();
  const [loading, setLoading] = useState(true);
  const [customerData, setCustomerData] = useState<any>(null);
  const [allPayments, setAllPayments] = useState<any[]>([]);
  const [filter, setFilter] = useState<'all' | 'pending' | 'overdue' | 'paid'>('all');
  const [bankAccounts, setBankAccounts] = useState<any[]>([]);

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
          
          // Müşterinin tüm ödemelerini al
          const paymentsRes = await paymentsApi.getAll();
          const customerPayments = paymentsRes.filter((p: any) => 
            customerContracts.some((c: any) => c.id === p.contract_id)
          );
          
          // Sözleşme bilgilerini ödemelere ekle
          const paymentsWithContract = customerPayments.map((payment: any) => {
            const contract = customerContracts.find((c: any) => c.id === payment.contract_id);
            return {
              ...payment,
              contract: contract,
            };
          });
          
          // Tarihe göre sırala (en eski önce)
          paymentsWithContract.sort((a: any, b: any) => {
            const dateA = new Date(a.due_date).getTime();
            const dateB = new Date(b.due_date).getTime();
            return dateA - dateB;
          });
          
          setAllPayments(paymentsWithContract);
        }

        // Load bank accounts
        try {
          const bankAccountsData = await companiesApi.getActiveBankAccounts();
          setBankAccounts(bankAccountsData);
        } catch (error) {
          console.error('Error loading bank accounts:', error);
        }
      } catch (err: unknown) {
        console.error('Error fetching payments:', err);
        toast.error(getErrorMessage(err));
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [user]);

  const handlePayment = async (paymentId: string, amount: number) => {
    if (!confirm(`${formatTurkishCurrency(amount)} tutarındaki ödemeyi yapmak istediğinize emin misiniz?`)) {
      return;
    }

    try {
      await paymentsApi.markAsPaid(paymentId, 'online', undefined, 'Müşteri tarafından online ödeme');
      toast.success('Ödeme başarıyla tamamlandı');
      // Sayfayı yenile
      window.location.reload();
    } catch (error: any) {
      toast.error(getErrorMessage(error));
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

  const filteredPayments = allPayments.filter((payment) => {
    if (filter === 'all') return true;
    if (filter === 'pending') return payment.status === 'pending';
    if (filter === 'overdue') return payment.status === 'overdue';
    if (filter === 'paid') return payment.status === 'paid';
    return true;
  });

  const pendingPayments = allPayments.filter((p) => p.status === 'pending' || p.status === 'overdue');
  const overduePayments = allPayments.filter((p) => p.status === 'overdue');
  const paidPayments = allPayments.filter((p) => p.status === 'paid');
  const totalDebt = pendingPayments.reduce((sum, p) => sum + Number(p.amount || 0), 0);
  const totalPaid = paidPayments.reduce((sum, p) => sum + Number(p.amount || 0), 0);

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold gradient-text mb-2">Ödemelerim</h1>
        <p className="text-gray-600 dark:text-gray-400">Ödeme geçmişiniz ve bekleyen ödemeleriniz</p>
      </div>

      {/* Özet Kartlar */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen Ödemeler</p>
              <p className="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                {pendingPayments.length}
              </p>
            </div>
            <CalendarIcon className="h-12 w-12 text-orange-500" />
          </div>
        </div>

        <div className="modern-card p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Geciken Ödemeler</p>
              <p className="text-2xl font-bold text-red-600 dark:text-red-400 mt-2">
                {overduePayments.length}
              </p>
            </div>
            <ExclamationTriangleIcon className="h-12 w-12 text-red-500" />
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

      {/* Banka Hesap Bilgileri */}
      {bankAccounts.length > 0 && (
        <div className="modern-card mb-8">
          <div className="p-6">
            <div className="flex items-center space-x-2 mb-4">
              <BuildingLibraryIcon className="h-6 w-6 text-primary-600 dark:text-primary-400" />
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                Havale ile Ödeme Yapmak İçin Banka Hesap Bilgileri
              </h2>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {bankAccounts.map((account) => (
                <div
                  key={account.id}
                  className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50"
                >
                  <h3 className="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <BuildingLibraryIcon className="h-5 w-5 mr-2 text-primary-600 dark:text-primary-400" />
                    {account.bank_name}
                  </h3>
                  <div className="space-y-2 text-sm">
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">Hesap Sahibi:</span>{' '}
                      <span className="font-medium text-gray-900 dark:text-white">
                        {account.account_holder_name}
                      </span>
                    </div>
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">Hesap No:</span>{' '}
                      <span className="font-medium text-gray-900 dark:text-white font-mono">
                        {account.account_number}
                      </span>
                    </div>
                    {account.iban && (
                      <div>
                        <span className="text-gray-600 dark:text-gray-400">IBAN:</span>{' '}
                        <span className="font-medium text-gray-900 dark:text-white font-mono">
                          {account.iban}
                        </span>
                      </div>
                    )}
                    {account.branch_name && (
                      <div>
                        <span className="text-gray-600 dark:text-gray-400">Şube:</span>{' '}
                        <span className="font-medium text-gray-900 dark:text-white">
                          {account.branch_name}
                        </span>
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Filtreler */}
      <div className="mb-6 flex space-x-2">
        <button
          onClick={() => setFilter('all')}
          className={`px-4 py-2 rounded-lg font-medium transition-colors ${
            filter === 'all'
              ? 'bg-primary-600 text-white'
              : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
          }`}
        >
          Tümü ({allPayments.length})
        </button>
        <button
          onClick={() => setFilter('pending')}
          className={`px-4 py-2 rounded-lg font-medium transition-colors ${
            filter === 'pending'
              ? 'bg-primary-600 text-white'
              : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
          }`}
        >
          Bekleyen ({pendingPayments.length})
        </button>
        <button
          onClick={() => setFilter('overdue')}
          className={`px-4 py-2 rounded-lg font-medium transition-colors ${
            filter === 'overdue'
              ? 'bg-primary-600 text-white'
              : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
          }`}
        >
          Gecikmiş ({overduePayments.length})
        </button>
        <button
          onClick={() => setFilter('paid')}
          className={`px-4 py-2 rounded-lg font-medium transition-colors ${
            filter === 'paid'
              ? 'bg-primary-600 text-white'
              : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
          }`}
        >
          Ödenen ({paidPayments.length})
        </button>
      </div>

      {/* Ödemeler Listesi */}
      {filteredPayments.length === 0 ? (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">
            {filter === 'all' 
              ? 'Henüz ödeme bulunmamaktadır.'
              : filter === 'pending'
              ? 'Bekleyen ödeme bulunmamaktadır.'
              : filter === 'overdue'
              ? 'Gecikmiş ödeme bulunmamaktadır.'
              : 'Ödenen ödeme bulunmamaktadır.'}
          </p>
        </div>
      ) : (
        <div className="modern-card">
          <div className="overflow-x-auto">
            <table className="table-modern">
              <thead>
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Ödeme No
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Sözleşme
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Vade Tarihi
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Tutar
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Durum
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    İşlemler
                  </th>
                </tr>
              </thead>
              <tbody>
                {filteredPayments.map((payment: any) => {
                  const dueDate = new Date(payment.due_date);
                  const isOverdue = payment.status === 'overdue';
                  const isPaid = payment.status === 'paid';

                  return (
                    <tr key={payment.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {payment.payment_number}
                        {payment.type === 'transportation' && (
                          <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                            Nakliye
                          </span>
                        )}
                        {payment.type === 'other' && (
                          <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                            Diğer
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                        <div className="flex items-center">
                          <DocumentTextIcon className="h-4 w-4 mr-2 text-primary-500" />
                          {payment.contract?.contract_number || 'N/A'}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                        {dueDate.toLocaleDateString('tr-TR')}
                        {isOverdue && (
                          <span className="ml-2 text-xs text-red-600 dark:text-red-400">
                            ({payment.days_overdue} gün gecikmiş)
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                        {formatTurkishCurrency(payment.amount)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {isPaid ? (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <CheckCircleIcon className="h-3 w-3 mr-1" />
                            Ödendi
                          </span>
                        ) : isOverdue ? (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <ExclamationTriangleIcon className="h-3 w-3 mr-1" />
                            Gecikmiş
                          </span>
                        ) : (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            <CalendarIcon className="h-3 w-3 mr-1" />
                            Beklemede
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        {isPaid ? (
                          <div className="text-gray-500 dark:text-gray-400">
                            {payment.paid_at && (
                              <div>
                                <div>Ödeme: {new Date(payment.paid_at).toLocaleDateString('tr-TR')}</div>
                                {payment.payment_method && (
                                  <div className="text-xs mt-1">
                                    {payment.payment_method === 'credit_card' && 'Kredi Kartı'}
                                    {payment.payment_method === 'bank_transfer' && 'Banka Transferi'}
                                    {payment.payment_method === 'cash' && 'Nakit'}
                                    {payment.payment_method === 'online' && 'Online'}
                                  </div>
                                )}
                              </div>
                            )}
                          </div>
                        ) : (
                          <button
                            onClick={() => handlePayment(payment.id, payment.amount)}
                            className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium"
                          >
                            Öde
                          </button>
                        )}
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
