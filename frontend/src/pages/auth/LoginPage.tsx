import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../stores/authStore';
import { useCompanyStore } from '../../stores/companyStore';
import { EnvelopeIcon, LockClosedIcon, EyeIcon, EyeSlashIcon, SunIcon, MoonIcon } from '@heroicons/react/24/outline';
import { useTheme } from '../../contexts/ThemeContext';
import { paths } from '../../routes/paths';

export function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const { login } = useAuthStore();
  const { theme, toggleTheme } = useTheme();
  const navigate = useNavigate();
  const { publicBrand, loadPublicBrand } = useCompanyStore();
  const projectName = publicBrand?.project_name?.trim() || 'DepoPazar';

  useEffect(() => {
    loadPublicBrand();
  }, [loadPublicBrand]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(email, password);
      const { user } = useAuthStore.getState();
      if (user?.role === 'customer') {
        navigate(paths.musteri.genelBakis);
      } else {
        navigate(paths.genelBakis);
      }
    } catch (err: unknown) {
      const message = err && typeof err === 'object' && 'response' in err
        ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined;
      setError(message || 'Giriş başarısız');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex font-sans antialiased">
      {/* Sol panel - Marka alanı (masaüstü) */}
      <div className="hidden lg:flex lg:w-[48%] xl:w-[52%] relative overflow-hidden flex-col justify-between p-10 xl:p-14 bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 dark:from-emerald-700 dark:via-emerald-800 dark:to-teal-900">
        {/* Dekoratif şekiller */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl animate-float" />
          <div className="absolute top-1/3 -left-20 w-72 h-72 rounded-full bg-teal-400/20 blur-2xl animate-float" style={{ animationDelay: '-2s' }} />
          <div className="absolute bottom-20 right-1/4 w-64 h-64 rounded-full bg-white/5 blur-2xl animate-float" style={{ animationDelay: '-4s' }} />
          <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full border border-white/5" />
          <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] rounded-full border border-white/5" />
          <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[200px] h-[200px] rounded-full border border-white/10" />
        </div>

        <div className="relative z-10">
          <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/20 shadow-xl mb-8">
            <svg className="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-7.963-3.962L4.5 7.5m15 0v10.125l-7.963-3.962L4.5 17.625M20.25 7.5L12 11.25 4.5 7.5M12 11.25v10.125" />
            </svg>
          </div>
          <h1 className="text-3xl xl:text-4xl font-bold text-white tracking-tight mb-2">
            {projectName}
          </h1>
          <p className="text-emerald-100/90 text-lg max-w-sm">
            Depo yönetiminizi tek ekrandan takip edin. Sözleşmeler, ödemeler ve stok bir arada.
          </p>
        </div>

        <div className="relative z-10 text-white/60 text-sm">
          © {new Date().getFullYear()} {projectName}
        </div>
      </div>

      {/* Sağ panel - Form */}
      <div className="flex-1 flex flex-col items-center justify-center min-h-screen bg-[#f8faf9] dark:bg-[#0a0c0b] py-10 px-4 sm:px-6 lg:px-12 relative">
        {/* Arka plan dokusu - mobil ve form tarafı */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute top-0 right-0 w-[80%] sm:w-[60%] h-[40%] bg-gradient-to-bl from-emerald-500/5 to-transparent dark:from-emerald-600/10 rounded-bl-[100px]" />
          <div className="absolute bottom-0 left-0 w-[70%] h-[30%] bg-gradient-to-tr from-teal-500/5 to-transparent dark:from-teal-600/10 rounded-tr-[80px]" />
          <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-gray-100/50 via-transparent to-transparent dark:from-white/[0.02] dark:via-transparent" />
        </div>

        {/* Tema butonu */}
        <div className="absolute top-4 right-4 sm:top-6 sm:right-6 z-20">
          <button
            onClick={toggleTheme}
            className="p-2.5 rounded-xl text-gray-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 bg-white/80 dark:bg-white/5 border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-md backdrop-blur-sm transition-all duration-200"
            title={theme === 'light' ? 'Koyu moda geç' : 'Açık moda geç'}
          >
            {theme === 'light' ? <MoonIcon className="h-5 w-5" /> : <SunIcon className="h-5 w-5" />}
          </button>
        </div>

        <div className="w-full max-w-[400px] relative z-10">
          {/* Mobilde logo */}
          <div className="lg:hidden text-center mb-8 animate-fade-in-up">
            <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-600 shadow-lg shadow-emerald-500/25 mb-4">
              <svg className="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-7.963-3.962L4.5 7.5m15 0v10.125l-7.963-3.962L4.5 17.625M20.25 7.5L12 11.25 4.5 7.5M12 11.25v10.125" />
              </svg>
            </div>
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white">{projectName}</h2>
            <p className="text-xs font-medium text-gray-500 dark:text-zinc-500 uppercase tracking-widest mt-1">Depo yönetim sistemi</p>
          </div>

          {/* Form kartı */}
          <div
            className="opacity-0 rounded-2xl sm:rounded-3xl border border-gray-200/80 dark:border-white/10 bg-white/80 dark:bg-[#111312]/90 shadow-xl shadow-gray-200/50 dark:shadow-none backdrop-blur-xl p-6 sm:p-8 animate-fade-in-up"
            style={{ animationDelay: '0.1s', animationFillMode: 'forwards' }}
          >
            <div className="hidden lg:block mb-6">
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Hoş geldiniz</h2>
              <p className="text-sm text-gray-500 dark:text-zinc-400 mt-0.5">Hesabınıza giriş yapın</p>
            </div>

            <form className="space-y-5" onSubmit={handleSubmit}>
              {error && (
                <div
                  className="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200/80 dark:border-red-500/20 p-3 flex items-center gap-2 animate-fade-in"
                  role="alert"
                >
                  <svg className="h-4 w-4 text-red-500 dark:text-red-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                  </svg>
                  <p className="text-sm font-medium text-red-800 dark:text-red-300">{error}</p>
                </div>
              )}

              <div className="opacity-0 space-y-2 animate-fade-in-up" style={{ animationDelay: '0.15s', animationFillMode: 'forwards' }}>
                <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                  E-posta
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 dark:text-zinc-500">
                    <EnvelopeIcon className="h-5 w-5" />
                  </div>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    pattern="[^\s@]+@[^\s@]+\.[^\s@]+"
                    className="block w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 text-gray-900 dark:text-zinc-100 placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 dark:focus:ring-emerald-400/20 dark:focus:border-emerald-400 transition-all duration-200 text-sm"
                    placeholder="ornek@email.com"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                  />
                </div>
              </div>

              <div className="opacity-0 space-y-2 animate-fade-in-up" style={{ animationDelay: '0.2s', animationFillMode: 'forwards' }}>
                <label htmlFor="password" className="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                  Şifre
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 dark:text-zinc-500">
                    <LockClosedIcon className="h-5 w-5" />
                  </div>
                  <input
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    required
                    className="block w-full pl-11 pr-11 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 text-gray-900 dark:text-zinc-100 placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 dark:focus:ring-emerald-400/20 dark:focus:border-emerald-400 transition-all duration-200 text-sm"
                    placeholder="••••••••"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-emerald-400 transition-colors"
                    aria-label={showPassword ? 'Şifreyi gizle' : 'Şifreyi göster'}
                  >
                    {showPassword ? <EyeSlashIcon className="h-5 w-5" /> : <EyeIcon className="h-5 w-5" />}
                  </button>
                </div>
              </div>

              <div className="opacity-0 pt-1 animate-fade-in-up" style={{ animationDelay: '0.25s', animationFillMode: 'forwards' }}>
                <button
                  type="submit"
                  disabled={loading}
                  className="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 dark:focus:ring-offset-[#0a0c0b] disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/30 transition-all duration-200 active:scale-[0.99]"
                >
                  {loading ? (
                    <>
                      <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden>
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                      </svg>
                      Giriş yapılıyor...
                    </>
                  ) : (
                    'Giriş Yap'
                  )}
                </button>
              </div>
            </form>
          </div>

          <p className="mt-8 text-center text-xs text-gray-400 dark:text-zinc-600">
            © {new Date().getFullYear()} {projectName}
          </p>
        </div>
      </div>
    </div>
  );
}
