import { useEffect, useState, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { contractsApi } from '../../services/api/contractsApi';
import { paymentsApi } from '../../services/api/paymentsApi';
import { itemsApi } from '../../services/api/itemsApi';
import { companiesApi, BankAccount } from '../../services/api/companiesApi';
import { ArrowLeftIcon, PrinterIcon, PlusIcon, XMarkIcon, PencilIcon, TrashIcon, CurrencyDollarIcon, CreditCardIcon, BuildingLibraryIcon, CubeIcon } from '@heroicons/react/24/outline';
import { formatTurkishCurrency } from '../../utils/inputFormatters';
import { EditContractModal } from '../../components/modals/EditContractModal';
import toast from 'react-hot-toast';

export function ContractDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [contract, setContract] = useState<any>(null);
  const [, setDebtInfo] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [showAddPaymentModal, setShowAddPaymentModal] = useState(false);
  const [paymentType, setPaymentType] = useState<'monthly' | 'intermediate' | null>(null);
  const [, setSelectedMonth] = useState<string | null>(null);
  const [paymentLoading, setPaymentLoading] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [editFormData, setEditFormData] = useState({
    start_date: '',
    end_date: '',
    transportation_fee: 0,
    pickup_location: '',
    discount: 0,
    driver_name: '',
    driver_phone: '',
    vehicle_plate: '',
    notes: '',
  });
  const [saving, setSaving] = useState(false);
  const [showPaymentMethodModal, setShowPaymentMethodModal] = useState(false);
  const [pendingMonthPrice, setPendingMonthPrice] = useState<any>(null);
  const [bankAccounts, setBankAccounts] = useState<BankAccount[]>([]);
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<'cash' | 'credit_card' | 'bank_transfer' | null>(null);
  const [selectedBankAccountId, setSelectedBankAccountId] = useState<string | null>(null);
  const [transactionId, setTransactionId] = useState('');
  const [paymentNotes, setPaymentNotes] = useState('');
  const [items, setItems] = useState<any[]>([]);
  const [itemsLoading, setItemsLoading] = useState(false);

  useEffect(() => {
    if (id) {
      loadContract();
    } else {
      setLoading(false);
    }
    loadBankAccounts();
  }, [id]);

  const loadBankAccounts = async () => {
    try {
      const accounts = await companiesApi.getActiveBankAccounts();
      setBankAccounts(accounts);
      if (accounts.length > 0 && !selectedBankAccountId) {
        setSelectedBankAccountId(accounts[0].id);
      }
    } catch (error) {
      console.error('Error loading bank accounts:', error);
    }
  };

  const loadContract = async () => {
    try {
      setError(null);
      setItems([]);
      setItemsLoading(true);
      // getTotalDebt çağrısını kaldırdık - frontend'de hesaplanıyor
      const contractData = await contractsApi.getById(id!);
      setContract(contractData);
      // Eşya listesini yükle
      try {
        const itemsList = await itemsApi.getAll(id!);
        setItems(Array.isArray(itemsList) ? itemsList : []);
      } catch (e) {
        console.error('Error loading items:', e);
        setItems([]);
      } finally {
        setItemsLoading(false);
      }
      // Debt bilgisini frontend'de hesapla
      const monthlyTotal = contractData.monthly_prices?.reduce((sum: number, mp: any) => sum + Number(mp.price), 0) || 0;
      const transportationFee = Number(contractData.transportation_fee || 0);
      const discount = Number(contractData.discount || 0);
      const totalDebt = monthlyTotal + transportationFee - discount;
      const paidAmount = contractData.payments?.filter((p: any) => p.status === 'paid').reduce((sum: number, p: any) => sum + Number(p.amount), 0) || 0;
      setDebtInfo({ totalDebt, paidAmount, remainingDebt: totalDebt - paidAmount });
      
      // Form verilerini doldur
      if (contractData) {
        setEditFormData({
          start_date: contractData.start_date ? new Date(contractData.start_date).toISOString().split('T')[0] : '',
          end_date: contractData.end_date ? new Date(contractData.end_date).toISOString().split('T')[0] : '',
          transportation_fee: contractData.transportation_fee || 0,
          pickup_location: contractData.pickup_location || '',
          discount: contractData.discount || 0,
          driver_name: contractData.driver_name || '',
          driver_phone: contractData.driver_phone || '',
          vehicle_plate: contractData.vehicle_plate || '',
          notes: contractData.notes || '',
        });
      }
    } catch (error: any) {
      console.error('Error loading contract:', error);
      const message = error.response?.data?.message;
      if (error.response?.status === 404) {
        setError(message || 'Sözleşme bulunamadı. Lütfen geçerli bir sözleşme ID\'si kullandığınızdan emin olun.');
      } else if (error.response?.status === 403) {
        setError(message || 'Bu sözleşmeye erişim yetkiniz yok.');
      } else {
        setError(message || 'Sözleşme yüklenirken bir hata oluştu.');
      }
    } finally {
      setLoading(false);
      setItemsLoading(false);
    }
  };

  const handleSave = async () => {
    if (!id || saving) return;
    
    setSaving(true);
    setError(null);
    try {
      await contractsApi.update(id, editFormData);
      await loadContract();
      setIsEditing(false);
      toast.success('Sözleşme bilgileri kaydedildi');
    } catch (error: any) {
      setError(error.response?.data?.message || 'Sözleşme güncellenirken bir hata oluştu');
      toast.error(error.response?.data?.message || 'Sözleşme güncellenirken bir hata oluştu');
    } finally {
      setSaving(false);
    }
  };

  const handleCancelEdit = () => {
    if (contract) {
      setEditFormData({
        start_date: contract.start_date ? new Date(contract.start_date).toISOString().split('T')[0] : '',
        end_date: contract.end_date ? new Date(contract.end_date).toISOString().split('T')[0] : '',
        transportation_fee: contract.transportation_fee || 0,
        pickup_location: contract.pickup_location || '',
        discount: contract.discount || 0,
        driver_name: contract.driver_name || '',
        driver_phone: contract.driver_phone || '',
        vehicle_plate: contract.vehicle_plate || '',
        notes: contract.notes || '',
      });
    }
    setIsEditing(false);
  };

  const handleDelete = async () => {
    if (!id) return;
    try {
      await contractsApi.delete(id);
      navigate('/contracts');
    } catch (error) {
      console.error('Error deleting contract:', error);
      toast.error('Sözleşme silinirken bir hata oluştu');
    }
  };

  const handlePrint = () => {
    window.print();
  };

  const handleCheckboxChange = async (monthPrice: any, checked: boolean) => {
    if (!contract || !id) return;

    if (checked) {
      // Ödeme yöntemi seçimi için modal göster
      setPendingMonthPrice(monthPrice);
      setShowPaymentMethodModal(true);
    } else {
      // Ödemeyi iptal et (beklemede yap)
      setPaymentLoading(true);
      try {
        const existingPayment = contract.payments?.find((p: any) => {
          const paymentMonth = new Date(p.due_date).toISOString().slice(0, 7);
          return paymentMonth === monthPrice.month && p.status === 'paid';
        });

        if (existingPayment) {
          await paymentsApi.update(existingPayment.id, {
            status: 'pending',
            paid_at: null,
            payment_method: null,
            bank_account_id: null,
          });
        }
        await loadContract();
      } catch (error) {
        console.error('Error updating payment:', error);
        toast.error('Ödeme güncellenirken bir hata oluştu');
      } finally {
        setPaymentLoading(false);
      }
    }
  };

  const handlePaymentMethodSelect = async (method: 'cash' | 'credit_card' | 'bank_transfer') => {
    if (!pendingMonthPrice) return;

    if (method === 'bank_transfer' && !selectedBankAccountId) {
      toast.error('Lütfen bir banka hesabı seçin');
      return;
    }

    setSelectedPaymentMethod(method);
    
    if (method === 'cash' || method === 'credit_card') {
      // Nakit veya kredi kartı için direkt kaydet
      await savePaymentWithMethod(method, null);
    } else {
      // Havale için transaction ID ve notes almak için form göster
      // Bu durumda modal içinde form gösterilecek
    }
  };

  const savePaymentWithMethod = async (method: 'cash' | 'credit_card' | 'bank_transfer', bankAccountId: string | null) => {
    if (!contract || !id || !pendingMonthPrice) return;

    setPaymentLoading(true);
    try {
      const [year, month] = pendingMonthPrice.month.split('-');
      const dueDate = new Date(parseInt(year), parseInt(month) - 1, 1);
      
      // Bu ay için zaten ödeme var mı kontrol et
      const existingPayment = contract.payments?.find((p: any) => {
        const paymentMonth = new Date(p.due_date).toISOString().slice(0, 7);
        return paymentMonth === pendingMonthPrice.month;
      });

      if (existingPayment) {
        // Mevcut ödemeyi güncelle
        await paymentsApi.markAsPaid(
          existingPayment.id,
          method,
          transactionId || undefined,
          paymentNotes || undefined,
          bankAccountId || undefined
        );
      } else {
        // Yeni ödeme oluştur
        const paymentNumber = `PAY-${new Date().getFullYear()}-${String(Date.now()).slice(-6)}`;
        await paymentsApi.create({
          payment_number: paymentNumber,
          contract_id: contract.id,
          amount: pendingMonthPrice.price,
          status: 'paid',
          due_date: dueDate.toISOString(),
          paid_at: new Date().toISOString(),
          payment_method: method,
          bank_account_id: bankAccountId,
          transaction_id: transactionId || null,
          notes: paymentNotes || null,
        });
      }
      
      // Modal'ı kapat ve state'i temizle
      setShowPaymentMethodModal(false);
      setPendingMonthPrice(null);
      setSelectedPaymentMethod(null);
      setTransactionId('');
      setPaymentNotes('');
      
      // Sözleşmeyi yeniden yükle
      await loadContract();
      toast.success('Ödeme başarıyla kaydedildi');
    } catch (error) {
      console.error('Error updating payment:', error);
      toast.error('Ödeme güncellenirken bir hata oluştu');
    } finally {
      setPaymentLoading(false);
    }
  };

  const handleAddPayment = async (formData: any) => {
    if (!contract || !id || paymentLoading) return;

    // Havale için banka hesabı kontrolü
    if (formData.payment_method === 'bank_transfer' && !formData.bank_account_id) {
      toast.error('Havale ödemesi için lütfen bir banka hesabı seçin');
      return;
    }

    setPaymentLoading(true);
    try {
      const paymentNumber = `PAY-${new Date().getFullYear()}-${String(Date.now()).slice(-6)}`;
      
      await paymentsApi.create({
        payment_number: paymentNumber,
        contract_id: contract.id,
        amount: formData.amount,
        status: 'paid',
        due_date: formData.due_date || new Date().toISOString(),
        paid_at: formData.paid_at || new Date().toISOString(),
        payment_method: formData.payment_method || 'cash',
        bank_account_id: formData.bank_account_id || null,
        transaction_id: formData.transaction_id || null,
        notes: formData.notes || null,
      });

      setShowAddPaymentModal(false);
      setPaymentType(null);
      setSelectedMonth(null);
      await loadContract();
      toast.success('Ara ödeme başarıyla eklendi');
    } catch (error) {
      console.error('Error creating payment:', error);
      toast.error('Ödeme eklenirken bir hata oluştu');
    } finally {
      setPaymentLoading(false);
    }
  };

  // Hesaplamaları useMemo ile optimize ettik (hooks kuralları için tüm return'lerden önce)
  const { monthlyTotal, transportationFee, discount, totalDebt, paidAmount, remainingDebt } = useMemo(() => {
    if (!contract) {
      return { monthlyTotal: 0, transportationFee: 0, discount: 0, totalDebt: 0, paidAmount: 0, remainingDebt: 0 };
    }
    const monthly = contract.monthly_prices?.reduce((sum: number, mp: any) => sum + Number(mp.price), 0) || 0;
    const transport = Number(contract.transportation_fee || 0);
    const disc = Number(contract.discount || 0);
    const total = monthly + transport - disc;
    const paid = contract.payments?.filter((p: any) => p.status === 'paid').reduce((sum: number, p: any) => sum + Number(p.amount), 0) || 0;
    return {
      monthlyTotal: monthly,
      transportationFee: transport,
      discount: disc,
      totalDebt: total,
      paidAmount: paid,
      remainingDebt: total - paid,
    };
  }, [contract]);

  if (!id) {
    return (
      <div className="modern-card p-8 text-center">
        <p className="text-gray-600 dark:text-gray-400 mb-4">Geçersiz sözleşme adresi.</p>
        <button
          onClick={() => navigate('/contracts')}
          className="btn-primary inline-flex items-center px-6 py-3"
        >
          <ArrowLeftIcon className="h-5 w-5 mr-2" />
          Sözleşmelere Dön
        </button>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="modern-card p-8 text-center">
        <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="modern-card p-8 text-center">
        <div className="text-center">
          <p className="text-red-600 dark:text-red-400 mb-4 text-lg font-semibold">{error}</p>
          <button
            onClick={() => navigate('/contracts')}
            className="btn-primary inline-flex items-center px-6 py-3"
          >
            <ArrowLeftIcon className="h-5 w-5 mr-2" />
            Sözleşmelere Dön
          </button>
        </div>
      </div>
    );
  }

  if (!contract) {
    return (
      <div className="modern-card p-8 text-center">
        <p className="text-gray-600 dark:text-gray-400">Sözleşme bulunamadı.</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <button
          onClick={() => navigate('/contracts')}
          className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
        >
          <ArrowLeftIcon className="h-5 w-5 mr-2" />
          Geri Dön
        </button>
        <div className="flex items-center gap-2">
          {!isEditing ? (
            <button
              onClick={() => setIsEditing(true)}
              className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            >
              <PencilIcon className="h-5 w-5 mr-2" />
              Düzenle
            </button>
          ) : (
            <button
              onClick={handleCancelEdit}
              disabled={saving}
              className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
            >
              İptal
            </button>
          )}
          <button
            onClick={handleSave}
            disabled={saving || !isEditing}
            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {saving ? 'Kaydediliyor...' : 'Kaydet'}
          </button>
          <button
            onClick={() => setShowDeleteConfirm(true)}
            className="inline-flex items-center px-4 py-2 border border-red-300 dark:border-red-600 rounded-md shadow-sm text-sm font-medium text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
          >
            <TrashIcon className="h-5 w-5 mr-2" />
            Sil
          </button>
          <button
            onClick={handlePrint}
            className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            <PrinterIcon className="h-5 w-5 mr-2" />
            Yazdır
          </button>
        </div>
      </div>

      {/* Fatura Görünümü */}
      <div className="modern-card-gradient p-8 print:shadow-none">
        <div className="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
          <div className="flex justify-between items-start">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">FATURA</h1>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Sözleşme No: <span className="font-medium">{contract.contract_number}</span>
              </p>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Tarih: {new Date(contract.created_at).toLocaleDateString('tr-TR')}
              </p>
            </div>
            <div className="text-right">
              <p className="text-sm font-medium text-gray-900 dark:text-white">DEPOPAZAR</p>
              <p className="text-sm text-gray-600 dark:text-gray-400">Eşya Depolama Hizmetleri</p>
            </div>
          </div>
        </div>

        {/* Müşteri Bilgileri */}
        <div className="grid grid-cols-2 gap-6 mb-6">
          <div>
            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">MÜŞTERİ BİLGİLERİ</h3>
            <div className="text-sm text-gray-600 dark:text-gray-400 space-y-1">
              <p><strong>Ad Soyad:</strong> {(contract.customer?.first_name ?? '') + ' ' + (contract.customer?.last_name ?? '') || '-'}</p>
              <p><strong>E-posta:</strong> {contract.customer?.email ?? '-'}</p>
              <p><strong>Telefon:</strong> {contract.customer?.phone ?? '-'}</p>
              <p><strong>Adres:</strong> {contract.customer?.address ?? '-'}</p>
            </div>
          </div>
          <div>
            <div className="flex items-center justify-between gap-2 mb-2">
              <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300">SÖZLEŞME BİLGİLERİ</h3>
              {isEditing && (
                <button
                  type="button"
                  onClick={handleSave}
                  disabled={saving}
                  className="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                >
                  {saving ? 'Kaydediliyor...' : 'Kaydet'}
                </button>
              )}
            </div>
            <div className="text-sm text-gray-600 dark:text-gray-400 space-y-1">
              <p><strong>Oda:</strong> {(contract.room?.room_number ?? '-') + (contract.room?.warehouse?.name ? ` - ${contract.room.warehouse.name}` : '')}</p>
              
              {isEditing ? (
                <>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Başlangıç Tarihi *</label>
                    <input
                      type="date"
                      required
                      value={editFormData.start_date}
                      onChange={(e) => setEditFormData({ ...editFormData, start_date: e.target.value })}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Bitiş Tarihi *</label>
                    <input
                      type="date"
                      required
                      value={editFormData.end_date}
                      onChange={(e) => setEditFormData({ ...editFormData, end_date: e.target.value })}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Alınan Yer</label>
                    <input
                      type="text"
                      value={editFormData.pickup_location}
                      onChange={(e) => setEditFormData({ ...editFormData, pickup_location: e.target.value })}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                      placeholder="Eşyanın alındığı yer"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Nakliye Ücreti (TL)</label>
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      value={editFormData.transportation_fee}
                      onChange={(e) => setEditFormData({ ...editFormData, transportation_fee: Number.parseFloat(e.target.value) || 0 })}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">İndirim (TL)</label>
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      value={editFormData.discount}
                      onChange={(e) => setEditFormData({ ...editFormData, discount: Number.parseFloat(e.target.value) || 0 })}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Şoför Adı</label>
                    <input
                      type="text"
                      value={editFormData.driver_name}
                      onChange={(e) => setEditFormData({ ...editFormData, driver_name: e.target.value })}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Şoför Telefonu</label>
                    <input
                      type="text"
                      value={editFormData.driver_phone}
                      onChange={(e) => setEditFormData({ ...editFormData, driver_phone: e.target.value })}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Araç Plakası</label>
                    <input
                      type="text"
                      value={editFormData.vehicle_plate}
                      onChange={(e) => setEditFormData({ ...editFormData, vehicle_plate: e.target.value })}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Notlar</label>
                    <textarea
                      value={editFormData.notes}
                      onChange={(e) => setEditFormData({ ...editFormData, notes: e.target.value })}
                      rows={3}
                      className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                      placeholder="Sözleşme ile ilgili notlar"
                    />
                  </div>
                </>
              ) : (
                <>
                  <p><strong>Başlangıç:</strong> {new Date(contract.start_date).toLocaleDateString('tr-TR')}</p>
                  <p><strong>Bitiş:</strong> {new Date(contract.end_date).toLocaleDateString('tr-TR')}</p>
                  {contract.pickup_location && (
                    <p><strong>Alınan Yer:</strong> {contract.pickup_location}</p>
                  )}
                  {contract.contract_staff && contract.contract_staff.length > 0 && (
                    <p><strong>Personel:</strong> {contract.contract_staff.map((cs: any) => `${cs.user?.first_name} ${cs.user?.last_name}`).join(', ')}</p>
                  )}
                  {contract.sold_by_user && !contract.contract_staff?.length && (
                    <p><strong>Satışı Yapan:</strong> {contract.sold_by_user.first_name} {contract.sold_by_user.last_name}</p>
                  )}
                  {contract.driver_name && (
                    <p><strong>Şoför:</strong> {contract.driver_name} {contract.driver_phone && `(${contract.driver_phone})`}</p>
                  )}
                  {contract.vehicle_plate && (
                    <p><strong>Araç Plakası:</strong> {contract.vehicle_plate}</p>
                  )}
                  {contract.notes && (
                    <p><strong>Genel Notlar:</strong> {contract.notes}</p>
                  )}
                </>
              )}
            </div>
          </div>
        </div>

        {/* Eşya Listesi - sadece eşya eklenmişse göster */}
        {items.length > 0 && (
          <div className="mb-6 border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
              <CubeIcon className="h-5 w-5 text-primary-500" />
              EŞYA LİSTESİ
            </h3>
            {itemsLoading ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Eşyalar yükleniyor...</p>
            ) : (
              <div className="space-y-4">
                {items.map((item: any, index: number) => {
                  const photoUrls: string[] = (() => {
                    if (!item.photo_url) return [];
                    try {
                      const parsed = typeof item.photo_url === 'string' ? JSON.parse(item.photo_url) : item.photo_url;
                      return Array.isArray(parsed) ? parsed : [];
                    } catch {
                      return [];
                    }
                  })();
                  const conditionLabels: Record<string, string> = {
                    new: 'Yeni',
                    used: 'Kullanılmış',
                    packaged: 'Paketli',
                    damaged: 'Hasarlı',
                  };
                  return (
                    <div
                      key={item.id || index}
                      className="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50"
                    >
                      <div className="flex flex-wrap gap-4">
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-semibold text-gray-900 dark:text-white">
                            {item.name || `Eşya #${index + 1}`}
                          </p>
                          {item.description && (
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{item.description}</p>
                          )}
                          {item.condition && (
                            <span className="inline-flex items-center mt-2 px-2 py-0.5 rounded text-xs font-medium bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200">
                              {conditionLabels[item.condition] || item.condition}
                            </span>
                          )}
                        </div>
                        {photoUrls.length > 0 && (
                          <div className="flex flex-wrap gap-2 items-start">
                            {photoUrls.map((url: string, photoIndex: number) => (
                              <a
                                key={photoIndex}
                                href={itemsApi.getPhotoFullUrl(url)}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="block shrink-0"
                              >
                                <img
                                  src={itemsApi.getPhotoFullUrl(url)}
                                  alt={`${item.name || 'Eşya'} - Fotoğraf ${photoIndex + 1}`}
                                  className="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600 hover:opacity-90 transition-opacity"
                                />
                              </a>
                            ))}
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        )}

        {/* Aylık Fiyatlandırma Tablosu */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-3">
            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300">AYLIK FİYATLANDIRMA</h3>
            <button
              onClick={() => {
                setPaymentType('intermediate');
                setShowAddPaymentModal(true);
              }}
              className="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <PlusIcon className="h-4 w-4 mr-1" />
              Ara Ödeme Ekle
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ay</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Fiyat (TL)</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Not</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ödendi</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ödeme Durumu</th>
                </tr>
              </thead>
              <tbody>
                {contract.monthly_prices?.map((mp: any, index: number) => {
                  const monthPayment = contract.payments?.find((p: any) => {
                    const paymentMonth = new Date(p.due_date).toISOString().slice(0, 7);
                    return paymentMonth === mp.month && (p.type === 'warehouse' || !p.type);
                  });
                  const isPaid = monthPayment?.status === 'paid';
                  
                  return (
                    <tr key={index}>
                      <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {new Date(mp.month + '-01').toLocaleDateString('tr-TR', { year: 'numeric', month: 'long' })}
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                        {formatTurkishCurrency(Number(mp.price))}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        {mp.notes || '-'}
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap text-center">
                        <input
                          type="checkbox"
                          checked={isPaid}
                          onChange={(e) => handleCheckboxChange(mp, e.target.checked)}
                          disabled={paymentLoading}
                          className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded disabled:opacity-50"
                        />
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap text-center">
                        {isPaid ? (
                          <div className="space-y-1">
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                              Ödendi
                            </span>
                            {monthPayment && (
                              <div className="text-xs text-gray-500 dark:text-gray-400 mt-1 text-left">
                                {monthPayment.payment_method === 'cash' && (
                                  <div className="flex items-center gap-1">
                                    <CurrencyDollarIcon className="h-3 w-3 text-green-600" />
                                    <span>Nakit</span>
                                  </div>
                                )}
                                {monthPayment.payment_method === 'credit_card' && (
                                  <div className="flex items-center gap-1">
                                    <CreditCardIcon className="h-3 w-3 text-purple-600" />
                                    <span>Kredi Kartı</span>
                                  </div>
                                )}
                                {monthPayment.payment_method === 'bank_transfer' && (
                                  <div className="flex items-center gap-1">
                                    <BuildingLibraryIcon className="h-3 w-3 text-blue-600" />
                                    <span>
                                      {monthPayment.bank_account?.bank_name || 'Havale/EFT'}
                                      {monthPayment.transaction_id && (
                                        <span className="block text-xs text-gray-400 mt-0.5">
                                          İşlem: {monthPayment.transaction_id}
                                        </span>
                                      )}
                                    </span>
                                  </div>
                                )}
                                {monthPayment.notes && (
                                  <div className="text-xs text-gray-400 mt-1 italic">
                                    {monthPayment.notes}
                                  </div>
                                )}
                              </div>
                            )}
                          </div>
                        ) : (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            Beklemede
                          </span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>

        {/* Ödemeler */}
        {contract.payments && contract.payments.length > 0 && (
          <div className="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ÖDEME GEÇMİŞİ</h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ödeme No</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tarih</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tutar (TL)</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ödeme Yöntemi</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Detaylar</th>
                    <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durum</th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  {contract.payments
                    .sort((a: any, b: any) => new Date(b.due_date).getTime() - new Date(a.due_date).getTime())
                    .map((payment: any) => {
                      const isMonthlyPayment = payment.type === 'warehouse' || (!payment.type && contract.monthly_prices?.some((mp: any) => {
                        const paymentMonth = new Date(payment.due_date).toISOString().slice(0, 7);
                        return paymentMonth === mp.month;
                      }));
                      const isTransportationPayment = payment.type === 'transportation';
                      const isIntermediatePayment = !isMonthlyPayment && !isTransportationPayment;
                      
                      return (
                        <tr key={payment.id} className={isIntermediatePayment ? 'bg-blue-50 dark:bg-blue-900/20' : isTransportationPayment ? 'bg-amber-50 dark:bg-amber-900/20' : ''}>
                          <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {payment.payment_number}
                            {isIntermediatePayment && (
                              <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                Ara Ödeme
                              </span>
                            )}
                            {isTransportationPayment && (
                              <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                Nakliye
                              </span>
                            )}
                          </td>
                          <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {new Date(payment.due_date).toLocaleDateString('tr-TR', {
                              year: 'numeric',
                              month: 'long',
                              day: 'numeric'
                            })}
                            {payment.paid_at && (
                              <p className="text-xs text-gray-500 dark:text-gray-400">
                                Ödendi: {new Date(payment.paid_at).toLocaleDateString('tr-TR')}
                              </p>
                            )}
                          </td>
                          <td className="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                            {formatTurkishCurrency(Number(payment.amount))}
                          </td>
                          <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {payment.payment_method === 'cash' ? (
                              <div className="flex items-center gap-2">
                                <CurrencyDollarIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                                <span className="font-medium">Nakit</span>
                              </div>
                            ) : 
                             payment.payment_method === 'credit_card' ? (
                              <div className="flex items-center gap-2">
                                <CreditCardIcon className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                <span className="font-medium">Kredi Kartı</span>
                              </div>
                             ) : 
                             payment.payment_method === 'bank_transfer' ? (
                               <div className="flex items-center gap-2">
                                 <BuildingLibraryIcon className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                 <div>
                                   <div className="font-medium">Havale/EFT</div>
                                   {payment.bank_account && (
                                     <div className="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                       {payment.bank_account.bank_name}
                                     </div>
                                   )}
                                 </div>
                               </div>
                             ) : 
                             payment.payment_method || '-'}
                          </td>
                          <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            <div className="space-y-1">
                              {payment.payment_method === 'bank_transfer' && payment.bank_account && (
                                <div className="text-xs">
                                  <div className="font-medium text-gray-700 dark:text-gray-300">Banka Hesabı:</div>
                                  <div className="text-gray-600 dark:text-gray-400">
                                    {payment.bank_account.account_holder_name}
                                  </div>
                                  <div className="text-gray-600 dark:text-gray-400">
                                    {payment.bank_account.account_number}
                                  </div>
                                  {payment.bank_account.iban && (
                                    <div className="text-gray-600 dark:text-gray-400">
                                      IBAN: {payment.bank_account.iban}
                                    </div>
                                  )}
                                </div>
                              )}
                              {payment.transaction_id && (
                                <div className="text-xs">
                                  <div className="font-medium text-gray-700 dark:text-gray-300">İşlem No:</div>
                                  <div className="text-gray-600 dark:text-gray-400 font-mono">
                                    {payment.transaction_id}
                                  </div>
                                </div>
                              )}
                              {payment.notes && (
                                <div className="text-xs mt-2">
                                  <div className="font-medium text-gray-700 dark:text-gray-300">Notlar:</div>
                                  <div className="text-gray-600 dark:text-gray-400 italic">
                                    {payment.notes}
                                  </div>
                                </div>
                              )}
                              {!payment.transaction_id && !payment.notes && (!payment.bank_account || payment.payment_method !== 'bank_transfer') && (
                                <span className="text-xs text-gray-400">-</span>
                              )}
                            </div>
                          </td>
                          <td className="px-4 py-3 whitespace-nowrap text-center">
                            <span
                              className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                payment.status === 'paid'
                                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                  : payment.status === 'overdue'
                                  ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                  : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                              }`}
                            >
                              {payment.status === 'paid' ? 'Ödendi' : payment.status === 'overdue' ? 'Gecikmiş' : 'Beklemede'}
                            </span>
                          </td>
                        </tr>
                      );
                    })}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Özet */}
        <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
          <div className="flex justify-end">
            <div className="w-64 space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600 dark:text-gray-400">Aylık Toplam:</span>
                <span className="font-medium text-gray-900 dark:text-white">{formatTurkishCurrency(monthlyTotal)}</span>
              </div>
              {transportationFee > 0 && (
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600 dark:text-gray-400">Nakliye Ücreti:</span>
                  <span className="font-medium text-gray-900 dark:text-white">{formatTurkishCurrency(transportationFee)}</span>
                </div>
              )}
              {discount > 0 && (
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600 dark:text-gray-400">İndirim:</span>
                  <span className="font-medium text-red-600 dark:text-red-400">-{formatTurkishCurrency(discount)}</span>
                </div>
              )}
              <div className="flex justify-between text-sm border-t border-gray-200 dark:border-gray-700 pt-2">
                <span className="font-medium text-gray-900 dark:text-white">Toplam Borç:</span>
                <span className="font-bold text-lg text-gray-900 dark:text-white">{formatTurkishCurrency(totalDebt)}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-600 dark:text-gray-400">Ödenen:</span>
                <span className="font-medium text-green-600 dark:text-green-400">{formatTurkishCurrency(paidAmount)}</span>
              </div>
              <div className="flex justify-between text-sm border-t border-gray-200 dark:border-gray-700 pt-2">
                <span className="font-medium text-gray-900 dark:text-white">Kalan Borç:</span>
                <span className="font-bold text-lg text-primary-600 dark:text-primary-400">{formatTurkishCurrency(remainingDebt)}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Altta Kaydet / İptal (ödendi tiklari vb. için) */}
        {isEditing && (
          <div className="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6 flex justify-end gap-3">
            <button
              onClick={handleCancelEdit}
              disabled={saving}
              className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
            >
              İptal
            </button>
            <button
              onClick={handleSave}
              disabled={saving}
              className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
            >
              {saving ? 'Kaydediliyor...' : 'Kaydet'}
            </button>
          </div>
        )}
      </div>

      {/* Silme Onay Modal */}
      {showDeleteConfirm && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => setShowDeleteConfirm(false)}
            />
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div className="sm:flex sm:items-start">
                  <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                    <TrashIcon className="h-6 w-6 text-red-600 dark:text-red-400" />
                  </div>
                  <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                      Sözleşmeyi Sil
                    </h3>
                    <div className="mt-2">
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        Bu sözleşmeyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.
                      </p>
                    </div>
                  </div>
                </div>
                <div className="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                  <button
                    type="button"
                    onClick={handleDelete}
                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                  >
                    Sil
                  </button>
                  <button
                    type="button"
                    onClick={() => setShowDeleteConfirm(false)}
                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto sm:text-sm"
                  >
                    İptal
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Error Message */}
      {error && !isEditing && (
        <div className="modern-card p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
          <p className="text-sm text-red-800 dark:text-red-300">{error}</p>
        </div>
      )}

      {/* Edit Contract Modal */}
      <EditContractModal
        isOpen={showEditModal}
        onClose={() => setShowEditModal(false)}
        onSuccess={() => {
          loadContract();
          setShowEditModal(false);
        }}
        contract={contract}
      />

      {/* Ödeme Yöntemi Seçim Modal */}
      {showPaymentMethodModal && pendingMonthPrice && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => {
                if (!paymentLoading) {
                  setShowPaymentMethodModal(false);
                  setPendingMonthPrice(null);
                  setSelectedPaymentMethod(null);
                  setTransactionId('');
                  setPaymentNotes('');
                }
              }}
            />
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    Ödeme Yöntemi Seçin
                  </h3>
                  <button
                    onClick={() => {
                      if (!paymentLoading) {
                        setShowPaymentMethodModal(false);
                        setPendingMonthPrice(null);
                        setSelectedPaymentMethod(null);
                        setTransactionId('');
                        setPaymentNotes('');
                      }
                    }}
                    className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  >
                    <XMarkIcon className="h-6 w-6" />
                  </button>
                </div>

                <div className="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <p className="text-sm text-blue-800 dark:text-blue-300">
                    <strong>Ödeme Tutarı:</strong> {formatTurkishCurrency(Number(pendingMonthPrice.price))}
                  </p>
                  <p className="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    Ay: {new Date(pendingMonthPrice.month + '-01').toLocaleDateString('tr-TR', { year: 'numeric', month: 'long' })}
                  </p>
                </div>

                {!selectedPaymentMethod ? (
                  <div className="space-y-3">
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                      Ödeme yöntemini seçin
                    </p>
                    <button
                      onClick={() => handlePaymentMethodSelect('cash')}
                      disabled={paymentLoading}
                      className="w-full p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary-500 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors disabled:opacity-50 flex items-center space-x-3"
                    >
                      <CurrencyDollarIcon className="h-8 w-8 text-green-600 dark:text-green-400" />
                      <div className="text-left flex-1">
                        <p className="font-semibold text-gray-900 dark:text-white">Nakit</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">Nakit ödeme</p>
                      </div>
                    </button>
                    <button
                      onClick={() => handlePaymentMethodSelect('credit_card')}
                      disabled={paymentLoading}
                      className="w-full p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary-500 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors disabled:opacity-50 flex items-center space-x-3"
                    >
                      <CreditCardIcon className="h-8 w-8 text-purple-600 dark:text-purple-400" />
                      <div className="text-left flex-1">
                        <p className="font-semibold text-gray-900 dark:text-white">Kredi Kartı</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">Kredi kartı ile ödeme</p>
                      </div>
                    </button>
                    <button
                      onClick={() => handlePaymentMethodSelect('bank_transfer')}
                      disabled={paymentLoading}
                      className="w-full p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary-500 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors disabled:opacity-50 flex items-center space-x-3"
                    >
                      <BuildingLibraryIcon className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                      <div className="text-left flex-1">
                        <p className="font-semibold text-gray-900 dark:text-white">Havale</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">Banka havalesi ile ödeme</p>
                      </div>
                    </button>
                  </div>
                ) : selectedPaymentMethod === 'bank_transfer' ? (
                  <form
                    onSubmit={(e) => {
                      e.preventDefault();
                      if (!selectedBankAccountId) {
                        toast.error('Lütfen bir banka hesabı seçin');
                        return;
                      }
                      savePaymentWithMethod('bank_transfer', selectedBankAccountId);
                    }}
                    className="space-y-4"
                  >
                    <div>
                      <label htmlFor="bankAccount" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Banka Hesabı <span className="text-red-500">*</span>
                      </label>
                      {bankAccounts.length === 0 ? (
                        <div className="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                          <p className="text-sm text-yellow-800 dark:text-yellow-300">
                            Aktif banka hesabı bulunamadı. Lütfen ayarlar sayfasından banka hesabı ekleyin.
                          </p>
                        </div>
                      ) : (
                        <select
                          id="bankAccount"
                          required
                          value={selectedBankAccountId || ''}
                          onChange={(e) => setSelectedBankAccountId(e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                        >
                          <option value="">Banka hesabı seçin</option>
                          {bankAccounts.map((account) => (
                            <option key={account.id} value={account.id}>
                              {account.bank_name} - {account.account_number} ({account.account_holder_name})
                            </option>
                          ))}
                        </select>
                      )}
                    </div>
                    <div>
                      <label htmlFor="transactionId" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        İşlem No (Opsiyonel)
                      </label>
                      <input
                        id="transactionId"
                        type="text"
                        placeholder="Havale işlem numarası"
                        value={transactionId}
                        onChange={(e) => setTransactionId(e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                      />
                    </div>
                    <div>
                      <label htmlFor="paymentNotes" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Notlar (Opsiyonel)
                      </label>
                      <textarea
                        id="paymentNotes"
                        rows={3}
                        placeholder="Ödeme ile ilgili notlar"
                        value={paymentNotes}
                        onChange={(e) => setPaymentNotes(e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                      />
                    </div>
                    <div className="flex space-x-3">
                      <button
                        type="button"
                        onClick={() => {
                          setSelectedPaymentMethod(null);
                          setTransactionId('');
                          setPaymentNotes('');
                        }}
                        disabled={paymentLoading}
                        className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                      >
                        Geri
                      </button>
                      <button
                        type="submit"
                        disabled={paymentLoading || !selectedBankAccountId}
                        className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {paymentLoading ? 'Kaydediliyor...' : 'Ödemeyi Kaydet'}
                      </button>
                    </div>
                  </form>
                ) : (
                  <div className="space-y-4">
                    <div>
                      <label htmlFor="paymentNotesCash" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Notlar (Opsiyonel)
                      </label>
                      <textarea
                        id="paymentNotesCash"
                        rows={3}
                        placeholder="Ödeme ile ilgili notlar"
                        value={paymentNotes}
                        onChange={(e) => setPaymentNotes(e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                      />
                    </div>
                    <div className="flex space-x-3">
                      <button
                        type="button"
                        onClick={() => {
                          setSelectedPaymentMethod(null);
                          setPaymentNotes('');
                        }}
                        disabled={paymentLoading}
                        className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                      >
                        Geri
                      </button>
                      <button
                        type="button"
                        onClick={() => savePaymentWithMethod(selectedPaymentMethod, null)}
                        disabled={paymentLoading}
                        className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {paymentLoading ? 'Kaydediliyor...' : 'Ödemeyi Kaydet'}
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Ara Ödeme Ekle Modal */}
      {showAddPaymentModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => {
                if (!paymentLoading) {
                  setShowAddPaymentModal(false);
                  setPaymentType(null);
                  setSelectedMonth(null);
                }
              }}
            />
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    Ara Ödeme Ekle
                  </h3>
                  <button
                    onClick={() => {
                      if (!paymentLoading) {
                        setShowAddPaymentModal(false);
                        setPaymentType(null);
                        setSelectedMonth(null);
                      }
                    }}
                    className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  >
                    <XMarkIcon className="h-6 w-6" />
                  </button>
                </div>

                <AddPaymentForm
                  contract={contract}
                  paymentType={paymentType}
                  onClose={() => {
                    setShowAddPaymentModal(false);
                    setPaymentType(null);
                    setSelectedMonth(null);
                  }}
                  onSubmit={handleAddPayment}
                  loading={paymentLoading}
                />
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// Add Payment Form Component
function AddPaymentForm({ contract, paymentType, onClose, onSubmit, loading }: any) {
  const [formData, setFormData] = useState({
    amount: '',
    due_date: '',
    paid_at: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    notes: '',
    month: '',
    bank_account_id: '',
    transaction_id: '',
  });
  const [bankAccounts, setBankAccounts] = useState<BankAccount[]>([]);

  useEffect(() => {
    loadBankAccounts();
  }, []);

  const loadBankAccounts = async () => {
    try {
      const accounts = await companiesApi.getActiveBankAccounts();
      setBankAccounts(accounts);
      if (accounts.length > 0 && !formData.bank_account_id) {
        setFormData({ ...formData, bank_account_id: accounts[0].id });
      }
    } catch (error) {
      console.error('Error loading bank accounts:', error);
    }
  };

  useEffect(() => {
    if (paymentType === 'monthly' && contract?.monthly_prices?.length > 0) {
      // İlk ödenmemiş ayı seç
      const unpaidMonth = contract.monthly_prices.find((mp: any) => {
        const monthPayment = contract.payments?.find((p: any) => {
          const paymentMonth = new Date(p.due_date).toISOString().slice(0, 7);
          return paymentMonth === mp.month && p.status === 'paid';
        });
        return !monthPayment;
      });
      
      if (unpaidMonth) {
        setFormData({
          ...formData,
          month: unpaidMonth.month,
          amount: unpaidMonth.price.toString(),
          due_date: new Date(unpaidMonth.month + '-01').toISOString().split('T')[0],
        });
      }
    }
  }, [paymentType, contract]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (loading) return; // Prevent double submission
    
    // Havale için banka hesabı kontrolü
    if (formData.payment_method === 'bank_transfer' && !formData.bank_account_id) {
      alert('Havale ödemesi için lütfen bir banka hesabı seçin');
      return;
    }
    
    onSubmit(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {paymentType === 'monthly' && contract?.monthly_prices && (
        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Ay Seçin
          </label>
          <select
            value={formData.month}
            onChange={(e) => {
              const selected = contract.monthly_prices.find((mp: any) => mp.month === e.target.value);
              setFormData({
                ...formData,
                month: e.target.value,
                amount: selected ? selected.price.toString() : '',
                due_date: selected ? new Date(selected.month + '-01').toISOString().split('T')[0] : '',
              });
            }}
            required
            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">Ay Seçin</option>
            {contract.monthly_prices.map((mp: any) => {
              const monthPayment = contract.payments?.find((p: any) => {
                const paymentMonth = new Date(p.due_date).toISOString().slice(0, 7);
                return paymentMonth === mp.month && p.status === 'paid';
              });
              if (monthPayment) return null;
              return (
                <option key={mp.month} value={mp.month}>
                  {new Date(mp.month + '-01').toLocaleDateString('tr-TR', { year: 'numeric', month: 'long' })} - {formatTurkishCurrency(Number(mp.price))}
                </option>
              );
            })}
          </select>
        </div>
      )}

      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Tutar (TL) *
        </label>
        <input
          type="number"
          step="0.01"
          min="0"
          required
          value={formData.amount}
          onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Tarih {paymentType === 'intermediate' && '*'}
        </label>
        <input
          type="date"
          required={paymentType === 'intermediate'}
          value={formData.due_date}
          onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Ödeme Tarihi *
        </label>
        <input
          type="date"
          required
          value={formData.paid_at}
          onChange={(e) => setFormData({ ...formData, paid_at: e.target.value })}
          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Ödeme Yöntemi
        </label>
        <select
          value={formData.payment_method}
          onChange={(e) => setFormData({ ...formData, payment_method: e.target.value, bank_account_id: e.target.value === 'bank_transfer' && bankAccounts.length > 0 ? bankAccounts[0].id : '' })}
          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
        >
          <option value="cash">Nakit</option>
          <option value="credit_card">Kredi Kartı</option>
          <option value="bank_transfer">Havale/EFT</option>
        </select>
      </div>

      {formData.payment_method === 'bank_transfer' && (
        <>
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Banka Hesabı <span className="text-red-500">*</span>
            </label>
            {bankAccounts.length === 0 ? (
              <div className="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                <p className="text-sm text-yellow-800 dark:text-yellow-300">
                  Aktif banka hesabı bulunamadı. Lütfen ayarlar sayfasından banka hesabı ekleyin.
                </p>
              </div>
            ) : (
              <select
                required={formData.payment_method === 'bank_transfer'}
                value={formData.bank_account_id}
                onChange={(e) => setFormData({ ...formData, bank_account_id: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Banka hesabı seçin</option>
                {bankAccounts.map((account) => (
                  <option key={account.id} value={account.id}>
                    {account.bank_name} - {account.account_number} ({account.account_holder_name})
                  </option>
                ))}
              </select>
            )}
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              İşlem No (Opsiyonel)
            </label>
            <input
              type="text"
              placeholder="Havale işlem numarası"
              value={formData.transaction_id}
              onChange={(e) => setFormData({ ...formData, transaction_id: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
            />
          </div>
        </>
      )}

      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Notlar
        </label>
        <textarea
          value={formData.notes}
          onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
          rows={3}
          className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
        />
      </div>

      <div className="flex justify-end space-x-3 pt-4">
        <button
          type="button"
          onClick={onClose}
          disabled={loading}
          className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
        >
          İptal
        </button>
        <button
          type="submit"
          disabled={loading}
          className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
        >
          {loading ? 'Kaydediliyor...' : 'Kaydet'}
        </button>
      </div>
    </form>
  );
}
