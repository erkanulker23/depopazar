import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { reportsApi } from '../../services/api/reportsApi';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import { CurrencyDollarIcon, BuildingOfficeIcon, CreditCardIcon } from '@heroicons/react/24/outline';

const MONTHS = [
  'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
  'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık',
];

export function ReportsPage() {
  const navigate = useNavigate();
  const now = new Date();
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [occupancy, setOccupancy] = useState<{
    total_rooms: number;
    occupied_rooms: number;
    empty_rooms: number;
    occupancy_rate: number;
  } | null>(null);
  const [revenue, setRevenue] = useState<{
    total_revenue: number;
    total_payments: number;
    payments?: { id: string; amount: number; paid_at: string; contract_number: string }[];
  } | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      setError(null);
      try {
        const [occ, rev] = await Promise.all([
          reportsApi.getOccupancy(),
          reportsApi.getRevenue(year, month),
        ]);
        setOccupancy(occ);
        setRevenue(rev);
      } catch (e: any) {
        setError(e?.response?.data?.message || e?.message || 'Raporlar yüklenemedi.');
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [year, month]);

  const years = [now.getFullYear(), now.getFullYear() - 1, now.getFullYear() - 2];

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold gradient-text mb-2">Raporlar</h1>
        <p className="text-gray-600 dark:text-gray-400">Doluluk ve gelir raporları</p>
      </div>

      <div className="mb-6 flex flex-wrap gap-4 items-center">
        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yıl</label>
          <select
            value={year}
            onChange={(e) => setYear(Number(e.target.value))}
            className="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            {years.map((y) => (
              <option key={y} value={y}>{y}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ay</label>
          <select
            value={month}
            onChange={(e) => setMonth(Number(e.target.value))}
            className="w-40 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            {MONTHS.map((m, i) => (
              <option key={i} value={i + 1}>{m}</option>
            ))}
          </select>
        </div>
      </div>

      {error && (
        <div className="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200">
          {error}
        </div>
      )}

      {/* Bank Account Payments Report Link */}
      <div className="mb-6">
        <button
          onClick={() => navigate('/reports/bank-accounts')}
          className="modern-card p-6 w-full text-left hover:shadow-lg transition-shadow cursor-pointer group"
        >
          <div className="flex items-center gap-3">
            <div className="p-3 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl shadow-lg">
              <CreditCardIcon className="h-6 w-6 text-white" />
            </div>
            <div className="flex-1">
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                Banka Hesap Raporu
              </h3>
              <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Hangi banka hesabına ne kadar para girmiş, ne zaman, hangi müşteriden – tüm detaylar
              </p>
            </div>
            <div className="text-primary-600 dark:text-primary-400 group-hover:translate-x-1 transition-transform">
              →
            </div>
          </div>
        </button>
      </div>

      {loading ? (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="modern-card p-6">
            <div className="flex items-center gap-2 mb-4">
              <BuildingOfficeIcon className="h-6 w-6 text-primary-500" />
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Doluluk Raporu</h2>
            </div>
            {occupancy && (
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Toplam oda</span>
                  <span className="font-medium">{occupancy.total_rooms}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Dolu</span>
                  <span className="font-medium text-green-600 dark:text-green-400">{occupancy.occupied_rooms}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Boş</span>
                  <span className="font-medium text-gray-500">{occupancy.empty_rooms}</span>
                </div>
                <div className="pt-3 border-t border-gray-200 dark:border-gray-600 flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Doluluk oranı</span>
                  <span className="font-semibold text-primary-600 dark:text-primary-400">
                    %{occupancy.occupancy_rate.toFixed(1)}
                  </span>
                </div>
              </div>
            )}
          </div>

          <div className="modern-card p-6">
            <div className="flex items-center gap-2 mb-4">
              <CurrencyDollarIcon className="h-6 w-6 text-primary-500" />
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                Gelir Raporu ({MONTHS[month - 1]} {year})
              </h2>
            </div>
            {revenue && (
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-gray-600 dark:text-gray-400">Toplam gelir</span>
                  <span className="text-xl font-bold text-primary-600 dark:text-primary-400">
                    {formatTurkishCurrency(revenue.total_revenue)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Ödeme sayısı</span>
                  <span className="font-medium">{revenue.total_payments}</span>
                </div>
                {revenue.payments && revenue.payments.length > 0 && (
                  <div className="pt-3 border-t border-gray-200 dark:border-gray-600">
                    <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ödemeler</p>
                    <div className="max-h-48 overflow-y-auto space-y-1">
                      {revenue.payments.map((p: any) => (
                        <div
                          key={p.id}
                          className="flex justify-between text-sm text-gray-600 dark:text-gray-400"
                        >
                          <span>{p.contract_number}</span>
                          <span>{formatTurkishCurrency(Number(p.amount))}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
