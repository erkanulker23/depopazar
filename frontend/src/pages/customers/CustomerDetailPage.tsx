import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { customersApi } from '../../services/api/customersApi';
import { itemsApi } from '../../services/api/itemsApi';
import {
  UsersIcon,
  ArrowLeftIcon,
  CreditCardIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  CalendarIcon,
  XMarkIcon,
  ArchiveBoxIcon,
} from '@heroicons/react/24/outline';
import { CustomerCalendar } from '../../components/calendar/CustomerCalendar';
import { formatTurkishCurrency } from '../../utils/inputFormatters';

export function CustomerDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [customerData, setCustomerData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState<any[]>([]);
  const [itemsLoading, setItemsLoading] = useState(true);
  const [deleteTarget, setDeleteTarget] = useState<any>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [deleteError, setDeleteError] = useState('');

  useEffect(() => {
    const fetchCustomer = async () => {
      if (!id) return;
      try {
        const data = await customersApi.getDebtInfo(id);
        setCustomerData(data);
      } catch (error) {
        console.error('Error fetching customer:', error);
      } finally {
        setLoading(false);
      }
    };

    const fetchItems = async () => {
      if (!id) return;
      try {
        setItemsLoading(true);
        const data = await itemsApi.getByCustomerId(id);
        setItems(data || []);
      } catch (error) {
        console.error('Error fetching items:', error);
      } finally {
        setItemsLoading(false);
      }
    };

    fetchCustomer();
    fetchItems();
  }, [id]);

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
        <p className="text-gray-600 dark:text-gray-400">Müşteri bulunamadı.</p>
      </div>
    );
  }

  const { customer, contracts, debtInfo } = customerData;

  // Calculate warehouse start and end dates from contracts
  const warehouseStartDate = contracts.length > 0 
    ? new Date(Math.min(...contracts.map((c: any) => new Date(c.start_date).getTime())))
    : null;
  const warehouseEndDate = contracts.length > 0
    ? new Date(Math.max(...contracts.map((c: any) => new Date(c.end_date).getTime())))
    : null;

  // Calculate months between start and end dates
  const calculateMonthsBetween = (start: Date, end: Date): number => {
    const years = end.getFullYear() - start.getFullYear();
    const months = end.getMonth() - start.getMonth();
    return years * 12 + months;
  };

  const warehouseMonths = warehouseStartDate && warehouseEndDate
    ? calculateMonthsBetween(warehouseStartDate, warehouseEndDate)
    : 0;

  // Get all payments from contracts
  const allPayments = contracts.reduce((acc: any[], contract: any) => {
    if (contract.payments) {
      acc.push(...contract.payments);
    }
    return acc;
  }, []);

  // Filter payments to only show those within warehouse date range
  const filteredPayments = warehouseStartDate && warehouseEndDate
    ? allPayments.filter((payment: any) => {
        let paymentDate: Date | null = null;
        if (payment.due_date) {
          paymentDate = new Date(payment.due_date);
        } else if (payment.paid_at) {
          paymentDate = new Date(payment.paid_at);
        }
        if (!paymentDate) return false;
        return paymentDate >= warehouseStartDate && paymentDate <= warehouseEndDate;
      })
    : allPayments;

  // Get paid payments from filtered payments
  const paidPayments = filteredPayments.filter((p: any) => p.status === 'paid');

  // Filter contracts to only show those within warehouse date range
  const filteredContracts = warehouseStartDate && warehouseEndDate
    ? contracts.filter((contract: any) => {
        const contractStart = new Date(contract.start_date);
        const contractEnd = new Date(contract.end_date);
        // Contract overlaps with warehouse date range
        return contractStart <= warehouseEndDate && contractEnd >= warehouseStartDate;
      })
    : contracts;
  
  // Calculate total debt and paid amount - only for filtered contracts and payments
  const totalDebt = filteredContracts.reduce((sum: number, contract: any) => {
    const monthlyTotal = contract.monthly_prices?.reduce((s: number, mp: any) => s + Number(mp.price), 0) || 0;
    const transport = Number(contract.transportation_fee || 0);
    const discount = Number(contract.discount || 0);
    return sum + monthlyTotal + transport - discount;
  }, 0);
  
  const totalPaid = paidPayments.reduce((sum: number, p: any) => sum + Number(p.amount), 0);
  const remainingDebt = totalDebt - totalPaid;

  // Group paid payments by month
  const paidPaymentsByMonth = paidPayments.reduce((acc: any, payment: any) => {
    if (payment.paid_at) {
      const paidDate = new Date(payment.paid_at);
      const monthKey = `${paidDate.getFullYear()}-${String(paidDate.getMonth() + 1).padStart(2, '0')}`;
      const monthNames = [
        'Ocak',
        'Şubat',
        'Mart',
        'Nisan',
        'Mayıs',
        'Haziran',
        'Temmuz',
        'Ağustos',
        'Eylül',
        'Ekim',
        'Kasım',
        'Aralık',
      ];
      
      if (!acc[monthKey]) {
        acc[monthKey] = {
          month: paidDate.getMonth() + 1,
          year: paidDate.getFullYear(),
          monthName: monthNames[paidDate.getMonth()],
          payments: [],
          totalAmount: 0,
        };
      }
      acc[monthKey].payments.push(payment);
      acc[monthKey].totalAmount += Number(payment.amount);
    }
    return acc;
  }, {});

  // Get intermediate payments (payments that don't match their due_date month)
  const intermediatePayments = paidPayments.filter((payment: any) => {
    if (!payment.paid_at) return false;
    const dueDate = new Date(payment.due_date);
    const paidDate = new Date(payment.paid_at);
    return (
      dueDate.getMonth() !== paidDate.getMonth() ||
      dueDate.getFullYear() !== paidDate.getFullYear()
    );
  });

  // Calculate total storage duration (months) - only for filtered contracts
  const totalStorageMonths = filteredContracts.reduce((sum: number, contract: any) => {
    const start = new Date(contract.start_date);
    const end = new Date(contract.end_date);
    const months = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24 * 30));
    return sum + months;
  }, 0);

  // Calculate total storage duration in years and months
  const years = Math.floor(totalStorageMonths / 12);
  const remainingMonths = totalStorageMonths % 12;

  // Calculate total months taken (toplam kaç aylık almış) - based on contract duration, not monthly_prices
  const totalMonthsTaken = filteredContracts.reduce((sum: number, contract: any) => {
    const start = new Date(contract.start_date);
    const end = new Date(contract.end_date);
    // Calculate months between start and end dates
    const years = end.getFullYear() - start.getFullYear();
    const months = end.getMonth() - start.getMonth();
    const totalMonths = years * 12 + months;
    return sum + totalMonths;
  }, 0);

  // Calculate total months paid (kaç ay ödenmiş)
  // Count unique months from paid payments' due_date
  const paidMonthsSet = new Set<string>();
  paidPayments.forEach((payment: any) => {
    if (payment.due_date) {
      const dueDate = new Date(payment.due_date);
      const monthKey = `${dueDate.getFullYear()}-${String(dueDate.getMonth() + 1).padStart(2, '0')}`;
      paidMonthsSet.add(monthKey);
    }
  });
  const totalMonthsPaid = paidMonthsSet.size;

  // Calculate pending payments count - only from filtered payments
  const pendingPayments = filteredPayments.filter((p: any) => p.status === 'pending' || p.status === 'overdue');
  const pendingPaymentsCount = pendingPayments.length;

  // Group payments by month for "Aylık Ödeme Durumu" display
  const paymentsByMonth: any = {};
  const monthNames = [
    'Ocak',
    'Şubat',
    'Mart',
    'Nisan',
    'Mayıs',
    'Haziran',
    'Temmuz',
    'Ağustos',
    'Eylül',
    'Ekim',
    'Kasım',
    'Aralık',
  ];

  filteredPayments.forEach((payment: any) => {
    if (payment.due_date) {
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
    }
  });

  // Check for data inconsistency: pending payments should not exceed total months taken
  const hasDataInconsistency = pendingPaymentsCount > totalMonthsTaken;

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate('/customers')}
          className="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-4"
        >
          <ArrowLeftIcon className="h-4 w-4 mr-2" />
          Müşterilere Dön
        </button>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          {customer.first_name} {customer.last_name}
        </h1>
      </div>

      {/* Muhasebe Özeti */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div className="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 shadow-lg rounded-lg p-6 text-white">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-blue-100 text-sm font-medium mb-1">Toplam Borç</p>
              <p className="text-3xl font-bold">{formatTurkishCurrency(totalDebt)}</p>
            </div>
            <CreditCardIcon className="h-12 w-12 text-blue-200 opacity-50" />
          </div>
        </div>
        <div className="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 shadow-lg rounded-lg p-6 text-white">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-green-100 text-sm font-medium mb-1">Ödenen Tutar</p>
              <p className="text-3xl font-bold">{formatTurkishCurrency(totalPaid)}</p>
            </div>
            <DocumentTextIcon className="h-12 w-12 text-green-200 opacity-50" />
          </div>
        </div>
        <div className={`bg-gradient-to-br shadow-lg rounded-lg p-6 text-white ${
          remainingDebt > 0 
            ? 'from-red-500 to-red-600 dark:from-red-600 dark:to-red-700' 
            : 'from-gray-500 to-gray-600 dark:from-gray-600 dark:to-gray-700'
        }`}>
          <div className="flex items-center justify-between">
            <div>
              <p className={`text-sm font-medium mb-1 ${
                remainingDebt > 0 ? 'text-red-100' : 'text-gray-100'
              }`}>
                Kalan Borç
              </p>
              <p className="text-3xl font-bold">{formatTurkishCurrency(remainingDebt)}</p>
            </div>
            <ExclamationTriangleIcon className={`h-12 w-12 opacity-50 ${
              remainingDebt > 0 ? 'text-red-200' : 'text-gray-200'
            }`} />
          </div>
        </div>
      </div>

      {/* Müşteri Bilgileri */}
      <div className="modern-card-gradient p-6 mb-6">
        <div className="flex justify-between items-start mb-4">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <UsersIcon className="h-5 w-5 mr-2" />
            Müşteri Bilgileri
          </h2>
          <div className="flex gap-6">
            <div className="text-right">
              <p className="text-sm text-gray-500 dark:text-gray-400">Toplam Depolama Süresi</p>
              <p className="text-2xl font-bold text-primary-600 dark:text-primary-400">
                {years > 0 && `${years} Yıl `}
                {remainingMonths > 0 && `${remainingMonths} Ay`}
                {totalStorageMonths === 0 && '0 Ay'}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                ({totalStorageMonths} ay toplam)
              </p>
            </div>
            <div className="text-right">
              <p className="text-sm text-gray-500 dark:text-gray-400">Toplam Aylık Alınan</p>
              <p className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {totalMonthsTaken} Ay
              </p>
            </div>
            <div className="text-right">
              <p className="text-sm text-gray-500 dark:text-gray-400">Ödenen Ay</p>
              <p className="text-2xl font-bold text-green-600 dark:text-green-400">
                {totalMonthsPaid} Ay
              </p>
            </div>
            <div className="text-right">
              <p className="text-sm text-gray-500 dark:text-gray-400">Bekleyen Ödeme</p>
              <p className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                {pendingPaymentsCount} Adet
              </p>
            </div>
          </div>
        </div>
        {hasDataInconsistency && (
          <div className="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div className="flex items-start">
              <ExclamationTriangleIcon className="h-5 w-5 text-red-600 dark:text-red-400 mr-2 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-sm font-medium text-red-800 dark:text-red-200 mb-1">
                  Veri Tutarsızlığı Tespit Edildi
                </p>
                <p className="text-sm text-red-700 dark:text-red-300">
                  Bu müşterinin <strong>{pendingPaymentsCount} adet</strong> bekleyen ödemesi var, 
                  ancak toplamda sadece <strong>{totalMonthsTaken} ay</strong> satın alınmış. 
                  Bu durum veri tutarsızlığına işaret ediyor. Lütfen ödemeleri kontrol edin ve gereksiz ödemeleri silin.
                </p>
              </div>
            </div>
          </div>
        )}
        
        {/* Aylık Ödeme Durumu */}
        {Object.keys(paymentsByMonth).length > 0 && (
          <div className="mt-4 mb-4">
            <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
              <CalendarIcon className="h-4 w-4 mr-2" />
              Aylık Ödeme Durumu
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              {Object.values(paymentsByMonth)
                .sort((a: any, b: any) => {
                  if (a.year !== b.year) return b.year - a.year;
                  return b.month - a.month;
                })
                .map((monthData: any) => {
                  const hasUnpaid = monthData.unpaid.length > 0;
                  const hasPaid = monthData.paid.length > 0;
                  
                  const baseClassName = hasUnpaid
                    ? 'p-3 rounded-lg border bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
                    : hasPaid
                    ? 'p-3 rounded-lg border bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                    : 'p-3 rounded-lg border bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600';

                  return (
                    <div
                      key={`${monthData.year}-${monthData.month}`}
                      className={baseClassName}
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
                        <div className="text-xs text-red-600 dark:text-red-400 font-medium">
                          Ödenmemiş: {formatTurkishCurrency(monthData.unpaid.reduce((sum: number, p: any) => sum + Number(p.amount), 0))}
                        </div>
                      )}
                    </div>
                  );
                })}
            </div>
          </div>
        )}
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <p className="text-sm font-medium text-gray-500 dark:text-gray-400">E-posta</p>
            <p className="text-sm text-gray-900 dark:text-white">{customer.email}</p>
          </div>
          <div>
            <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Telefon</p>
            <p className="text-sm text-gray-900 dark:text-white">{customer.phone || '-'}</p>
          </div>
          <div>
            <p className="text-sm font-medium text-gray-500 dark:text-gray-400">TC Kimlik No</p>
            <p className="text-sm text-gray-900 dark:text-white">{customer.identity_number || '-'}</p>
          </div>
          <div>
            <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Adres</p>
            <p className="text-sm text-gray-900 dark:text-white">{customer.address || '-'}</p>
          </div>
        </div>
        
        {/* Depo Başlangıç ve Bitiş Tarihleri */}
        {warehouseStartDate && warehouseEndDate && (
          <div className="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div className="flex items-center mb-3">
              <CalendarIcon className="h-5 w-5 text-blue-600 dark:text-blue-400 mr-2" />
              <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                Depo Tarih Aralığı
              </h3>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Başlangıç Tarihi</p>
                <p className="text-sm font-bold text-blue-700 dark:text-blue-300">
                  {warehouseStartDate.toLocaleDateString('tr-TR', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                  })}
                </p>
              </div>
              <div>
                <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Bitiş Tarihi</p>
                <p className="text-sm font-bold text-blue-700 dark:text-blue-300">
                  {warehouseEndDate.toLocaleDateString('tr-TR', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                  })}
                </p>
              </div>
              <div>
                <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Toplam Süre</p>
                <p className="text-sm font-bold text-blue-700 dark:text-blue-300">
                  {warehouseMonths} Ay
                </p>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  ({Math.floor(warehouseMonths / 12)} Yıl {warehouseMonths % 12} Ay)
                </p>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Borç Durumu */}
      <div className="modern-card-gradient p-6 mb-6">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
          <CreditCardIcon className="h-5 w-5 mr-2" />
          Borç Durumu
        </h2>
        {debtInfo?.hasDebt ? (
          <>
            <div className="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg mb-4">
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400">Toplam Borç</p>
                <p className="text-3xl font-bold text-red-600 dark:text-red-400">
                  {formatTurkishCurrency(debtInfo.totalDebt)}
                </p>
              </div>
              <div className="text-right">
                <p className="text-sm text-gray-600 dark:text-gray-400">Bekleyen Ödeme</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  {debtInfo.unpaidPaymentsCount}
                </p>
              </div>
            </div>

            {/* Borç Başlangıç Ayı ve Ödenmeyen Aylar */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              {debtInfo.firstDebtMonth && (
                <div className="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                  <div className="flex items-center mb-2">
                    <ExclamationTriangleIcon className="h-5 w-5 text-yellow-600 dark:text-yellow-400 mr-2" />
                    <p className="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Borcun Başladığı Ay
                    </p>
                  </div>
                  <p className="text-lg font-bold text-yellow-700 dark:text-yellow-300">
                    {debtInfo.firstDebtMonth.monthName}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {debtInfo.firstDebtMonth.year} yılı {debtInfo.firstDebtMonth.month}. ay
                  </p>
                </div>
              )}

              {debtInfo.unpaidMonths && debtInfo.unpaidMonths.length > 0 && (
                <div className="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                  <div className="flex items-center mb-2">
                    <CalendarIcon className="h-5 w-5 text-orange-600 dark:text-orange-400 mr-2" />
                    <p className="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Ödenmeyen Aylar
                    </p>
                  </div>
                  <p className="text-lg font-bold text-orange-700 dark:text-orange-300">
                    {debtInfo.unpaidMonths.length} Ay
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Toplam: {formatTurkishCurrency(debtInfo.unpaidMonths.reduce((sum: number, m: any) => sum + m.totalAmount, 0))}
                  </p>
                </div>
              )}
            </div>

            {/* Ödenmeyen Aylar Detay Listesi */}
            {debtInfo.unpaidMonths && debtInfo.unpaidMonths.length > 0 && (
              <div className="mt-4">
                <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Ödenmeyen Aylar Detayı
                </h3>
                <div className="space-y-2">
                  {debtInfo.unpaidMonths.map((month: any) => (
                    <div
                      key={`${month.year}-${month.month}`}
                      className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                    >
                      <div className="flex items-center">
                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                          {month.monthName}
                        </span>
                      </div>
                      <div className="text-right">
                        <span className="text-sm font-bold text-red-600 dark:text-red-400">
                          {formatTurkishCurrency(month.totalAmount)}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </>
        ) : (
          <div className="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Toplam Borç</p>
              <p className="text-3xl font-bold text-green-600 dark:text-green-400">0.00 TL</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-gray-600 dark:text-gray-400">Durum</p>
              <p className="text-lg font-bold text-gray-900 dark:text-white">Borç Yok</p>
            </div>
          </div>
        )}

        {pendingPayments.length > 0 && (
          <div className="mt-4">
            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Ödenmemiş Ödemeler
            </h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                      Ödeme No
                    </th>
                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                      Tutar
                    </th>
                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                      Vade
                    </th>
                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                      Durum
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {pendingPayments.map((payment: any) => (
                    <tr key={payment.id}>
                      <td className="px-4 py-2 text-sm text-gray-900 dark:text-white">
                        {payment.payment_number}
                      </td>
                      <td className="px-4 py-2 text-sm text-gray-900 dark:text-white">
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
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Ödenmiş Faturalar */}
        {Object.keys(paidPaymentsByMonth).length > 0 && (
          <div className="mt-6">
            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Ödenmiş Faturalar
            </h3>
            <div className="space-y-3">
              {Object.values(paidPaymentsByMonth)
                .sort((a: any, b: any) => {
                  if (a.year !== b.year) return b.year - a.year;
                  return b.month - a.month;
                })
                .map((monthData: any) => (
                  <div
                    key={`${monthData.year}-${monthData.month}`}
                    className="border border-green-200 dark:border-green-800 rounded-lg p-4 bg-green-50 dark:bg-green-900/20"
                  >
                    <div className="flex items-center justify-between mb-2">
                      <h4 className="text-sm font-semibold text-gray-900 dark:text-white">
                        {monthData.monthName} {monthData.year}
                      </h4>
                      <span className="text-sm font-bold text-green-600 dark:text-green-400">
                        {formatTurkishCurrency(monthData.totalAmount)}
                      </span>
                    </div>
                    <div className="space-y-1">
                      {monthData.payments.map((payment: any) => (
                        <div
                          key={payment.id}
                          className="flex items-center justify-between text-xs bg-white dark:bg-gray-800 rounded p-2"
                        >
                          <div className="flex items-center gap-2">
                            <span className="text-gray-600 dark:text-gray-400">
                              {payment.payment_number}
                            </span>
                            <span className="text-gray-500 dark:text-gray-500">
                              {new Date(payment.paid_at).toLocaleDateString('tr-TR', {
                                day: 'numeric',
                                month: 'long',
                              })}
                            </span>
                          </div>
                          <span className="text-gray-900 dark:text-white font-medium">
                            {formatTurkishCurrency(Number(payment.amount))}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
            </div>
          </div>
        )}

        {/* Ara Ödemeler */}
        {intermediatePayments.length > 0 && (
          <div className="mt-6">
            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Ara Ödemeler
            </h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
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
                      Ödeme Tarihi
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {intermediatePayments.map((payment: any) => (
                    <tr key={payment.id}>
                      <td className="px-4 py-2 text-sm text-gray-900 dark:text-white">
                        {payment.payment_number}
                      </td>
                      <td className="px-4 py-2 text-sm text-gray-900 dark:text-white">
                        {formatTurkishCurrency(Number(payment.amount))}
                      </td>
                      <td className="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                        {new Date(payment.due_date).toLocaleDateString('tr-TR')}
                      </td>
                      <td className="px-4 py-2 text-sm text-green-600 dark:text-green-400">
                        {new Date(payment.paid_at).toLocaleDateString('tr-TR')}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>

      {/* Takvim Görünümü */}
      <div className="modern-card-gradient p-6 mb-6">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
          <CalendarIcon className="h-5 w-5 mr-2" />
          Takvim Görünümü
        </h2>
        <CustomerCalendar contracts={filteredContracts} payments={filteredPayments} />
      </div>

      {/* Eşya Listesi */}
      {items.length > 0 && (() => {
        // Filter items to only show those within warehouse date range
        const filteredItems = warehouseStartDate && warehouseEndDate
          ? items.filter((item: any) => {
              if (!item.stored_at) return false;
              const storedDate = new Date(item.stored_at);
              return storedDate >= warehouseStartDate && storedDate <= warehouseEndDate;
            })
          : items;
        
        return filteredItems.length > 0 ? (
          <div className="modern-card-gradient p-6 mb-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
              <ArchiveBoxIcon className="h-5 w-5 mr-2" />
              Eşya Listesi ({filteredItems.length})
            </h2>
            {itemsLoading ? (
              <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead className="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Eşya Adı
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Oda
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Miktar
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Depolanma Tarihi
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Açıklama
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        İşlemler
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                    {filteredItems.map((item) => (
                    <tr key={item.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <ArchiveBoxIcon className="h-5 w-5 text-primary-500 mr-2" />
                          <span className="text-sm font-medium text-gray-900 dark:text-white">
                            {item.name}
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <button
                          onClick={() => navigate(`/rooms/${item.room_id}`)}
                          className="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                        >
                          {item.room?.room_number || '-'}
                        </button>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {item.quantity || '-'} {item.unit || ''}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {item.stored_at
                          ? new Date(item.stored_at).toLocaleDateString('tr-TR')
                          : '-'}
                      </td>
                      <td className="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {item.description || '-'}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => {
                            setDeleteError('');
                            setDeleteTarget(item);
                          }}
                          className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                          title="Eşyayı Sil"
                        >
                          <XMarkIcon className="h-5 w-5" />
                        </button>
                      </td>
                    </tr>
                  ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        ) : null;
      })()}

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
                    Eşyayı Sil
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
                  <strong className="text-gray-900 dark:text-white">{deleteTarget.name}</strong> eşyasını
                  silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
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
                        await itemsApi.remove(deleteTarget.id);
                        setDeleteTarget(null);
                        const data = await itemsApi.getByCustomerId(id!);
                        setItems(data || []);
                      } catch (err: any) {
                        setDeleteError(
                          err.response?.data?.message || 'Eşya silinirken bir hata oluştu',
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

      {/* Aktif Sözleşmeler */}
      <div className="modern-card-gradient p-6">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
          <DocumentTextIcon className="h-5 w-5 mr-2" />
          Aktif Sözleşmeler ({filteredContracts.length})
        </h2>
        {filteredContracts.length === 0 ? (
          <p className="text-gray-600 dark:text-gray-400">Aktif sözleşme bulunmamaktadır.</p>
        ) : (
          <div className="space-y-4">
            {filteredContracts.map((contract: any) => {
              const start = new Date(contract.start_date);
              const end = new Date(contract.end_date);
              const months = Math.round(
                (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24 * 30),
              );
              const now = new Date();
              const remainingDays = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

              return (
                <div
                  key={contract.id}
                  className="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                >
                  <div className="flex justify-between items-start mb-2">
                    <div>
                      <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                        {contract.contract_number}
                      </h3>
                      <p className="text-xs text-gray-500 dark:text-gray-400">
                        Oda: {contract.room?.room_number || '-'}
                      </p>
                    </div>
                    <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                      {months} Aylık
                    </span>
                  </div>
                  <div className="grid grid-cols-2 gap-4 mt-3 text-sm">
                    <div>
                      <p className="text-gray-500 dark:text-gray-400">Başlangıç</p>
                      <p className="text-gray-900 dark:text-white">
                        {start.toLocaleDateString('tr-TR')}
                      </p>
                    </div>
                    <div>
                      <p className="text-gray-500 dark:text-gray-400">Bitiş</p>
                      <p className="text-gray-900 dark:text-white">
                        {end.toLocaleDateString('tr-TR')}
                      </p>
                    </div>
                    <div>
                      <p className="text-gray-500 dark:text-gray-400">Aylık Fiyat</p>
                      <p className="text-gray-900 dark:text-white">
                        {formatTurkishCurrency(Number(contract.monthly_price))}
                      </p>
                    </div>
                    <div>
                      <p className="text-gray-500 dark:text-gray-400">Kalan Süre</p>
                      <p
                        className={`font-medium ${
                          remainingDays < 30
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-gray-900 dark:text-white'
                        }`}
                      >
                        {remainingDays > 0 ? `${remainingDays} gün` : 'Süresi dolmuş'}
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
  );
}
