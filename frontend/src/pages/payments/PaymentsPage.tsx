import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { paymentsApi } from '../../services/api/paymentsApi';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import { CreditCardIcon, UsersIcon, MagnifyingGlassIcon, FunnelIcon, BanknotesIcon, TrashIcon } from '@heroicons/react/24/outline';
import { CollectPaymentModal } from '../../components/modals/CollectPaymentModal';

export function PaymentsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [payments, setPayments] = useState<any[]>([]);
  const [filteredPayments, setFilteredPayments] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
  const [selectedPayments, setSelectedPayments] = useState<Set<string>>(new Set());
  const [deleteTarget, setDeleteTarget] = useState<any>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [deleteError, setDeleteError] = useState('');

  useEffect(() => {
    fetchPayments();

    // Check if modal should be opened from URL
    if (searchParams.get('collect') === 'true') {
      setIsPaymentModalOpen(true);
      // Clean URL
      const newParams = new URLSearchParams(searchParams);
      newParams.delete('collect');
      setSearchParams(newParams, { replace: true });
    }

    // Check if status filter should be set from URL on initial load
    const statusParam = searchParams.get('status');
    if (statusParam) {
      setStatusFilter(statusParam);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    let filtered = payments;

    // Search filter
    if (searchTerm.trim() !== '') {
      const searchLower = searchTerm.toLowerCase();
      filtered = filtered.filter(
        (payment) =>
          payment.payment_number?.toLowerCase().includes(searchLower) ||
          payment.contract?.contract_number?.toLowerCase().includes(searchLower) ||
          payment.contract?.customer?.first_name?.toLowerCase().includes(searchLower) ||
          payment.contract?.customer?.last_name?.toLowerCase().includes(searchLower) ||
          payment.contract?.customer?.email?.toLowerCase().includes(searchLower) ||
          payment.amount?.toString().includes(searchTerm),
      );
    }

    // Status filter
    if (statusFilter !== 'all') {
      if (statusFilter === 'unpaid') {
        // For "unpaid", show both pending and overdue payments
        filtered = filtered.filter((payment) => 
          payment.status === 'pending' || payment.status === 'overdue'
        );
      } else {
        filtered = filtered.filter((payment) => payment.status === statusFilter);
      }
    }

    setFilteredPayments(filtered);
  }, [searchTerm, statusFilter, payments]);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'paid':
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
      case 'overdue':
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    const statusMap: Record<string, string> = {
      paid: 'Ödendi',
      pending: 'Beklemede',
      overdue: 'Gecikmiş',
      cancelled: 'İptal',
    };
    return statusMap[status] || status;
  };

  const fetchPayments = async () => {
    try {
      setLoading(true);
      const data = await paymentsApi.getAll();
      if (data && Array.isArray(data)) {
        setPayments(data);
        setFilteredPayments(data);
      } else {
        setPayments([]);
        setFilteredPayments([]);
      }
    } catch (error: any) {
      console.error('Error fetching payments:', error);
      setPayments([]);
      setFilteredPayments([]);
    } finally {
      setLoading(false);
    }
  };

  const handlePaymentSuccess = () => {
    fetchPayments();
  };

  const handleDelete = async () => {
    setDeleteError('');
    setDeleteLoading(true);
    try {
      if (deleteTarget === 'bulk') {
        await paymentsApi.deleteMany(Array.from(selectedPayments));
        setSelectedPayments(new Set());
      } else {
        await paymentsApi.delete(deleteTarget.id);
      }
      setDeleteTarget(null);
      fetchPayments();
    } catch (err: any) {
      setDeleteError(
        err.response?.data?.message || 'Ödeme silinirken bir hata oluştu',
      );
    } finally {
      setDeleteLoading(false);
    }
  };

  return (
    <div>
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold gradient-text mb-2">Ödemeler</h1>
          <p className="text-gray-600 dark:text-gray-400">Tüm ödeme kayıtları ve durumları</p>
        </div>
        <button
          type="button"
          onClick={() => setIsPaymentModalOpen(true)}
          className="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center space-x-2 shadow-lg hover:shadow-xl font-medium text-base"
        >
          <BanknotesIcon className="h-6 w-6" />
          <span>Ödeme Al</span>
        </button>
      </div>

      <CollectPaymentModal
        isOpen={isPaymentModalOpen}
        onClose={() => setIsPaymentModalOpen(false)}
        onSuccess={handlePaymentSuccess}
      />

      {/* Silme Onay Modalı */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <button
              type="button"
              className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 cursor-default"
              onClick={() => {
                if (!deleteLoading) {
                  setDeleteTarget(null);
                  setDeleteError('');
                }
              }}
              onKeyDown={(e) => {
                if (e.key === 'Escape' && !deleteLoading) {
                  setDeleteTarget(null);
                  setDeleteError('');
                }
              }}
              aria-label="Modal'ı kapat"
              disabled={deleteLoading}
            ></button>
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                    <TrashIcon className="h-6 w-6 text-red-600 dark:text-red-400" />
                  </div>
                  <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                      Ödeme Sil
                    </h3>
                    <div className="mt-2">
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        {deleteTarget === 'bulk' ? (
                          <>
                            Seçili <strong>{selectedPayments.size}</strong> ödemeyi silmek
                            istediğinize emin misiniz? Bu işlem geri alınamaz.
                          </>
                        ) : (
                          <>
                            <strong>{deleteTarget.payment_number}</strong> numaralı ödemeyi
                            silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
                          </>
                        )}
                      </p>
                    </div>
                    {deleteError && (
                      <div className="mt-3 rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                        <p className="text-sm text-red-800 dark:text-red-200">{deleteError}</p>
                      </div>
                    )}
                  </div>
                </div>
              </div>
              <div className="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  onClick={handleDelete}
                  disabled={deleteLoading}
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                >
                  {deleteLoading ? 'Siliniyor...' : 'Sil'}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    if (!deleteLoading) {
                      setDeleteTarget(null);
                      setDeleteError('');
                    }
                  }}
                  disabled={deleteLoading}
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                >
                  İptal
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Toplu İşlemler Toolbar */}
      {selectedPayments.size > 0 && (
        <div className="mb-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg flex items-center justify-between">
          <span className="text-sm font-medium text-primary-900 dark:text-primary-200">
            {selectedPayments.size} ödeme seçildi
          </span>
          <div className="flex gap-2">
            <button
              onClick={() => setSelectedPayments(new Set())}
              className="px-3 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
            >
              Seçimi Temizle
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

      {/* Filtreleme ve Arama */}
      <div className="mb-4 space-y-4">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            placeholder="Ödeme ara (ödeme no, müşteri, sözleşme, tutar)..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>
        <div className="flex gap-4 flex-wrap">
          <div className="flex items-center gap-2">
            <FunnelIcon className="h-5 w-5 text-gray-400" />
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
                // Update URL parameter
                if (e.target.value === 'all') {
                  searchParams.delete('status');
                } else {
                  searchParams.set('status', e.target.value);
                }
                setSearchParams(searchParams, { replace: true });
              }}
              className="block px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
              <option value="all">Tüm Durumlar</option>
              <option value="paid">Ödendi</option>
              <option value="pending">Beklemede</option>
              <option value="overdue">Gecikmiş</option>
              <option value="unpaid">Ödenmemiş (Beklemede + Gecikmiş)</option>
              <option value="cancelled">İptal</option>
            </select>
          </div>
        </div>
      </div>

      {loading && (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
        </div>
      )}
      {!loading && filteredPayments.length === 0 && (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">
            {payments.length === 0
              ? 'Henüz ödeme bulunmamaktadır.'
              : 'Arama kriterlerinize uygun ödeme bulunamadı.'}
          </p>
        </div>
      )}
      {!loading && filteredPayments.length > 0 && (
        <div className="modern-card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="table-modern">
              <thead>
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                    <input
                      type="checkbox"
                      checked={selectedPayments.size === filteredPayments.length && filteredPayments.length > 0}
                      onChange={(e) => {
                        if (e.target.checked) {
                          setSelectedPayments(new Set(filteredPayments.map((p) => p.id)));
                        } else {
                          setSelectedPayments(new Set());
                        }
                      }}
                      className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Ödeme No
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Müşteri
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Sözleşme
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Tutar
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Vade Tarihi
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
                {filteredPayments.map((payment) => (
                  <tr key={payment.id} className={selectedPayments.has(payment.id) ? 'bg-primary-50 dark:bg-primary-900/10' : ''}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <input
                        type="checkbox"
                        checked={selectedPayments.has(payment.id)}
                        onChange={(e) => {
                          const newSelected = new Set(selectedPayments);
                          if (e.target.checked) {
                            newSelected.add(payment.id);
                          } else {
                            newSelected.delete(payment.id);
                          }
                          setSelectedPayments(newSelected);
                        }}
                        className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <CreditCardIcon className="h-5 w-5 text-primary-500 mr-2" />
                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                          {payment.payment_number}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      <div className="flex items-center">
                        <UsersIcon className="h-4 w-4 mr-1 text-gray-400" />
                        {payment.contract?.customer?.first_name} {payment.contract?.customer?.last_name}
                      </div>
                      <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {payment.contract?.customer?.email}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {payment.contract?.contract_number || '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {formatTurkishCurrency(Number(payment.amount))}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {new Date(payment.due_date).toLocaleDateString('tr-TR')}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(
                          payment.status,
                        )}`}
                      >
                        {getStatusText(payment.status)}
                        {payment.days_overdue > 0 && ` (${payment.days_overdue} gün)`}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        onClick={() => {
                          setDeleteError('');
                          setDeleteTarget(payment);
                        }}
                        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center"
                      >
                        <TrashIcon className="h-4 w-4 mr-1" />
                        Sil
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
