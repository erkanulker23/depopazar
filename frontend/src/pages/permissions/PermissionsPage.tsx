import { ShieldCheckIcon, UserGroupIcon, KeyIcon } from '@heroicons/react/24/outline';

export function PermissionsPage() {
  const roles = [
    {
      id: 'super_admin',
      name: 'Süper Admin',
      description: 'Sistemdeki tüm yetkilere sahiptir. Şirketleri, kullanıcıları ve tüm verileri yönetebilir.',
      permissions: [
        'Tüm şirketleri yönetme',
        'Sistem kullanıcılarını ekleme/düzenleme/silme',
        'Tüm depo ve oda verilerine erişim',
        'Tüm finansal raporları görme',
        'Sistem ayarlarını değiştirme',
      ],
      color: 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
    },
    {
      id: 'company_owner',
      name: 'Depo Sahibi',
      description: 'Kendi şirketine ait tüm operasyonları yönetebilir.',
      permissions: [
        'Kendi personellerini yönetme',
        'Müşteri ve sözleşme yönetimi',
        'Depo ve oda yönetimi',
        'Ödeme alma ve finansal takip',
        'Şirket raporlarını görme',
      ],
      color: 'text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400',
    },
    {
      id: 'accounting',
      name: 'Muhasebe',
      description: 'Finansal kayıtlar ve raporlama işlemlerini yürütür.',
      permissions: [
        'Ödeme kayıtlarını görme ve ekleme',
        'Finansal raporlara erişim',
        'Müşteri bakiye takibi',
        'Sözleşme bedellerini görme',
      ],
      color: 'text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400',
    },
    {
      id: 'data_entry',
      name: 'Veri Girişi',
      description: 'Sisteme veri girişi ve operasyonel takipleri yapar.',
      permissions: [
        'Yeni müşteri kaydı oluşturma',
        'Sözleşme girişi yapma',
        'Depo ve oda durumlarını güncelleme',
        'Nakliye işlerini takip etme',
      ],
      color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400',
    },
    {
      id: 'company_staff',
      name: 'Personel',
      description: 'Genel operasyonel işlemleri gerçekleştirir.',
      permissions: [
        'Müşteri bilgilerini görme',
        'Depo ve oda durumlarını izleme',
        'Nakliye işlerini görme',
      ],
      color: 'text-gray-600 bg-gray-100 dark:bg-gray-900/30 dark:text-gray-400',
    },
  ];

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold gradient-text mb-2">Kullanıcı Yetkileri</h1>
        <p className="text-gray-600 dark:text-gray-400">
          Sistemdeki rollerin sahip olduğu yetki ve sınırlamalar
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {roles.map((role) => (
          <div key={role.id} className="modern-card p-6 flex flex-col h-full">
            <div className="flex items-center mb-4">
              <div className={`p-2 rounded-lg ${role.color} mr-3`}>
                <ShieldCheckIcon className="h-6 w-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-white">
                {role.name}
              </h3>
            </div>
            
            <p className="text-sm text-gray-600 dark:text-gray-400 mb-6 flex-grow">
              {role.description}
            </p>

            <div className="space-y-3 mt-auto">
              <h4 className="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center">
                <KeyIcon className="h-3 w-3 mr-1" />
                Temel Yetkiler
              </h4>
              <ul className="space-y-2">
                {role.permissions.map((permission, index) => (
                  <li key={index} className="flex items-start text-sm text-gray-700 dark:text-gray-300">
                    <span className="text-primary-500 mr-2">•</span>
                    {permission}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        ))}
      </div>

      <div className="mt-12 modern-card p-6 bg-primary-50 dark:bg-primary-900/10 border-primary-100 dark:border-primary-900/20">
        <div className="flex items-start">
          <div className="flex-shrink-0">
            <UserGroupIcon className="h-6 w-6 text-primary-600 dark:text-primary-400" />
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-primary-800 dark:text-primary-200">
              Yetki Yönetimi Hakkında
            </h3>
            <div className="mt-2 text-sm text-primary-700 dark:text-primary-300">
              <p>
                Roller ve yetkiler sistem güvenliği için önceden tanımlanmıştır. Yeni bir kullanıcı eklerken bu rollerden birini atayarak kullanıcının sistemdeki erişim alanını belirleyebilirsiniz. 
                Herhangi bir yetki değişikliği talebi için lütfen sistem yöneticisi ile iletişime geçin.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
