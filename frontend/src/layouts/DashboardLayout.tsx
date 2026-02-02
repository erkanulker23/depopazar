import { Outlet, Link, useLocation } from 'react-router-dom';
import { useAuthStore } from '../stores/authStore';
import { useCompanyStore } from '../stores/companyStore';
import { useState, useEffect } from 'react';
import {
  HomeIcon,
  BuildingOfficeIcon,
  CubeIcon,
  UsersIcon,
  DocumentTextIcon,
  CreditCardIcon,
  ArrowRightOnRectangleIcon,
  PlusCircleIcon,
  UserGroupIcon,
  BanknotesIcon,
  Bars3Icon,
  XMarkIcon,
  Cog6ToothIcon,
  BellIcon,
  ChartBarIcon,
  TruckIcon,
  ShieldCheckIcon,
  SunIcon,
  MoonIcon,
} from '@heroicons/react/24/outline';
import { notificationsApi } from '../services/api/notificationsApi';
import { useTheme } from '../contexts/ThemeContext';

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: HomeIcon },
  { name: 'Depo Girişi Ekle', href: '/contracts?newSale=true', icon: PlusCircleIcon },
  { name: 'Ödeme Al', href: '/payments?collect=true', icon: BanknotesIcon, highlight: true },
  { name: 'Tüm Girişler', href: '/contracts', icon: DocumentTextIcon },
  { name: 'Nakliye İşler', href: '/transportation-jobs', icon: TruckIcon },
  { name: 'Kullanıcılar', href: '/staff', icon: UserGroupIcon },
  { name: 'Kullanıcı Yetkileri', href: '/permissions', icon: ShieldCheckIcon },
  { name: 'Depolar', href: '/warehouses', icon: BuildingOfficeIcon },
  { name: 'Odalar', href: '/rooms', icon: CubeIcon },
  { name: 'Müşteriler', href: '/customers', icon: UsersIcon },
  { name: 'Ödemeler', href: '/payments', icon: CreditCardIcon },
  { name: 'Raporlar', href: '/reports', icon: ChartBarIcon },
  { name: 'Ayarlar', href: '/settings', icon: Cog6ToothIcon },
];

export function DashboardLayout() {
  const { user, logout } = useAuthStore();
  const { theme, toggleTheme } = useTheme();
  const location = useLocation();
  const { projectName, logoUrl, loadCompany } = useCompanyStore();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [notifications, setNotifications] = useState<any[]>([]);
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [loadingNotifications, setLoadingNotifications] = useState(false);

  useEffect(() => {
    loadCompany();
  }, [location.pathname, loadCompany]);

  useEffect(() => {
    fetchNotifications();
    // Her 30 saniyede bir bildirimleri güncelle
    const interval = setInterval(fetchNotifications, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchNotifications = async () => {
    try {
      setLoadingNotifications(true);
      const data = await notificationsApi.getAll();
      setNotifications(data || []);
    } catch {
      setNotifications([]);
    } finally {
      setLoadingNotifications(false);
    }
  };

  const handleMarkAsRead = async (id: string) => {
    try {
      await notificationsApi.markAsRead(id);
      setNotifications(notifications.map(n => n.id === id ? { ...n, is_read: true } : n));
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const unreadCount = notifications.filter(n => !n.is_read).length;

  const renderNavItem = (item: typeof navigation[0], isMobile = false) => {
    const itemPath = item.href.split('?')[0];
    const searchParams = new URLSearchParams(location.search);
    
    // Özel aktif durum kontrolü
    let isActive = false;
    
    if (itemPath === '/contracts') {
      // "Depo Girişi Ekle" için: newSale=true query parametresi varsa aktif
      if (item.name === 'Depo Girişi Ekle') {
        isActive = location.pathname === itemPath && searchParams.get('newSale') === 'true';
      }
      // "Tüm Girişler" için: query parametresi yoksa aktif
      else if (item.name === 'Tüm Girişler') {
        isActive = location.pathname === itemPath && !searchParams.get('newSale');
      }
      // "Ödeme Al" için: hiçbir zaman aktif olmamalı (sadece highlight)
      else if (item.name === 'Ödeme Al') {
        isActive = false;
      }
      // Diğer durumlar için normal kontrol
      else {
        isActive = location.pathname === itemPath;
      }
    } else {
      // Diğer sayfalar için normal kontrol
      isActive = location.pathname === itemPath;
    }
    
    const isHighlight = item.highlight;
    return (
      <Link
        key={item.name}
        to={item.href}
        onClick={() => isMobile && setMobileMenuOpen(false)}
        className={`group relative flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 cursor-pointer ${
          isActive
            ? isHighlight
              ? 'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg shadow-green-500/30 scale-105'
              : 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30 scale-105'
            : isHighlight
            ? 'text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-green-900/10 hover:text-green-600 dark:hover:text-green-400 hover:scale-102'
            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white hover:scale-102'
        }`}
      >
        {isActive && (
          <div className="absolute left-0 top-0 bottom-0 w-1 bg-white rounded-r-full" />
        )}
        <item.icon
          className={`mr-3 flex-shrink-0 h-5 w-5 transition-transform duration-200 ${
            isActive
              ? 'text-white'
              : isHighlight
              ? 'text-green-500 dark:text-green-400 group-hover:scale-110'
              : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300 group-hover:scale-110'
          }`}
        />
        <span className="flex-1">{item.name}</span>
        {isHighlight && !isActive && (
          <span className="ml-2 px-2 py-0.5 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">
            Yeni
          </span>
        )}
      </Link>
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
      <div className="flex h-screen">
        {/* Mobile Menu Button */}
        <button
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          className="md:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          {mobileMenuOpen ? (
            <XMarkIcon className="h-6 w-6" />
          ) : (
            <Bars3Icon className="h-6 w-6" />
          )}
        </button>

        {/* Mobile Menu Overlay */}
        {mobileMenuOpen && (
          <div
            role="button"
            tabIndex={0}
            className="md:hidden fixed inset-0 z-40 bg-black bg-opacity-50"
            onClick={() => setMobileMenuOpen(false)}
            onKeyDown={(e) => {
              if (e.key === 'Escape' || e.key === 'Enter' || e.key === ' ') {
                setMobileMenuOpen(false);
              }
            }}
            aria-label="Menüyü kapat"
          />
        )}

        {/* Sidebar - Modern Design */}
        <div className={`fixed md:static inset-y-0 left-0 z-40 md:z-auto w-72 transform transition-transform duration-300 ease-in-out ${
          mobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        } md:flex md:flex-col`}>
          <div className="flex flex-col flex-grow pt-6 bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border-r border-gray-200/50 dark:border-gray-700/50 shadow-lg">
            <div className="flex items-center flex-shrink-0 px-6 mb-8">
              <div className="flex items-center space-x-3">
                {logoUrl ? (
                  <img
                    src={logoUrl}
                    alt=""
                    className="w-10 h-10 rounded-xl object-contain bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-600"
                  />
                ) : (
                  <div className="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg">
                    <BuildingOfficeIcon className="h-6 w-6 text-white" />
                  </div>
                )}
                <div>
                  <h1 className="text-xl font-bold bg-gradient-to-r from-primary-600 to-primary-500 bg-clip-text text-transparent">
                    {projectName}
                  </h1>
                  <p className="text-xs text-gray-500 dark:text-gray-400">Depo Yönetim Sistemi</p>
                </div>
              </div>
            </div>
            <div className="mt-2 flex-grow flex flex-col px-3 overflow-y-auto">
              <nav className="flex-1 space-y-1.5">
                {navigation
                  .filter((item) => {
                    // Müşteriler dışındaki herkes Kullanıcılar ve Yetkileri görebilsin
                    if (['/staff', '/permissions'].includes(item.href)) {
                      return user?.role !== 'customer';
                    }
                    // Raporlar ve Ayarlar sadece Admin ve Sahibe özel kalsın
                    if (['/reports', '/settings'].includes(item.href)) {
                      return user?.role === 'super_admin' || user?.role === 'company_owner';
                    }
                    return true;
                  })
                  .map((item) => renderNavItem(item))}
              </nav>
            </div>
            <div className="flex-shrink-0 flex border-t border-gray-200/50 dark:border-gray-700/50 p-4 mx-3 mb-4">
              <div className="flex-shrink-0 w-full group block">
                <div className="flex items-center space-x-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                  <div className="flex-shrink-0">
                    <div className="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center text-white font-semibold shadow-md">
                      {user?.first_name?.[0]}{user?.last_name?.[0]}
                    </div>
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-900 dark:text-white truncate">
                      {user?.first_name} {user?.last_name}
                    </p>
                    <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                      {user?.email}
                    </p>
                  </div>
                  <button
                    onClick={logout}
                    className="ml-2 p-2 text-gray-400 hover:text-red-500 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200"
                    title="Çıkış Yap"
                  >
                    <ArrowRightOnRectangleIcon className="h-5 w-5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Main content - Modern Design */}
        <div className="flex flex-col flex-1 overflow-hidden md:ml-0">
          {/* Header with Notifications */}
          <header className="sticky top-0 z-30 bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div className="flex items-center justify-end px-4 sm:px-6 md:px-8 h-16 space-x-4">
              <button
                onClick={toggleTheme}
                className="p-2 text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                title={theme === 'light' ? 'Koyu Moda Geç' : 'Açık Moda Geç'}
              >
                {theme === 'light' ? (
                  <MoonIcon className="h-6 w-6" />
                ) : (
                  <SunIcon className="h-6 w-6" />
                )}
              </button>

              <div className="relative">
                <button
                  onClick={() => setNotificationsOpen(!notificationsOpen)}
                  className="relative p-2 text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                  title="Bildirimler"
                >
                  <BellIcon className="h-6 w-6" />
                  {unreadCount > 0 && (
                    <span className="absolute top-0 right-0 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                      {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                  )}
                </button>

                {/* Notifications Dropdown */}
                {notificationsOpen && (
                  <>
                    <div
                      className="fixed inset-0 z-40"
                      onClick={() => setNotificationsOpen(false)}
                    />
                    <div className="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-[600px] overflow-hidden flex flex-col">
                      <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                          Bildirimler
                        </h3>
                        {unreadCount > 0 && (
                          <button
                            onClick={async () => {
                              const unread = notifications.filter(n => !n.is_read);
                              await Promise.all(unread.map(n => notificationsApi.markAsRead(n.id)));
                              setNotifications(notifications.map(n => ({ ...n, is_read: true })));
                            }}
                            className="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
                          >
                            Tümünü okundu işaretle
                          </button>
                        )}
                      </div>
                      <div className="overflow-y-auto flex-1">
                        {loadingNotifications ? (
                          <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                            Yükleniyor...
                          </div>
                        ) : notifications.length === 0 ? (
                          <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                            Bildirim bulunmamaktadır
                          </div>
                        ) : (
                          <div className="divide-y divide-gray-200 dark:divide-gray-700">
                            {notifications.map((notification) => (
                              <div
                                key={notification.id}
                                onClick={() => {
                                  if (!notification.is_read) {
                                    handleMarkAsRead(notification.id);
                                  }
                                }}
                                className={`p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors ${
                                  !notification.is_read ? 'bg-primary-50/50 dark:bg-primary-900/10' : ''
                                }`}
                              >
                                <div className="flex items-start space-x-3">
                                  <div className={`flex-shrink-0 w-2 h-2 rounded-full mt-2 ${
                                    !notification.is_read ? 'bg-primary-500' : 'bg-transparent'
                                  }`} />
                                  <div className="flex-1 min-w-0">
                                    <p className={`text-sm font-medium ${
                                      !notification.is_read
                                        ? 'text-gray-900 dark:text-white'
                                        : 'text-gray-700 dark:text-gray-300'
                                    }`}>
                                      {notification.title}
                                    </p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                      {notification.message}
                                    </p>
                                    <p className="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                      {new Date(notification.created_at).toLocaleString('tr-TR', {
                                        day: 'numeric',
                                        month: 'short',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                      })}
                                    </p>
                                  </div>
                                </div>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    </div>
                  </>
                )}
              </div>
            </div>
          </header>
          <main className="flex-1 relative overflow-y-auto focus:outline-none bg-gradient-to-br from-gray-50 via-white to-gray-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <div className="py-8 pt-16 md:pt-8 pb-24 md:pb-8">
              <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                <Outlet />
              </div>
            </div>
          </main>
        </div>

        {/* Bottom Navigation - Mobile Only */}
        <div className="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border-t border-gray-200 dark:border-gray-700 px-2 pt-2 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
          <div className="flex items-center justify-around h-16">
            <Link
              to="/dashboard"
              className={`flex flex-col items-center justify-center flex-1 h-full space-y-1 transition-colors ${
                location.pathname === '/dashboard' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'
              }`}
            >
              <HomeIcon className="h-6 w-6" />
              <span className="text-[10px] font-semibold">Ana Sayfa</span>
              {location.pathname === '/dashboard' && <span className="w-1 h-1 bg-primary-600 dark:bg-primary-400 rounded-full" />}
            </Link>
            <Link
              to="/customers"
              className={`flex flex-col items-center justify-center flex-1 h-full space-y-1 transition-colors ${
                location.pathname === '/customers' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'
              }`}
            >
              <UsersIcon className="h-6 w-6" />
              <span className="text-[10px] font-semibold">Müşteriler</span>
              {location.pathname === '/customers' && <span className="w-1 h-1 bg-primary-600 dark:bg-primary-400 rounded-full" />}
            </Link>
            <div className="relative -mt-10 flex-shrink-0">
              <Link
                to="/contracts?newSale=true"
                className="flex items-center justify-center w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl text-white shadow-[0_8px_16px_-3px_rgba(14,165,233,0.4)] border-4 border-white dark:border-gray-900 transform transition-all active:scale-90 hover:scale-105"
                title="Depo Girişi Ekle"
              >
                <PlusCircleIcon className="h-8 w-8" />
              </Link>
            </div>
            <Link
              to="/payments"
              className={`flex flex-col items-center justify-center flex-1 h-full space-y-1 transition-colors ${
                location.pathname === '/payments' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'
              }`}
            >
              <CreditCardIcon className="h-6 w-6" />
              <span className="text-[10px] font-semibold">Ödemeler</span>
              {location.pathname === '/payments' && <span className="w-1 h-1 bg-primary-600 dark:bg-primary-400 rounded-full" />}
            </Link>
            <button
              onClick={() => setMobileMenuOpen(true)}
              className="flex flex-col items-center justify-center flex-1 h-full space-y-1 text-gray-500 dark:text-gray-400 transition-colors"
            >
              <Bars3Icon className="h-6 w-6" />
              <span className="text-[10px] font-semibold">Menü</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
