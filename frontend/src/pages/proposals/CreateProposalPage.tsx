import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { proposalsApi } from '../../services/api/proposalsApi';
import { servicesApi, Service } from '../../services/api/servicesApi';
import { customersApi, Customer } from '../../services/api/customersApi';
import { getErrorMessage } from '../../utils/errorMessage';
import { PlusIcon, TrashIcon, ArrowLeftIcon, UserPlusIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';
import { AddCustomerModal } from '../../components/modals/AddCustomerModal';

export function CreateProposalPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isEdit = Boolean(id);
  const [loading, setLoading] = useState(false);
  const [loadingProposal, setLoadingProposal] = useState(isEdit);
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [services, setServices] = useState<Service[]>([]);
  const [isAddCustomerModalOpen, setIsAddCustomerModalOpen] = useState(false);
  
  const DEFAULT_TRANSPORT_TERMS = `1. Eşyalar profesyonel ekibimiz tarafından taşınacaktır.
2. Taşıma sırasında oluşabilecek hasarlara karşı sigorta kapsamı uygulanır.
3. Teslim tarihi ve saati müşteri ile mutabık kalınarak belirlenir.
4. Ödeme teklifte belirtilen tutar üzerinden yapılacaktır.`;

  const [formData, setFormData] = useState({
    title: '',
    customer_id: '',
    valid_until: '',
    notes: '',
    transport_terms: DEFAULT_TRANSPORT_TERMS,
    currency: 'TRY',
  });

  const [items, setItems] = useState<any[]>([
    { service_id: '', name: '', quantity: 1, unit_price: 0, total_price: 0 }
  ]);

  useEffect(() => {
    loadDependencies();
  }, []);

  useEffect(() => {
    if (isEdit && id && customers.length > 0 && services.length > 0) {
      loadProposal();
    }
  }, [isEdit, id, customers.length, services.length]);

  const loadDependencies = async () => {
    try {
      const [custsRes, srvs] = await Promise.all([
        customersApi.getAll({ page: 1, limit: 500 }),
        servicesApi.getServices(),
      ]);
      const custList = custsRes?.data ?? (Array.isArray(custsRes) ? custsRes : []);
      setCustomers(Array.isArray(custList) ? custList : []);
      setServices(Array.isArray(srvs) ? srvs : []);
    } catch (err: unknown) {
      console.error(err);
      toast.error(getErrorMessage(err));
    }
  };

  const loadProposal = async () => {
    if (!id) return;
    try {
      setLoadingProposal(true);
      const proposal = await proposalsApi.getById(id);
      setFormData({
        title: proposal.title || '',
        customer_id: proposal.customer_id || '',
        valid_until: proposal.valid_until ? new Date(proposal.valid_until).toISOString().split('T')[0] : '',
        notes: proposal.notes || '',
        transport_terms: proposal.transport_terms || DEFAULT_TRANSPORT_TERMS,
        currency: proposal.currency || 'TRY',
      });
      const proposalItems = (proposal.items || []).map((it: any) => ({
        service_id: it.service_id || '',
        name: it.name || '',
        quantity: Number(it.quantity) || 1,
        unit_price: Number(it.unit_price) || 0,
        total_price: Number(it.total_price) || 0,
      }));
      setItems(proposalItems.length > 0 ? proposalItems : [{ service_id: '', name: '', quantity: 1, unit_price: 0, total_price: 0 }]);
    } catch (err: unknown) {
      toast.error(getErrorMessage(err));
      navigate('/proposals');
    } finally {
      setLoadingProposal(false);
    }
  };

  const handleServiceChange = (index: number, serviceId: string) => {
    const newItems = [...items];
    const service = services.find(s => s.id === serviceId);
    
    if (service) {
      newItems[index] = {
        ...newItems[index],
        service_id: service.id,
        name: service.name,
        unit_price: service.unit_price,
        total_price: service.unit_price * newItems[index].quantity
      };
    } else {
      newItems[index] = {
        ...newItems[index],
        service_id: '',
        name: '',
        unit_price: 0,
        total_price: 0
      };
    }
    setItems(newItems);
  };

  const handleItemChange = (index: number, field: string, value: any) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], [field]: value };
    
    if (field === 'quantity' || field === 'unit_price') {
      newItems[index].total_price = newItems[index].quantity * newItems[index].unit_price;
    }
    
    setItems(newItems);
  };

  const addItem = () => {
    setItems([...items, { service_id: '', name: '', quantity: 1, unit_price: 0, total_price: 0 }]);
  };

  const removeItem = (index: number) => {
    if (items.length === 1) return;
    const newItems = items.filter((_, i) => i !== index);
    setItems(newItems);
  };

  const calculateTotal = () => {
    return items.reduce((sum, item) => sum + item.total_price, 0);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setLoading(true);
      const payload = {
        ...formData,
        transport_terms: formData.transport_terms || undefined,
        items: items.map(item => ({
          service_id: item.service_id || undefined,
          name: item.name,
          quantity: Number(item.quantity),
          unit_price: Number(item.unit_price),
        }))
      };
      if (isEdit && id) {
        await proposalsApi.update(id, payload);
        toast.success('Teklif güncellendi');
      } else {
        await proposalsApi.create(payload);
        toast.success('Teklif oluşturuldu');
      }
      navigate('/proposals');
    } catch (err: unknown) {
      toast.error(getErrorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6 max-w-4xl mx-auto pb-10">
      <div className="flex items-center gap-4">
        <button 
          onClick={() => navigate('/proposals')}
          className="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
        >
          <ArrowLeftIcon className="w-5 h-5 text-gray-500" />
        </button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            {isEdit ? 'Teklif Düzenle' : 'Yeni Teklif Oluştur'}
          </h1>
        </div>
      </div>

      {loadingProposal ? (
        <div className="py-12 text-center text-gray-500 dark:text-gray-400">Yükleniyor...</div>
      ) : (
      <>
      <form onSubmit={handleSubmit} className="space-y-8">
        {/* Proposal Details */}
        <div className="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Teklif Bilgileri</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Teklif Başlığı <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                required
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                placeholder="Örn: Ofis Taşıma Hizmeti Teklifi"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Müşteri
              </label>
              <div className="flex gap-2">
                <select
                  value={formData.customer_id}
                  onChange={(e) => setFormData({ ...formData, customer_id: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                >
                  <option value="">Seçiniz (Opsiyonel)</option>
                  {customers.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.first_name} {c.last_name}
                    </option>
                  ))}
                </select>
                <button
                  type="button"
                  onClick={() => setIsAddCustomerModalOpen(true)}
                  className="p-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                  title="Yeni Müşteri Ekle"
                >
                  <UserPlusIcon className="w-5 h-5" />
                </button>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Geçerlilik Tarihi
              </label>
              <input
                type="date"
                value={formData.valid_until}
                onChange={(e) => setFormData({ ...formData, valid_until: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Notlar
              </label>
              <textarea
                rows={3}
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Taşıma Şartları
              </label>
              <textarea
                rows={6}
                value={formData.transport_terms}
                onChange={(e) => setFormData({ ...formData, transport_terms: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                placeholder="Taşıma şartları..."
              />
            </div>
          </div>
        </div>

        {/* Items */}
        <div className="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Hizmetler / Ürünler</h2>
            <button
              type="button"
              onClick={addItem}
              className="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center"
            >
              <PlusIcon className="w-4 h-4 mr-1" />
              Satır Ekle
            </button>
          </div>

          <div className="space-y-4">
            {items.map((item, index) => (
              <div key={index} className="flex flex-col md:flex-row gap-4 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg items-start relative group">
                <div className="flex-1 w-full md:w-auto space-y-4 md:space-y-0 md:flex md:gap-4">
                  <div className="flex-1 min-w-[200px]">
                    <label className="block text-xs font-medium text-gray-500 mb-1 md:hidden">Hizmet Seçin</label>
                    <select
                      value={item.service_id}
                      onChange={(e) => handleServiceChange(index, e.target.value)}
                      className="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                    >
                      <option value="">Hizmet Seçin...</option>
                      {services.map((s) => (
                        <option key={s.id} value={s.id}>{s.name}</option>
                      ))}
                    </select>
                  </div>
                  
                  <div className="flex-[2] min-w-[200px]">
                    <label className="block text-xs font-medium text-gray-500 mb-1 md:hidden">Hizmet Adı</label>
                    <input
                      type="text"
                      placeholder="Hizmet Adı"
                      value={item.name}
                      onChange={(e) => handleItemChange(index, 'name', e.target.value)}
                      className="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                      required
                    />
                  </div>

                  <div className="w-24">
                    <label className="block text-xs font-medium text-gray-500 mb-1 md:hidden">Miktar</label>
                    <input
                      type="number"
                      placeholder="Miktar"
                      min="0"
                      value={item.quantity}
                      onChange={(e) => handleItemChange(index, 'quantity', parseFloat(e.target.value))}
                      className="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                      required
                    />
                  </div>

                  <div className="w-32">
                    <label className="block text-xs font-medium text-gray-500 mb-1 md:hidden">Birim Fiyat</label>
                    <input
                      type="number"
                      placeholder="Birim Fiyat"
                      min="0"
                      step="0.01"
                      value={item.unit_price}
                      onChange={(e) => handleItemChange(index, 'unit_price', parseFloat(e.target.value))}
                      className="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                      required
                    />
                  </div>

                  <div className="w-32 flex items-center justify-end font-semibold text-gray-900 dark:text-white">
                    {new Intl.NumberFormat('tr-TR', { style: 'currency', currency: formData.currency }).format(item.total_price)}
                  </div>
                </div>

                {items.length > 1 && (
                  <button
                    type="button"
                    onClick={() => removeItem(index)}
                    className="p-2 text-red-400 hover:text-red-600 transition-colors"
                  >
                    <TrashIcon className="w-5 h-5" />
                  </button>
                )}
              </div>
            ))}
          </div>

          <div className="mt-6 flex justify-end items-center gap-4 text-lg font-bold text-gray-900 dark:text-white p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <span>Toplam Tutar:</span>
            <span>{new Intl.NumberFormat('tr-TR', { style: 'currency', currency: formData.currency }).format(calculateTotal())}</span>
          </div>
        </div>

        <div className="flex justify-end gap-4">
          <button
            type="button"
            onClick={() => navigate('/proposals')}
            className="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            İptal
          </button>
          <button
            type="submit"
            disabled={loading}
            className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors"
          >
            {loading ? (isEdit ? 'Kaydediliyor...' : 'Oluşturuluyor...') : (isEdit ? 'Güncelle' : 'Teklifi Oluştur')}
          </button>
        </div>
      </form>
      <AddCustomerModal
        isOpen={isAddCustomerModalOpen}
        onClose={() => setIsAddCustomerModalOpen(false)}
        onSuccess={(newCustomer) => {
          if (newCustomer) {
            setCustomers([...customers, newCustomer]);
            setFormData({ ...formData, customer_id: newCustomer.id });
            toast.success('Müşteri başarıyla eklendi');
          }
        }}
      />
      </>
      )}
    </div>
  );
}
