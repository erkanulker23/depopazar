import { useEffect, useState } from 'react';
import { transportationJobsApi, TransportationJob } from '../../services/api/transportationJobsApi';
import { customersApi } from '../../services/api/customersApi';
import { apiClient } from '../../services/api/apiClient';
import {
  TruckIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  TrashIcon,
  XMarkIcon,
  PencilIcon,
  DocumentTextIcon,
  MapPinIcon,
  UserGroupIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  CalendarIcon,
  ClockIcon,
} from '@heroicons/react/24/outline';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import toast from 'react-hot-toast';

interface TransportationJobFormData {
  customer_id: string;
  pickup_address: string;
  pickup_floor_status: string;
  pickup_elevator_status: string;
  pickup_room_count: number | '';
  delivery_address: string;
  delivery_floor_status: string;
  delivery_elevator_status: string;
  delivery_room_count: number | '';
  staff_ids: string[];
  price: number | '';
  vat_rate: number;
  price_includes_vat: boolean;
  contract_pdf_file: File | null;
  contract_pdf_url: string;
  notes: string;
  job_date: string;
  status: string;
  is_paid: boolean;
}

export function TransportationJobsPage() {
  const [jobs, setJobs] = useState<TransportationJob[]>([]);
  const [filteredJobs, setFilteredJobs] = useState<TransportationJob[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isEditMode, setIsEditMode] = useState(false);
  const [editingJob, setEditingJob] = useState<TransportationJob | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedYear, setSelectedYear] = useState<number | ''>('');
  const [selectedMonth, setSelectedMonth] = useState<number | ''>('');
  const [customers, setCustomers] = useState<any[]>([]);
  const [staff, setStaff] = useState<any[]>([]);
  const [deleteTarget, setDeleteTarget] = useState<TransportationJob | null>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [expandedJobs, setExpandedJobs] = useState<Set<string>>(new Set());
  const [formData, setFormData] = useState<TransportationJobFormData>({
    customer_id: '',
    pickup_address: '',
    pickup_floor_status: '',
    pickup_elevator_status: '',
    pickup_room_count: '',
    delivery_address: '',
    delivery_floor_status: '',
    delivery_elevator_status: '',
    delivery_room_count: '',
    staff_ids: [],
    price: '',
    vat_rate: 20,
    price_includes_vat: false,
    contract_pdf_file: null,
    contract_pdf_url: '',
    notes: '',
    job_date: '',
    status: 'pending',
    is_paid: false,
  });

  useEffect(() => {
    fetchJobs();
    fetchCustomers();
    fetchStaff();
  }, []);

  useEffect(() => {
    fetchJobs();
  }, [selectedYear, selectedMonth]);

  useEffect(() => {
    if (searchTerm.trim() === '') {
      setFilteredJobs(jobs);
    } else {
      const filtered = jobs.filter((job) => {
        const customerName = `${job.customer?.first_name || ''} ${job.customer?.last_name || ''}`.toLowerCase();
        return customerName.includes(searchTerm.toLowerCase());
      });
      setFilteredJobs(filtered);
    }
  }, [searchTerm, jobs]);

  const fetchJobs = async () => {
    setLoading(true);
    try {
      const params: { limit: number; year?: number; month?: number } = { limit: 100 };
      if (selectedYear !== '') params.year = selectedYear as number;
      if (selectedMonth !== '') params.month = selectedMonth as number;
      
      const res = await transportationJobsApi.getAll(params);
      // Response format kontrolü: res zaten PaginatedResponse, res.data array olmalı
      const jobsData = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
      setJobs(jobsData);
      setFilteredJobs(jobsData);
    } catch (error: any) {
      console.error('Error fetching transportation jobs:', error);
      console.error('Error details:', {
        status: error?.response?.status,
        statusText: error?.response?.statusText,
        data: error?.response?.data,
        message: error?.message,
      });
      const errorMessage = 
        error?.response?.data?.message || 
        error?.message || 
        `Nakliye işleri yüklenirken bir hata oluştu${error?.response?.status ? ` (${error.response.status})` : ''}`;
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const fetchCustomers = async () => {
    try {
      const res = await customersApi.getAll({ limit: 100 });
      setCustomers(res.data);
    } catch (error) {
      console.error('Error fetching customers:', error);
    }
  };

  const fetchStaff = async () => {
    try {
      const response = await apiClient.get('/users');
      const staffUsers = (response.data || []).filter(
        (user: any) => user.role === 'company_staff' || user.role === 'company_owner'
      );
      setStaff(staffUsers);
    } catch (error) {
      console.error('Error fetching staff:', error);
    }
  };

  const handleOpenModal = (job?: TransportationJob) => {
    if (job) {
      setIsEditMode(true);
      setEditingJob(job);
      const staffIds = job.staff?.map((s: any) => s.user_id || s.user?.id) || [];
      // Eğer eski format varsa (il, ilçe, mahalle) birleştir, yoksa yeni formatı kullan
      const pickupAddress = job.pickup_address || 
        (job.pickup_province ? `${job.pickup_province}${job.pickup_district ? ` / ${job.pickup_district}` : ''}${job.pickup_neighborhood ? ` / ${job.pickup_neighborhood}` : ''}`.trim() : '');
      const deliveryAddress = job.delivery_address || 
        (job.delivery_province ? `${job.delivery_province}${job.delivery_district ? ` / ${job.delivery_district}` : ''}${job.delivery_neighborhood ? ` / ${job.delivery_neighborhood}` : ''}`.trim() : '');
      setFormData({
        customer_id: job.customer_id,
        pickup_address: pickupAddress,
        pickup_floor_status: job.pickup_floor_status || '',
        pickup_elevator_status: job.pickup_elevator_status || '',
        pickup_room_count: job.pickup_room_count || '',
        delivery_address: deliveryAddress,
        delivery_floor_status: job.delivery_floor_status || '',
        delivery_elevator_status: job.delivery_elevator_status || '',
        delivery_room_count: job.delivery_room_count || '',
        staff_ids: staffIds,
        price: job.price || '',
        vat_rate: job.vat_rate || 20,
        price_includes_vat: job.price_includes_vat || false,
        contract_pdf_file: null,
        contract_pdf_url: job.contract_pdf_url || '',
        notes: job.notes || '',
        job_date: job.job_date ? new Date(job.job_date).toISOString().split('T')[0] : '',
        status: job.status || 'pending',
        is_paid: job.is_paid || false,
      });
    } else {
      setIsEditMode(false);
      setEditingJob(null);
      setFormData({
        customer_id: '',
        pickup_address: '',
        pickup_floor_status: '',
        pickup_elevator_status: '',
        pickup_room_count: '',
        delivery_address: '',
        delivery_floor_status: '',
        delivery_elevator_status: '',
        delivery_room_count: '',
        staff_ids: [],
        price: '',
        vat_rate: 20,
        price_includes_vat: false,
        contract_pdf_file: null,
        contract_pdf_url: '',
        notes: '',
        job_date: '',
        status: 'pending',
        is_paid: false,
      });
    }
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setIsEditMode(false);
    setEditingJob(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const submitData: any = {
        ...formData,
        pickup_room_count: formData.pickup_room_count === '' ? null : Number(formData.pickup_room_count),
        delivery_room_count: formData.delivery_room_count === '' ? null : Number(formData.delivery_room_count),
        staff_ids: formData.staff_ids,
        price: formData.price === '' ? null : Number(formData.price),
        vat_rate: Number(formData.vat_rate),
        job_date: formData.job_date || null,
        contract_pdf_file: undefined, // Don't send file in JSON
        contract_pdf_url: formData.contract_pdf_url || null,
      };

      let jobId: string;

      if (isEditMode && editingJob) {
        await transportationJobsApi.update(editingJob.id, submitData);
        jobId = editingJob.id;
        toast.success('Nakliye işi başarıyla güncellendi');
      } else {
        const newJob = await transportationJobsApi.create(submitData);
        jobId = newJob.id;
        toast.success('Nakliye işi başarıyla oluşturuldu');
      }

      // Upload PDF if file is selected
      if (formData.contract_pdf_file) {
        try {
          await transportationJobsApi.uploadPdf(jobId, formData.contract_pdf_file);
          toast.success('PDF sözleşme başarıyla yüklendi');
        } catch (error: any) {
          toast.error(error.response?.data?.message || 'PDF yüklenirken bir hata oluştu');
        }
      }

      handleCloseModal();
      fetchJobs();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Nakliye işi kaydedilirken bir hata oluştu');
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;

    setDeleteLoading(true);
    try {
      await transportationJobsApi.delete(deleteTarget.id);
      toast.success('Nakliye işi başarıyla silindi');
      setDeleteTarget(null);
      fetchJobs();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Nakliye işi silinirken bir hata oluştu');
    } finally {
      setDeleteLoading(false);
    }
  };

  const toggleJobExpansion = (jobId: string) => {
    setExpandedJobs((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(jobId)) {
        newSet.delete(jobId);
      } else {
        newSet.add(jobId);
      }
      return newSet;
    });
  };

  if (loading) {
    return (
      <div className="modern-card p-8 text-center">
        <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold gradient-text mb-2">Nakliye İşleri</h1>
          <p className="text-gray-600 dark:text-gray-400">Nakliye işleri yönetimi ve takibi</p>
        </div>
        <button onClick={() => handleOpenModal()} className="btn-primary inline-flex items-center px-6 py-3">
          <PlusIcon className="h-5 w-5 mr-2" />
          Yeni Nakliye İşi Ekle
        </button>
      </div>

      {/* Filters */}
      <div className="mb-6 space-y-4">
        {/* Search */}
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400 dark:text-gray-500" />
          </div>
          <input
            type="text"
            placeholder="Müşteri ara..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="block w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent shadow-sm transition-all duration-200"
          />
        </div>

        {/* Year and Month Filters */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Yıl
            </label>
            <select
              value={selectedYear}
              onChange={(e) => setSelectedYear(e.target.value === '' ? '' : parseInt(e.target.value, 10))}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent shadow-sm"
            >
              <option value="">Tüm Yıllar</option>
              {Array.from({ length: 10 }, (_, i) => {
                const year = new Date().getFullYear() - i;
                return (
                  <option key={year} value={year}>
                    {year}
                  </option>
                );
              })}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Ay
            </label>
            <select
              value={selectedMonth}
              onChange={(e) => setSelectedMonth(e.target.value === '' ? '' : parseInt(e.target.value, 10))}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent shadow-sm"
            >
              <option value="">Tüm Aylar</option>
              {[
                { value: 1, label: 'Ocak' },
                { value: 2, label: 'Şubat' },
                { value: 3, label: 'Mart' },
                { value: 4, label: 'Nisan' },
                { value: 5, label: 'Mayıs' },
                { value: 6, label: 'Haziran' },
                { value: 7, label: 'Temmuz' },
                { value: 8, label: 'Ağustos' },
                { value: 9, label: 'Eylül' },
                { value: 10, label: 'Ekim' },
                { value: 11, label: 'Kasım' },
                { value: 12, label: 'Aralık' },
              ].map((month) => (
                <option key={month.value} value={month.value}>
                  {month.label}
                </option>
              ))}
            </select>
          </div>
          <div className="flex items-end">
            <button
              onClick={() => {
                setSelectedYear('');
                setSelectedMonth('');
              }}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors font-medium"
            >
              Filtreleri Temizle
            </button>
          </div>
        </div>
      </div>

      {/* Jobs List */}
      {filteredJobs.length === 0 ? (
        <div className="modern-card p-12 text-center">
          <div className="flex flex-col items-center">
            <div className="rounded-full bg-gray-100 dark:bg-gray-800 p-4 mb-4">
              <TruckIcon className="h-12 w-12 text-gray-400" />
            </div>
            <p className="text-gray-600 dark:text-gray-400 text-lg">Henüz nakliye işi bulunmamaktadır.</p>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4">
          {filteredJobs.map((job) => {
            const isExpanded = expandedJobs.has(job.id);
            const pickupAddress = job.pickup_address || 
              (job.pickup_province ? `${job.pickup_province}${job.pickup_district ? ` / ${job.pickup_district}` : ''}${job.pickup_neighborhood ? ` / ${job.pickup_neighborhood}` : ''}`.trim() : '');
            const deliveryAddress = job.delivery_address || 
              (job.delivery_province ? `${job.delivery_province}${job.delivery_district ? ` / ${job.delivery_district}` : ''}${job.delivery_neighborhood ? ` / ${job.delivery_neighborhood}` : ''}`.trim() : '');

            return (
              <div
                key={job.id}
                className="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-lg border border-gray-200 dark:border-gray-700 transition-all duration-300 overflow-hidden"
              >
                {/* Main Card Content - Always Visible */}
                <div
                  onClick={() => toggleJobExpansion(job.id)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      e.preventDefault();
                      toggleJobExpansion(job.id);
                    }
                  }}
                  role="button"
                  tabIndex={0}
                  className="p-6 cursor-pointer select-none focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset rounded-t-xl"
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-3 mb-3">
                        <div className="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-md">
                          <TruckIcon className="h-6 w-6 text-white" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <h3 className="text-xl font-bold text-gray-900 dark:text-white truncate">
                            {job.customer?.first_name} {job.customer?.last_name}
                          </h3>
                          {job.customer?.email && (
                            <p className="text-sm text-gray-500 dark:text-gray-400 truncate mt-0.5">
                              {job.customer.email}
                            </p>
                          )}
                        </div>
                        <div className="flex flex-col gap-2 items-end">
                          <span
                            className={`flex-shrink-0 px-3 py-1.5 text-xs font-semibold rounded-full ${
                              job.status === 'completed'
                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                : job.status === 'in_progress'
                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                : job.status === 'cancelled'
                                ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                            }`}
                          >
                            {job.status === 'completed'
                              ? 'Tamamlandı'
                              : job.status === 'in_progress'
                              ? 'Devam Ediyor'
                              : job.status === 'cancelled'
                              ? 'İptal Edildi'
                              : 'Beklemede'}
                          </span>
                          <span
                            className={`flex-shrink-0 px-3 py-1.5 text-xs font-semibold rounded-full ${
                              job.is_paid
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                                : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300'
                            }`}
                          >
                            {job.is_paid ? 'Ödeme Alındı' : 'Ödeme Alınmadı'}
                          </span>
                        </div>
                      </div>

                      {/* Summary Info */}
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        {pickupAddress && (
                          <div className="flex items-start gap-2">
                            <MapPinIcon className="h-5 w-5 text-primary-500 flex-shrink-0 mt-0.5" />
                            <div className="min-w-0 flex-1">
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Alış Noktası
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white line-clamp-2">
                                {pickupAddress}
                              </p>
                            </div>
                          </div>
                        )}
                        {deliveryAddress && (
                          <div className="flex items-start gap-2">
                            <MapPinIcon className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
                            <div className="min-w-0 flex-1">
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Teslimat Noktası
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white line-clamp-2">
                                {deliveryAddress}
                              </p>
                            </div>
                          </div>
                        )}
                        <div className="flex items-start gap-2">
                          <div className="flex flex-col gap-2 w-full">
                            {job.price && (
                              <div>
                                <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                  Fiyat
                                </p>
                                <p className="text-sm font-semibold text-gray-900 dark:text-white">
                                  {formatTurkishCurrency(Number(job.price))}
                                </p>
                                {job.vat_rate && (
                                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {job.price_includes_vat ? 'KDV Dahil' : 'KDV Hariç'} (%{job.vat_rate})
                                  </p>
                                )}
                              </div>
                            )}
                            {job.job_date && (
                              <div className="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                                <CalendarIcon className="h-4 w-4" />
                                <span>{new Date(job.job_date).toLocaleDateString('tr-TR')}</span>
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* Expand/Collapse Icon */}
                    <div className="flex items-start gap-2 ml-4">
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          toggleJobExpansion(job.id);
                        }}
                        className="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                        title={isExpanded ? 'Detayları Gizle' : 'Detayları Göster'}
                      >
                        {isExpanded ? (
                          <ChevronUpIcon className="h-5 w-5" />
                        ) : (
                          <ChevronDownIcon className="h-5 w-5" />
                        )}
                      </button>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleOpenModal(job);
                        }}
                        className="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                        title="Düzenle"
                      >
                        <PencilIcon className="h-5 w-5" />
                      </button>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          setDeleteTarget(job);
                        }}
                        className="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                        title="Sil"
                      >
                        <TrashIcon className="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </div>

                {/* Expanded Details - Animated */}
                <div
                  className={`overflow-hidden transition-all duration-300 ease-in-out ${
                    isExpanded ? 'max-h-[2000px] opacity-100' : 'max-h-0 opacity-0'
                  }`}
                >
                  <div className="px-6 pb-6 border-t border-gray-200 dark:border-gray-700 pt-6 bg-gray-50 dark:bg-gray-900/50">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      {/* Pickup Details */}
                      <div className="space-y-4">
                        <div className="flex items-center gap-2 mb-3">
                          <div className="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
                            <MapPinIcon className="h-5 w-5 text-primary-600 dark:text-primary-400" />
                          </div>
                          <h4 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Eşya Alındığı Yer
                          </h4>
                        </div>
                        <div className="space-y-2 pl-12">
                          {pickupAddress && (
                            <div>
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Adres
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">
                                {pickupAddress}
                              </p>
                            </div>
                          )}
                          {job.pickup_floor_status && (
                            <div>
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Kat Durumu
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white">{job.pickup_floor_status}</p>
                            </div>
                          )}
                          {job.pickup_elevator_status && (
                            <div>
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Asansör Durumu
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white">{job.pickup_elevator_status}</p>
                            </div>
                          )}
                          {job.pickup_room_count && (
                            <div>
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Oda Sayısı
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white">{job.pickup_room_count}</p>
                            </div>
                          )}
                        </div>
                      </div>

                      {/* Delivery Details */}
                      <div className="space-y-4">
                        <div className="flex items-center gap-2 mb-3">
                          <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <MapPinIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                          </div>
                          <h4 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Eşyanın Gittiği Adres
                          </h4>
                        </div>
                        <div className="space-y-2 pl-12">
                          {deliveryAddress && (
                            <div>
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Adres
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">
                                {deliveryAddress}
                              </p>
                            </div>
                          )}
                          {job.delivery_floor_status && (
                            <div>
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Kat Durumu
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white">{job.delivery_floor_status}</p>
                            </div>
                          )}
                          {job.delivery_elevator_status && (
                            <div>
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Asansör Durumu
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white">{job.delivery_elevator_status}</p>
                            </div>
                          )}
                          {job.delivery_room_count && (
                            <div>
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Oda Sayısı
                              </p>
                              <p className="text-sm text-gray-900 dark:text-white">{job.delivery_room_count}</p>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>

                    {/* Additional Info */}
                    <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-6">
                      {job.staff && job.staff.length > 0 && (
                        <div>
                          <div className="flex items-center gap-2 mb-3">
                            <UserGroupIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            <h4 className="text-sm font-semibold text-gray-900 dark:text-white">Personel</h4>
                          </div>
                          <div className="flex flex-wrap gap-2">
                            {job.staff.map((s: any, idx: number) => (
                              <span
                                key={s.id || s.user?.id || idx}
                                className="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg text-sm font-medium"
                              >
                                {s.user?.first_name} {s.user?.last_name}
                              </span>
                            ))}
                          </div>
                        </div>
                      )}

                      {job.contract_pdf_url && (
                        <div>
                          <div className="flex items-center gap-2 mb-3">
                            <DocumentTextIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            <h4 className="text-sm font-semibold text-gray-900 dark:text-white">Sözleşme</h4>
                          </div>
                          <a
                            href={job.contract_pdf_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-sm font-medium"
                          >
                            <DocumentTextIcon className="h-4 w-4" />
                            PDF'i Görüntüle
                          </a>
                        </div>
                      )}
                    </div>

                    {/* Notes */}
                    {job.notes && (
                      <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                          <ClockIcon className="h-4 w-4" />
                          Notlar
                        </h4>
                        <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                          {job.notes}
                        </p>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Add/Edit Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onClick={handleCloseModal} />

            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    {isEditMode ? 'Nakliye İşi Düzenle' : 'Yeni Nakliye İşi Ekle'}
                  </h3>
                  <button
                    onClick={handleCloseModal}
                    className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  >
                    <XMarkIcon className="h-6 w-6" />
                  </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                  {/* Müşteri Seçimi */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Müşteri <span className="text-red-500">*</span>
                    </label>
                    <select
                      required
                      value={formData.customer_id}
                      onChange={(e) => setFormData({ ...formData, customer_id: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                    >
                      <option value="">Müşteri seçin</option>
                      {customers.map((customer) => (
                        <option key={customer.id} value={customer.id}>
                          {customer.first_name} {customer.last_name} - {customer.email}
                        </option>
                      ))}
                    </select>
                  </div>

                  {/* Eşya Alındığı Yer */}
                  <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h4 className="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                      <MapPinIcon className="h-5 w-5 mr-2" />
                      Eşya Alındığı Yer
                    </h4>
                    <div className="mb-4">
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Açık Adres <span className="text-red-500">*</span>
                      </label>
                      <textarea
                        required
                        rows={3}
                        value={formData.pickup_address}
                        onChange={(e) => setFormData({ ...formData, pickup_address: e.target.value })}
                        placeholder="İl, İlçe, Mahalle, Sokak, Bina No vb. tam adres bilgisi"
                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                      />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kat Durumu</label>
                        <input
                          type="text"
                          value={formData.pickup_floor_status}
                          onChange={(e) => setFormData({ ...formData, pickup_floor_status: e.target.value })}
                          placeholder="örn: Zemin, 1. Kat, 2. Kat"
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asansör Durumu</label>
                        <select
                          value={formData.pickup_elevator_status}
                          onChange={(e) => setFormData({ ...formData, pickup_elevator_status: e.target.value })}
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        >
                          <option value="">Seçin</option>
                          <option value="Var">Var</option>
                          <option value="Yok">Yok</option>
                        </select>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Oda Sayısı</label>
                        <input
                          type="number"
                          min="0"
                          value={formData.pickup_room_count}
                          onChange={(e) =>
                            setFormData({ ...formData, pickup_room_count: e.target.value === '' ? '' : Number(e.target.value) })
                          }
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Eşyanın Gittiği Adres */}
                  <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h4 className="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                      <MapPinIcon className="h-5 w-5 mr-2" />
                      Eşyanın Gittiği Adres
                    </h4>
                    <div className="mb-4">
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Açık Adres <span className="text-red-500">*</span>
                      </label>
                      <textarea
                        required
                        rows={3}
                        value={formData.delivery_address}
                        onChange={(e) => setFormData({ ...formData, delivery_address: e.target.value })}
                        placeholder="İl, İlçe, Mahalle, Sokak, Bina No vb. tam adres bilgisi"
                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                      />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kat Durumu</label>
                        <input
                          type="text"
                          value={formData.delivery_floor_status}
                          onChange={(e) => setFormData({ ...formData, delivery_floor_status: e.target.value })}
                          placeholder="örn: Zemin, 1. Kat, 2. Kat"
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asansör Durumu</label>
                        <select
                          value={formData.delivery_elevator_status}
                          onChange={(e) => setFormData({ ...formData, delivery_elevator_status: e.target.value })}
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        >
                          <option value="">Seçin</option>
                          <option value="Var">Var</option>
                          <option value="Yok">Yok</option>
                        </select>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Oda Sayısı</label>
                        <input
                          type="number"
                          min="0"
                          value={formData.delivery_room_count}
                          onChange={(e) =>
                            setFormData({ ...formData, delivery_room_count: e.target.value === '' ? '' : Number(e.target.value) })
                          }
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Diğer Bilgiler */}
                  <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h4 className="text-md font-semibold text-gray-900 dark:text-white mb-4">Diğer Bilgiler</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Personel <span className="text-gray-500">(Çoklu seçim)</span>
                        </label>
                        <select
                          multiple
                          value={formData.staff_ids}
                          onChange={(e) => {
                            const selectedIds = Array.from(e.target.selectedOptions, (option) => option.value);
                            setFormData({ ...formData, staff_ids: selectedIds });
                          }}
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white min-h-[100px]"
                          size={5}
                        >
                          {staff.map((staffMember) => (
                            <option key={staffMember.id} value={staffMember.id}>
                              {staffMember.first_name} {staffMember.last_name} ({staffMember.email})
                            </option>
                          ))}
                        </select>
                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                          Çoklu seçim için Ctrl (Windows) veya Cmd (Mac) tuşuna basılı tutun
                        </p>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">İş Tarihi</label>
                        <input
                          type="date"
                          value={formData.job_date}
                          onChange={(e) => setFormData({ ...formData, job_date: e.target.value })}
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiyat</label>
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={formData.price}
                          onChange={(e) =>
                            setFormData({ ...formData, price: e.target.value === '' ? '' : Number(e.target.value) })
                          }
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">KDV Oranı (%)</label>
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          max="100"
                          value={formData.vat_rate}
                          onChange={(e) =>
                            setFormData({ ...formData, vat_rate: e.target.value === '' ? 20 : Number(e.target.value) })
                          }
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
                        <select
                          value={formData.status}
                          onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        >
                          <option value="pending">Beklemede</option>
                          <option value="in_progress">Devam Ediyor</option>
                          <option value="completed">Tamamlandı</option>
                          <option value="cancelled">İptal Edildi</option>
                        </select>
                      </div>
                    </div>
                    <div className="mt-4 space-y-2">
                      <label className="flex items-center">
                        <input
                          type="checkbox"
                          checked={formData.price_includes_vat}
                          onChange={(e) => setFormData({ ...formData, price_includes_vat: e.target.checked })}
                          className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                        <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">KDV Dahil</span>
                      </label>
                      <label className="flex items-center">
                        <input
                          type="checkbox"
                          checked={formData.is_paid}
                          onChange={(e) => setFormData({ ...formData, is_paid: e.target.checked })}
                          className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                        <span className="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Ödeme Alındı</span>
                      </label>
                    </div>
                    <div className="mt-4">
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        PDF Sözleşme (Opsiyonel)
                      </label>
                      <input
                        type="file"
                        accept=".pdf"
                        onChange={(e) => {
                          const file = e.target.files?.[0] || null;
                          setFormData({ ...formData, contract_pdf_file: file });
                        }}
                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                      />
                      {formData.contract_pdf_file && (
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                          Seçilen dosya: {formData.contract_pdf_file.name}
                        </p>
                      )}
                      {formData.contract_pdf_url && !formData.contract_pdf_file && (
                        <div className="mt-2">
                          <a
                            href={formData.contract_pdf_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center"
                          >
                            <DocumentTextIcon className="h-4 w-4 mr-1" />
                            Mevcut PDF'i görüntüle
                          </a>
                        </div>
                      )}
                    </div>
                    <div className="mt-4">
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notlar</label>
                      <textarea
                        rows={4}
                        value={formData.notes}
                        onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                      />
                    </div>
                  </div>

                  <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                      type="button"
                      onClick={handleCloseModal}
                      className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                    >
                      İptal
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700"
                    >
                      {isEditMode ? 'Güncelle' : 'Kaydet'}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Delete Modal */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => !deleteLoading && setDeleteTarget(null)}
            />
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">Nakliye İşini Sil</h3>
                  <button
                    onClick={() => !deleteLoading && setDeleteTarget(null)}
                    className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  >
                    <XMarkIcon className="h-6 w-6" />
                  </button>
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  Bu nakliye işini silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
                </p>
                <div className="flex justify-end space-x-3">
                  <button
                    onClick={() => !deleteLoading && setDeleteTarget(null)}
                    disabled={deleteLoading}
                    className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                  >
                    İptal
                  </button>
                  <button
                    onClick={handleDelete}
                    disabled={deleteLoading}
                    className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50"
                  >
                    {deleteLoading ? 'Siliniyor...' : 'Sil'}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
