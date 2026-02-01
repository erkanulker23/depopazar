import { useState, useEffect, useMemo } from 'react';
import { 
  XMarkIcon, 
  PlusIcon, 
  UserIcon, 
  CalendarIcon,
  UserGroupIcon,
  TruckIcon,
  DocumentTextIcon,
  CurrencyDollarIcon,
  TagIcon,
  MapPinIcon,
  DocumentArrowUpIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  TrashIcon,
  MagnifyingGlassIcon,
  ChevronUpDownIcon,
  PhotoIcon
} from '@heroicons/react/24/outline';
import { Combobox, Transition } from '@headlessui/react';
import { AddCustomerModal } from './AddCustomerModal';
import { customersApi } from '../../services/api/customersApi';
import { roomsApi } from '../../services/api/roomsApi';
import { warehousesApi } from '../../services/api/warehousesApi';
import { contractsApi } from '../../services/api/contractsApi';
import { itemsApi } from '../../services/api/itemsApi';
import { apiClient } from '../../services/api/apiClient';
import { formatTurkishCurrency, formatPhoneNumber } from '../../utils/inputFormatters';

interface NewSaleModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

interface MonthlyPrice {
  month: string; // YYYY-MM formatında
  price: number;
  notes?: string;
}

interface Item {
  id: string; // Unique identifier for React key
  name: string;
  description: string;
  quantity: number;
  unit: string;
  condition: string; // 'new', 'used', 'packaged', 'damaged'
  photoUrls: string[]; // Eşya fotoğrafları (yüklendikten sonra URL'ler)
}

export function NewSaleModal({ isOpen, onClose, onSuccess }: NewSaleModalProps) {
  const [formData, setFormData] = useState({
    customer_id: '',
    room_id: '',
    start_date: '',
    end_date: '',
    transportation_fee: 0,
    has_transportation: false,
    pickup_location: '',
    discount: 0,
    driver_name: '',
    driver_phone: '',
    vehicle_plate: '',
    notes: '',
  });

  const [monthlyPrices, setMonthlyPrices] = useState<MonthlyPrice[]>([]);
  const [monthlyFee, setMonthlyFee] = useState<number>(0);
  const [customers, setCustomers] = useState<any[]>([]);
  const [warehouses, setWarehouses] = useState<any[]>([]);
  const [rooms, setRooms] = useState<any[]>([]);
  const [selectedWarehouseId, setSelectedWarehouseId] = useState<string>('');
  const [staffUsers, setStaffUsers] = useState<any[]>([]);
  const [ownerUsers, setOwnerUsers] = useState<any[]>([]);
  const [selectedStaffIds, setSelectedStaffIds] = useState<string[]>([]);
  const [soldByUserId, setSoldByUserId] = useState<string>('');
  const [contractPdfFile, setContractPdfFile] = useState<File | null>(null);
  const [items, setItems] = useState<Item[]>([]);
  const [hasItems, setHasItems] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [showAddCustomerModal, setShowAddCustomerModal] = useState(false);
  const [customerQuery, setCustomerQuery] = useState('');

  const selectedCustomer = useMemo(
    () => customers.find((c) => c.id === formData.customer_id) ?? null,
    [customers, formData.customer_id]
  );

  const filteredCustomers = useMemo(() => {
    const q = customerQuery.trim().toLowerCase();
    if (!q) return customers;
    return customers.filter((c) => {
      const name = `${c.first_name} ${c.last_name}`.toLowerCase();
      const email = (c.email || '').toLowerCase();
      const phone = (c.phone || '').replaceAll(/\s/g, '');
      return name.includes(q) || email.includes(q) || phone.includes(q);
    });
  }, [customers, customerQuery]);

  useEffect(() => {
    if (isOpen) {
      loadData();
    }
  }, [isOpen]);

  const loadData = async () => {
    try {
      const [customersRes, warehousesRes, roomsRes, usersRes] = await Promise.all([
        customersApi.getAll({ limit: 100 }),
        warehousesApi.getAll(),
        roomsApi.getAll(),
        apiClient.get('/users'),
      ]);
      setCustomers(customersRes.data);
      setWarehouses(warehousesRes.filter((w: any) => w.is_active));
      setRooms(roomsRes);
      // Hizmet veren personel (company_staff)
      const serviceStaff = (usersRes.data || []).filter(
        (user: any) => user.role === 'company_staff'
      );
      setStaffUsers(serviceStaff);
      // Depo sahipleri (company_owner)
      const warehouseOwners = (usersRes.data || []).filter(
        (user: any) => user.role === 'company_owner'
      );
      setOwnerUsers(warehouseOwners);
    } catch (err) {
      console.error('Error loading data:', err);
    }
  };

  const generateMonths = (startDate: string, endDate: string): string[] => {
    if (!startDate || !endDate) return [];
    const start = new Date(startDate);
    const end = new Date(endDate);
    const months: string[] = [];
    const current = new Date(start);

    while (current <= end) {
      const year = current.getFullYear();
      const month = String(current.getMonth() + 1).padStart(2, '0');
      months.push(`${year}-${month}`);
      current.setMonth(current.getMonth() + 1);
    }

    return months;
  };

  const handleDateChange = (field: 'start_date' | 'end_date', value: string) => {
    setFormData({ ...formData, [field]: value });
    
    if (field === 'start_date' && formData.end_date) {
      const months = generateMonths(value, formData.end_date);
      setMonthlyPrices(months.map(month => ({ month, price: monthlyFee || 0 })));
    } else if (field === 'end_date' && formData.start_date) {
      const months = generateMonths(formData.start_date, value);
      setMonthlyPrices(months.map(month => ({ month, price: monthlyFee || 0 })));
    }
  };

  const handleMonthlyFeeChange = (value: number) => {
    setMonthlyFee(value);
    // Tüm aylara otomatik olarak bu değeri gir
    if (monthlyPrices.length > 0) {
      setMonthlyPrices(monthlyPrices.map(mp => ({ ...mp, price: value })));
    }
  };

  const updateMonthlyPrice = (index: number, field: 'price' | 'notes', value: string | number) => {
    const updated = [...monthlyPrices];
    updated[index] = { ...updated[index], [field]: value };
    setMonthlyPrices(updated);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    
    // Türkçe validasyon mesajları
    if (!formData.customer_id) {
      setError('Lütfen bir müşteri seçin.');
      return;
    }
    if (!selectedWarehouseId) {
      setError('Lütfen bir depo seçin.');
      return;
    }
    if (!formData.room_id) {
      setError('Lütfen bir oda seçin.');
      return;
    }
    if (!formData.start_date) {
      setError('Lütfen başlangıç tarihini girin.');
      return;
    }
    if (!formData.end_date) {
      setError('Lütfen bitiş tarihini girin.');
      return;
    }
    if (new Date(formData.start_date) >= new Date(formData.end_date)) {
      setError('Bitiş tarihi başlangıç tarihinden sonra olmalıdır.');
      return;
    }
    if (monthlyPrices.length === 0) {
      setError('Lütfen en az bir ay için fiyat girin.');
      return;
    }
    if (monthlyFee <= 0) {
      setError('Aylık ücret 0\'dan büyük olmalıdır.');
      return;
    }
    
    setLoading(true);

    try {
      // Contract numarası oluştur
      const contractNumber = `CNT-${new Date().getFullYear()}-${String(Date.now()).slice(-6)}`;

      // PDF dosyasını yükle (varsa)
      let pdfUrl = null;
      if (contractPdfFile) {
        const formDataPdf = new FormData();
        formDataPdf.append('file', contractPdfFile);
        formDataPdf.append('type', 'contract');
        // TODO: Backend'de file upload endpoint'i oluşturulmalı
        // const uploadResponse = await apiClient.post('/upload', formDataPdf);
        // pdfUrl = uploadResponse.data.url;
      }

      const contractData = {
        ...formData,
        contract_number: contractNumber,
        monthly_price: monthlyPrices.length > 0 ? monthlyPrices[0].price : 0, // İlk ay fiyatı default
        payment_frequency_months: 1,
        is_active: true,
        monthly_prices: monthlyPrices,
        contract_pdf_url: pdfUrl,
        staff_ids: selectedStaffIds, // Çoklu personel (hizmet veren personel)
        sold_by_user_id: soldByUserId || null, // Satışı yapan kişi (depo sahibi)
      };

      const createdContract = await contractsApi.create(contractData);

      // Eşyaları ekle (varsa) - hiçbir alan zorunlu değil
      if (hasItems && items.length > 0 && formData.room_id) {
        for (const item of items) {
          // Eşya adı boş olsa bile ekle (zorunlu değil)
          await itemsApi.create({
            room_id: formData.room_id,
            contract_id: createdContract.id,
            name: item.name || 'İsimsiz Eşya',
            description: item.description || null,
            quantity: item.quantity || null,
            unit: item.unit || null,
            condition: item.condition || 'new',
            photo_url: item.photoUrls?.length ? JSON.stringify(item.photoUrls) : null,
            stored_at: new Date().toISOString(),
          });
        }
      }

      onSuccess();
      onClose();
      resetForm();
    } catch (err: any) {
      // Backend'den gelen hata mesajlarını Türkçe'ye çevir
      const errorMessage = err.response?.data?.message || '';
      let turkishError = 'Satış eklenirken bir hata oluştu';
      
      if (errorMessage) {
        // Yaygın hata mesajlarını Türkçe'ye çevir
        if (errorMessage.includes('customer') || errorMessage.includes('Customer')) {
          turkishError = 'Müşteri bilgisi bulunamadı.';
        } else if (errorMessage.includes('room') || errorMessage.includes('Room')) {
          turkishError = 'Oda bilgisi bulunamadı veya oda dolu.';
        } else if (errorMessage.includes('warehouse') || errorMessage.includes('Warehouse')) {
          turkishError = 'Depo bilgisi bulunamadı.';
        } else if (errorMessage.includes('date') || errorMessage.includes('Date')) {
          turkishError = 'Tarih bilgisi hatalı.';
        } else if (errorMessage.includes('price') || errorMessage.includes('Price')) {
          turkishError = 'Fiyat bilgisi hatalı.';
        } else if (errorMessage.includes('required') || errorMessage.includes('Required')) {
          turkishError = 'Lütfen tüm zorunlu alanları doldurun.';
        } else if (errorMessage.includes('invalid') || errorMessage.includes('Invalid')) {
          turkishError = 'Girilen bilgiler geçersiz.';
        } else if (errorMessage.includes('duplicate') || errorMessage.includes('Duplicate')) {
          turkishError = 'Bu kayıt zaten mevcut.';
        } else if (errorMessage.includes('not found') || errorMessage.includes('Not found')) {
          turkishError = 'Kayıt bulunamadı.';
        } else {
          // Eğer mesaj zaten Türkçe karakterler içeriyorsa olduğu gibi kullan
          turkishError = errorMessage;
        }
      }
      
      setError(turkishError);
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setFormData({
      customer_id: '',
      room_id: '',
      start_date: '',
      end_date: '',
      transportation_fee: 0,
      has_transportation: false,
      pickup_location: '',
      discount: 0,
      driver_name: '',
      driver_phone: '',
      vehicle_plate: '',
      notes: '',
    });
    setSelectedWarehouseId('');
    setMonthlyPrices([]);
    setMonthlyFee(0);
    setSelectedStaffIds([]);
    setSoldByUserId('');
    setCustomerQuery('');
    setContractPdfFile(null);
    setItems([]);
    setHasItems(false);
    setError('');
  };

  const handleWarehouseChange = (warehouseId: string) => {
    setSelectedWarehouseId(warehouseId);
    // Depo değiştiğinde oda seçimini sıfırla
    setFormData({ ...formData, room_id: '' });
  };

  // Seçili depoya göre odaları filtrele
  const filteredRooms = selectedWarehouseId
    ? rooms.filter((room) => room.warehouse_id === selectedWarehouseId && room.status === 'empty')
    : [];

  const addItem = () => {
    setItems([...items, { 
      id: `item-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`,
      name: '', 
      description: '', 
      quantity: 1, 
      unit: 'adet', 
      condition: 'new',
      photoUrls: []
    }]);
    setHasItems(true);
  };

  const removeItem = (index: number) => {
    const newItems = items.filter((_, i) => i !== index);
    setItems(newItems);
    if (newItems.length === 0) {
      setHasItems(false);
    }
  };

  const updateItem = (index: number, field: keyof Item, value: string | number | string[]) => {
    const updated = [...items];
    updated[index] = { ...updated[index], [field]: value };
    setItems(updated);
  };

  const [uploadingPhotoForIndex, setUploadingPhotoForIndex] = useState<number | null>(null);

  const handleItemPhotoSelect = async (index: number, files: FileList | null) => {
    if (!files?.length) return;
    setUploadingPhotoForIndex(index);
    try {
      const newUrls: string[] = [];
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) continue;
        const url = await itemsApi.uploadPhoto(file);
        newUrls.push(url);
      }
      if (newUrls.length > 0) {
        const item = items[index];
        updateItem(index, 'photoUrls', [...(item?.photoUrls || []), ...newUrls]);
      }
    } catch (err: any) {
      const msg = err.response?.data?.message || err.message || 'Fotoğraf yüklenirken hata oluştu';
      setError(msg);
    } finally {
      setUploadingPhotoForIndex(null);
    }
  };

  const removeItemPhoto = (itemIndex: number, photoIndex: number) => {
    const item = items[itemIndex];
    if (!item?.photoUrls?.length) return;
    const newUrls = item.photoUrls.filter((_, i) => i !== photoIndex);
    updateItem(itemIndex, 'photoUrls', newUrls);
  };


  if (!isOpen) return null;

  const monthlyTotal = monthlyPrices.reduce((sum, mp) => sum + Number(mp.price || 0), 0);
  const transportFee = formData.has_transportation ? Number(formData.transportation_fee || 0) : 0;
  const discountAmount = Number(formData.discount || 0);
  const totalDebt = monthlyTotal + transportFee - discountAmount;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div 
          className="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" 
          onClick={onClose}
          onKeyDown={(e) => {
            if (e.key === 'Escape') onClose();
          }}
          tabIndex={-1}
          aria-label="Modalı kapat"
        />

        <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full border border-gray-200 dark:border-gray-700">
          {/* Header */}
          <div className="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <div className="p-2 bg-white/20 rounded-lg">
                  <PlusIcon className="h-6 w-6 text-white" />
                </div>
                <div>
                  <h3 className="text-xl font-bold text-white">
                    Yeni Satış Gir
                  </h3>
                  <p className="text-sm text-primary-100 mt-0.5">Sözleşme bilgilerini doldurun</p>
                </div>
              </div>
              <button
                onClick={onClose}
                className="p-2 text-white/80 hover:text-white hover:bg-white/20 rounded-lg transition-colors"
              >
                <XMarkIcon className="h-6 w-6" />
              </button>
            </div>
          </div>

          <div className="bg-white dark:bg-gray-800 px-6 py-6 max-h-[calc(100vh-200px)] overflow-y-auto">
            {error && (
              <div className="mb-6 p-4 text-sm text-red-700 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg dark:text-red-200">
                <div className="flex items-center">
                  <span className="font-medium">{error}</span>
                </div>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Temel Bilgiler */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center space-x-2 mb-4">
                  <UserIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                  <h4 className="text-base font-semibold text-gray-900 dark:text-white">Temel Bilgiler</h4>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label htmlFor="customer-combobox" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Müşteri *
                    </label>
                    <div className="flex gap-2">
                      <div className="flex-1 relative">
                        <Combobox
                          nullable
                          by="id"
                          value={selectedCustomer}
                          onChange={(customer) => {
                            setFormData((prev) => ({ ...prev, customer_id: customer?.id ?? '' }));
                            setCustomerQuery('');
                          }}
                        >
                          <div className="relative">
                            <div className="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400 dark:text-gray-500 z-10">
                              <MagnifyingGlassIcon className="h-5 w-5" aria-hidden="true" />
                            </div>
                            <Combobox.Input
                              id="customer-combobox"
                              className="w-full pl-10 pr-10 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                              displayValue={(c: any) =>
                                c ? `${c.first_name} ${c.last_name} - ${c.email}` : ''
                              }
                              onChange={(e) => setCustomerQuery(e.target.value)}
                              placeholder="Müşteri ara (ad, soyad, e-posta, telefon)..."
                            />
                            <div className="absolute inset-y-0 right-2 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                              <ChevronUpDownIcon className="h-5 w-5" aria-hidden="true" />
                            </div>
                          </div>
                          <Transition
                            enter="transition duration-100 ease-out"
                            enterFrom="transform scale-95 opacity-0"
                            enterTo="transform scale-100 opacity-100"
                            leave="transition duration-75 ease-out"
                            leaveFrom="transform scale-100 opacity-100"
                            leaveTo="transform scale-95 opacity-0"
                          >
                            <Combobox.Options className="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 py-1 shadow-lg focus:outline-none empty:invisible">
                              {filteredCustomers.length === 0 ? (
                                <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                  Müşteri bulunamadı.
                                </div>
                              ) : (
                                filteredCustomers.map((customer) => (
                                  <Combobox.Option
                                    key={customer.id}
                                    value={customer}
                                    className={({ active }) =>
                                      `relative cursor-pointer select-none px-4 py-2.5 ${
                                        active
                                          ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-900 dark:text-primary-100'
                                          : 'text-gray-900 dark:text-white'
                                      }`
                                    }
                                  >
                                    <span className="block truncate">
                                      {customer.first_name} {customer.last_name}
                                      <span className="text-gray-500 dark:text-gray-400 font-normal">
                                        {' '}
                                        - {customer.email}
                                      </span>
                                    </span>
                                  </Combobox.Option>
                                ))
                              )}
                            </Combobox.Options>
                          </Transition>
                        </Combobox>
                      </div>
                      <button
                        type="button"
                        onClick={() => setShowAddCustomerModal(true)}
                        className="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-lg border-2 border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900/30 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all"
                        title="Yeni müşteri ekle"
                        aria-label="Yeni müşteri ekle"
                      >
                        <PlusIcon className="h-5 w-5" />
                      </button>
                    </div>
                  </div>

                  <div>
                    <label htmlFor="warehouse-select" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Depo *
                    </label>
                    <select
                      id="warehouse-select"
                      required
                      value={selectedWarehouseId}
                      onChange={(e) => handleWarehouseChange(e.target.value)}
                      onInvalid={(e) => {
                        e.preventDefault();
                        setError('Lütfen bir depo seçin.');
                      }}
                      className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                    >
                      <option value="">Depo Seçin</option>
                      {warehouses.map((warehouse) => (
                        <option key={warehouse.id} value={warehouse.id}>
                          {warehouse.name} {warehouse.city ? `- ${warehouse.city}` : ''}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="md:col-span-2">
                    <label htmlFor="room-select" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Oda *
                    </label>
                    <select
                      id="room-select"
                      required
                      disabled={!selectedWarehouseId}
                      value={formData.room_id}
                      onChange={(e) => {
                        const selectedRoom = filteredRooms.find(r => r.id === e.target.value);
                        setFormData({ ...formData, room_id: e.target.value });
                        // Oda seçildiğinde aylık ücreti otomatik doldur
                        if (selectedRoom && selectedRoom.monthly_price) {
                          handleMonthlyFeeChange(Number(selectedRoom.monthly_price));
                        }
                      }}
                      onInvalid={(e) => {
                        e.preventDefault();
                        setError('Lütfen bir oda seçin.');
                      }}
                      className={`w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all ${
                        !selectedWarehouseId ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800' : ''
                      }`}
                    >
                      <option value="">
                        {selectedWarehouseId ? 'Oda Seçin' : 'Önce Depo Seçin'}
                      </option>
                      {filteredRooms.map((room) => (
                        <option key={room.id} value={room.id}>
                          {room.room_number} ({room.area_m2}m²)
                        </option>
                      ))}
                    </select>
                    {selectedWarehouseId && filteredRooms.length === 0 && (
                      <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Bu depoda boş oda bulunmamaktadır.
                      </p>
                    )}
                  </div>
                </div>
              </div>

              {/* Tarih Bilgileri */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center space-x-2 mb-4">
                  <CalendarIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                  <h4 className="text-base font-semibold text-gray-900 dark:text-white">Tarih Bilgileri</h4>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label htmlFor="start-date" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Başlangıç Tarihi *
                    </label>
                    <input
                      id="start-date"
                      type="date"
                      required
                      value={formData.start_date}
                      onChange={(e) => handleDateChange('start_date', e.target.value)}
                      onInvalid={(e) => {
                        e.preventDefault();
                        setError('Lütfen başlangıç tarihini girin.');
                      }}
                      className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                    />
                  </div>

                  <div>
                    <label htmlFor="end-date" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Bitiş Tarihi *
                    </label>
                    <input
                      id="end-date"
                      type="date"
                      required
                      value={formData.end_date}
                      onChange={(e) => handleDateChange('end_date', e.target.value)}
                      onInvalid={(e) => {
                        e.preventDefault();
                        setError('Lütfen bitiş tarihini girin.');
                      }}
                      className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                    />
                  </div>
                </div>
              </div>

              {/* Personel Seçimi - Hizmet Veren Personel */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center space-x-2 mb-4">
                  <UserGroupIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                  <h4 className="text-base font-semibold text-gray-900 dark:text-white">Personel Seçimi</h4>
                  <span className="text-xs text-gray-500 dark:text-gray-400">(Çoklu seçim yapabilirsiniz - Hizmet veren personeller)</span>
                </div>
                <div className="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                  {staffUsers.length === 0 ? (
                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Henüz hizmet veren personel bulunmamaktadır.</p>
                  ) : (
                    <div className="space-y-2">
                      {staffUsers.map((user) => (
                        <label
                          key={user.id}
                          htmlFor={`staff-${user.id}`}
                          className={`flex items-center p-3 rounded-lg cursor-pointer transition-all ${
                            selectedStaffIds.includes(user.id)
                              ? 'bg-primary-50 dark:bg-primary-900/20 border-2 border-primary-500'
                              : 'bg-gray-50 dark:bg-gray-700/50 border-2 border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                          }`}
                          aria-label={`${user.first_name} ${user.last_name} - Personel`}
                        >
                          <input
                            type="checkbox"
                            id={`staff-${user.id}`}
                            checked={selectedStaffIds.includes(user.id)}
                            onChange={(e) => {
                              if (e.target.checked) {
                                setSelectedStaffIds([...selectedStaffIds, user.id]);
                              } else {
                                setSelectedStaffIds(selectedStaffIds.filter((id) => id !== user.id));
                              }
                            }}
                            className="h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded transition-all"
                          />
                          <div className="ml-3 flex-1">
                            <span className="text-sm font-medium text-gray-900 dark:text-white">
                              {user.first_name} {user.last_name}
                            </span>
                            <span className="ml-2 text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                              Personel
                            </span>
                          </div>
                        </label>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              {/* Satışı Yapan Kişi - Depo Sahibi */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center space-x-2 mb-4">
                  <UserIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                  <h4 className="text-base font-semibold text-gray-900 dark:text-white">Satışı Yapan Kişi</h4>
                  <span className="text-xs text-gray-500 dark:text-gray-400">(Depo sahibi)</span>
                </div>
                <div>
                  {ownerUsers.length === 0 ? (
                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Henüz depo sahibi bulunmamaktadır.</p>
                  ) : (
                    <select
                      value={soldByUserId}
                      onChange={(e) => setSoldByUserId(e.target.value)}
                      className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                    >
                      <option value="">Depo Sahibi Seçin</option>
                      {ownerUsers.map((user) => (
                        <option key={user.id} value={user.id}>
                          {user.first_name} {user.last_name}
                        </option>
                      ))}
                    </select>
                  )}
                </div>
              </div>

              {/* Fiyatlandırma */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center space-x-2 mb-4">
                  <CurrencyDollarIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                  <h4 className="text-base font-semibold text-gray-900 dark:text-white">Fiyatlandırma</h4>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label htmlFor="monthly-fee-input" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      <CurrencyDollarIcon className="h-4 w-4 inline mr-1" />
                      Aylık Ücret (TL) *
                    </label>
                    <input
                      id="monthly-fee-input"
                      type="number"
                      step="0.01"
                      min="0"
                      required
                      value={monthlyFee}
                      onChange={(e) => handleMonthlyFeeChange(Number(e.target.value) || 0)}
                      onInvalid={(e) => {
                        e.preventDefault();
                        setError('Lütfen aylık ücreti girin.');
                      }}
                      className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                      placeholder="0.00"
                    />
                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Bu değer tüm aylara otomatik olarak girilir, isterseniz her ayı ayrı ayrı değiştirebilirsiniz.
                    </p>
                  </div>
                  <div>
                    <label htmlFor="discount-input" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      <TagIcon className="h-4 w-4 inline mr-1" />
                      İndirim (TL) <span className="text-gray-500 text-xs font-normal">(Opsiyonel)</span>
                    </label>
                    <input
                      id="discount-input"
                      type="number"
                      step="0.01"
                      min="0"
                      value={formData.discount}
                      onChange={(e) => setFormData({ ...formData, discount: Number(e.target.value) || 0 })}
                      className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                      placeholder="0.00"
                    />
                  </div>
                </div>
              </div>

              {/* Nakliye Bilgileri */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center space-x-2 mb-4">
                  <TruckIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                  <h4 className="text-base font-semibold text-gray-900 dark:text-white">Nakliye Bilgileri</h4>
                  <span className="text-xs text-gray-500 dark:text-gray-400">(Opsiyonel)</span>
                </div>
                <div className="flex items-center mb-4">
                  <input
                    type="checkbox"
                    id="has_transportation"
                    checked={formData.has_transportation}
                    onChange={(e) => setFormData({ ...formData, has_transportation: e.target.checked, transportation_fee: e.target.checked ? formData.transportation_fee : 0 })}
                    className="h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded transition-all"
                  />
                  <label htmlFor="has_transportation" className="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                    Nakliye ücreti ekle
                  </label>
                </div>

                {formData.has_transportation && (
                  <div className="mt-4 space-y-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label htmlFor="transportation-fee" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                          Nakliye Ücreti (TL)
                        </label>
                        <input
                          id="transportation-fee"
                          type="number"
                          step="0.01"
                          min="0"
                          value={formData.transportation_fee}
                          onChange={(e) => setFormData({ ...formData, transportation_fee: Number(e.target.value) || 0 })}
                          className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                          placeholder="0.00"
                        />
                      </div>
                      <div>
                        <label htmlFor="vehicle-plate" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                          Araç Plakası
                        </label>
                        <input
                          id="vehicle-plate"
                          type="text"
                          value={formData.vehicle_plate}
                          onChange={(e) => setFormData({ ...formData, vehicle_plate: e.target.value })}
                          placeholder="34 ABC 123"
                          className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                        />
                      </div>
                      <div>
                        <label htmlFor="driver-name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                          Şoför Adı
                        </label>
                        <input
                          id="driver-name"
                          type="text"
                          value={formData.driver_name}
                          onChange={(e) => setFormData({ ...formData, driver_name: e.target.value })}
                          placeholder="Şoför adı"
                          className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                        />
                      </div>
                      <div>
                        <label htmlFor="driver-phone" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                          Şoför Telefonu
                        </label>
                        <input
                          id="driver-phone"
                          type="tel"
                          value={formData.driver_phone}
                          onChange={(e) => {
                            const formatted = formatPhoneNumber(e.target.value);
                            setFormData({ ...formData, driver_phone: formatted });
                          }}
                          placeholder="0XXX XXX XX XX"
                          className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                        />
                      </div>
                    </div>
                  </div>
                )}
              </div>

              {/* Eşya Listesi */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center space-x-2">
                    <CubeIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                    <h4 className="text-base font-semibold text-gray-900 dark:text-white">Eşya Listesi</h4>
                    <span className="text-xs text-gray-500 dark:text-gray-400">(Opsiyonel)</span>
                  </div>
                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      id="has-items"
                      checked={hasItems}
                      onChange={(e) => {
                        setHasItems(e.target.checked);
                        if (e.target.checked && items.length === 0) {
                          addItem();
                        } else if (!e.target.checked) {
                          setItems([]);
                        }
                      }}
                      className="h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded transition-all"
                    />
                    <label htmlFor="has-items" className="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                      Eşya listesi ekle
                    </label>
                  </div>
                </div>

                {hasItems && (
                  <div className="space-y-3">
                    {items.map((item, index) => (
                      <div key={item.id} className="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div className="flex items-start justify-between mb-3">
                          <span className="text-sm font-semibold text-gray-900 dark:text-white">Eşya #{index + 1}</span>
                          <button
                            type="button"
                            onClick={() => removeItem(index)}
                            className="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
                            aria-label="Eşyayı kaldır"
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          <div className="md:col-span-2">
                            <label htmlFor={`item-name-${index}`} className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                              Eşya Adı
                            </label>
                            <input
                              id={`item-name-${index}`}
                              type="text"
                              value={item.name}
                              onChange={(e) => updateItem(index, 'name', e.target.value)}
                              placeholder="Örn: Koltuk Takımı, Buzdolabı"
                              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                            />
                          </div>
                          <div>
                            <label htmlFor={`item-description-${index}`} className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                              Açıklama
                            </label>
                            <input
                              id={`item-description-${index}`}
                              type="text"
                              value={item.description}
                              onChange={(e) => updateItem(index, 'description', e.target.value)}
                              placeholder="Eşya açıklaması"
                              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                            />
                          </div>
                          <div className="grid grid-cols-2 gap-3">
                            <div>
                              <label htmlFor={`item-quantity-${index}`} className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Miktar
                              </label>
                              <input
                                id={`item-quantity-${index}`}
                                type="number"
                                min="1"
                                value={item.quantity}
                                onChange={(e) => updateItem(index, 'quantity', Number(e.target.value) || 1)}
                                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                              />
                            </div>
                            <div>
                              <label htmlFor={`item-unit-${index}`} className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Birim
                              </label>
                              <select
                                id={`item-unit-${index}`}
                                value={item.unit}
                                onChange={(e) => updateItem(index, 'unit', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                              >
                                <option value="adet">Adet</option>
                                <option value="kutu">Kutu</option>
                                <option value="paket">Paket</option>
                                <option value="takım">Takım</option>
                                <option value="set">Set</option>
                                <option value="parça">Parça</option>
                              </select>
                            </div>
                          </div>
                          <div className="md:col-span-2">
                            <div className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                              Eşya Durumu
                            </div>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                              {[
                                { value: 'new', label: 'Sıfır' },
                                { value: 'used', label: 'İkinci El' },
                                { value: 'packaged', label: 'Paketlenmiş' },
                                { value: 'damaged', label: 'Hasarlı' }
                              ].map((option) => (
                                <label
                                  key={option.value}
                                  htmlFor={`item-condition-${index}-${option.value}`}
                                  className={`flex items-center p-3 rounded-lg cursor-pointer transition-all border-2 ${
                                    item.condition === option.value
                                      ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-500 text-primary-700 dark:text-primary-300'
                                      : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-500'
                                  }`}
                                >
                                  <input
                                    type="radio"
                                    id={`item-condition-${index}-${option.value}`}
                                    name={`item-condition-${index}`}
                                    value={option.value}
                                    checked={item.condition === option.value}
                                    onChange={(e) => updateItem(index, 'condition', e.target.value)}
                                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                                  />
                                  <span className="ml-2 text-sm font-medium">{option.label}</span>
                                </label>
                              ))}
                            </div>
                          </div>
                          {/* Eşya Fotoğrafı */}
                          <div className="md:col-span-2">
                            <label className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                              <PhotoIcon className="h-4 w-4 inline mr-1" />
                              Eşya Fotoğrafı <span className="text-gray-500 font-normal">(Opsiyonel, max 5MB)</span>
                            </label>
                            <div className="flex flex-wrap gap-2 items-start">
                              {(item.photoUrls || []).map((url, photoIndex) => (
                                <div key={url} className="relative group">
                                  <img
                                    src={itemsApi.getPhotoFullUrl(url)}
                                    alt={`Eşya ${index + 1} fotoğraf ${photoIndex + 1}`}
                                    className="w-16 h-16 object-cover rounded-lg border border-gray-200 dark:border-gray-600"
                                  />
                                  <button
                                    type="button"
                                    onClick={() => removeItemPhoto(index, photoIndex)}
                                    className="absolute -top-1 -right-1 p-0.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 hover:bg-red-600 transition-opacity"
                                    aria-label="Fotoğrafı kaldır"
                                  >
                                    <XMarkIcon className="h-3.5 w-3.5" />
                                  </button>
                                </div>
                              ))}
                              <label className="flex flex-col items-center justify-center w-16 h-16 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-900/10 transition-colors">
                                <input
                                  type="file"
                                  accept="image/*"
                                  multiple
                                  className="sr-only"
                                  disabled={uploadingPhotoForIndex === index}
                                  onChange={(e) => {
                                    handleItemPhotoSelect(index, e.target.files);
                                    e.target.value = '';
                                  }}
                                />
                                {uploadingPhotoForIndex === index ? (
                                  <svg className="animate-spin h-6 w-6 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                  </svg>
                                ) : (
                                  <PhotoIcon className="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                )}
                                <span className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ekle</span>
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                    <button
                      type="button"
                      onClick={addItem}
                      className="w-full py-2.5 px-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:border-primary-400 dark:hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all flex items-center justify-center space-x-2"
                    >
                      <PlusIcon className="h-4 w-4" />
                      <span>Yeni Eşya Ekle</span>
                    </button>
                  </div>
                )}
              </div>

              {/* Ek Bilgiler */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center space-x-2 mb-4">
                  <MapPinIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                  <h4 className="text-base font-semibold text-gray-900 dark:text-white">Ek Bilgiler</h4>
                </div>
                <div className="space-y-4">
                  <div>
                    <label htmlFor="pickup-location" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Eşyanın Alındığı Yer
                    </label>
                    <input
                      id="pickup-location"
                      type="text"
                      value={formData.pickup_location}
                      onChange={(e) => setFormData({ ...formData, pickup_location: e.target.value })}
                      placeholder="Örn: İstanbul, Kadıköy"
                      className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                    />
                  </div>

                  {/* PDF Sözleşme */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      <DocumentArrowUpIcon className="h-4 w-4 inline mr-1" />
                      PDF Sözleşme Ekle <span className="text-gray-500 text-xs font-normal">(Opsiyonel, max 10MB)</span>
                    </label>
                    <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-primary-400 dark:hover:border-primary-500 transition-colors">
                      <div className="space-y-1 text-center">
                        <DocumentTextIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <div className="flex text-sm text-gray-600 dark:text-gray-400">
                          <label htmlFor="pdf-upload" className="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                            <span>Dosya seç</span>
                            <input
                              id="pdf-upload"
                              type="file"
                              accept=".pdf"
                              onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (file) {
                                  if (file.size > 10 * 1024 * 1024) {
                                    setError('PDF dosyası 10MB\'dan büyük olamaz.');
                                    return;
                                  }
                                  setContractPdfFile(file);
                                }
                              }}
                              className="sr-only"
                            />
                          </label>
                          <p className="pl-1">veya sürükle-bırak</p>
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">PDF dosyası yükleyin</p>
                        {contractPdfFile && (
                          <div className="mt-2 p-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                            <p className="text-xs font-medium text-primary-800 dark:text-primary-200 flex items-center">
                              <DocumentTextIcon className="h-4 w-4 mr-1" />
                              {contractPdfFile.name}
                            </p>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {monthlyPrices.length > 0 && (
                <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                  <div className="flex items-center space-x-2 mb-4">
                    <ClipboardDocumentListIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                    <h4 className="text-base font-semibold text-gray-900 dark:text-white">Aylık Fiyatlandırma</h4>
                    <span className="text-xs text-gray-500 dark:text-gray-400">({monthlyPrices.length} ay)</span>
                  </div>
                  <div className="space-y-3 max-h-64 overflow-y-auto pr-2">
                    {monthlyPrices.map((mp, index) => {
                      const monthDate = new Date(mp.month + '-01');
                      const monthName = monthDate.toLocaleDateString('tr-TR', { year: 'numeric', month: 'long' });
                      return (
                        <div key={mp.month} className="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                          <div className="flex items-center justify-between mb-3">
                            <span className="text-sm font-semibold text-gray-900 dark:text-white">{monthName}</span>
                            <span className="text-xs text-gray-500 dark:text-gray-400">{mp.month}</span>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                              <label htmlFor={`price-${mp.month}`} className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Fiyat (TL) *
                              </label>
                              <input
                                id={`price-${mp.month}`}
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                value={mp.price}
                                onChange={(e) => updateMonthlyPrice(index, 'price', Number(e.target.value) || 0)}
                                onInvalid={(e) => {
                                  e.preventDefault();
                                  setError(`${monthName} için fiyat girmelisiniz.`);
                                }}
                                placeholder="0.00"
                                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                              />
                            </div>
                            <div>
                              <label htmlFor={`notes-${mp.month}`} className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Notlar (Opsiyonel)
                              </label>
                              <input
                                id={`notes-${mp.month}`}
                                type="text"
                                value={mp.notes || ''}
                                onChange={(e) => updateMonthlyPrice(index, 'notes', e.target.value)}
                                placeholder="Not ekleyin"
                                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all"
                              />
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Özet */}
              <div className="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl p-6 border-2 border-primary-200 dark:border-primary-800">
                <div className="flex items-center space-x-2 mb-4">
                  <CurrencyDollarIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                  <h4 className="text-base font-semibold text-gray-900 dark:text-white">Özet</h4>
                </div>
                <div className="space-y-3 bg-white dark:bg-gray-800 rounded-lg p-4">
                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600 dark:text-gray-400">Aylık Toplam:</span>
                    <span className="text-sm font-semibold text-gray-900 dark:text-white">{formatTurkishCurrency(monthlyTotal)}</span>
                  </div>
                  {formData.has_transportation && transportFee > 0 && (
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600 dark:text-gray-400">Nakliye:</span>
                      <span className="text-sm font-semibold text-gray-900 dark:text-white">{formatTurkishCurrency(transportFee)}</span>
                    </div>
                  )}
                  {discountAmount > 0 && (
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600 dark:text-gray-400">İndirim:</span>
                      <span className="text-sm font-semibold text-red-600 dark:text-red-400">-{formatTurkishCurrency(discountAmount)}</span>
                    </div>
                  )}
                  <div className="flex justify-between items-center pt-3 border-t-2 border-gray-200 dark:border-gray-700">
                    <span className="text-base font-bold text-gray-900 dark:text-white">Toplam Borç:</span>
                    <span className="text-2xl font-bold text-primary-600 dark:text-primary-400">
                      {formatTurkishCurrency(totalDebt)}
                    </span>
                  </div>
                </div>
              </div>

              {/* Notlar */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <label htmlFor="notes-textarea" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Genel Notlar
                </label>
                <textarea
                  id="notes-textarea"
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  rows={3}
                  placeholder="Sözleşme ile ilgili notlarınızı buraya yazabilirsiniz..."
                  className="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all resize-none"
                />
              </div>

              {/* Butonlar */}
              <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                  type="button"
                  onClick={onClose}
                  className="px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-all"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  className="px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 border border-transparent rounded-lg shadow-lg hover:shadow-xl hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center space-x-2"
                >
                  {loading ? (
                    <>
                      <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <span>Kaydediliyor...</span>
                    </>
                  ) : (
                    <>
                      <PlusIcon className="h-4 w-4" />
                      <span>Satışı Kaydet</span>
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>

        <AddCustomerModal
          isOpen={showAddCustomerModal}
          onClose={() => setShowAddCustomerModal(false)}
          onSuccess={async (customer) => {
            setShowAddCustomerModal(false);
            if (customer?.id) {
              await loadData();
              setFormData((f) => ({ ...f, customer_id: customer.id }));
            }
          }}
        />
      </div>
    </div>
  );
}
