import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { customersApi } from '../../services/api/customersApi';
import {
  UsersIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  TrashIcon,
  XMarkIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  CreditCardIcon,
  CalendarIcon,
} from '@heroicons/react/24/outline';
import { AddCustomerModal } from '../../components/modals/AddCustomerModal';
import { CollectPaymentModal } from '../../components/modals/CollectPaymentModal';
import { exportCustomersToExcel, importCustomersFromExcel } from '../../utils/excelUtils';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import toast from 'react-hot-toast';

export function CustomersPage() {
  const navigate = useNavigate();
  const [customers, setCustomers] = useState<any[]>([]);
  const [filteredCustomers, setFilteredCustomers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [importing, setImporting] = useState(false);
  const [selectedCustomers, setSelectedCustomers] = useState<Set<string>>(new Set());
  const [deleteTarget, setDeleteTarget] = useState<any>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [deleteError, setDeleteError] = useState('');
  const [expandedCustomer, setExpandedCustomer] = useState<string | null>(null);
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
  const [paymentModalCustomer, setPaymentModalCustomer] = useState<any>(null);
  const [paymentModalPayments, setPaymentModalPayments] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [limit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(0);

  const fetchCustomers = async () => {
    setLoading(true);
    try {
      const res = await customersApi.getAll({ page, limit });
      setCustomers(res.data);
      setFilteredCustomers(res.data);
      setTotal(res.total);
      setTotalPages(res.totalPages);
    } catch (error) {
      console.error('Error fetching customers:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCustomers();
  }, [page]);

  useEffect(() => {
    if (searchTerm.trim() === '') {
      setFilteredCustomers(customers);
    } else {
      const filtered = customers.filter(
        (customer) =>
          customer.first_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          customer.last_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          customer.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          (customer.phone && customer.phone.includes(searchTerm)),
      );
      setFilteredCustomers(filtered);
    }
  }, [searchTerm, customers]);

  return (
    <div>
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold gradient-text mb-2">Müşteriler</h1>
          <p className="text-gray-600 dark:text-gray-400">Müşteri yönetimi ve takibi</p>
        </div>
        <div className="flex flex-wrap gap-2 w-full sm:w-auto">
          <button
            onClick={() => {
              exportCustomersToExcel(customers);
            }}
            className="hidden sm:inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
          >
            <ArrowDownTrayIcon className="h-5 w-5 mr-2" />
            Excel'e Aktar
          </button>
          <label className="hidden sm:inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer">
            <ArrowUpTrayIcon className="h-5 w-5 mr-2" />
            İçe Aktar
            <input
              type="file"
              accept=".xlsx,.xls"
              className="hidden"
              onChange={async (e) => {
                const file = e.target.files?.[0];
                if (!file) return;

                setImporting(true);
                try {
                  const data = await importCustomersFromExcel(file);
                  // Import data to API
                  for (const row of data) {
                    if (row.Ad && row.Soyad && row['E-posta']) {
                      await customersApi.create({
                        first_name: row.Ad,
                        last_name: row.Soyad,
                        email: row['E-posta'],
                        phone: row.Telefon || '',
                        identity_number: row['TC Kimlik No'] || '',
                        address: row.Adres || '',
                        notes: row.Notlar || '',
                      });
                    }
                  }
                  await fetchCustomers();
                  toast.success(`${data.length} müşteri başarıyla içe aktarıldı.`);
                } catch (error: any) {
                  toast.error('İçe aktarma hatası: ' + (error.message || 'Bilinmeyen hata'));
                } finally {
                  setImporting(false);
                  e.target.value = '';
                }
              }}
              disabled={importing}
            />
          </label>
          <button
            onClick={() => setIsModalOpen(true)}
            className="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            Yeni Müşteri
          </button>
        </div>
      </div>

      {/* Filtreleme */}
      <div className="mb-4">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            placeholder="Müşteri ara (ad, soyad, e-posta, telefon)..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>
      </div>

      <AddCustomerModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSuccess={fetchCustomers}
      />

      <CollectPaymentModal
        isOpen={isPaymentModalOpen}
        onClose={() => {
          setIsPaymentModalOpen(false);
          setPaymentModalCustomer(null);
          setPaymentModalPayments([]);
        }}
        onSuccess={() => {
          fetchCustomers();
          setIsPaymentModalOpen(false);
          setPaymentModalCustomer(null);
          setPaymentModalPayments([]);
        }}
        initialCustomer={paymentModalCustomer}
        initialPayments={paymentModalPayments}
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
                    Müşteriyi Sil
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
                      <strong>{selectedCustomers.size} müşteri</strong> silmek istediğinize emin
                      misiniz? Bu işlem geri alınamaz.
                    </>
                  ) : (
                    <>
                      <strong className="text-gray-900 dark:text-white">
                        {deleteTarget.first_name} {deleteTarget.last_name}
                      </strong>{' '}
                      müşterisini silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
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
                          await customersApi.bulkDelete(Array.from(selectedCustomers));
                          setSelectedCustomers(new Set());
                        } else {
                          await customersApi.remove(deleteTarget.id);
                        }
                        setDeleteTarget(null);
                        fetchCustomers();
                      } catch (err: any) {
                        setDeleteError(
                          err.response?.data?.message || 'Müşteri silinirken bir hata oluştu',
                        );
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

      {/* Toplu İşlemler Toolbar */}
      {selectedCustomers.size > 0 && (
        <div className="mb-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg flex items-center justify-between">
          <span className="text-sm font-medium text-primary-900 dark:text-primary-200">
            {selectedCustomers.size} müşteri seçildi
          </span>
          <div className="flex gap-2">
            <button
              onClick={() => setSelectedCustomers(new Set())}
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
      {loading ? (
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto mb-4"></div>
          <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
        </div>
      ) : filteredCustomers.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
          <UsersIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm ? 'Arama sonucu bulunamadı.' : 'Henüz müşteri bulunmamaktadır.'}
          </p>
        </div>
      ) : (
        <>
          {/* Mobile View - Cards */}
          <div className="grid grid-cols-1 gap-4 md:hidden">
            {filteredCustomers.map((customer) => {
              const activeContracts = customer.contracts?.filter((c: any) => c.is_active) || [];
              const totalDebt = activeContracts.reduce((sum: number, contract: any) => {
                const unpaidPayments = contract.payments?.filter(
                  (p: any) => p.status === 'pending' || p.status === 'overdue'
                ) || [];
                return sum + unpaidPayments.reduce((s: number, p: any) => s + Number(p.amount), 0);
              }, 0);

              return (
                <div key={customer.id} className="modern-card p-4 relative group">
                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center space-x-3 overflow-hidden">
                      <div className="w-10 h-10 bg-primary-50 dark:bg-primary-900/20 rounded-full flex-shrink-0 flex items-center justify-center">
                        <UsersIcon className="h-6 w-6 text-primary-500" />
                      </div>
                      <div className="min-w-0">
                        <h3 className="text-sm font-bold text-gray-900 dark:text-white truncate">
                          {customer.first_name} {customer.last_name}
                        </h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400 truncate">{customer.email}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-1">
                      <button
                        onClick={() => navigate(`/customers/${customer.id}`)}
                        className="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                      >
                        <UsersIcon className="h-5 w-5" />
                      </button>
                      <button
                        onClick={() => {
                          setDeleteError('');
                          setDeleteTarget(customer);
                        }}
                        className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                      >
                        <TrashIcon className="h-5 w-5" />
                      </button>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    <div>
                      <p className="text-[10px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500 mb-1">Telefon</p>
                      <p className="text-sm text-gray-700 dark:text-gray-300 font-medium">{customer.phone || '-'}</p>
                    </div>
                    <div>
                      <p className="text-[10px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500 mb-1">Sözleşme</p>
                      <p className="text-sm text-gray-700 dark:text-gray-300 font-medium">{activeContracts.length} Aktif</p>
                    </div>
                  </div>

                  <div className="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span className="text-[10px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500">Borç Durumu</span>
                    {totalDebt > 0 ? (
                      <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        {formatTurkishCurrency(totalDebt)}
                      </span>
                    ) : (
                      <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        Borç Yok
                      </span>
                    )}
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
                        checked={selectedCustomers.size === filteredCustomers.length && filteredCustomers.length > 0}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedCustomers(new Set(filteredCustomers.map((c) => c.id)));
                          } else {
                            setSelectedCustomers(new Set());
                          }
                        }}
                        className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        onClick={(e) => e.stopPropagation()}
                      />
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Müşteri
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      E-posta
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Telefon
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Sözleşme
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Borç Durumu
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      İşlemler
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {filteredCustomers.map((customer) => {
                    const activeContracts = customer.contracts?.filter((c: any) => c.is_active) || [];
                    const totalDebt = activeContracts.reduce((sum: number, contract: any) => {
                      const unpaidPayments = contract.payments?.filter(
                        (p: any) => p.status === 'pending' || p.status === 'overdue'
                      ) || [];
                      return sum + unpaidPayments.reduce((s: number, p: any) => s + Number(p.amount), 0);
                    }, 0);

                    // Get all payments from active contracts
                    const allPayments = activeContracts.reduce((acc: any[], contract: any) => {
                      if (contract.payments) {
                        acc.push(...contract.payments);
                      }
                      return acc;
                    }, []);

                    // Group payments by month (due_date)
                    const paymentsByMonth: Record<string, any> = {};
                    const monthNames = [
                      'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
                      'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
                    ];

                    allPayments.forEach((payment: any) => {
                      const dueDate = new Date(payment.due_date);
                      const monthKey = `${dueDate.getFullYear()}-${String(dueDate.getMonth() + 1).padStart(2, '0')}`;
                      
                      if (!paymentsByMonth[monthKey]) {
                        paymentsByMonth[monthKey] = {
                          year: dueDate.getFullYear(),
                          month: dueDate.getMonth() + 1,
                          monthName: monthNames[dueDate.getMonth()],
                          paid: [] as any[],
                          unpaid: [] as any[],
                        };
                      }
                      
                      if (payment.status === 'paid') {
                        paymentsByMonth[monthKey].paid.push(payment);
                      } else {
                        paymentsByMonth[monthKey].unpaid.push(payment);
                      }
                    });

                    const isExpanded = expandedCustomer === customer.id;
                    const pendingPayments = allPayments.filter((p: any) => p.status === 'pending' || p.status === 'overdue');

                    return (
                      <React.Fragment key={customer.id}>
                        <tr className="hover:bg-gray-50 dark:hover:bg-gray-700">
                          <td className="px-6 py-4 whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                            <input
                              type="checkbox"
                              checked={selectedCustomers.has(customer.id)}
                              onChange={(e) => {
                                const newSelected = new Set(selectedCustomers);
                                if (e.target.checked) {
                                  newSelected.add(customer.id);
                                } else {
                                  newSelected.delete(customer.id);
                                }
                                setSelectedCustomers(newSelected);
                              }}
                              className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                              onClick={(e) => e.stopPropagation()}
                            />
                          </td>
                          <td
                            className="px-6 py-4 whitespace-nowrap cursor-pointer"
                            onClick={() => {
                              if (isExpanded) {
                                setExpandedCustomer(null);
                              } else {
                                setExpandedCustomer(customer.id);
                              }
                            }}
                          >
                            <div className="flex items-center">
                              {isExpanded ? (
                                <ChevronUpIcon className="h-4 w-4 text-gray-400 mr-2" />
                              ) : (
                                <ChevronDownIcon className="h-4 w-4 text-gray-400 mr-2" />
                              )}
                              <UsersIcon className="h-5 w-5 text-primary-500 mr-2" />
                              <span className="text-sm font-medium text-gray-900 dark:text-white">
                                {customer.first_name} {customer.last_name}
                              </span>
                            </div>
                          </td>
                          <td 
                            className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 cursor-pointer"
                            onClick={() => {
                              if (isExpanded) {
                                setExpandedCustomer(null);
                              } else {
                                setExpandedCustomer(customer.id);
                              }
                            }}
                          >
                            {customer.email}
                          </td>
                          <td 
                            className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 cursor-pointer"
                            onClick={() => {
                              if (isExpanded) {
                                setExpandedCustomer(null);
                              } else {
                                setExpandedCustomer(customer.id);
                              }
                            }}
                          >
                            {customer.phone || '-'}
                          </td>
                          <td 
                            className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 cursor-pointer"
                            onClick={() => {
                              if (isExpanded) {
                                setExpandedCustomer(null);
                              } else {
                                setExpandedCustomer(customer.id);
                              }
                            }}
                          >
                            {activeContracts.length} Aktif
                          </td>
                          <td 
                            className="px-6 py-4 whitespace-nowrap cursor-pointer"
                            onClick={() => {
                              if (isExpanded) {
                                setExpandedCustomer(null);
                              } else {
                                setExpandedCustomer(customer.id);
                              }
                            }}
                          >
                            {totalDebt > 0 ? (
                              <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                {formatTurkishCurrency(totalDebt)}
                              </span>
                            ) : (
                              <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Borç Yok
                              </span>
                            )}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                            <div className="flex items-center gap-2">
                              <button
                                onClick={() => navigate(`/customers/${customer.id}`)}
                                className="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                title="Detayları Görüntüle"
                              >
                                <UsersIcon className="h-5 w-5" />
                              </button>
                              <button
                                onClick={() => {
                                  setDeleteError('');
                                  setDeleteTarget(customer);
                                }}
                                className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                title="Sil"
                              >
                                <TrashIcon className="h-5 w-5" />
                              </button>
                            </div>
                          </td>
                        </tr>
                        {isExpanded && (
                          <tr>
                            <td colSpan={7} className="px-6 py-4 bg-gray-50 dark:bg-gray-800/50">
                              <div className="space-y-4">
                                {/* Ödenmiş Aylar */}
                                {Object.keys(paymentsByMonth).length > 0 && (
                                  <div>
                                    <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                      <CalendarIcon className="h-4 w-4 mr-2" />
                                      Aylık Ödeme Durumu
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                      {Object.values(paymentsByMonth)
                                        .sort((a: any, b: any) => {
                                          if (a.year !== b.year) return b.year - a.year;
                                          return b.month - a.month;
                                        })
                                        .map((monthData: any) => {
                                          const hasUnpaid = monthData.unpaid.length > 0;
                                          const hasPaid = monthData.paid.length > 0;
                                          
                                          const handleMonthClick = () => {
                                            if (hasUnpaid && monthData.unpaid.length > 0) {
                                              setPaymentModalCustomer(customer);
                                              setPaymentModalPayments(monthData.unpaid);
                                              setIsPaymentModalOpen(true);
                                            }
                                          };

                                          const baseClassName = hasUnpaid
                                            ? 'p-3 rounded-lg border bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 cursor-pointer hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors'
                                            : hasPaid
                                            ? 'p-3 rounded-lg border bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                                            : 'p-3 rounded-lg border bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600';

                                          return (
                                            <div
                                              key={`${monthData.year}-${monthData.month}`}
                                              className={baseClassName}
                                              onClick={handleMonthClick}
                                              onKeyDown={(e) => {
                                                if ((e.key === 'Enter' || e.key === ' ') && hasUnpaid && monthData.unpaid.length > 0) {
                                                  e.preventDefault();
                                                  handleMonthClick();
                                                }
                                              }}
                                              role={hasUnpaid ? 'button' : undefined}
                                              tabIndex={hasUnpaid ? 0 : undefined}
                                              title={hasUnpaid ? 'Ödeme almak için tıklayın' : undefined}
                                            >
                                              <div className="flex items-center justify-between mb-2">
                                                <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                  {monthData.monthName} {monthData.year}
                                                </span>
                                                {(() => {
                                                  if (hasUnpaid) {
                                                    return (
                                                      <span className="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                        Ödenmedi
                                                      </span>
                                                    );
                                                  }
                                                  if (hasPaid) {
                                                    return (
                                                      <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Ödendi
                                                      </span>
                                                    );
                                                  }
                                                  return (
                                                    <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                      Beklemede
                                                    </span>
                                                  );
                                                })()}
                                              </div>
                                              {hasPaid && (
                                                <div className="text-xs text-gray-600 dark:text-gray-400">
                                                  Ödenen: {formatTurkishCurrency(monthData.paid.reduce((sum: number, p: any) => sum + Number(p.amount), 0))}
                                                </div>
                                              )}
                                              {hasUnpaid && (
                                                <div className="text-xs text-red-600 dark:text-red-400 font-medium flex items-center justify-between">
                                                  <span>Ödenmemiş: {formatTurkishCurrency(monthData.unpaid.reduce((sum: number, p: any) => sum + Number(p.amount), 0))}</span>
                                                  <span className="ml-2 text-xs text-red-700 dark:text-red-300 font-semibold">Ödeme Al →</span>
                                                </div>
                                              )}
                                            </div>
                                          );
                                        })}
                                    </div>
                                  </div>
                                )}

                                {/* Bekleyen Ödemeler */}
                                {pendingPayments.length > 0 && (
                                  <div>
                                    <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                      <CreditCardIcon className="h-4 w-4 mr-2" />
                                      Bekleyen Ödemeler ({pendingPayments.length})
                                    </h4>
                                    <div className="overflow-x-auto">
                                      <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-100 dark:bg-gray-700">
                                          <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                              Ödeme No
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                              Tutar
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                              Vade Tarihi
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                              Durum
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                              İşlem
                                            </th>
                                          </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                          {pendingPayments
                                            .sort((a: any, b: any) => new Date(a.due_date).getTime() - new Date(b.due_date).getTime())
                                            .map((payment: any, index: number) => (
                                              <tr key={payment.id || `payment-${index}`}>
                                                <td className="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                  {payment.payment_number}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">
                                                  {formatTurkishCurrency(Number(payment.amount))}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                                  {new Date(payment.due_date).toLocaleDateString('tr-TR')}
                                                </td>
                                                <td className="px-4 py-2">
                                                  <span
                                                    className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                      payment.status === 'overdue'
                                                        ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                                    }`}
                                                  >
                                                    {payment.status === 'overdue' ? 'Gecikmiş' : 'Beklemede'}
                                                    {payment.days_overdue > 0 && ` (${payment.days_overdue} gün)`}
                                                  </span>
                                                </td>
                                                <td className="px-4 py-2">
                                                  <button
                                                    onClick={() => navigate(`/payments?status=${payment.status === 'overdue' ? 'overdue' : 'pending'}`)}
                                                    className="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                  >
                                                    Ödemeler Sayfasına Git
                                                  </button>
                                                </td>
                                              </tr>
                                            ))}
                                        </tbody>
                                      </table>
                                    </div>
                                  </div>
                                )}

                                {pendingPayments.length === 0 && Object.keys(paymentsByMonth).length === 0 && (
                                  <div className="text-center py-4 text-gray-500 dark:text-gray-400">
                                    Henüz ödeme kaydı bulunmamaktadır.
                                  </div>
                                )}
                              </div>
                            </td>
                          </tr>
                        )}
                      </React.Fragment>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        </>
      )}
      {totalPages > 1 && !searchTerm && (
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
    </div>
  );
}
