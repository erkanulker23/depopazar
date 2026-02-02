import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { contractsApi, type ContractStatusFilter, type ContractPaymentFilter, type ContractDebtFilter } from '../../services/api/contractsApi';
import { paymentsApi } from '../../services/api/paymentsApi';
import { customersApi } from '../../services/api/customersApi';
import { DocumentTextIcon, PlusIcon, CreditCardIcon, TrashIcon, XMarkIcon, StopIcon } from '@heroicons/react/24/outline';
import { NewSaleModal } from '../../components/modals/NewSaleModal';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import toast from 'react-hot-toast';

export function ContractsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [contracts, setContracts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isNewSaleModalOpen, setIsNewSaleModalOpen] = useState(false);
  const [selectedContract, setSelectedContract] = useState<any>(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [selectedContracts, setSelectedContracts] = useState<Set<string>>(new Set());
  const [deleteTarget, setDeleteTarget] = useState<any>(null);
  const [terminateTarget, setTerminateTarget] = useState<any>(null);
  const [terminateDebtInfo, setTerminateDebtInfo] = useState<{ totalDebt: number; remainingDebt: number; contractNumber: string } | null>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [terminateLoading, setTerminateLoading] = useState(false);
  const [deleteError, setDeleteError] = useState('');
  const [terminateError, setTerminateError] = useState('');
  const [customersWithMultipleContracts, setCustomersWithMultipleContracts] = useState<any[]>([]);
  const [showMultipleContractsWarning, setShowMultipleContractsWarning] = useState(false);
  const [page, setPage] = useState(1);
  const [limit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(0);
  const [statusFilter, setStatusFilter] = useState<ContractStatusFilter>('all');
  const [paymentFilter, setPaymentFilter] = useState<ContractPaymentFilter>('all');
  const [debtFilter, setDebtFilter] = useState<ContractDebtFilter>('all');

  useEffect(() => {
    if (searchParams.get('newSale') === 'true') {
      setIsNewSaleModalOpen(true);
      setSearchParams({});
    }
  }, [searchParams]);

  useEffect(() => {
    fetchContracts();
  }, [page, statusFilter, paymentFilter, debtFilter]);

  useEffect(() => {
    checkMultipleContracts();
  }, []);

  const checkMultipleContracts = async () => {
    try {
      const data = await contractsApi.getCustomersWithMultipleContracts();
      setCustomersWithMultipleContracts(data);
      setShowMultipleContractsWarning(data.length > 0);
    } catch (error) {
      console.error('Error checking multiple contracts:', error);
    }
  };

  const fetchContracts = async () => {
    setLoading(true);
    try {
      const res = await contractsApi.getAll({
        page,
        limit,
        status: statusFilter,
        paymentStatus: paymentFilter,
        debtStatus: debtFilter,
      });
      setContracts(res.data);
      setTotal(res.total);
      setTotalPages(res.totalPages);
    } catch (error) {
      console.error('Error fetching contracts:', error);
    } finally {
      setLoading(false);
    }
  };

  const setFilter = (type: 'status' | 'payment' | 'debt', value: string) => {
    setPage(1);
    if (type === 'status') setStatusFilter(value as ContractStatusFilter);
    if (type === 'payment') setPaymentFilter(value as ContractPaymentFilter);
    if (type === 'debt') setDebtFilter(value as ContractDebtFilter);
  };

  return (
    <div>
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold gradient-text mb-2">Tüm Girişler</h1>
          <p className="text-gray-600 dark:text-gray-400">Sözleşme ve ödeme yönetimi</p>
        </div>
        <button
          onClick={() => setIsNewSaleModalOpen(true)}
          className="btn-primary w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 cursor-pointer relative z-10"
        >
          <PlusIcon className="h-5 w-5 mr-2" />
          Yeni Satış Gir
        </button>
      </div>

      {/* Filtreler */}
      <div className="modern-card mb-6 p-4">
        <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Filtrele</h3>
        <div className="flex flex-wrap gap-4 items-end">
          <div>
            <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Durum</label>
            <select
              value={statusFilter}
              onChange={(e) => setFilter('status', e.target.value)}
              className="w-full min-w-[160px] px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="all">Tümü</option>
              <option value="active">Aktif</option>
              <option value="terminated">Sonlandırılanlar</option>
            </select>
          </div>
          <div>
            <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Ödeme</label>
            <select
              value={paymentFilter}
              onChange={(e) => setFilter('payment', e.target.value)}
              className="w-full min-w-[160px] px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="all">Tümü</option>
              <option value="has_payment">Ödeme yapanlar</option>
              <option value="no_payment">Ödeme yapmayanlar</option>
            </select>
          </div>
          <div>
            <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Borç</label>
            <select
              value={debtFilter}
              onChange={(e) => setFilter('debt', e.target.value)}
              className="w-full min-w-[160px] px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="all">Tümü</option>
              <option value="has_debt">Borcu olanlar</option>
              <option value="no_debt">Borcu olmayanlar</option>
            </select>
          </div>
          {(statusFilter !== 'all' || paymentFilter !== 'all' || debtFilter !== 'all') && (
            <button
              type="button"
              onClick={() => {
                setPage(1);
                setStatusFilter('all');
                setPaymentFilter('all');
                setDebtFilter('all');
              }}
              className="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              Filtreleri temizle
            </button>
          )}
        </div>
      </div>

      {/* Birden fazla aktif sözleşmesi olan müşteriler uyarısı */}
      {showMultipleContractsWarning && customersWithMultipleContracts.length > 0 && (
        <div className="mb-6 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-4">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <h3 className="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                ⚠️ Uyarı: Birden Fazla Aktif Sözleşmesi Olan Müşteriler
              </h3>
              <p className="text-sm text-yellow-700 dark:text-yellow-300 mb-3">
                Aşağıdaki {customersWithMultipleContracts.length} müşterinin birden fazla aktif sözleşmesi bulunmaktadır. 
                Her müşterinin sadece 1 aktif sözleşmesi olmalıdır. Lütfen gereksiz sözleşmeleri sonlandırın.
              </p>
              <div className="space-y-2">
                {customersWithMultipleContracts.map((customer) => (
                  <div key={customer.customer_id} className="bg-white dark:bg-gray-800 rounded p-3 border border-yellow-200 dark:border-yellow-700">
                    <div className="font-medium text-gray-900 dark:text-white">
                      {customer.customer_name} ({customer.customer_email})
                    </div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {customer.active_contracts_count} aktif sözleşme:
                    </div>
                    <div className="mt-2 space-y-1">
                      {customer.contracts.map((contract: any) => (
                        <div key={contract.id} className="text-xs text-gray-500 dark:text-gray-400 pl-2 border-l-2 border-yellow-300 dark:border-yellow-600">
                          • {contract.contract_number} - {contract.room?.room_number || 'Oda bilgisi yok'} 
                          ({new Date(contract.start_date).toLocaleDateString('tr-TR')} - {new Date(contract.end_date).toLocaleDateString('tr-TR')})
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </div>
            <button
              onClick={() => {
                setShowMultipleContractsWarning(false);
                checkMultipleContracts();
              }}
              className="ml-4 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-200"
            >
              <XMarkIcon className="h-5 w-5" />
            </button>
          </div>
        </div>
      )}

      <NewSaleModal
        isOpen={isNewSaleModalOpen}
        onClose={() => {
          setIsNewSaleModalOpen(false);
          // URL'den parametreyi temizle
          if (searchParams.get('newSale') === 'true') {
            setSearchParams({});
          }
        }}
        onSuccess={() => {
          fetchContracts();
          checkMultipleContracts();
          setIsNewSaleModalOpen(false);
          setSearchParams({});
        }}
      />

      {/* Delete Modal */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => {
                if (!deleteLoading) {
                  setDeleteTarget(null);
                  setDeleteError('');
                }
              }}
            />
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    Satışı Sil
                  </h3>
                  <button
                    onClick={() => {
                      if (!deleteLoading) {
                        setDeleteTarget(null);
                        setDeleteError('');
                      }
                    }}
                    className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  >
                    <XMarkIcon className="h-6 w-6" />
                  </button>
                </div>
                {deleteError && (
                  <div className="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                    <p className="text-sm text-red-800 dark:text-red-200">{deleteError}</p>
                  </div>
                )}
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                  {deleteTarget === 'bulk' ? (
                    <>
                      <strong>{selectedContracts.size} satış</strong> silmek istediğinize emin
                      misiniz? Bu işlem geri alınamaz.
                    </>
                  ) : (
                    <>
                      <strong className="text-gray-900 dark:text-white">
                        {deleteTarget.contract_number}
                      </strong>{' '}
                      satışını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
                    </>
                  )}
                </p>
                <div className="flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={() => {
                      if (!deleteLoading) {
                        setDeleteTarget(null);
                        setDeleteError('');
                      }
                    }}
                    disabled={deleteLoading}
                    className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                  >
                    İptal
                  </button>
                  <button
                    type="button"
                    onClick={async () => {
                      setDeleteError('');
                      setDeleteLoading(true);
                      try {
                        if (deleteTarget === 'bulk') {
                          const result = await contractsApi.bulkDelete(Array.from(selectedContracts));
                          // Bulk işlem sonucunu kontrol et
                          if (result?.failed > 0) {
                            const errorDetails = result.details?.join('\n') || '';
                            setDeleteError(
                              `${result.message}${errorDetails ? '\n\nDetaylar:\n' + errorDetails : ''}`,
                            );
                            // Başarılı olanları listeden çıkar
                            if (result.success > 0) {
                              fetchContracts();
                              checkMultipleContracts();
                            }
                          } else {
                            setSelectedContracts(new Set());
                            setDeleteTarget(null);
                            fetchContracts();
                            checkMultipleContracts();
                          }
                        } else {
                          await contractsApi.delete(deleteTarget.id);
                          setDeleteTarget(null);
                          fetchContracts();
                          checkMultipleContracts();
                        }
                      } catch (err: any) {
                        const errorMessage =
                          err.response?.data?.message ||
                          err.message ||
                          'Satış silinirken bir hata oluştu';
                        setDeleteError(errorMessage);
                      } finally {
                        setDeleteLoading(false);
                      }
                    }}
                    disabled={deleteLoading}
                    className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                  >
                    {deleteLoading ? 'Siliniyor...' : 'Sil'}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Terminate Modal */}
      {terminateTarget && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => {
                if (!terminateLoading) {
                  setTerminateTarget(null);
                  setTerminateError('');
                }
              }}
            />
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    Depolamayı Sonlandır
                  </h3>
                  <button
                    onClick={() => {
                      if (!terminateLoading) {
                        setTerminateTarget(null);
                        setTerminateError('');
                        setTerminateDebtInfo(null);
                      }
                    }}
                    className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  >
                    <XMarkIcon className="h-6 w-6" />
                  </button>
                </div>
                {terminateError && (
                  <div className="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                    <p className="text-sm text-red-800 dark:text-red-200">{terminateError}</p>
                  </div>
                )}
                {terminateDebtInfo && terminateDebtInfo.remainingDebt > 0 && (
                  <div className="mb-4 rounded-md bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-4">
                    <div className="flex">
                      <div className="flex-shrink-0">
                        <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                          <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                        </svg>
                      </div>
                      <div className="ml-3">
                        <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                          Uyarı: Depo Borcu Mevcut
                        </h3>
                        <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                          {terminateTarget === 'bulk' ? (
                            <p>
                              Seçilen sözleşmelerden bazılarında borç bulunmaktadır. Borçlu sözleşmeler:{' '}
                              <strong>{terminateDebtInfo.contractNumber}</strong>
                            </p>
                          ) : (
                            <p>
                              <strong>{terminateDebtInfo.contractNumber}</strong> sözleşmesinde{' '}
                              <strong>{formatTurkishCurrency(terminateDebtInfo.remainingDebt)}</strong> borç
                              bulunmaktadır. Depolamayı sonlandırmadan önce borcun ödenmesi önerilir.
                            </p>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                )}
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                  {terminateTarget === 'bulk' ? (
                    <>
                      <strong>{selectedContracts.size} satış</strong> için depolamayı sonlandırmak
                      istediğinize emin misiniz? Sözleşmeler pasif hale gelecek ve odalar boşaltılacak.
                    </>
                  ) : (
                    <>
                      <strong className="text-gray-900 dark:text-white">
                        {terminateTarget.contract_number}
                      </strong>{' '}
                      sözleşmesi için depolamayı sonlandırmak istediğinize emin misiniz? Sözleşme
                      pasif hale gelecek ve oda boşaltılacak.
                    </>
                  )}
                </p>
                <div className="flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={() => {
                      if (!terminateLoading) {
                        setTerminateTarget(null);
                        setTerminateError('');
                      }
                    }}
                    disabled={terminateLoading}
                    className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                  >
                    İptal
                  </button>
                  <button
                    type="button"
                    onClick={async () => {
                      setTerminateError('');
                      setTerminateLoading(true);
                      try {
                        if (terminateTarget === 'bulk') {
                          const result = await contractsApi.bulkTerminate(Array.from(selectedContracts));
                          // Bulk işlem sonucunu kontrol et
                          if (result?.failed > 0) {
                            const errorDetails = result.details?.join('\n') || '';
                            setTerminateError(
                              `${result.message}${errorDetails ? '\n\nDetaylar:\n' + errorDetails : ''}`,
                            );
                            // Başarılı olanları listeden çıkar
                            if (result.success > 0) {
                              fetchContracts();
                            }
                          } else {
                            setSelectedContracts(new Set());
                            setTerminateTarget(null);
                            setTerminateDebtInfo(null);
                            fetchContracts();
                            checkMultipleContracts();
                          }
                        } else {
                          await contractsApi.terminate(terminateTarget.id);
                          setTerminateTarget(null);
                          setTerminateDebtInfo(null);
                          fetchContracts();
                          checkMultipleContracts();
                        }
                      } catch (err: any) {
                        const errorMessage =
                          err.response?.data?.message ||
                          err.message ||
                          'Depolama sonlandırılırken bir hata oluştu';
                        setTerminateError(errorMessage);
                      } finally {
                        setTerminateLoading(false);
                      }
                    }}
                    disabled={terminateLoading}
                    className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 disabled:opacity-50"
                  >
                    {terminateLoading ? 'Sonlandırılıyor...' : 'Sonlandır'}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Toplu İşlemler Toolbar */}
      {selectedContracts.size > 0 && (
        <div className="mb-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg flex items-center justify-between">
          <span className="text-sm font-medium text-primary-900 dark:text-primary-200">
            {selectedContracts.size} satış seçildi
          </span>
          <div className="flex gap-2">
            <button
              onClick={() => setSelectedContracts(new Set())}
              className="px-3 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
            >
              Seçimi Temizle
            </button>
            <button
              onClick={async () => {
                setTerminateError('');
                // Toplu işlem için borç kontrolü yap
                const selectedContractsData = contracts.filter((c) => selectedContracts.has(c.id));
                let hasDebt = false;
                const debtContracts: string[] = [];
                
                for (const contract of selectedContractsData) {
                  const monthlyTotal = contract.monthly_prices?.reduce((sum: number, mp: any) => sum + Number(mp.price), 0) || 0;
                  const transport = Number(contract.transportation_fee || 0);
                  const discount = Number(contract.discount || 0);
                  const paid = contract.payments?.filter((p: any) => p.status === 'paid').reduce((sum: number, p: any) => sum + Number(p.amount), 0) || 0;
                  const total = monthlyTotal + transport - discount;
                  const remaining = total - paid;
                  
                  if (remaining > 0) {
                    hasDebt = true;
                    debtContracts.push(contract.contract_number);
                  }
                }
                
                if (hasDebt) {
                  setTerminateDebtInfo({
                    totalDebt: 0,
                    remainingDebt: 0,
                    contractNumber: debtContracts.join(', '),
                  });
                } else {
                  setTerminateDebtInfo(null);
                }
                setTerminateTarget('bulk');
              }}
              className="px-3 py-1 text-sm text-white bg-orange-600 hover:bg-orange-700 rounded inline-flex items-center"
            >
              <StopIcon className="h-4 w-4 mr-1" />
              Depolamayı Sonlandır
            </button>
            <button
              onClick={() => {
                setDeleteError('');
                setDeleteTarget('bulk');
              }}
              className="px-3 py-1 text-sm text-white bg-red-600 hover:bg-red-700 rounded inline-flex items-center"
            >
              <TrashIcon className="h-4 w-4 mr-1" />
              Seçilenleri Sil
            </button>
          </div>
        </div>
      )}

      {loading ? (
        <div className="modern-card p-8 text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto mb-4"></div>
          <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
        </div>
      ) : contracts.length === 0 ? (
        <div className="modern-card p-8 text-center">
          <DocumentTextIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600 dark:text-gray-400">Henüz sözleşme bulunmamaktadır.</p>
        </div>
      ) : (
        <>
          {/* Mobile View - Cards */}
          <div className="grid grid-cols-1 gap-4 md:hidden">
            {contracts.map((contract) => {
              const monthlyTotal = contract.monthly_prices?.reduce((sum: number, mp: any) => sum + Number(mp.price), 0) || 0;
              const transport = Number(contract.transportation_fee || 0);
              const discount = Number(contract.discount || 0);
              const paid = contract.payments?.filter((p: any) => p.status === 'paid').reduce((sum: number, p: any) => sum + Number(p.amount), 0) || 0;
              const total = monthlyTotal + transport - discount;
              const remaining = Math.max(0, total - paid);

              return (
                <div key={contract.id} className="modern-card p-4 space-y-4">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center space-x-3 overflow-hidden">
                      <div className="w-10 h-10 bg-primary-50 dark:bg-primary-900/20 rounded-full flex items-center justify-center flex-shrink-0">
                        <DocumentTextIcon className="h-6 w-6 text-primary-500" />
                      </div>
                      <div className="min-w-0">
                        <h3 className="text-sm font-bold text-gray-900 dark:text-white truncate">
                          {contract.contract_number}
                        </h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                          {contract.customer?.first_name} {contract.customer?.last_name}
                        </p>
                      </div>
                    </div>
                    {contract.is_active ? (
                      <span className="px-2 py-1 text-[10px] font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 flex-shrink-0">
                        AKTİF
                      </span>
                    ) : (
                      <span className="px-2 py-1 text-[10px] font-bold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 flex-shrink-0">
                        SONLANDI
                      </span>
                    )}
                  </div>

                  <div className="grid grid-cols-2 gap-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    <div>
                      <p className="text-[10px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500 mb-1">Oda</p>
                      <p className="text-sm text-gray-700 dark:text-gray-300 font-medium">{contract.room?.room_number || '-'}</p>
                    </div>
                    <div>
                      <p className="text-[10px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500 mb-1">Başlangıç</p>
                      <p className="text-sm text-gray-700 dark:text-gray-300 font-medium">{new Date(contract.start_date).toLocaleDateString('tr-TR')}</p>
                    </div>
                  </div>

                  <div className="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                      <p className="text-[10px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500 mb-1">Kalan Borç</p>
                      <p className={`text-base font-bold ${remaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'}`}>
                        {formatTurkishCurrency(remaining)}
                      </p>
                    </div>
                    <div className="flex gap-2">
                      <button
                        onClick={() => {
                          setSelectedContract(contract);
                          setShowPaymentModal(true);
                        }}
                        className="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                        title="Ödeme Al"
                      >
                        <CreditCardIcon className="h-5 w-5" />
                      </button>
                      <button
                        onClick={async (e) => {
                          e.stopPropagation();
                          setTerminateError('');
                          try {
                            const { totalDebt, remainingDebt } = await contractsApi.getTotalDebt(contract.id);
                            setTerminateDebtInfo({
                              totalDebt,
                              remainingDebt,
                              contractNumber: contract.contract_number,
                            });
                            setTerminateTarget(contract);
                          } catch {
                            setTerminateDebtInfo(null);
                            setTerminateTarget(contract);
                          }
                        }}
                        className="p-2 text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors"
                        title="Sonlandır"
                      >
                        <StopIcon className="h-5 w-5" />
                      </button>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          setDeleteError('');
                          setDeleteTarget(contract);
                        }}
                        className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        title="Sil"
                      >
                        <TrashIcon className="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="hidden md:block modern-card overflow-hidden">
            <div className="overflow-x-auto">
              <table className="table-modern">
                <thead>
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                      <input
                        type="checkbox"
                        checked={selectedContracts.size === contracts.length && contracts.length > 0}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedContracts(new Set(contracts.map((c) => c.id)));
                          } else {
                            setSelectedContracts(new Set());
                          }
                        }}
                        className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        onClick={(e) => e.stopPropagation()}
                      />
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Sözleşme No
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Müşteri
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Oda
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Başlangıç
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Bitiş
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Durum
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Aylık Fiyat
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Nakliye
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Toplam Borç
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Personel
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      İşlemler
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {contracts.map((contract) => (
                    <tr key={contract.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                      <td className="px-6 py-4 whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                        <input
                          type="checkbox"
                          checked={selectedContracts.has(contract.id)}
                          onChange={(e) => {
                            const newSelected = new Set(selectedContracts);
                            if (e.target.checked) {
                              newSelected.add(contract.id);
                            } else {
                              newSelected.delete(contract.id);
                            }
                            setSelectedContracts(newSelected);
                          }}
                          className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                          onClick={(e) => e.stopPropagation()}
                        />
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <DocumentTextIcon className="h-5 w-5 text-primary-500 mr-2" />
                          <button
                            onClick={() => window.location.href = `/contracts/${contract.id}`}
                            className="text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                          >
                            {contract.contract_number}
                          </button>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {contract.customer?.first_name} {contract.customer?.last_name}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {contract.room?.room_number || '-'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {new Date(contract.start_date).toLocaleDateString('tr-TR')}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {new Date(contract.end_date).toLocaleDateString('tr-TR')}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {contract.is_active ? (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            Aktif
                          </span>
                        ) : (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            Sonlandırıldı
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {formatTurkishCurrency(Number(contract.monthly_price))}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {formatTurkishCurrency(Number(contract.transportation_fee || 0))}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {(() => {
                          const monthlyTotal = contract.monthly_prices?.reduce((sum: number, mp: any) => sum + Number(mp.price), 0) || 0;
                          const transport = Number(contract.transportation_fee || 0);
                          const discount = Number(contract.discount || 0);
                          const paid = contract.payments?.filter((p: any) => p.status === 'paid').reduce((sum: number, p: any) => sum + Number(p.amount), 0) || 0;
                          const total = monthlyTotal + transport - discount;
                          const remaining = Math.max(0, total - paid);
                          return (
                            <div>
                              <div className={`font-semibold ${remaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'}`}>
                                {formatTurkishCurrency(remaining)}
                              </div>
                              <div className="text-xs text-gray-500">Toplam: {formatTurkishCurrency(total)} | Ödenen: {formatTurkishCurrency(paid)}</div>
                            </div>
                          );
                        })()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {contract.contract_staff && contract.contract_staff.length > 0 ? (
                          <div className="flex flex-wrap gap-1">
                            {contract.contract_staff.map((cs: any) => (
                              <span
                                key={cs.id}
                                className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                              >
                                {cs.user?.first_name} {cs.user?.last_name}
                              </span>
                            ))}
                          </div>
                        ) : contract.sold_by_user ? (
                          <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                            {contract.sold_by_user.first_name} {contract.sold_by_user.last_name}
                          </span>
                        ) : (
                          '-'
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        <div className="flex gap-2">
                          <button
                            onClick={() => {
                              setSelectedContract(contract);
                              setShowPaymentModal(true);
                            }}
                            className="btn-success inline-flex items-center px-3 py-1.5 text-xs cursor-pointer relative z-10"
                          >
                            <CreditCardIcon className="h-4 w-4 mr-1" />
                            Ödeme Al
                          </button>
                          <button
                            onClick={async (e) => {
                              e.stopPropagation();
                              setTerminateError('');
                              // Borç bilgisini hesapla
                              try {
                                const { totalDebt, remainingDebt } = await contractsApi.getTotalDebt(contract.id);
                                setTerminateDebtInfo({
                                  totalDebt,
                                  remainingDebt,
                                  contractNumber: contract.contract_number,
                                });
                                setTerminateTarget(contract);
                              } catch {
                                setTerminateDebtInfo(null);
                                setTerminateTarget(contract);
                              }
                            }}
                            className="px-3 py-1.5 text-xs text-white bg-orange-600 hover:bg-orange-700 rounded inline-flex items-center"
                            title="Depolamayı Sonlandır"
                          >
                            <StopIcon className="h-4 w-4 mr-1" />
                            Sonlandır
                          </button>
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              setDeleteError('');
                              setDeleteTarget(contract);
                            }}
                            className="px-3 py-1.5 text-xs text-white bg-red-600 hover:bg-red-700 rounded inline-flex items-center"
                            title="Sil"
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </>
      )}
      {totalPages > 1 && (
        <div className="flex items-center justify-between px-6 py-3 mt-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {(page - 1) * limit + 1}-{Math.min(page * limit, total)} / {total}
          </p>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page <= 1}
              className="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
            >
              Önceki
            </button>
            <button
              type="button"
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              disabled={page >= totalPages}
              className="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
            >
              Sonraki
            </button>
          </div>
        </div>
      )}

      {/* Ödeme Alma Modal */}
      {showPaymentModal && selectedContract && (
        <PaymentModal
          contract={selectedContract}
          isOpen={showPaymentModal}
          onClose={() => {
            setShowPaymentModal(false);
            setSelectedContract(null);
          }}
          onSuccess={() => {
            fetchContracts();
            setShowPaymentModal(false);
            setSelectedContract(null);
          }}
        />
      )}
    </div>
  );
}

// Ödeme Alma Modal Component
function PaymentModal({ contract, isOpen, onClose, onSuccess }: any) {
  const [paymentType, setPaymentType] = useState<'monthly' | 'intermediate' | 'total'>('monthly');
  const [selectedMonth, setSelectedMonth] = useState<string>('');
  const [customerTotalDebt, setCustomerTotalDebt] = useState<number | null>(null);
  const [loadingDebt, setLoadingDebt] = useState(false);
  const [formData, setFormData] = useState({
    amount: '',
    due_date: '',
    paid_at: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    notes: '',
  });
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (paymentType === 'monthly' && contract?.monthly_prices?.length > 0) {
      const unpaidMonth = contract.monthly_prices.find((mp: any) => {
        const monthPayment = contract.payments?.find((p: any) => {
          const paymentMonth = new Date(p.due_date).toISOString().slice(0, 7);
          return paymentMonth === mp.month && p.status === 'paid';
        });
        return !monthPayment;
      });

      if (unpaidMonth) {
        setSelectedMonth(unpaidMonth.month);
        setFormData((prev) => ({
          ...prev,
          amount: unpaidMonth.price.toString(),
          due_date: new Date(unpaidMonth.month + '-01').toISOString().split('T')[0],
        }));
      }
    } else if (paymentType === 'total') {
      // Toplam borç hesapla
      const monthlyTotal = contract.monthly_prices?.reduce((sum: number, mp: any) => sum + Number(mp.price), 0) || 0;
      const transport = Number(contract.transportation_fee || 0);
      const discount = Number(contract.discount || 0);
      const paid = contract.payments?.filter((p: any) => p.status === 'paid').reduce((sum: number, p: any) => sum + Number(p.amount), 0) || 0;
      const total = monthlyTotal + transport - discount;
      const remaining = total - paid;

      setFormData((prev) => ({
        ...prev,
        amount: remaining > 0 ? remaining.toString() : '0',
      }));

      if (contract?.customer_id) {
        setLoadingDebt(true);
        customersApi.getDebtInfo(contract.customer_id)
          .then((data) => {
            setCustomerTotalDebt(data.totalDebt || 0);
          })
          .catch(() => {
            setCustomerTotalDebt(null);
          })
          .finally(() => {
            setLoadingDebt(false);
          });
      }
    } else {
      setCustomerTotalDebt(null);
    }
  }, [paymentType, contract]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      if (paymentType === 'monthly' && selectedMonth) {
        const existing = (contract.payments || []).find((p: any) => {
          const pm = new Date(p.due_date).toISOString().slice(0, 7);
          return pm === selectedMonth && (p.status === 'pending' || p.status === 'overdue');
        });
        if (!existing) {
          toast.error('Bu ay için kayıtlı ödeme bulunamadı. Lütfen Ara Ödeme veya Toplam Ödeme kullanın.');
          setLoading(false);
          return;
        }
        const paidAt = formData.paid_at ? `${formData.paid_at}T12:00:00.000Z` : new Date().toISOString();
        await paymentsApi.update(existing.id, {
          status: 'paid',
          paid_at: paidAt,
          payment_method: formData.payment_method,
          notes: formData.notes || null,
          days_overdue: 0,
        });
      } else {
        const paymentNumber = `PAY-${new Date().getFullYear()}-${String(Date.now()).slice(-6)}`;
        await paymentsApi.create({
          payment_number: paymentNumber,
          contract_id: contract.id,
          amount: Number(formData.amount),
          status: 'paid',
          due_date: formData.due_date || new Date().toISOString(),
          paid_at: formData.paid_at ? `${formData.paid_at}T12:00:00.000Z` : new Date().toISOString(),
          payment_method: formData.payment_method,
          notes: formData.notes || null,
        });
      }

      onSuccess();
    } catch (error: any) {
      const msg = error?.response?.data?.message || error?.message || 'Ödeme kaydedilirken bir hata oluştu';
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={onClose} />
        <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Ödeme Al - {contract.contract_number}
            </h3>
            
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Ödeme Türü
                </label>
                <div className="grid grid-cols-3 gap-2">
                  <button
                    type="button"
                    onClick={() => setPaymentType('monthly')}
                    className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                      paymentType === 'monthly'
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    }`}
                  >
                    Ay Ödemesi
                  </button>
                  <button
                    type="button"
                    onClick={() => setPaymentType('intermediate')}
                    className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                      paymentType === 'intermediate'
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    }`}
                  >
                    Ara Ödeme
                  </button>
                  <button
                    type="button"
                    onClick={() => setPaymentType('total')}
                    className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                      paymentType === 'total'
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    }`}
                  >
                    Toplam Ödeme
                  </button>
                </div>
              </div>

              {paymentType === 'monthly' && contract?.monthly_prices && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Ay Seçin
                  </label>
                  <select
                    value={selectedMonth}
                    onChange={(e) => {
                      const selected = contract.monthly_prices.find((mp: any) => mp.month === e.target.value);
                      setSelectedMonth(e.target.value);
                      setFormData({
                        ...formData,
                        amount: selected ? selected.price.toString() : '',
                        due_date: selected ? new Date(selected.month + '-01').toISOString().split('T')[0] : '',
                      });
                    }}
                    required
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                  >
                    <option value="">Ay Seçin</option>
                    {contract.monthly_prices.map((mp: any) => {
                      const monthPayment = contract.payments?.find((p: any) => {
                        const paymentMonth = new Date(p.due_date).toISOString().slice(0, 7);
                        return paymentMonth === mp.month && p.status === 'paid';
                      });
                      if (monthPayment) return null;
                      return (
                        <option key={mp.month} value={mp.month}>
                          {new Date(mp.month + '-01').toLocaleDateString('tr-TR', { year: 'numeric', month: 'long' })} - {formatTurkishCurrency(Number(mp.price))}
                        </option>
                      );
                    })}
                  </select>
                </div>
              )}

              {paymentType === 'total' && (
                <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                  <div className="flex items-start">
                    <div className="flex-shrink-0">
                      <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                      </svg>
                    </div>
                    <div className="ml-3 flex-1">
                      <h3 className="text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">
                        Müşteri Toplam Borcu
                      </h3>
                      {loadingDebt ? (
                        <p className="text-sm text-blue-700 dark:text-blue-300">Yükleniyor...</p>
                      ) : customerTotalDebt !== null ? (
                        <p className="text-lg font-semibold text-blue-900 dark:text-blue-100">
                          {formatTurkishCurrency(customerTotalDebt)}
                        </p>
                      ) : (
                        <p className="text-sm text-blue-700 dark:text-blue-300">Borç bilgisi alınamadı</p>
                      )}
                    </div>
                  </div>
                </div>
              )}

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Tutar (TL) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  value={formData.amount}
                  onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                />
              </div>

              {paymentType !== 'total' && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Vade Tarihi
                  </label>
                  <input
                    type="date"
                    value={formData.due_date}
                    onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                  />
                </div>
              )}

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Ödeme Tarihi *
                </label>
                <input
                  type="date"
                  required
                  value={formData.paid_at}
                  onChange={(e) => setFormData({ ...formData, paid_at: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Ödeme Yöntemi
                </label>
                <select
                  value={formData.payment_method}
                  onChange={(e) => setFormData({ ...formData, payment_method: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="cash">Nakit</option>
                  <option value="credit_card">Kredi Kartı</option>
                  <option value="bank_transfer">Havale/EFT</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Notlar
                </label>
                <textarea
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  rows={3}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div className="flex justify-end space-x-3 pt-4">
                <button
                  type="button"
                  onClick={onClose}
                  disabled={loading}
                  className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                >
                  {loading ? 'Kaydediliyor...' : 'Ödemeyi Kaydet'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
