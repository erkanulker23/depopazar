import { useEffect, useState } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { paymentsApi } from '../../services/api/paymentsApi';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import { CreditCardIcon, MagnifyingGlassIcon, FunnelIcon, BanknotesIcon, TrashIcon } from '@heroicons/react/24/outline';
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
      <div className="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-zinc-100 mb-1">Ödemeler</h1>
          <p className="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-widest font-bold">Ödeme kayıtları</p>
        </div>
        <button
          type="button"
          onClick={() => setIsPaymentModalOpen(true)}
          className="w-full sm:w-auto px-4 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all flex items-center justify-center space-x-1.5 shadow-lg shadow-emerald-500/10 font-bold text-sm"
        >
          <BanknotesIcon className="h-4 w-4" />
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
              className="fixed inset-0 transition-opacity bg-black/60 backdrop-blur-sm cursor-default"
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
            <div className="inline-block align-bottom bg-white dark:bg-[#121214] rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-[#27272a]">
              <div className="bg-white dark:bg-[#121214] px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-50 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
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
              <div className="bg-gray-50 dark:bg-[#050807] px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <button
                  type="button"
                  onClick={handleDelete}
                  disabled={deleteLoading}
                  className="w-full sm:w-auto inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-sm font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 transition-all"
                >
                  {deleteLoading ? 'Siliniyor...' : 'Evet, Sil'}
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
                  className="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-xl border border-gray-300 dark:border-[#27272a] shadow-sm px-4 py-2 bg-white dark:bg-[#18181b] text-sm font-bold text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-[#27272a] focus:outline-none transition-all"
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
        <div className="mb-4 p-3.5 bg-emerald-50 dark:bg-emerald-500/5 rounded-xl flex items-center justify-between border border-emerald-100 dark:border-emerald-500/20 animate-in fade-in slide-in-from-top-2 duration-300">
          <span className="text-xs font-bold text-emerald-900 dark:text-emerald-400">
            {selectedPayments.size} ödeme seçildi
          </span>
          <div className="flex gap-2">
            <button
              onClick={() => setSelectedPayments(new Set())}
              className="px-3 py-1 text-[10px] font-bold text-gray-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-800 rounded-lg transition-all"
            >
              Seçimi Temizle
            </button>
            <button
              onClick={() => {
                setDeleteError('');
                setDeleteTarget('bulk');
              }}
              className="px-3 py-1 text-[10px] font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg inline-flex items-center transition-all shadow-lg shadow-red-500/20"
            >
              <TrashIcon className="h-3.5 w-3.5 mr-1" />
              Seçilenleri Sil
            </button>
          </div>
        </div>
      )}

      {/* Filtreleme ve Arama */}
      <div className="mb-6 space-y-4">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <MagnifyingGlassIcon className="h-4 w-4 text-gray-400 dark:text-zinc-500" />
          </div>
          <input
            type="text"
            placeholder="Ödeme ara (ödeme no, müşteri, sözleşme, tutar)..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="block w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-[#27272a] rounded-xl leading-5 bg-white dark:bg-[#121214] text-gray-900 dark:text-zinc-100 placeholder-gray-400 dark:placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 sm:text-sm transition-all"
          />
        </div>
        <div className="flex gap-3 flex-wrap">
          <div className="flex items-center gap-2">
            <FunnelIcon className="h-4 w-4 text-gray-400 dark:text-zinc-500" />
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
              className="block px-3 py-1.5 border border-gray-200 dark:border-[#27272a] rounded-lg bg-white dark:bg-[#121214] text-gray-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 sm:text-xs transition-all"
            >
              <option value="all">Tüm Durumlar</option>
              <option value="paid">Ödendi</option>
              <option value="pending">Beklemede</option>
              <option value="overdue">Gecikmiş</option>
              <option value="unpaid">Ödenmemiş</option>
              <option value="cancelled">İptal</option>
            </select>
          </div>
        </div>
      </div>

      {loading && (
        <div className="modern-card p-12 text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto mb-4"></div>
          <p className="text-sm text-gray-500 dark:text-zinc-500 font-medium">Yükleniyor...</p>
        </div>
      )}
      {!loading && filteredPayments.length === 0 && (
        <div className="modern-card p-12 text-center">
          <CreditCardIcon className="h-12 w-12 text-gray-300 dark:text-zinc-700 mx-auto mb-4" />
          <p className="text-sm text-gray-500 dark:text-zinc-500 font-medium">
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
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest w-12">
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
                      className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 bg-transparent"
                    />
                  </th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">
                    Ödeme Bilgisi
                  </th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">
                    Müşteri & Sözleşme
                  </th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">
                    Finansal
                  </th>
                  <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">
                    Durum
                  </th>
                  <th className="px-6 py-3 text-right text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">
                    İşlemler
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50 dark:divide-zinc-800">
                {filteredPayments.map((payment) => (
                  <tr key={payment.id} className={`${selectedPayments.has(payment.id) ? 'bg-emerald-50/30 dark:bg-emerald-500/5' : ''} hover:bg-gray-50/50 dark:hover:bg-zinc-900/50 transition-colors`}>
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
                        className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 bg-transparent"
                      />
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <CreditCardIcon className="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-500 mr-2.5" />
                        <div className="flex flex-col">
                          <span className="text-xs font-bold text-gray-900 dark:text-zinc-100">{payment.payment_number}</span>
                          <span className="text-[10px] text-gray-500 dark:text-zinc-500 font-medium">Vade: {new Date(payment.due_date).toLocaleDateString('tr-TR')}</span>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex flex-col">
                        {payment.contract?.customer?.id ? (
                          <Link 
                            to={`/customers/${payment.contract.customer.id}`}
                            className="text-xs font-bold text-gray-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                          >
                            {payment.contract.customer.first_name} {payment.contract.customer.last_name}
                          </Link>
                        ) : (
                          <span className="text-xs font-bold text-gray-900 dark:text-zinc-100">{payment.contract?.customer?.first_name} {payment.contract?.customer?.last_name}</span>
                        )}
                        {payment.contract_id && (
                          <Link 
                            to={`/contracts/${payment.contract_id}`}
                            className="text-[10px] text-emerald-600 dark:text-emerald-50 font-bold hover:underline"
                          >
                            Sözleşme: {payment.contract?.contract_number || '-'}
                          </Link>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-xs font-bold text-gray-900 dark:text-zinc-100">
                      {formatTurkishCurrency(Number(payment.amount))}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full border ${getStatusColor(
                          payment.status,
                        )} ${
                          payment.status === 'paid' ? 'border-emerald-100 dark:border-emerald-500/20' :
                          payment.status === 'pending' ? 'border-yellow-100 dark:border-yellow-500/20' :
                          'border-red-100 dark:border-red-500/20'
                        }`}
                      >
                        {getStatusText(payment.status)}
                        {payment.days_overdue > 0 && ` (${payment.days_overdue} gün)`}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right">
                      <button
                        onClick={() => {
                          setDeleteError('');
                          setDeleteTarget(payment);
                        }}
                        className="p-1.5 text-red-600 dark:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors"
                        title="Sil"
                      >
                        <TrashIcon className="h-4.5 w-4.5" />
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
