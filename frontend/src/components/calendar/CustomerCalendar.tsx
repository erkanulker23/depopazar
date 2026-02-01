import { useState } from 'react';
import {
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/react/24/outline';
import { formatTurkishCurrency } from '../../utils/inputFormatters';

interface CustomerCalendarProps {
  contracts: any[];
  payments: any[];
}

interface PaymentDetailModalProps {
  payment: any;
  isOpen: boolean;
  onClose: () => void;
}

function PaymentDetailModal({ payment, isOpen, onClose }: PaymentDetailModalProps) {
  if (!isOpen || !payment) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={onClose}></div>
        <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
          <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Ödeme Detayları
            </h3>
            <div className="space-y-3">
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Ödeme No
                </label>
                <p className="text-sm text-gray-900 dark:text-white">{payment.payment_number}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Tutar</label>
                <p className="text-sm text-gray-900 dark:text-white">
                  {formatTurkishCurrency(Number(payment.amount))}
                </p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Vade Tarihi
                </label>
                <p className="text-sm text-gray-900 dark:text-white">
                  {new Date(payment.due_date).toLocaleDateString('tr-TR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                  })}
                </p>
              </div>
              {payment.paid_at && (
                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Ödeme Tarihi
                  </label>
                  <p className="text-sm text-gray-900 dark:text-white">
                    {new Date(payment.paid_at).toLocaleDateString('tr-TR', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                    })}
                  </p>
                </div>
              )}
              {payment.payment_method && (
                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Ödeme Yöntemi
                  </label>
                  <p className="text-sm text-gray-900 dark:text-white">
                    {payment.payment_method === 'credit_card'
                      ? 'Kredi Kartı'
                      : payment.payment_method === 'bank_transfer'
                        ? 'Banka Havalesi'
                        : payment.payment_method === 'cash'
                          ? 'Nakit'
                          : payment.payment_method}
                  </p>
                </div>
              )}
              {payment.transaction_id && (
                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    İşlem No
                  </label>
                  <p className="text-sm text-gray-900 dark:text-white">{payment.transaction_id}</p>
                </div>
              )}
              {payment.notes && (
                <div>
                  <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Notlar</label>
                  <p className="text-sm text-gray-900 dark:text-white">{payment.notes}</p>
                </div>
              )}
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Durum</label>
                <p className="text-sm">
                  <span
                    className={`px-2 py-1 rounded-full text-xs font-semibold ${
                      payment.status === 'paid'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                        : payment.status === 'overdue'
                          ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                          : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                    }`}
                  >
                    {payment.status === 'paid'
                      ? 'Ödendi'
                      : payment.status === 'overdue'
                        ? 'Gecikmiş'
                        : 'Beklemede'}
                  </span>
                </p>
              </div>
            </div>
            <div className="mt-6 flex justify-end">
              <button
                onClick={onClose}
                className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                Kapat
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export function CustomerCalendar({ contracts, payments }: CustomerCalendarProps) {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedPayment, setSelectedPayment] = useState<any>(null);
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);

  const year = currentDate.getFullYear();

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

  const prevYear = () => {
    setCurrentDate(new Date(year - 1, 0, 1));
  };

  const nextYear = () => {
    setCurrentDate(new Date(year + 1, 0, 1));
  };

  // Get payments grouped by month (only paid payments show the day from paid_at date)
  const getPaymentsByMonth = () => {
    const paymentsByMonth: {
      [key: string]: {
        month: number;
        year: number;
        monthName: string;
        payments: any[];
        paidPayments: any[];
      };
    } = {};

    // Get all unique months from contracts
    contracts.forEach((contract) => {
      const start = new Date(contract.start_date);
      const end = new Date(contract.end_date);
      const current = new Date(start);

      while (current <= end) {
        const monthKey = `${current.getFullYear()}-${String(current.getMonth() + 1).padStart(2, '0')}`;
        if (!paymentsByMonth[monthKey]) {
          paymentsByMonth[monthKey] = {
            month: current.getMonth() + 1,
            year: current.getFullYear(),
            monthName: monthNames[current.getMonth()],
            payments: [],
            paidPayments: [],
          };
        }
        current.setMonth(current.getMonth() + 1);
      }
    });

    // Add payments to their respective months based on paid_at date (for paid) or due_date (for unpaid)
    payments.forEach((payment) => {
      if (payment.status === 'paid' && payment.paid_at) {
        // For paid payments, use the paid_at month
        const paidDate = new Date(payment.paid_at);
        const monthKey = `${paidDate.getFullYear()}-${String(paidDate.getMonth() + 1).padStart(2, '0')}`;
        
        if (!paymentsByMonth[monthKey]) {
          paymentsByMonth[monthKey] = {
            month: paidDate.getMonth() + 1,
            year: paidDate.getFullYear(),
            monthName: monthNames[paidDate.getMonth()],
            payments: [],
            paidPayments: [],
          };
        }
        paymentsByMonth[monthKey].payments.push(payment);
        paymentsByMonth[monthKey].paidPayments.push(payment);
      } else {
        // For unpaid payments, use the due_date month
        const dueDate = new Date(payment.due_date);
        const monthKey = `${dueDate.getFullYear()}-${String(dueDate.getMonth() + 1).padStart(2, '0')}`;
        
        if (paymentsByMonth[monthKey]) {
          paymentsByMonth[monthKey].payments.push(payment);
        }
      }
    });

    return paymentsByMonth;
  };

  const paymentsByMonth = getPaymentsByMonth();

  const renderMonthCalendar = () => {
    const months = [];
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;

    for (let monthIndex = 0; monthIndex < 12; monthIndex++) {
      const monthKey = `${year}-${String(monthIndex + 1).padStart(2, '0')}`;
      const monthData = paymentsByMonth[monthKey];
      const isCurrentMonth = year === currentYear && monthIndex + 1 === currentMonth;
      const hasPayments = monthData && (monthData.payments?.length > 0 || monthData.paidPayments?.length > 0);

      // Get all unique day numbers from ALL payments in this month (paid and unpaid)
      const paymentDays: number[] = [];
      const allPaymentsInMonth = monthData?.payments || [];
      
      if (allPaymentsInMonth.length > 0) {
        allPaymentsInMonth.forEach((payment: any) => {
          let day: number | null = null;
          // For paid payments, use paid_at date
          if (payment.status === 'paid' && payment.paid_at) {
            day = new Date(payment.paid_at).getDate();
          } 
          // For unpaid payments, use due_date
          else if (payment.due_date) {
            day = new Date(payment.due_date).getDate();
          }
          
          if (day && !paymentDays.includes(day)) {
            paymentDays.push(day);
          }
        });
        paymentDays.sort((a, b) => a - b);
      }

      months.push(
        <div
          key={monthIndex}
          className={`border rounded-lg p-4 min-h-[180px] ${
            isCurrentMonth
              ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
              : 'border-gray-200 dark:border-gray-700'
          } ${hasPayments ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : ''}`}
        >
          <div className="text-center">
            <div className="text-sm font-semibold text-gray-900 dark:text-white mb-3">
              {monthNames[monthIndex]}
            </div>
            {hasPayments && paymentDays.length > 0 ? (
              <div className="flex flex-col items-center">
                <div className="flex flex-wrap justify-center gap-1 mb-2">
                  {paymentDays.map((day) => (
                    <div
                      key={`day-${day}`}
                      className="text-xl font-bold text-blue-600 dark:text-blue-400"
                    >
                      {day}
                    </div>
                  ))}
                </div>
                <div className="space-y-2 w-full max-h-48 overflow-y-auto">
                  {/* Show ALL payments, not just paid ones */}
                  {allPaymentsInMonth.map((payment: any) => {
                    // Determine which date to use for display
                    let displayDate: Date;
                    let day: number;
                    let month: number;
                    let year: number;
                    
                    if (payment.status === 'paid' && payment.paid_at) {
                      displayDate = new Date(payment.paid_at);
                    } else {
                      displayDate = new Date(payment.due_date);
                    }
                    
                    day = displayDate.getDate();
                    month = displayDate.getMonth() + 1;
                    year = displayDate.getFullYear();
                    const formattedDate = `${String(day).padStart(2, '0')}.${String(month).padStart(2, '0')}.${year}`;
                    
                    // Check if this is the payment for this month or an intermediate payment
                    const dueDate = new Date(payment.due_date);
                    const isIntermediatePayment = 
                      payment.status === 'paid' && payment.paid_at
                        ? (dueDate.getMonth() !== displayDate.getMonth() || 
                           dueDate.getFullYear() !== displayDate.getFullYear())
                        : false;
                    
                    const getPaymentMethodText = (method: string | null) => {
                      if (!method) return '';
                      switch (method) {
                        case 'credit_card':
                          return 'kredi kartı';
                        case 'bank_transfer':
                          return 'havale';
                        case 'cash':
                          return 'nakit';
                        default:
                          return method;
                      }
                    };

                    const paymentMethodText = getPaymentMethodText(payment.payment_method);

                    return (
                      <div
                        key={payment.id}
                        role="button"
                        tabIndex={0}
                        className="text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-800 rounded px-2 py-2 text-center cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors shadow-sm"
                        onClick={() => {
                          setSelectedPayment(payment);
                          setIsPaymentModalOpen(true);
                        }}
                        onKeyDown={(e) => {
                          if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            setSelectedPayment(payment);
                            setIsPaymentModalOpen(true);
                          }
                        }}
                      >
                        {/* Payment status badge */}
                        <div className="mb-1">
                          <span
                            className={`inline-block px-2 py-0.5 rounded text-xs font-semibold ${
                              payment.status === 'paid'
                                ? isIntermediatePayment
                                  ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                  : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                : payment.status === 'overdue'
                                ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300'
                            }`}
                          >
                            {payment.status === 'paid'
                              ? isIntermediatePayment
                                ? 'Ara Ödeme'
                                : 'Ödendi'
                              : payment.status === 'overdue'
                              ? 'Gecikmiş'
                              : 'Beklemede'}
                          </span>
                        </div>
                        
                        {/* Payment date and method */}
                        {payment.status === 'paid' ? (
                          paymentMethodText ? (
                            <div className="text-gray-700 dark:text-gray-300">
                              <span className="font-medium">{formattedDate}</span> tarihinde{' '}
                              <span className="font-semibold">{paymentMethodText}</span> ile ödendi
                            </div>
                          ) : (
                            <div className="text-gray-700 dark:text-gray-300">
                              <span className="font-medium">{formattedDate}</span> tarihinde ödendi
                            </div>
                          )
                        ) : (
                          <div className="text-gray-700 dark:text-gray-300">
                            Vade: <span className="font-medium">{formattedDate}</span>
                          </div>
                        )}
                        
                        {/* Amount */}
                        <div className="text-gray-600 dark:text-gray-400 mt-1 font-semibold">
                          {formatTurkishCurrency(Number(payment.amount))}
                        </div>
                        
                        {/* Show due date for intermediate payments or unpaid payments */}
                        {(isIntermediatePayment || payment.status !== 'paid') && payment.due_date && (
                          <div className="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Vade: {new Date(payment.due_date).toLocaleDateString('tr-TR', {
                              day: '2-digit',
                              month: '2-digit',
                              year: 'numeric',
                            })}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
                {allPaymentsInMonth.length > 1 && (
                  <div
                    className="text-xs text-blue-600 dark:text-blue-400 cursor-pointer hover:underline mt-2"
                    onClick={() => {
                      if (allPaymentsInMonth.length > 0) {
                        setSelectedPayment(allPaymentsInMonth[0]);
                        setIsPaymentModalOpen(true);
                      }
                    }}
                  >
                    {allPaymentsInMonth.length} ödeme - Tüm Detaylar
                  </div>
                )}
              </div>
            ) : monthData && monthData.payments.length > 0 ? (
              <div className="text-xs text-gray-500 dark:text-gray-400">
                {monthData.payments.filter((p: any) => p.status === 'pending').length} bekleyen
              </div>
            ) : (
              <div className="text-xs text-gray-400 dark:text-gray-600">-</div>
            )}
          </div>
        </div>
      );
    }

    return (
      <div className="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        {months}
      </div>
    );
  };

  // Calculate total months for all contracts
  const totalMonths = contracts.reduce((sum, contract) => {
    const start = new Date(contract.start_date);
    const end = new Date(contract.end_date);
    const months = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24 * 30));
    return sum + months;
  }, 0);

  return (
    <div className="p-6">
      <div className="mb-4">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
            {year} Yılı
          </h3>
          <div className="flex items-center gap-2">
            <button
              onClick={prevYear}
              className="p-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
            >
              <ChevronLeftIcon className="h-5 w-5" />
            </button>
            <button
              onClick={() => setCurrentDate(new Date())}
              className="px-3 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
            >
              Bu Yıl
            </button>
            <button
              onClick={nextYear}
              className="p-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
            >
              <ChevronRightIcon className="h-5 w-5" />
            </button>
          </div>
        </div>
        <div className="text-sm text-gray-600 dark:text-gray-400 mb-4">
          Toplam Kiralama Süresi: <span className="font-semibold">{totalMonths} Ay</span>
        </div>
      </div>

      {renderMonthCalendar()}

      <div className="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div className="flex flex-wrap gap-4 text-xs">
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 bg-blue-100 dark:bg-blue-900 rounded border border-blue-300 dark:border-blue-700"></div>
            <span className="text-gray-600 dark:text-gray-400">Ödeme Yapılan Ay (Gün numarası gösterilir)</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 border border-primary-500 bg-primary-50 dark:bg-primary-900/20 rounded"></div>
            <span className="text-gray-600 dark:text-gray-400">Bu Ay</span>
          </div>
        </div>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-3">
          * Ödeme yapılan aylarda, ödemenin yapıldığı gün numarası gösterilir.
        </p>
      </div>

      <PaymentDetailModal
        payment={selectedPayment}
        isOpen={isPaymentModalOpen}
        onClose={() => {
          setIsPaymentModalOpen(false);
          setSelectedPayment(null);
        }}
      />
    </div>
  );
}
