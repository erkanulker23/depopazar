import { Fragment, useEffect, useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { servicesApi, Service, ServiceCategory } from '../../services/api/servicesApi';
import toast from 'react-hot-toast';

interface AddServiceModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  editData?: Service | null;
  categories: ServiceCategory[];
}

export function AddServiceModal({ isOpen, onClose, onSuccess, editData, categories }: AddServiceModalProps) {
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    category_id: '',
    name: '',
    description: '',
    unit_price: 0,
    unit: '',
  });

  useEffect(() => {
    if (editData) {
      setFormData({
        category_id: editData.category_id,
        name: editData.name,
        description: editData.description || '',
        unit_price: editData.unit_price,
        unit: editData.unit || '',
      });
    } else {
      setFormData({
        category_id: categories.length > 0 ? categories[0].id : '',
        name: '',
        description: '',
        unit_price: 0,
        unit: '',
      });
    }
  }, [editData, isOpen, categories]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.category_id) {
      toast.error('Lütfen bir kategori seçin');
      return;
    }
    try {
      setLoading(true);
      if (editData) {
        await servicesApi.updateService(editData.id, formData);
        toast.success('Hizmet güncellendi');
      } else {
        await servicesApi.createService(formData);
        toast.success('Hizmet oluşturuldu');
      }
      onSuccess();
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Bir hata oluştu');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Transition.Root show={isOpen} as={Fragment}>
      <Dialog as="div" className="relative z-50" onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in duration-200"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </Transition.Child>

        <div className="fixed inset-0 z-10 overflow-y-auto">
          <div className="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enterTo="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 translate-y-0 sm:scale-100"
              leaveTo="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <Dialog.Panel className="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <div className="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                  <button
                    type="button"
                    className="rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    onClick={onClose}
                  >
                    <span className="sr-only">Kapat</span>
                    <XMarkIcon className="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>
                
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <Dialog.Title as="h3" className="text-base font-semibold leading-6 text-gray-900 dark:text-white">
                      {editData ? 'Hizmet Düzenle' : 'Yeni Hizmet Ekle'}
                    </Dialog.Title>
                    <div className="mt-2">
                      <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                          <label htmlFor="category" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Kategori
                          </label>
                          <select
                            id="category"
                            name="category_id"
                            value={formData.category_id}
                            onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:text-white px-3 py-2 border"
                          >
                            <option value="">Seçiniz</option>
                            {categories.map((cat) => (
                              <option key={cat.id} value={cat.id}>
                                {cat.name}
                              </option>
                            ))}
                          </select>
                        </div>

                        <div>
                          <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Hizmet Adı
                          </label>
                          <input
                            type="text"
                            name="name"
                            id="name"
                            required
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:text-white px-3 py-2 border"
                          />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <label htmlFor="unit_price" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                              Birim Fiyat (₺)
                            </label>
                            <input
                              type="number"
                              name="unit_price"
                              id="unit_price"
                              min="0"
                              step="0.01"
                              required
                              value={formData.unit_price}
                              onChange={(e) => setFormData({ ...formData, unit_price: parseFloat(e.target.value) })}
                              className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:text-white px-3 py-2 border"
                            />
                          </div>
                          <div>
                            <label htmlFor="unit" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                              Birim (Opsiyonel)
                            </label>
                            <input
                              type="text"
                              name="unit"
                              id="unit"
                              placeholder="Adet, Saat"
                              value={formData.unit}
                              onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                              className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:text-white px-3 py-2 border"
                            />
                          </div>
                        </div>

                        <div>
                          <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Açıklama
                          </label>
                          <textarea
                            name="description"
                            id="description"
                            rows={3}
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:text-white px-3 py-2 border"
                          />
                        </div>

                        <div className="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                          <button
                            type="submit"
                            disabled={loading}
                            className="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 sm:ml-3 sm:w-auto disabled:opacity-50"
                          >
                            {loading ? 'Kaydediliyor...' : editData ? 'Güncelle' : 'Kaydet'}
                          </button>
                          <button
                            type="button"
                            className="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto"
                            onClick={onClose}
                          >
                            İptal
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition.Root>
  );
}
