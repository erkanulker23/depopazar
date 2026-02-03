import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { paths } from '../../routes/paths';
import { usersApi, User } from '../../services/api/usersApi';
import { ArrowLeftIcon, KeyIcon, UserIcon, PhoneIcon, EnvelopeIcon, CalendarIcon, BriefcaseIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export function StaffDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [resettingPassword, setResettingPassword] = useState(false);
  const [newPassword, setNewPassword] = useState('');
  const [generatedPassword, setGeneratedPassword] = useState('');

  useEffect(() => {
    if (id) {
      loadUser(id);
    }
  }, [id]);

  const loadUser = async (userId: string) => {
    try {
      setLoading(true);
      const data = await usersApi.getById(userId);
      setUser(data);
    } catch (error: any) {
      toast.error('Kullanıcı bilgileri yüklenemedi');
      navigate(paths.kullanicilar);
    } finally {
      setLoading(false);
    }
  };

  const handleResetPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!id) return;

    if (!confirm('Kullanıcının şifresini sıfırlamak istediğinize emin misiniz?')) return;

    try {
      setResettingPassword(true);
      const result = await usersApi.resetPassword(id, newPassword || undefined);
      toast.success('Şifre başarıyla sıfırlandı');
      if (result.password) {
        setGeneratedPassword(result.password);
      }
      setNewPassword('');
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Şifre sıfırlanırken bir hata oluştu');
    } finally {
      setResettingPassword(false);
    }
  };

  if (loading) {
    return <div className="p-8 text-center">Yükleniyor...</div>;
  }

  if (!user) {
    return <div className="p-8 text-center">Kullanıcı bulunamadı.</div>;
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div className="flex items-center gap-4">
        <button 
          onClick={() => navigate(paths.kullanicilar)}
          className="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
        >
          <ArrowLeftIcon className="w-5 h-5 text-gray-500" />
        </button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Kullanıcı Detayı</h1>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* User Info Card */}
        <div className="md:col-span-2 bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div className="flex items-center gap-4 mb-6">
            <div className="h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
              <UserIcon className="h-8 w-8 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                {user.first_name} {user.last_name}
              </h2>
              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1
                ${user.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'}
              `}>
                {user.is_active ? 'Aktif' : 'Pasif'}
              </span>
            </div>
          </div>

          <div className="space-y-4">
            <div className="flex items-center gap-3 text-gray-600 dark:text-gray-300">
              <EnvelopeIcon className="h-5 w-5" />
              <span>{user.email}</span>
            </div>
            <div className="flex items-center gap-3 text-gray-600 dark:text-gray-300">
              <PhoneIcon className="h-5 w-5" />
              <span>{user.phone || '-'}</span>
            </div>
            <div className="flex items-center gap-3 text-gray-600 dark:text-gray-300">
              <BriefcaseIcon className="h-5 w-5" />
              <span className="capitalize">{user.role.replace('_', ' ')}</span>
            </div>
            <div className="flex items-center gap-3 text-gray-600 dark:text-gray-300">
              <CalendarIcon className="h-5 w-5" />
              <span>Kayıt: {new Date(user.created_at).toLocaleDateString('tr-TR')}</span>
            </div>
          </div>
        </div>

        {/* Actions Card */}
        <div className="space-y-6">
          {/* Password Reset */}
          <div className="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <KeyIcon className="h-5 w-5" />
              Şifre Sıfırla
            </h3>
            
            <form onSubmit={handleResetPassword} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Yeni Şifre (Opsiyonel)
                </label>
                <input
                  type="text"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  placeholder="Otomatik oluşturmak için boş bırakın"
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm"
                />
              </div>
              <button
                type="submit"
                disabled={resettingPassword}
                className="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 disabled:opacity-50 transition-colors text-sm font-medium"
              >
                {resettingPassword ? 'Sıfırlanıyor...' : 'Şifreyi Sıfırla'}
              </button>
            </form>

            {generatedPassword && (
              <div className="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p className="text-sm text-green-800 dark:text-green-300 font-medium">Yeni Şifre:</p>
                <p className="text-lg font-mono font-bold text-green-900 dark:text-green-200 select-all">{generatedPassword}</p>
                <p className="text-xs text-green-600 dark:text-green-400 mt-1">Lütfen bu şifreyi kaydedin, tekrar gösterilmeyecektir.</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
