import { useState, useEffect } from 'react';
import { XMarkIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import { apiClient } from '../../services/api/apiClient';
import { companiesApi, Company } from '../../services/api/companiesApi';
import { useAuthStore } from '../../stores/authStore';
import { formatPhoneNumber, unformatPhoneNumber, isValidEmail, isValidPhoneNumber } from '../../utils/inputFormatters';

interface AddStaffModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export function AddStaffModal({ isOpen, onClose, onSuccess }: AddStaffModalProps) {
  const user = useAuthStore((s) => s.user);
  const isSuperAdmin = user?.role === 'super_admin';

  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    role: 'company_staff',
    company_id: '',
  });
  const [companies, setCompanies] = useState<Company[]>([]);
  const [companiesLoading, setCompaniesLoading] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<{ email?: string; phone?: string }>({});

  useEffect(() => {
    if (isOpen && isSuperAdmin) {
      setCompaniesLoading(true);
      companiesApi
        .getAll()
        .then(setCompanies)
        .catch(() => setCompanies([]))
        .finally(() => setCompaniesLoading(false));
    }
  }, [isOpen, isSuperAdmin]);

  const generatePassword = () => {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let retVal = "";
    for (let i = 0, n = charset.length; i < length; ++i) {
      retVal += charset.charAt(Math.floor(Math.random() * n));
    }
    setFormData({ ...formData, password: retVal });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setFieldErrors({});
    
    // Validate email
    if (!isValidEmail(formData.email)) {
      setFieldErrors({ email: 'Lütfen geçerli bir e-posta adresi girin.' });
      return;
    }
    
    // Validate phone if provided
    if (formData.phone && !isValidPhoneNumber(formData.phone)) {
      setFieldErrors({ phone: 'Lütfen geçerli bir telefon numarası girin. (Format: 0XXX XXX XX XX)' });
      return;
    }

    if (isSuperAdmin && !formData.company_id) {
      setError('Personel eklemek için şirket seçmelisiniz.');
      return;
    }

    setLoading(true);

    try {
      const submitData: Record<string, unknown> = {
        ...formData,
        phone: formData.phone ? unformatPhoneNumber(formData.phone) : '',
      };
      if (isSuperAdmin && formData.company_id) {
        submitData.company_id = formData.company_id;
      }
      await apiClient.post('/auth/register', submitData);
      onSuccess();
      onClose();
      setFormData({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        password: '',
        role: 'company_staff',
        company_id: '',
      });
    } catch (err: any) {
      const msg = err.response?.data?.message;
      const message = Array.isArray(msg) ? msg[0] : msg;
      setError(message || 'Personel eklenirken bir hata oluştu. Lütfen oturumunuzun açık olduğundan emin olup tekrar deneyin.');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onClick={onClose} />

        <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                Yeni Kullanıcı Ekle
              </h3>
              <button
                onClick={onClose}
                className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
              >
                <XMarkIcon className="h-6 w-6" />
              </button>
            </div>

            {error && (
              <div className="mb-4 p-3 text-sm text-red-700 bg-red-100 border border-red-400 rounded dark:bg-red-900 dark:text-red-200">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Ad *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.first_name}
                    onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Soyad *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.last_name}
                    onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  E-posta *
                </label>
                <input
                  type="email"
                  required
                  value={formData.email}
                  onChange={(e) => {
                    const value = e.target.value;
                    // Only allow email format characters
                    if (value === '' || /^[^\s@]*@?[^\s@]*\.?[^\s@]*$/.test(value)) {
                      setFormData({ ...formData, email: value });
                      if (fieldErrors.email) setFieldErrors({ ...fieldErrors, email: undefined });
                    }
                  }}
                  onBlur={(e) => {
                    if (e.target.value && !isValidEmail(e.target.value)) {
                      setFieldErrors({ ...fieldErrors, email: 'Lütfen geçerli bir e-posta adresi girin.' });
                    } else {
                      setFieldErrors({ ...fieldErrors, email: undefined });
                    }
                  }}
                  className={`w-full px-3 py-2 border ${fieldErrors.email ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'} rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white`}
                />
                {fieldErrors.email && (
                  <p className="mt-1 text-sm text-red-600 dark:text-red-400">{fieldErrors.email}</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Telefon
                </label>
                <input
                  type="tel"
                  placeholder="0XXX XXX XX XX"
                  value={formData.phone}
                  onChange={(e) => {
                    const formatted = formatPhoneNumber(e.target.value);
                    setFormData({ ...formData, phone: formatted });
                    if (fieldErrors.phone) setFieldErrors({ ...fieldErrors, phone: undefined });
                  }}
                  onBlur={(e) => {
                    if (e.target.value && !isValidPhoneNumber(e.target.value)) {
                      setFieldErrors({ ...fieldErrors, phone: 'Lütfen geçerli bir telefon numarası girin. (Format: 0XXX XXX XX XX)' });
                    } else {
                      setFieldErrors({ ...fieldErrors, phone: undefined });
                    }
                  }}
                  className={`w-full px-3 py-2 border ${fieldErrors.phone ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'} rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white`}
                />
                {fieldErrors.phone && (
                  <p className="mt-1 text-sm text-red-600 dark:text-red-400">{fieldErrors.phone}</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Şifre *
                </label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    required
                    value={formData.password}
                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    className="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white font-mono"
                  />
                  <button
                    type="button"
                    onClick={generatePassword}
                    className="px-3 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-1 text-sm whitespace-nowrap"
                    title="Otomatik Şifre Oluştur"
                  >
                    <ArrowPathIcon className="h-4 w-4" />
                    Oluştur
                  </button>
                </div>
              </div>

              {isSuperAdmin && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Şirket *
                  </label>
                  <select
                    required
                    value={formData.company_id}
                    onChange={(e) => setFormData({ ...formData, company_id: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                    disabled={companiesLoading}
                  >
                    <option value="">Şirket seçin</option>
                    {companies.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name}
                      </option>
                    ))}
                  </select>
                  {companies.length === 0 && !companiesLoading && (
                    <p className="mt-1 text-sm text-amber-600 dark:text-amber-400">Henüz şirket bulunmuyor. Önce bir şirket oluşturun.</p>
                  )}
                </div>
              )}

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Rol *
                </label>
                <select
                  required
                  value={formData.role}
                  onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="company_staff">Personel</option>
                  <option value="company_owner">Depo Sahibi</option>
                  <option value="data_entry">Veri Girişi</option>
                  <option value="accounting">Muhasebe</option>
                  {isSuperAdmin && <option value="super_admin">Süper Admin</option>}
                </select>
              </div>

              <div className="flex justify-end space-x-3 pt-4">
                <button
                  type="button"
                  onClick={onClose}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  className="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                >
                  {loading ? 'Kaydediliyor...' : 'Kullanıcı Ekle'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
