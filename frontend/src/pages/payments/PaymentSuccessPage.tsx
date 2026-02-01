import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { CheckCircleIcon } from '@heroicons/react/24/outline';
import { paymentsApi } from '../../services/api/paymentsApi';
import { formatTurkishCurrency } from '../../utils/inputFormatters';

export function PaymentSuccessPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [payment, setPayment] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const paymentId = searchParams.get('payment_id');

  useEffect(() => {
    if (paymentId) {
      loadPayment();
    } else {
      setLoading(false);
    }
  }, [paymentId]);

  const loadPayment = async () => {
    try {
      const data = await paymentsApi.getById(paymentId!);
      setPayment(data);
    } catch (error) {
      console.error('Error loading payment:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto mt-8">
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-8 text-center">
        <div className="flex justify-center mb-4">
          <div className="rounded-full bg-green-100 dark:bg-green-900/20 p-3">
            <CheckCircleIcon className="h-12 w-12 text-green-600 dark:text-green-400" />
          </div>
        </div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Ödeme Başarılı!
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mb-6">
          Ödemeniz başarıyla alınmıştır.
        </p>
        {payment && (
          <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6 text-left">
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-600 dark:text-gray-400">Ödeme No:</span>
                <span className="font-medium text-gray-900 dark:text-white">{payment.payment_number}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600 dark:text-gray-400">Tutar:</span>
                <span className="font-medium text-gray-900 dark:text-white">
                  {formatTurkishCurrency(Number(payment.amount))}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600 dark:text-gray-400">Durum:</span>
                <span className="font-medium text-green-600 dark:text-green-400">Ödendi</span>
              </div>
            </div>
          </div>
        )}
        <button
          onClick={() => navigate('/payments')}
          className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
        >
          Ödemeler Sayfasına Dön
        </button>
      </div>
    </div>
  );
}
