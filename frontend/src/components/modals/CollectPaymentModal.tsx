import { useState, useEffect } from 'react';
import { XMarkIcon, CreditCardIcon, UserIcon, BanknotesIcon, MagnifyingGlassIcon, CurrencyDollarIcon, BuildingLibraryIcon } from '@heroicons/react/24/outline';
import { customersApi } from '../../services/api/customersApi';
import { paymentsApi } from '../../services/api/paymentsApi';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import { companiesApi, BankAccount } from '../../services/api/companiesApi';

interface CollectPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess?: () => void;
  initialCustomer?: any;
  initialPayments?: any[];
}

export function CollectPaymentModal({ isOpen, onClose, onSuccess, initialCustomer, initialPayments }: CollectPaymentModalProps) {
  const [step, setStep] = useState<'customer' | 'customerInfo' | 'payment' | 'paymentMethod' | 'card' | 'bankTransfer'>('customer');
  const [, setSelectedPaymentMethod] = useState<'cash' | 'bank_transfer' | 'credit_card' | null>(null);
  const [bankTransferData, setBankTransferData] = useState({
    transactionId: '',
    notes: '',
  });
  const [loading, setLoading] = useState(false);
  const [customers, setCustomers] = useState<any[]>([]);
  const [filteredCustomers, setFilteredCustomers] = useState<any[]>([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCustomer, setSelectedCustomer] = useState<any>(null);
  const [customerDebtInfo, setCustomerDebtInfo] = useState<any>(null);
  const [payments, setPayments] = useState<any[]>([]);
  const [selectedPayment, setSelectedPayment] = useState<any>(null);
  const [paytrSettings, setPaytrSettings] = useState<any>(null);
  const [paytrToken, setPaytrToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [bankAccounts, setBankAccounts] = useState<BankAccount[]>([]);
  const [selectedBankAccountId, setSelectedBankAccountId] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;
    
    if (isOpen) {
      // Load settings and bank accounts
      loadPaytrSettings().catch((error) => {
        console.error('Error loading PayTR settings:', error);
        if (isMounted) {
          setError('PayTR ayarları yüklenemedi');
        }
      });
      
      loadBankAccounts().catch((error) => {
        console.error('Error loading bank accounts:', error);
        // Don't set error for bank accounts as it's not critical
      });
      
      // If initial customer and payments are provided, skip to payment selection
      if (initialCustomer && initialPayments && initialPayments.length > 0) {
        setSelectedCustomer(initialCustomer);
        setPayments(initialPayments);
        // Load customer debt info
        customersApi.getDebtInfo(initialCustomer.id).then((customerData) => {
          if (isMounted) {
            setCustomerDebtInfo(customerData);
          }
        }).catch((error) => {
          console.error('Error loading customer debt info:', error);
          if (isMounted) {
            setError('Müşteri borç bilgileri yüklenemedi');
          }
        });
        setStep('payment');
      } else {
        loadCustomers().catch((error) => {
          console.error('Error loading customers:', error);
          if (isMounted) {
            setError('Müşteriler yüklenemedi');
          }
        });
        setStep('customer');
      }
    } else {
      // Reset state when modal closes
      setStep('customer');
      setSelectedCustomer(null);
      setCustomerDebtInfo(null);
      setSelectedPayment(null);
      setPaytrToken(null);
      setError(null);
      setSearchTerm('');
      setSelectedPaymentMethod(null);
      setSelectedBankAccountId(null);
      setBankTransferData({
        transactionId: '',
        notes: '',
      });
    }
    
    return () => {
      isMounted = false;
    };
  }, [isOpen, initialCustomer, initialPayments]);

  // Filter customers based on search term
  useEffect(() => {
    if (searchTerm.trim() === '') {
      setFilteredCustomers(customers);
    } else {
      const searchLower = searchTerm.toLowerCase();
      const filtered = customers.filter((customer) => {
        const customerName = (customer.first_name || customer.user?.first_name || '').toLowerCase();
        const customerLastName = (customer.last_name || customer.user?.last_name || '').toLowerCase();
        const customerEmail = (customer.email || customer.user?.email || '').toLowerCase();
        const customerPhone = (customer.phone || customer.user?.phone || '').toLowerCase();
        
        return (
          customerName.includes(searchLower) ||
          customerLastName.includes(searchLower) ||
          customerEmail.includes(searchLower) ||
          customerPhone.includes(searchLower) ||
          `${customerName} ${customerLastName}`.includes(searchLower)
        );
      });
      setFilteredCustomers(filtered);
    }
  }, [searchTerm, customers]);

  const calculateCustomerDebt = (customer: any): number => {
    if (!customer.contracts) return 0;
    
    const activeContracts = customer.contracts.filter((c: any) => c.is_active) || [];
    const allPayments = activeContracts.flatMap((contract: any) => contract.payments || []);
    const unpaidPayments = allPayments.filter(
      (p: any) => p.status === 'pending' || p.status === 'overdue'
    );
    
    // Make sure to parse amounts correctly
    const totalDebt = unpaidPayments.reduce((sum: number, p: any) => {
      const amount = Number(p.amount) || 0;
      return sum + amount;
    }, 0);
    
    return totalDebt;
  };

  const loadCustomers = async () => {
    try {
      const res = await customersApi.getAll({ limit: 100 });
      if (res && res.data && Array.isArray(res.data)) {
        setCustomers(res.data);
        setFilteredCustomers(res.data);
      } else {
        setCustomers([]);
        setFilteredCustomers([]);
      }
    } catch (error: any) {
      console.error('Error loading customers:', error);
      setError('Müşteriler yüklenemedi: ' + (error.response?.data?.message || error.message || 'Bilinmeyen hata'));
      setCustomers([]);
      setFilteredCustomers([]);
    }
  };

  const loadPaytrSettings = async () => {
    try {
      const settings = await companiesApi.getPaytrSettings();
      if (settings) {
        setPaytrSettings(settings);
        
        if (!settings.is_active || !settings.merchant_id || !settings.merchant_key || !settings.merchant_salt) {
          // Don't set error here, just log - PayTR is optional
          console.warn('PayTR ayarları aktif değil veya eksik');
        }
      }
    } catch (error: any) {
      console.error('Error loading PayTR settings:', error);
      // Don't set error here as PayTR is optional - only log
      setPaytrSettings(null);
    }
  };

  const loadBankAccounts = async () => {
    try {
      const accounts = await companiesApi.getActiveBankAccounts();
      if (accounts && Array.isArray(accounts)) {
        setBankAccounts(accounts);
        // Auto-select first account if available
        if (accounts.length > 0 && !selectedBankAccountId) {
          setSelectedBankAccountId(accounts[0].id);
        }
      } else {
        setBankAccounts([]);
      }
    } catch (error: any) {
      console.error('Error loading bank accounts:', error);
      setBankAccounts([]);
    }
  };

  const handleCustomerSelect = async (customer: any) => {
    if (!customer || !customer.id) {
      setError('Geçersiz müşteri seçildi');
      return;
    }
    
    setSelectedCustomer(customer);
    setLoading(true);
    setError(null);
    
    try {
      const customerData = await customersApi.getDebtInfo(customer.id);
      if (customerData) {
        const unpaidPayments = customerData.unpaidPayments || [];
        setPayments(Array.isArray(unpaidPayments) ? unpaidPayments : []);
        setCustomerDebtInfo(customerData);
        setStep('customerInfo');
      } else {
        setError('Müşteri bilgileri alınamadı');
        setPayments([]);
        setCustomerDebtInfo(null);
      }
    } catch (error: any) {
      console.error('Error loading customer debt info:', error);
      setError('Müşteri bilgileri yüklenemedi: ' + (error.response?.data?.message || error.message || 'Bilinmeyen hata'));
      setPayments([]);
      setCustomerDebtInfo(null);
    } finally {
      setLoading(false);
    }
  };

  const handleContinueToPayment = () => {
    if (payments.length === 0) {
      setError('Bu müşterinin ödenmemiş ödemesi bulunmamaktadır.');
      return;
    }
    setStep('payment');
  };

  const handlePaymentSelect = (payment: any) => {
    setSelectedPayment(payment);
    setError(null);
    setStep('paymentMethod');
  };

  const handlePaymentMethodSelect = async (method: 'cash' | 'bank_transfer' | 'credit_card') => {
    if (loading) return; // Prevent double clicks
    
    setSelectedPaymentMethod(method);
    setError(null);

    if (method === 'cash') {
      // Nakit ödeme - direkt kaydet
      await handleCashPayment();
    } else if (method === 'bank_transfer') {
      // Havale ödeme - işlem no ile kaydet
      setStep('bankTransfer');
    } else if (method === 'credit_card') {
      // Kredi kartı - PayTR ile
      await handleCreditCardPayment();
    }
  };

  const handleCashPayment = async () => {
    if (!selectedPayment || loading) return;
    
    setLoading(true);
    setError(null);
    try {
      await paymentsApi.markAsPaid(selectedPayment.id, 'cash');
      if (onSuccess) {
        onSuccess();
      }
      onClose();
    } catch (error: any) {
      setError('Ödeme kaydedilemedi: ' + (error.response?.data?.message || error.message || 'Bilinmeyen hata'));
    } finally {
      setLoading(false);
    }
  };

  const handleBankTransferSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedPayment || loading) return;

    if (!selectedBankAccountId) {
      setError('Lütfen bir banka hesabı seçin');
      return;
    }

    setLoading(true);
    setError(null);
    try {
      await paymentsApi.markAsPaid(
        selectedPayment.id,
        'bank_transfer',
        bankTransferData.transactionId || undefined,
        bankTransferData.notes || undefined,
        selectedBankAccountId
      );
      if (onSuccess) {
        onSuccess();
      }
      onClose();
    } catch (error: any) {
      setError('Ödeme kaydedilemedi: ' + (error.response?.data?.message || error.message || 'Bilinmeyen hata'));
    } finally {
      setLoading(false);
    }
  };

  const handleCreditCardPayment = async () => {
    if (!selectedPayment) return;

    setLoading(true);
    setError(null);
    try {
      if (!paytrSettings?.is_active) {
        setError('PayTR ayarları aktif değil');
        setLoading(false);
        return;
      }

      const result = await paymentsApi.initiatePaytr(selectedPayment.id, selectedCustomer.id);
      setPaytrToken(result.token);
      setStep('card');
    } catch (error: any) {
      setError('Ödeme başlatılamadı: ' + (error.response?.data?.message || error.message || 'Bilinmeyen hata'));
    } finally {
      setLoading(false);
    }
  };



  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onClick={onClose} />

        <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                Ödeme Al
              </h3>
              <button
                onClick={onClose}
                className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
              >
                <XMarkIcon className="h-6 w-6" />
              </button>
            </div>

            {error && (
              <div className="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p className="text-sm text-red-800 dark:text-red-300">{error}</p>
              </div>
            )}

            {/* Step 1: Customer Selection */}
            {step === 'customer' && (
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  Ödeme almak istediğiniz müşteriyi seçin
                </p>
                
                {/* Search Input */}
                <div className="mb-4 relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    type="text"
                    placeholder="Müşteri ara (ad, soyad, telefon, email)..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>

                <div className="max-h-96 overflow-y-auto space-y-2">
                  {filteredCustomers.length === 0 ? (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      {searchTerm.trim() !== '' 
                        ? 'Arama kriterlerinize uygun müşteri bulunamadı.'
                        : 'Müşteri bulunamadı.'}
                    </div>
                  ) : (
                    filteredCustomers.map((customer) => {
                    const customerName = customer.first_name || customer.user?.first_name || '';
                    const customerLastName = customer.last_name || customer.user?.last_name || '';
                    const customerPhone = customer.phone || customer.user?.phone || 'Telefon yok';
                    const totalDebt = calculateCustomerDebt(customer);
                    
                    return (
                      <button
                        key={customer.id}
                        onClick={() => handleCustomerSelect(customer)}
                        disabled={loading}
                        className="w-full text-left p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                      >
                        <div className="flex items-center space-x-3">
                          <UserIcon className="h-8 w-8 text-primary-500 flex-shrink-0" />
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center justify-between mb-1">
                              <p className="font-medium text-gray-900 dark:text-white truncate">
                                {customerName} {customerLastName}
                              </p>
                              {totalDebt > 0 && (
                                <span className="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300 flex-shrink-0">
                                  {formatTurkishCurrency(Number(totalDebt))} Borç
                                </span>
                              )}
                              {totalDebt === 0 && (
                                <span className="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300 flex-shrink-0">
                                  Borç Yok
                                </span>
                              )}
                            </div>
                            <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                              {customerPhone}
                            </p>
                          </div>
                        </div>
                      </button>
                    );
                  })
                  )}
                </div>
              </div>
            )}

            {/* Step 1.5: Customer Information with Debt/Receivables */}
            {step === 'customerInfo' && selectedCustomer && customerDebtInfo && (
              <div>
                <div className="mb-4 flex items-center space-x-2">
                  <button
                    onClick={() => {
                      setStep('customer');
                      setSelectedCustomer(null);
                      setCustomerDebtInfo(null);
                      setPayments([]);
                    }}
                    className="text-sm text-primary-600 dark:text-primary-400 hover:underline"
                  >
                    ← Müşteri Seç
                  </button>
                </div>

                {/* Customer Information */}
                <div className="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                    Müşteri Bilgileri
                  </h4>
                  <div className="space-y-2 text-sm">
                    <div className="flex items-center space-x-2">
                      <UserIcon className="h-5 w-5 text-gray-400" />
                      <span className="text-gray-700 dark:text-gray-300">
                        <strong>{selectedCustomer?.user?.first_name || selectedCustomer?.first_name || ''} {selectedCustomer?.user?.last_name || selectedCustomer?.last_name || ''}</strong>
                      </span>
                    </div>
                    <div className="text-gray-600 dark:text-gray-400">
                      <strong>E-posta:</strong> {selectedCustomer.user?.email || selectedCustomer.email}
                    </div>
                    {selectedCustomer.phone && (
                      <div className="text-gray-600 dark:text-gray-400">
                        <strong>Telefon:</strong> {selectedCustomer.phone}
                      </div>
                    )}
                    {selectedCustomer.identity_number && (
                      <div className="text-gray-600 dark:text-gray-400">
                        <strong>TC Kimlik No:</strong> {selectedCustomer.identity_number}
                      </div>
                    )}
                  </div>
                </div>

                {/* Debt/Receivables Information */}
                <div className={`mb-6 p-4 border-2 rounded-lg ${
                  (customerDebtInfo.totalDebt > 0 || payments.length > 0)
                    ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20' 
                    : 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20'
                }`}>
                  <div className="flex items-center justify-between mb-3">
                    <h4 className="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                      <BanknotesIcon className="h-5 w-5 mr-2" />
                      Borç/Alacak Durumu
                    </h4>
                  </div>
                  <div className="space-y-2">
                    {(customerDebtInfo.totalDebt > 0 || payments.length > 0) ? (
                      <>
                        <div className="text-lg font-bold text-red-700 dark:text-red-300">
                          Toplam Borç: {formatTurkishCurrency(
                            customerDebtInfo.totalDebt > 0 
                              ? Number(customerDebtInfo.totalDebt)
                              : payments.reduce((sum: number, p: any) => sum + Number(p.amount || 0), 0)
                          )}
                        </div>
                        <div className="text-sm text-gray-700 dark:text-gray-300">
                          Ödenmemiş Ödeme Sayısı: {payments.length || customerDebtInfo.unpaidPayments?.length || 0}
                        </div>
                        {customerDebtInfo.debtInfo?.firstDebtMonth && (
                          <div className="text-xs text-gray-600 dark:text-gray-400">
                            İlk Borç Ayı: {customerDebtInfo.debtInfo.firstDebtMonth.monthName}
                          </div>
                        )}
                      </>
                    ) : (
                      <div className="text-lg font-bold text-green-700 dark:text-green-300">
                        Borç Yok - Tüm ödemeler tamamlanmış
                      </div>
                    )}
                  </div>
                </div>

                {payments.length > 0 ? (
                  <button
                    onClick={handleContinueToPayment}
                    className="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                  >
                    Ödeme Seçimine Devam Et
                  </button>
                ) : (
                  <div className="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p className="text-sm text-yellow-800 dark:text-yellow-300">
                      Bu müşterinin ödenmemiş ödemesi bulunmamaktadır.
                    </p>
                  </div>
                )}
              </div>
            )}

            {/* Step 2: Payment Selection */}
            {step === 'payment' && (
              <div>
                <div className="mb-4 flex items-center space-x-2">
                  <button
                    onClick={() => {
                      setStep('customerInfo');
                    }}
                    className="text-sm text-primary-600 dark:text-primary-400 hover:underline"
                  >
                    ← Müşteri Bilgileri
                  </button>
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  <strong>{selectedCustomer?.user?.first_name || selectedCustomer?.first_name || ''} {selectedCustomer?.user?.last_name || selectedCustomer?.last_name || ''}</strong> için ödeme seçin
                </p>
                <div className="max-h-96 overflow-y-auto space-y-2">
                  {payments.length === 0 ? (
                    <p className="text-center text-gray-500 dark:text-gray-400 py-8">
                      Ödenmemiş ödeme bulunmamaktadır
                    </p>
                  ) : (
                    payments.map((payment) => (
                      <button
                        key={payment.id}
                        onClick={() => handlePaymentSelect(payment)}
                        disabled={loading}
                        className="w-full text-left p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                      >
                        <div className="flex items-center justify-between">
                          <div>
                            <p className="font-medium text-gray-900 dark:text-white">
                              {payment.payment_number}
                            </p>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                              Vade: {new Date(payment.due_date).toLocaleDateString('tr-TR')}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="font-semibold text-gray-900 dark:text-white">
                              {formatTurkishCurrency(Number(payment.amount))}
                            </p>
                            <p className={`text-xs ${
                              payment.status === 'overdue' 
                                ? 'text-red-600 dark:text-red-400' 
                                : 'text-yellow-600 dark:text-yellow-400'
                            }`}>
                              {payment.status === 'overdue' ? 'Gecikmiş' : 'Beklemede'}
                            </p>
                          </div>
                        </div>
                      </button>
                    ))
                  )}
                </div>
              </div>
            )}

            {/* Step 3: Payment Method Selection */}
            {step === 'paymentMethod' && selectedPayment && (
              <div>
                <div className="mb-4 flex items-center space-x-2">
                  <button
                    onClick={() => {
                      setStep('payment');
                      setSelectedPayment(null);
                      setSelectedPaymentMethod(null);
                    }}
                    className="text-sm text-primary-600 dark:text-primary-400 hover:underline"
                  >
                    ← Ödeme Seç
                  </button>
                </div>
                <div className="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <p className="text-sm text-blue-800 dark:text-blue-300">
                    <strong>Ödeme Tutarı:</strong> {formatTurkishCurrency(Number(selectedPayment?.amount))}
                  </p>
                  <p className="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    Ödeme No: {selectedPayment?.payment_number}
                  </p>
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  Ödeme yöntemini seçin
                </p>
                <div className="space-y-3">
                  <button
                    onClick={() => handlePaymentMethodSelect('cash')}
                    disabled={loading}
                    className="w-full p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary-500 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors disabled:opacity-50 flex items-center space-x-3"
                  >
                    <CurrencyDollarIcon className="h-8 w-8 text-green-600 dark:text-green-400" />
                    <div className="text-left flex-1">
                      <p className="font-semibold text-gray-900 dark:text-white">Nakit</p>
                      <p className="text-xs text-gray-500 dark:text-gray-400">Nakit ödeme al</p>
                    </div>
                  </button>
                  <button
                    onClick={() => handlePaymentMethodSelect('bank_transfer')}
                    disabled={loading}
                    className="w-full p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary-500 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors disabled:opacity-50 flex items-center space-x-3"
                  >
                    <BuildingLibraryIcon className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                    <div className="text-left flex-1">
                      <p className="font-semibold text-gray-900 dark:text-white">Havale</p>
                      <p className="text-xs text-gray-500 dark:text-gray-400">Banka havalesi ile ödeme al</p>
                    </div>
                  </button>
                  <button
                    onClick={() => handlePaymentMethodSelect('credit_card')}
                    disabled={loading}
                    className="w-full p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary-500 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors disabled:opacity-50 flex items-center space-x-3"
                  >
                    <CreditCardIcon className="h-8 w-8 text-purple-600 dark:text-purple-400" />
                    <div className="text-left flex-1">
                      <p className="font-semibold text-gray-900 dark:text-white">Kredi Kartı</p>
                      <p className="text-xs text-gray-500 dark:text-gray-400">PayTR ile güvenli ödeme</p>
                    </div>
                  </button>
                </div>
              </div>
            )}

            {/* Step 4: Bank Transfer Form */}
            {step === 'bankTransfer' && selectedPayment && (
              <div>
                <div className="mb-4 flex items-center space-x-2">
                  <button
                    onClick={() => {
                      setStep('paymentMethod');
                      setBankTransferData({
                        transactionId: '',
                        notes: '',
                      });
                    }}
                    className="text-sm text-primary-600 dark:text-primary-400 hover:underline"
                  >
                    ← Ödeme Yöntemi Seç
                  </button>
                </div>
                <div className="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <p className="text-sm text-blue-800 dark:text-blue-300">
                    <strong>Ödeme Tutarı:</strong> {formatTurkishCurrency(Number(selectedPayment?.amount))}
                  </p>
                </div>
                <form onSubmit={handleBankTransferSubmit} className="space-y-4">
                  <div>
                    <label htmlFor="bankAccount" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Banka Hesabı <span className="text-red-500">*</span>
                    </label>
                    {bankAccounts.length === 0 ? (
                      <div className="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                        <p className="text-sm text-yellow-800 dark:text-yellow-300">
                          Aktif banka hesabı bulunamadı. Lütfen ayarlar sayfasından banka hesabı ekleyin.
                        </p>
                      </div>
                    ) : (
                      <select
                        id="bankAccount"
                        required
                        value={selectedBankAccountId || ''}
                        onChange={(e) => setSelectedBankAccountId(e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                      >
                        <option value="">Banka hesabı seçin</option>
                        {bankAccounts.map((account) => (
                          <option key={account.id} value={account.id}>
                            {account.bank_name} - {account.account_number} ({account.account_holder_name})
                          </option>
                        ))}
                      </select>
                    )}
                  </div>
                  <div>
                    <label htmlFor="bankTransactionId" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      İşlem No (Opsiyonel)
                    </label>
                    <input
                      id="bankTransactionId"
                      type="text"
                      placeholder="Havale işlem numarası"
                      value={bankTransferData.transactionId}
                      onChange={(e) => setBankTransferData({ ...bankTransferData, transactionId: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div>
                    <label htmlFor="bankNotes" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Notlar (Opsiyonel)
                    </label>
                    <textarea
                      id="bankNotes"
                      rows={3}
                      placeholder="Havale ile ilgili notlar"
                      value={bankTransferData.notes}
                      onChange={(e) => setBankTransferData({ ...bankTransferData, notes: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div className="flex space-x-3">
                    <button
                      type="submit"
                      disabled={loading}
                      className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {loading ? 'Kaydediliyor...' : 'Ödemeyi Kaydet'}
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        setStep('paymentMethod');
                        setBankTransferData({
                          transactionId: '',
                          notes: '',
                        });
                      }}
                      className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                      İptal
                    </button>
                  </div>
                </form>
              </div>
            )}

            {/* Step 5: PayTR Card Payment */}
            {step === 'card' && paytrToken && (
              <div>
                <div className="mb-4 flex items-center space-x-2">
                  <button
                    onClick={() => {
                      setStep('paymentMethod');
                      setPaytrToken(null);
                    }}
                    className="text-sm text-primary-600 dark:text-primary-400 hover:underline"
                  >
                    ← Ödeme Yöntemi Seç
                  </button>
                </div>
                <div className="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <p className="text-sm text-blue-800 dark:text-blue-300">
                    <strong>Ödeme Tutarı:</strong> {formatTurkishCurrency(Number(selectedPayment?.amount))}
                  </p>
                </div>
                <div className="space-y-4">
                  <iframe
                    src={`https://www.paytr.com/odeme/guvenli/${paytrToken}`}
                    className="w-full h-96 border border-gray-300 dark:border-gray-600 rounded-lg"
                    title="PayTR Ödeme Formu"
                  />
                </div>
                <div className="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                  <p className="text-xs text-yellow-800 dark:text-yellow-300">
                    Ödeme tamamlandıktan sonra sayfa otomatik olarak yönlendirilecektir.
                  </p>
                </div>
              </div>
            )}


            {loading && (
              <div className="mt-4 flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
