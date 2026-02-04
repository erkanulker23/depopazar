import { useState, useEffect } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { apiClient } from '../../services/api/apiClient';
import { useAuthStore } from '../../stores/authStore';
import { formatPhoneNumber, unformatPhoneNumber, isValidEmail, isValidPhoneNumber } from '../../utils/inputFormatters';

interface EditStaffModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  staff: any;
}

export function EditStaffModal({
  isOpen,
  onClose,
  onSuccess,
  staff,
}: EditStaffModalProps) {
  const user = useAuthStore((s) => s.user);
  const isSuperAdmin = user?.role === 'super_admin';

  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    role: 'company_staff',
    is_active: true,
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<{ email?: string; phone?: string }>({});

  useEffect(() => {
    if (staff) {
      setFormData({
        first_name: staff.first_name || '',
        last_name: staff.last_name || '',
        email: staff.email || '',
        phone: staff.phone || '',
        password: '', // Don't pre-fill password
        role: staff.role || 'company_staff',
        is_active: staff.is_active ?? true,
      });
    }
  }, [staff]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setFieldErrors({});
    
    // Validate email
    if (formData.email && !isValidEmail(formData.email)) {
      setFieldErrors({ email: 'Lütfen geçerli bir e-posta adresi girin.' });
      return;
    }
    
    // Validate phone if provided
    if (formData.phone && !isValidPhoneNumber(formData.phone)) {
      setFieldErrors({ phone: 'Lütfen geçerli bir telefon numarası girin. (Format: 0XXX XXX XX XX)' });
      return;
    }
    
    setLoading(true);

    try {
      // Only include password if it's been changed
      const updateData: any = {
        first_name: formData.first_name,
        last_name: formData.last_name,
        email: formData.email,
        phone: formData.phone ? unformatPhoneNumber(formData.phone) : '',
        role: formData.role,
        is_active: formData.is_active,
      };

      // Only include password if it's not empty
      if (formData.password) {
        updateData.password = formData.password;
      }

      await apiClient.patch(`/users/${staff.id}`, updateData);
      onSuccess();
      onClose();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Personel güncellenirken bir hata oluştu');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen || !staff) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={onClose}></div>

        <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-medium text-gray-900 dark:text-white">Kullanıcı Düzenle</h3>
              <button
                onClick={onClose}
                className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
              >
                <XMarkIcon className="h-6 w-6" />
              </button>
            </div>

            {error && (
              <div className="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                <p className="text-sm text-red-800 dark:text-red-200">{error}</p>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
              <div>
                <label htmlFor="edit-first-name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Ad *
                </label>
                <input
                  id="edit-first-name"
                  type="text"
                  required
                  value={formData.first_name}
                  onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
                <div>
                  <label htmlFor="edit-last-name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Soyad *
                  </label>
                  <input
                    id="edit-last-name"
                    type="text"
                    required
                    value={formData.last_name}
                    onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label htmlFor="edit-email" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  E-posta *
                </label>
                <input
                  id="edit-email"
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
                <label htmlFor="edit-phone" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Telefon
                </label>
                <input
                  id="edit-phone"
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
                <label htmlFor="edit-password" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Şifre (Değiştirmek için doldurun)
                </label>
                <input
                  id="edit-password"
                  type="password"
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  placeholder="Boş bırakılırsa değiştirilmez"
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div>
                <label htmlFor="edit-role" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Rol *
                </label>
                <select
                  id="edit-role"
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

              <div>
                <label className="flex items-center">
                  <input
                    type="checkbox"
                    checked={formData.is_active}
                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                    className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700"
                  />
                  <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                </label>
              </div>

              <div className="flex justify-end space-x-3 pt-4">
                <button
                  type="button"
                  onClick={onClose}
                  className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                >
                  {loading ? 'Güncelleniyor...' : 'Güncelle'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
