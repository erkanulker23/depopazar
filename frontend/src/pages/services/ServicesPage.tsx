import { useState, useEffect } from 'react';
import { servicesApi, Service, ServiceCategory } from '../../services/api/servicesApi';
import { PlusIcon, PencilIcon, TrashIcon, FolderPlusIcon, TagIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';
import { AddServiceCategoryModal } from '../../components/modals/AddServiceCategoryModal';
import { AddServiceModal } from '../../components/modals/AddServiceModal';

export function ServicesPage() {
  const [categories, setCategories] = useState<ServiceCategory[]>([]);
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);
  
  // Modal states
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [showServiceModal, setShowServiceModal] = useState(false);
  const [editingCategory, setEditingCategory] = useState<ServiceCategory | null>(null);
  const [editingService, setEditingService] = useState<Service | null>(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [cats, srvs] = await Promise.all([
        servicesApi.getCategories(),
        servicesApi.getServices(),
      ]);
      setCategories(cats);
      setServices(srvs);
    } catch (error) {
      console.error(error);
      toast.error('Veriler yüklenirken hata oluştu');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteCategory = async (id: string) => {
    if (!confirm('Kategoriyi silmek istediğinize emin misiniz? Altındaki hizmetler de silinecektir.')) return;
    try {
      await servicesApi.deleteCategory(id);
      toast.success('Kategori silindi');
      loadData();
    } catch (error) {
      toast.error('Kategori silinemedi');
    }
  };

  const handleDeleteService = async (id: string) => {
    if (!confirm('Hizmeti silmek istediğinize emin misiniz?')) return;
    try {
      await servicesApi.deleteService(id);
      toast.success('Hizmet silindi');
      loadData();
    } catch (error) {
      toast.error('Hizmet silinemedi');
    }
  };

  // Group services by category
  const servicesByCategory = categories.map(cat => ({
    ...cat,
    items: services.filter(s => s.category_id === cat.id)
  }));

  if (loading) return <div className="p-4">Yükleniyor...</div>;

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Hizmetler ve Kategoriler</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">Hizmetlerinizi kategorize ederek yönetin</p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => { setEditingCategory(null); setShowCategoryModal(true); }}
            className="flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <FolderPlusIcon className="w-5 h-5 mr-2" />
            Yeni Kategori
          </button>
          <button
            onClick={() => { setEditingService(null); setShowServiceModal(true); }}
            className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors"
          >
            <PlusIcon className="w-5 h-5 mr-2" />
            Yeni Hizmet
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        {servicesByCategory.map(category => (
          <div key={category.id} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col">
            <div className="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-start">
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-white flex items-center">
                  <TagIcon className="w-4 h-4 mr-2 text-primary-500" />
                  {category.name}
                </h3>
                {category.description && (
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{category.description}</p>
                )}
              </div>
              <div className="flex items-center space-x-1">
                <button
                  onClick={() => { setEditingCategory(category); setShowCategoryModal(true); }}
                  className="p-1 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                >
                  <PencilIcon className="w-4 h-4" />
                </button>
                <button
                  onClick={() => handleDeleteCategory(category.id)}
                  className="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                >
                  <TrashIcon className="w-4 h-4" />
                </button>
              </div>
            </div>
            
            <div className="flex-1 p-4">
              {category.items.length === 0 ? (
                <div className="text-center py-6 text-sm text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-100 dark:border-gray-700 rounded-lg">
                  Bu kategoride henüz hizmet yok
                </div>
              ) : (
                <ul className="space-y-3">
                  {category.items.map(service => (
                    <li key={service.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors group">
                      <div>
                        <div className="font-medium text-sm text-gray-900 dark:text-white">{service.name}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">
                          {service.unit_price} ₺ {service.unit && `/ ${service.unit}`}
                        </div>
                      </div>
                      <div className="flex items-center opacity-0 group-hover:opacity-100 transition-opacity space-x-2">
                        <button
                          onClick={() => { setEditingService(service); setShowServiceModal(true); }}
                          className="p-1 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                        >
                          <PencilIcon className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDeleteService(service.id)}
                          className="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                        >
                          <TrashIcon className="w-4 h-4" />
                        </button>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        ))}
      </div>

      {showCategoryModal && (
        <AddServiceCategoryModal
          isOpen={showCategoryModal}
          onClose={() => setShowCategoryModal(false)}
          onSuccess={loadData}
          editData={editingCategory}
        />
      )}

      {showServiceModal && (
        <AddServiceModal
          isOpen={showServiceModal}
          onClose={() => setShowServiceModal(false)}
          onSuccess={loadData}
          editData={editingService}
          categories={categories}
        />
      )}
    </div>
  );
}
