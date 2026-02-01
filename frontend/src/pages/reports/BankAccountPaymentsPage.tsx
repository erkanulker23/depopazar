import { useState, useEffect } from 'react';
import { reportsApi } from '../../services/api/reportsApi';
import { bankAccountsApi } from '../../services/api/bankAccountsApi';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import { exportBankAccountReportToExcel } from '../../utils/excelUtils';
import { BuildingOfficeIcon, UserIcon, CreditCardIcon, ChevronDownIcon, ChevronUpIcon, ArrowDownTrayIcon } from '@heroicons/react/24/outline';

interface BankAccountPayment {
  id: string;
  payment_number: string;
  amount: number;
  paid_at: string;
  transaction_id: string | null;
  payment_method: string | null;
  notes: string | null;
  contract_number: string;
  contract_id: string;
}

interface CustomerData {
  customer: {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
  } | null;
  payments: BankAccountPayment[];
  total_amount: number;
  payment_count: number;
}

interface BankAccountData {
  bank_account: {
    id: string;
    bank_name: string;
    account_number: string;
    account_holder_name: string;
    iban: string | null;
    branch_name: string | null;
  } | null;
  customers: CustomerData[];
  total_amount: number;
  total_payments: number;
}

interface ReportData {
  total_amount: number;
  total_payments: number;
  bank_accounts: BankAccountData[];
}

export function BankAccountPaymentsPage() {
  const [reportData, setReportData] = useState<ReportData | null>(null);
  const [bankAccounts, setBankAccounts] = useState<any[]>([]);
  const [selectedBankAccountId, setSelectedBankAccountId] = useState<string>('');
  const [startDate, setStartDate] = useState<string>('');
  const [endDate, setEndDate] = useState<string>('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [expandedBankAccounts, setExpandedBankAccounts] = useState<Set<string>>(new Set());
  const [expandedCustomers, setExpandedCustomers] = useState<Set<string>>(new Set());

  useEffect(() => {
    fetchBankAccounts();
  }, []);

  useEffect(() => {
    fetchReportData();
  }, [selectedBankAccountId, startDate, endDate]);

  const fetchBankAccounts = async () => {
    try {
      const data = await bankAccountsApi.getAll();
      setBankAccounts(data || []);
    } catch (error) {
      console.error('Error fetching bank accounts:', error);
    }
  };

  const fetchReportData = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await reportsApi.getBankAccountPaymentsByCustomer({
        bankAccountId: selectedBankAccountId || undefined,
        startDate: startDate || undefined,
        endDate: endDate || undefined,
      });
      setReportData(data);
      // Auto-expand all bank accounts initially
      if (data.bank_accounts) {
        setExpandedBankAccounts(new Set(data.bank_accounts.map((ba: BankAccountData) => ba.bank_account?.id || '')));
      }
    } catch (e: any) {
      setError(e?.response?.data?.message || e?.message || 'Rapor yüklenemedi.');
    } finally {
      setLoading(false);
    }
  };

  const toggleBankAccount = (bankAccountId: string) => {
    const newExpanded = new Set(expandedBankAccounts);
    if (newExpanded.has(bankAccountId)) {
      newExpanded.delete(bankAccountId);
    } else {
      newExpanded.add(bankAccountId);
    }
    setExpandedBankAccounts(newExpanded);
  };

  const toggleCustomer = (customerId: string) => {
    const newExpanded = new Set(expandedCustomers);
    if (newExpanded.has(customerId)) {
      newExpanded.delete(customerId);
    } else {
      newExpanded.add(customerId);
    }
    setExpandedCustomers(newExpanded);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('tr-TR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const handleExportExcel = () => {
    if (!reportData || !reportData.bank_accounts?.length) return;
    exportBankAccountReportToExcel(reportData);
  };

  return (
    <div>
      <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold gradient-text mb-2">Banka Hesap Raporu</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Hangi banka hesabına ne kadar para girmiş, ne zaman girmiş, hangi müşteriden gelmiş – tüm detaylar
          </p>
        </div>
        {reportData && reportData.bank_accounts?.length > 0 && (
          <button
            type="button"
            onClick={handleExportExcel}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-medium shadow-sm transition-colors"
          >
            <ArrowDownTrayIcon className="h-5 w-5" />
            Excel&apos;e Aktar
          </button>
        )}
      </div>

      {/* Filters */}
      <div className="modern-card p-6 mb-6">
        <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Filtreler</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label htmlFor="bank-account-filter" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Banka Hesabı
            </label>
            <select
              id="bank-account-filter"
              value={selectedBankAccountId}
              onChange={(e) => setSelectedBankAccountId(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Tüm Banka Hesapları</option>
              {bankAccounts.map((ba) => (
                <option key={ba.id} value={ba.id}>
                  {ba.bank_name} - {ba.account_number} ({ba.account_holder_name})
                </option>
              ))}
            </select>
          </div>
          <div>
            <label htmlFor="start-date" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Başlangıç Tarihi (Ödeme tarihi)
            </label>
            <input
              id="start-date"
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
          </div>
          <div>
            <label htmlFor="end-date" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Bitiş Tarihi (Ödeme tarihi)
            </label>
            <input
              id="end-date"
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
          </div>
          <div className="flex items-end">
            <button
              type="button"
              onClick={() => {
                setStartDate('');
                setEndDate('');
              }}
              className="px-4 py-2 text-sm font-medium text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
            >
              Tüm tarihler
            </button>
          </div>
        </div>
      </div>

      {/* Summary Cards */}
      {reportData && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div className="modern-card p-6">
            <div className="flex items-center gap-2 mb-2">
              <CreditCardIcon className="h-6 w-6 text-primary-500" />
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Toplam Tutar</h2>
            </div>
            <p className="text-3xl font-bold text-primary-600 dark:text-primary-400">
              {formatTurkishCurrency(reportData.total_amount)}
            </p>
          </div>
          <div className="modern-card p-6">
            <div className="flex items-center gap-2 mb-2">
              <BuildingOfficeIcon className="h-6 w-6 text-primary-500" />
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Toplam Ödeme</h2>
            </div>
            <p className="text-3xl font-bold text-primary-600 dark:text-primary-400">
              {reportData.total_payments}
            </p>
          </div>
        </div>
      )}

      {error && (
        <div className="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200">
          {error}
        </div>
      )}

      {loading ? (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
        </div>
      ) : (() => {
        const hasBankAccounts = reportData && reportData.bank_accounts.length > 0;
        return hasBankAccounts ? (
        <div className="space-y-4">
          {reportData.bank_accounts.map((bankAccountData) => {
            const bankAccountId = bankAccountData.bank_account?.id || '';
            const isBankAccountExpanded = expandedBankAccounts.has(bankAccountId);

            return (
              <div key={bankAccountId} className="modern-card overflow-hidden">
                {/* Bank Account Header */}
                <button
                  onClick={() => toggleBankAccount(bankAccountId)}
                  className="w-full p-6 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <BuildingOfficeIcon className="h-6 w-6 text-primary-500" />
                      <h3 className="text-xl font-semibold text-gray-900 dark:text-white">
                        {bankAccountData.bank_account?.bank_name || 'Bilinmeyen Banka'}
                      </h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-sm">
                      <div>
                        <span className="text-gray-500 dark:text-gray-400">Hesap No:</span>
                        <span className="ml-2 font-medium text-gray-900 dark:text-white">
                          {bankAccountData.bank_account?.account_number || '-'}
                        </span>
                      </div>
                      <div>
                        <span className="text-gray-500 dark:text-gray-400">Hesap Sahibi:</span>
                        <span className="ml-2 font-medium text-gray-900 dark:text-white">
                          {bankAccountData.bank_account?.account_holder_name || '-'}
                        </span>
                      </div>
                      {bankAccountData.bank_account?.iban && (
                        <div>
                          <span className="text-gray-500 dark:text-gray-400">IBAN:</span>
                          <span className="ml-2 font-medium text-gray-900 dark:text-white">
                            {bankAccountData.bank_account.iban}
                          </span>
                        </div>
                      )}
                    </div>
                    <div className="flex items-center gap-6 mt-4">
                      <div>
                        <span className="text-sm text-gray-500 dark:text-gray-400">Toplam Tutar:</span>
                        <span className="ml-2 text-lg font-bold text-primary-600 dark:text-primary-400">
                          {formatTurkishCurrency(bankAccountData.total_amount)}
                        </span>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500 dark:text-gray-400">Ödeme Sayısı:</span>
                        <span className="ml-2 text-lg font-bold text-gray-900 dark:text-white">
                          {bankAccountData.total_payments}
                        </span>
                      </div>
                      <div>
                        <span className="text-sm text-gray-500 dark:text-gray-400">Müşteri Sayısı:</span>
                        <span className="ml-2 text-lg font-bold text-gray-900 dark:text-white">
                          {bankAccountData.customers.length}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div className="ml-4">
                    {isBankAccountExpanded ? (
                      <ChevronUpIcon className="h-6 w-6 text-gray-400" />
                    ) : (
                      <ChevronDownIcon className="h-6 w-6 text-gray-400" />
                    )}
                  </div>
                </button>

                {/* Customers List */}
                {isBankAccountExpanded && (
                  <div className="border-t border-gray-200 dark:border-gray-700 p-6">
                    <div className="space-y-4">
                      {bankAccountData.customers.map((customerData) => {
                        const customerId = customerData.customer?.id || '';
                        const isCustomerExpanded = expandedCustomers.has(customerId);

                        return (
                          <div
                            key={customerId}
                            className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                          >
                            {/* Customer Header */}
                            <button
                              onClick={() => toggleCustomer(customerId)}
                              className="w-full p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-left"
                            >
                              <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                  <UserIcon className="h-5 w-5 text-primary-500" />
                                  <h4 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    {customerData.customer
                                      ? `${customerData.customer.first_name} ${customerData.customer.last_name}`
                                      : 'Bilinmeyen Müşteri'}
                                  </h4>
                                </div>
                                {customerData.customer && (
                                  <div className="text-sm text-gray-600 dark:text-gray-400 ml-8">
                                    <span>{customerData.customer.email}</span>
                                    {customerData.customer.phone && (
                                      <span className="ml-4">{customerData.customer.phone}</span>
                                    )}
                                  </div>
                                )}
                                <div className="flex items-center gap-6 mt-3 ml-8">
                                  <div>
                                    <span className="text-sm text-gray-500 dark:text-gray-400">Toplam:</span>
                                    <span className="ml-2 font-bold text-primary-600 dark:text-primary-400">
                                      {formatTurkishCurrency(customerData.total_amount)}
                                    </span>
                                  </div>
                                  <div>
                                    <span className="text-sm text-gray-500 dark:text-gray-400">Ödeme:</span>
                                    <span className="ml-2 font-medium text-gray-900 dark:text-white">
                                      {customerData.payment_count} adet
                                    </span>
                                  </div>
                                </div>
                              </div>
                              <div className="ml-4">
                                {isCustomerExpanded ? (
                                  <ChevronUpIcon className="h-5 w-5 text-gray-400" />
                                ) : (
                                  <ChevronDownIcon className="h-5 w-5 text-gray-400" />
                                )}
                              </div>
                            </button>

                            {/* Payments List */}
                            {isCustomerExpanded && (
                              <div className="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/30">
                                <div className="p-4">
                                  <h5 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    Ödeme Detayları
                                  </h5>
                                  <div className="space-y-3">
                                    {customerData.payments.map((payment) => (
                                      <div
                                        key={payment.id}
                                        className="p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600"
                                      >
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                          <div>
                                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                              Ödeme No:
                                            </span>
                                            <p className="font-medium text-gray-900 dark:text-white">
                                              {payment.payment_number}
                                            </p>
                                          </div>
                                          <div>
                                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                              Sözleşme No:
                                            </span>
                                            <p className="font-medium text-gray-900 dark:text-white">
                                              {payment.contract_number}
                                            </p>
                                          </div>
                                          <div>
                                            <span className="text-xs text-gray-500 dark:text-gray-400">Tutar:</span>
                                            <p className="font-bold text-lg text-primary-600 dark:text-primary-400">
                                              {formatTurkishCurrency(payment.amount)}
                                            </p>
                                          </div>
                                          <div>
                                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                              Ödeme Tarihi:
                                            </span>
                                            <p className="font-medium text-gray-900 dark:text-white">
                                              {formatDate(payment.paid_at)}
                                            </p>
                                          </div>
                                          {payment.transaction_id && (
                                            <div>
                                              <span className="text-xs text-gray-500 dark:text-gray-400">
                                                İşlem No:
                                              </span>
                                              <p className="font-medium text-gray-900 dark:text-white">
                                                {payment.transaction_id}
                                              </p>
                                            </div>
                                          )}
                                          {payment.payment_method && (
                                            <div>
                                              <span className="text-xs text-gray-500 dark:text-gray-400">
                                                Ödeme Yöntemi:
                                              </span>
                                              <p className="font-medium text-gray-900 dark:text-white">
                                                {payment.payment_method}
                                              </p>
                                            </div>
                                          )}
                                          {payment.notes && (
                                            <div className="md:col-span-2">
                                              <span className="text-xs text-gray-500 dark:text-gray-400">Notlar:</span>
                                              <p className="font-medium text-gray-900 dark:text-white">
                                                {payment.notes}
                                              </p>
                                            </div>
                                          )}
                                        </div>
                                      </div>
                                    ))}
                                  </div>
                                </div>
                              </div>
                            )}
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
        ) : (
          <div className="modern-card p-8 text-center">
            <p className="text-gray-600 dark:text-gray-400">
              {(() => {
                if (selectedBankAccountId) {
                  return 'Seçilen banka hesabına ait ödeme bulunamadı.';
                }
                return 'Banka hesaplarına ait ödeme bulunamadı.';
              })()}
            </p>
          </div>
        );
      })()}
    </div>
  );
}
