import { useState, useEffect, useRef } from 'react';
import { companiesApi, Company, MailSettings, PaytrSettings, SmsSettings, BankAccount } from '../../services/api/companiesApi';
import { backupApi } from '../../services/api/backupApi';
import { useAuthStore } from '../../stores/authStore';
import { useCompanyStore } from '../../stores/companyStore';
import { formatPhoneNumber, unformatPhoneNumber, isValidEmail, isValidPhoneNumber } from '../../utils/inputFormatters';
import toast from 'react-hot-toast';
import {
  BuildingOfficeIcon,
  EnvelopeIcon,
  CheckCircleIcon,
  XCircleIcon,
  PhotoIcon,
  TrashIcon,
  CreditCardIcon,
  ExclamationTriangleIcon,
  BuildingLibraryIcon,
  PlusIcon,
  PencilIcon,
  ChatBubbleLeftRightIcon,
  CloudArrowDownIcon,
  ArrowDownTrayIcon,
} from '@heroicons/react/24/outline';


export function SettingsPage() {
  const { user } = useAuthStore();
  const { updateCompany } = useCompanyStore();
  const [activeTab, setActiveTab] = useState<'company' | 'mail' | 'paytr' | 'sms' | 'bank' | 'backup'>('company');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [creatingBackup, setCreatingBackup] = useState(false);
  const [backups, setBackups] = useState<string[]>([]);
  const [testing, setTesting] = useState(false);
  const [testingSms, setTestingSms] = useState(false);
  const [logoUploading, setLogoUploading] = useState(false);
  const [contractUploading, setContractUploading] = useState(false);
  const [insuranceUploading, setInsuranceUploading] = useState(false);
  const [company, setCompany] = useState<Company | null>(null);
  const [, setMailSettings] = useState<MailSettings | null>(null);
  const [, setPaytrSettings] = useState<PaytrSettings | null>(null);
  const [, setSmsSettings] = useState<SmsSettings | null>(null);
  const [bankAccounts, setBankAccounts] = useState<BankAccount[]>([]);
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);
  const [testSmsResult, setTestSmsResult] = useState<{ success: boolean; message: string } | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{ [key: string]: string }>({});
  const [editingBankAccount, setEditingBankAccount] = useState<BankAccount | null>(null);
  const [showBankAccountModal, setShowBankAccountModal] = useState(false);

  // Company form state
  const [companyForm, setCompanyForm] = useState({
    name: '',
    project_name: '',
    email: '',
    phone: '',
    whatsapp_number: '',
    address: '',
    mersis_number: '',
    trade_registry_number: '',
    tax_office: '',
  });

  // Mail settings form state
  const [mailForm, setMailForm] = useState({
    smtp_host: '',
    smtp_port: 587,
    smtp_secure: false,
    smtp_username: '',
    smtp_password: '',
    from_email: '',
    from_name: '',
    contract_created_template: '',
    payment_received_template: '',
    contract_expiring_template: '',
    payment_reminder_template: '',
    welcome_template: '',
    notify_admin_on_contract: true,
    notify_admin_on_payment: true,
    admin_contract_created_template: '',
    admin_payment_received_template: '',
    is_active: false,
  });

  // PayTR settings form state
  const [paytrForm, setPaytrForm] = useState({
    merchant_id: '',
    merchant_key: '',
    merchant_salt: '',
    is_active: false,
    test_mode: true,
  });

  // SMS settings form state
  const [smsForm, setSmsForm] = useState({
    username: '',
    password: '',
    sender_id: '',
    api_url: 'https://api.netgsm.com.tr',
    is_active: false,
    test_mode: true,
  });

  // Bank account form state
  const [bankAccountForm, setBankAccountForm] = useState({
    bank_name: '',
    account_holder_name: '',
    account_number: '',
    iban: '',
    branch_name: '',
    is_active: true,
  });

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    if (activeTab === 'backup') {
      loadBackups();
    }
  }, [activeTab]);

  const loadBackups = async () => {
    try {
      const list = await backupApi.list();
      setBackups(list);
    } catch (error) {
      console.error('Failed to load backups:', error);
      toast.error('Yedekler yüklenirken hata oluştu');
    }
  };

  const handleCreateBackup = async () => {
    try {
      setCreatingBackup(true);
      await backupApi.create();
      toast.success('Yedekleme başarıyla oluşturuldu');
      loadBackups();
    } catch (error: any) {
      toast.error('Yedekleme oluşturulamadı: ' + (error.response?.data?.message || error.message));
    } finally {
      setCreatingBackup(false);
    }
  };

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Check if user has a company (except for super_admin)
      if (!user?.company_id && user?.role !== 'super_admin') {
        setError('Bu kullanıcının bir firması bulunmamaktadır. Lütfen bir firmaya atanmanız gerekmektedir.');
        setLoading(false);
        return;
      }

      // Load company data first
      let companyData: Company | null = null;
      try {
        companyData = await companiesApi.getCurrent();
        if (companyData) {
          setCompany(companyData);
          setCompanyForm({
            name: companyData.name || '',
            project_name: companyData.project_name ?? '',
            email: companyData.email || '',
            phone: companyData.phone ? formatPhoneNumber(companyData.phone) : '',
            whatsapp_number: companyData.whatsapp_number ? formatPhoneNumber(companyData.whatsapp_number) : '',
            address: companyData.address || '',
            mersis_number: companyData.mersis_number || '',
            trade_registry_number: companyData.trade_registry_number || '',
            tax_office: companyData.tax_office || '',
          });
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.message;
        if (errorMessage?.includes('User has no company') || errorMessage?.includes('no company')) {
          setError('Bu kullanıcının bir firması bulunmamaktadır. Lütfen bir firmaya atanmanız gerekmektedir.');
          setLoading(false);
          return;
        }
        throw error;
      }

      // Load mail settings (may fail for super_admin without company_id)
      try {
        const mailData = await companiesApi.getMailSettings();
        setMailSettings(mailData);
        setMailForm({
          smtp_host: mailData.smtp_host || '',
          smtp_port: mailData.smtp_port || 587,
          smtp_secure: mailData.smtp_secure || false,
          smtp_username: mailData.smtp_username || '',
          smtp_password: '', // Don't show password
          from_email: mailData.from_email || '',
          from_name: mailData.from_name || '',
          contract_created_template: mailData.contract_created_template || '',
          payment_received_template: mailData.payment_received_template || '',
          contract_expiring_template: mailData.contract_expiring_template || '',
          payment_reminder_template: mailData.payment_reminder_template || '',
          welcome_template: mailData.welcome_template || '',
          notify_admin_on_contract: mailData.notify_admin_on_contract !== undefined ? mailData.notify_admin_on_contract : true,
          notify_admin_on_payment: mailData.notify_admin_on_payment !== undefined ? mailData.notify_admin_on_payment : true,
          admin_contract_created_template: mailData.admin_contract_created_template || '',
          admin_payment_received_template: mailData.admin_payment_received_template || '',
          is_active: mailData.is_active || false,
        });
      } catch (error: any) {
        console.warn('Failed to load mail settings:', error);
        // Don't show error for mail settings if user doesn't have company
        const errorMessage = error.response?.data?.message || error.message;
        if (!errorMessage?.includes('User has no company') && !errorMessage?.includes('no company')) {
          console.error('Unexpected error loading mail settings:', error);
        }
      }

      // Load PayTR settings (may fail for super_admin without company_id)
      try {
        const paytrData = await companiesApi.getPaytrSettings();
        setPaytrSettings(paytrData);
        setPaytrForm({
          merchant_id: paytrData.merchant_id || '',
          merchant_key: paytrData.merchant_key || '',
          merchant_salt: paytrData.merchant_salt || '',
          is_active: paytrData.is_active || false,
          test_mode: paytrData.test_mode !== undefined ? paytrData.test_mode : true,
        });
      } catch (error: any) {
        console.warn('Failed to load PayTR settings:', error);
        // Don't show error for PayTR settings if user doesn't have company
        const errorMessage = error.response?.data?.message || error.message;
        if (!errorMessage?.includes('User has no company') && !errorMessage?.includes('no company')) {
          console.error('Unexpected error loading PayTR settings:', error);
        }
      }

      // Load SMS settings (may fail for super_admin without company_id)
      try {
        const smsData = await companiesApi.getSmsSettings();
        setSmsSettings(smsData);
        setSmsForm({
          username: smsData.username || '',
          password: '', // Don't show password
          sender_id: smsData.sender_id || '',
          api_url: smsData.api_url || 'https://api.netgsm.com.tr',
          is_active: smsData.is_active || false,
          test_mode: smsData.test_mode !== undefined ? smsData.test_mode : true,
        });
      } catch (error: any) {
        console.warn('Failed to load SMS settings:', error);
        // Don't show error for SMS settings if user doesn't have company
        const errorMessage = error.response?.data?.message || error.message;
        if (!errorMessage?.includes('User has no company') && !errorMessage?.includes('no company')) {
          console.error('Unexpected error loading SMS settings:', error);
        }
      }

      // Load bank accounts (may fail if table doesn't exist yet - migration not run)
      try {
        const bankAccountsData = await companiesApi.getBankAccounts();
        setBankAccounts(bankAccountsData);
      } catch (error: any) {
        console.warn('Failed to load bank accounts:', error);
        // Don't show error for bank accounts if it's a table not found error (migration not run)
        const errorMessage = error.response?.data?.message || error.message || '';
        if (!errorMessage.includes('User has no company') && !errorMessage.includes('no company')) {
          // Only log unexpected errors, don't break the page
          console.error('Unexpected error loading bank accounts:', error);
        }
        // Set empty array so page doesn't break
        setBankAccounts([]);
      }
    } catch (error: any) {
      console.error('Failed to load settings:', error);
      const errorMessage = error.response?.data?.message || error.message;
      if (errorMessage?.includes('User has no company') || errorMessage?.includes('no company')) {
        setError('Bu kullanıcının bir firması bulunmamaktadır. Lütfen bir firmaya atanmanız gerekmektedir.');
      } else {
        setError('Ayarlar yüklenirken bir hata oluştu: ' + errorMessage);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleCompanySubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFieldErrors({});
    
    // Validate email if provided
    if (companyForm.email && !isValidEmail(companyForm.email)) {
      setFieldErrors({ company_email: 'Lütfen geçerli bir e-posta adresi girin.' });
      return;
    }
    
    // Validate phone if provided
    if (companyForm.phone && !isValidPhoneNumber(companyForm.phone)) {
      setFieldErrors({ company_phone: 'Lütfen geçerli bir telefon numarası girin. (Format: 0XXX XXX XX XX)' });
      return;
    }
    
    // Validate WhatsApp number if provided
    if (companyForm.whatsapp_number && !isValidPhoneNumber(companyForm.whatsapp_number)) {
      setFieldErrors({ company_whatsapp: 'Lütfen geçerli bir telefon numarası girin. (Format: 0XXX XXX XX XX)' });
      return;
    }
    
    try {
      setSaving(true);
      const submitData = {
        ...companyForm,
        phone: companyForm.phone ? unformatPhoneNumber(companyForm.phone) : '',
        whatsapp_number: companyForm.whatsapp_number ? unformatPhoneNumber(companyForm.whatsapp_number) : '',
      };
      const updated = await companiesApi.update(submitData);
      setCompany(updated);
      updateCompany(updated); // Update the global store so DashboardLayout reflects changes immediately
      toast.success('Firma bilgileri başarıyla güncellendi!');
    } catch (error: any) {
      toast.error('Hata: ' + (error.response?.data?.message || error.message));
    } finally {
      setSaving(false);
    }
  };

  const handleMailSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSaving(true);
      const updated = await companiesApi.updateMailSettings(mailForm);
      setMailSettings(updated);
      toast.success('Mail ayarları başarıyla güncellendi!');
    } catch (error: any) {
      toast.error('Hata: ' + (error.response?.data?.message || error.message));
    } finally {
      setSaving(false);
    }
  };

  const handleTestConnection = async () => {
    try {
      setTesting(true);
      setTestResult(null);
      await companiesApi.testMailConnection();
      setTestResult({ success: true, message: 'Mail bağlantısı başarılı!' });
    } catch (error: any) {
      setTestResult({
        success: false,
        message: error.response?.data?.message || 'Mail bağlantısı başarısız!',
      });
    } finally {
      setTesting(false);
    }
  };

  const handlePaytrSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSaving(true);
      const updated = await companiesApi.updatePaytrSettings(paytrForm);
      setPaytrSettings(updated);
      toast.success('PayTR ayarları başarıyla güncellendi!');
    } catch (error: any) {
      toast.error('Hata: ' + (error.response?.data?.message || error.message));
    } finally {
      setSaving(false);
    }
  };

  const handleSmsSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSaving(true);
      const updated = await companiesApi.updateSmsSettings(smsForm);
      setSmsSettings(updated);
      toast.success('SMS ayarları başarıyla güncellendi!');
    } catch (error: any) {
      toast.error('Hata: ' + (error.response?.data?.message || error.message));
    } finally {
      setSaving(false);
    }
  };

  const handleTestSmsConnection = async () => {
    try {
      setTestingSms(true);
      setTestSmsResult(null);
      const result = await companiesApi.testSmsConnection();
      setTestSmsResult(result);
      if (result.success) {
        toast.success(result.message);
      } else {
        toast.error(result.message);
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || 'SMS bağlantısı başarısız!';
      setTestSmsResult({
        success: false,
        message: errorMessage,
      });
      toast.error(errorMessage);
    } finally {
      setTestingSms(false);
    }
  };

  const logoInputRef = useRef<HTMLInputElement>(null);
  const contractInputRef = useRef<HTMLInputElement>(null);
  const insuranceInputRef = useRef<HTMLInputElement>(null);

  const handleLogoUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      toast.error('Sadece resim dosyaları (JPEG, PNG, GIF, WebP) yüklenebilir.');
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      toast.error('Logo en fazla 2MB olabilir.');
      return;
    }
    try {
      setLogoUploading(true);
      const updated = await companiesApi.uploadLogo(file);
      setCompany(updated);
      updateCompany(updated); // Update the global store
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Logo yüklenemedi.');
    } finally {
      setLogoUploading(false);
      e.target.value = '';
    }
  };

  const handleLogoRemove = async () => {
    if (!company?.logo_url) return;
    if (!confirm('Logoyu kaldırmak istediğinize emin misiniz?')) return;
    try {
      setLogoUploading(true);
      const updated = await companiesApi.deleteLogo();
      setCompany(updated);
      updateCompany(updated); // Update the global store
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Logo kaldırılamadı.');
    } finally {
      setLogoUploading(false);
    }
  };

  const handleContractUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.type !== 'application/pdf') {
      toast.error('Sadece PDF dosyaları yüklenebilir.');
      return;
    }
    if (file.size > 10 * 1024 * 1024) {
      toast.error('Dosya en fazla 10MB olabilir.');
      return;
    }
    try {
      setContractUploading(true);
      const updated = await companiesApi.uploadContractTemplate(file);
      setCompany(updated);
      toast.success('Sözleşme şablonu yüklendi.');
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Sözleşme şablonu yüklenemedi.');
    } finally {
      setContractUploading(false);
      e.target.value = '';
    }
  };

  const handleContractRemove = async () => {
    if (!company?.contract_template_url) return;
    if (!confirm('Sözleşme şablonunu kaldırmak istediğinize emin misiniz?')) return;
    try {
      setContractUploading(true);
      const updated = await companiesApi.deleteContractTemplate();
      setCompany(updated);
      toast.success('Sözleşme şablonu kaldırıldı.');
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Sözleşme şablonu kaldırılamadı.');
    } finally {
      setContractUploading(false);
    }
  };

  const handleInsuranceUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.type !== 'application/pdf') {
      toast.error('Sadece PDF dosyaları yüklenebilir.');
      return;
    }
    if (file.size > 10 * 1024 * 1024) {
      toast.error('Dosya en fazla 10MB olabilir.');
      return;
    }
    try {
      setInsuranceUploading(true);
      const updated = await companiesApi.uploadInsuranceTemplate(file);
      setCompany(updated);
      toast.success('Sigorta şablonu yüklendi.');
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Sigorta şablonu yüklenemedi.');
    } finally {
      setInsuranceUploading(false);
      e.target.value = '';
    }
  };

  const handleInsuranceRemove = async () => {
    if (!company?.insurance_template_url) return;
    if (!confirm('Sigorta şablonunu kaldırmak istediğinize emin misiniz?')) return;
    try {
      setInsuranceUploading(true);
      const updated = await companiesApi.deleteInsuranceTemplate();
      setCompany(updated);
      toast.success('Sigorta şablonu kaldırıldı.');
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Sigorta şablonu kaldırılamadı.');
    } finally {
      setInsuranceUploading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Ayarlar</h1>
            <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
              Firma bilgileri, mail ve ödeme ayarlarını yönetin
            </p>
          </div>
        </div>

        <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
          <div className="flex items-start space-x-3">
            <ExclamationTriangleIcon className="h-6 w-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
            <div>
              <h3 className="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mb-2">
                Firma Ataması Gerekli
              </h3>
              <p className="text-yellow-700 dark:text-yellow-400">
                {error}
              </p>
              <p className="text-yellow-700 dark:text-yellow-400 mt-2 text-sm">
                Lütfen sistem yöneticinizle iletişime geçerek bir firmaya atanmanızı talep edin.
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Ayarlar</h1>
          <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Firma bilgileri, mail ve ödeme ayarlarını yönetin
          </p>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 dark:border-gray-700">
        <nav className="-mb-px flex space-x-8">
          <button
            onClick={() => setActiveTab('company')}
            className={`${
              activeTab === 'company'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
          >
            <BuildingOfficeIcon className="h-5 w-5" />
            <span>Firma Bilgileri</span>
          </button>
          <button
            onClick={() => setActiveTab('mail')}
            className={`${
              activeTab === 'mail'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
          >
            <EnvelopeIcon className="h-5 w-5" />
            <span>Mail Ayarları</span>
          </button>
          <button
            onClick={() => setActiveTab('paytr')}
            className={`${
              activeTab === 'paytr'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
          >
            <CreditCardIcon className="h-5 w-5" />
            <span>PayTR Ayarları</span>
          </button>
          <button
            onClick={() => setActiveTab('sms')}
            className={`${
              activeTab === 'sms'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
          >
            <ChatBubbleLeftRightIcon className="h-5 w-5" />
            <span>SMS Ayarları</span>
          </button>
          <button
            onClick={() => setActiveTab('bank')}
            className={`${
              activeTab === 'bank'
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
          >
            <BuildingLibraryIcon className="h-5 w-5" />
            <span>Banka Hesapları</span>
          </button>
          {user?.role === 'super_admin' && (
            <button
              onClick={() => setActiveTab('backup')}
              className={`${
                activeTab === 'backup'
                  ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
              } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
            >
              <CloudArrowDownIcon className="h-5 w-5" />
              <span>Yedekleme</span>
            </button>
          )}
        </nav>
      </div>

      {/* Company Info Tab */}
      {activeTab === 'company' && (
        <form onSubmit={handleCompanySubmit} className="space-y-6">
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
              Firma Bilgileri
            </h2>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Firma Ünvanı <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  value={companyForm.name}
                  onChange={(e) => setCompanyForm({ ...companyForm, name: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Proje Adı
                </label>
                <input
                  type="text"
                  value={companyForm.project_name}
                  onChange={(e) => setCompanyForm({ ...companyForm, project_name: e.target.value })}
                  placeholder="DepoPazar"
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                />
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Uygulama içinde görünen marka adı (sidebar, giriş vb.). Boş bırakılırsa &quot;DepoPazar&quot; kullanılır.
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  E-posta
                </label>
                <input
                  type="email"
                  value={companyForm.email}
                  onChange={(e) => {
                    const value = e.target.value;
                    // Only allow email format characters
                    if (value === '' || /^[^\s@]*@?[^\s@]*\.?[^\s@]*$/.test(value)) {
                      setCompanyForm({ ...companyForm, email: value });
                      if (fieldErrors.company_email) {
                        const newErrors = { ...fieldErrors };
                        delete newErrors.company_email;
                        setFieldErrors(newErrors);
                      }
                    }
                  }}
                  onBlur={(e) => {
                    if (e.target.value && !isValidEmail(e.target.value)) {
                      setFieldErrors({ ...fieldErrors, company_email: 'Lütfen geçerli bir e-posta adresi girin.' });
                    } else {
                      const newErrors = { ...fieldErrors };
                      delete newErrors.company_email;
                      setFieldErrors(newErrors);
                    }
                  }}
                  className={`w-full px-4 py-2 border ${fieldErrors.company_email ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'} rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white`}
                />
                {fieldErrors.company_email && (
                  <p className="mt-1 text-sm text-red-600 dark:text-red-400">{fieldErrors.company_email}</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Telefon Numarası
                </label>
                <input
                  type="tel"
                  placeholder="0XXX XXX XX XX"
                  value={companyForm.phone}
                  onChange={(e) => {
                    const formatted = formatPhoneNumber(e.target.value);
                    setCompanyForm({ ...companyForm, phone: formatted });
                    if (fieldErrors.company_phone) {
                      const newErrors = { ...fieldErrors };
                      delete newErrors.company_phone;
                      setFieldErrors(newErrors);
                    }
                  }}
                  onBlur={(e) => {
                    if (e.target.value && !isValidPhoneNumber(e.target.value)) {
                      setFieldErrors({ ...fieldErrors, company_phone: 'Lütfen geçerli bir telefon numarası girin. (Format: 0XXX XXX XX XX)' });
                    } else {
                      const newErrors = { ...fieldErrors };
                      delete newErrors.company_phone;
                      setFieldErrors(newErrors);
                    }
                  }}
                  className={`w-full px-4 py-2 border ${fieldErrors.company_phone ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'} rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white`}
                />
                {fieldErrors.company_phone && (
                  <p className="mt-1 text-sm text-red-600 dark:text-red-400">{fieldErrors.company_phone}</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  WhatsApp Numarası
                </label>
                <input
                  type="tel"
                  placeholder="0XXX XXX XX XX"
                  value={companyForm.whatsapp_number}
                  onChange={(e) => {
                    const formatted = formatPhoneNumber(e.target.value);
                    setCompanyForm({ ...companyForm, whatsapp_number: formatted });
                    if (fieldErrors.company_whatsapp) {
                      const newErrors = { ...fieldErrors };
                      delete newErrors.company_whatsapp;
                      setFieldErrors(newErrors);
                    }
                  }}
                  onBlur={(e) => {
                    if (e.target.value && !isValidPhoneNumber(e.target.value)) {
                      setFieldErrors({ ...fieldErrors, company_whatsapp: 'Lütfen geçerli bir telefon numarası girin. (Format: 0XXX XXX XX XX)' });
                    } else {
                      const newErrors = { ...fieldErrors };
                      delete newErrors.company_whatsapp;
                      setFieldErrors(newErrors);
                    }
                  }}
                  className={`w-full px-4 py-2 border ${fieldErrors.company_whatsapp ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'} rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white`}
                />
                {fieldErrors.company_whatsapp && (
                  <p className="mt-1 text-sm text-red-600 dark:text-red-400">{fieldErrors.company_whatsapp}</p>
                )}
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Adres
                </label>
                <textarea
                  rows={3}
                  value={companyForm.address}
                  onChange={(e) => setCompanyForm({ ...companyForm, address: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Mersis Numarası
                </label>
                <input
                  type="text"
                  value={companyForm.mersis_number}
                  onChange={(e) =>
                    setCompanyForm({ ...companyForm, mersis_number: e.target.value })
                  }
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Ticaret Sicil Numarası
                </label>
                <input
                  type="text"
                  value={companyForm.trade_registry_number}
                  onChange={(e) =>
                    setCompanyForm({ ...companyForm, trade_registry_number: e.target.value })
                  }
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Vergi Dairesi
                </label>
                <input
                  type="text"
                  value={companyForm.tax_office}
                  onChange={(e) => setCompanyForm({ ...companyForm, tax_office: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Logo
                </label>
                <div className="flex flex-wrap items-end gap-4">
                  <div className="flex items-center gap-4">
                    {company?.logo_url ? (
                      <img
                        src={company.logo_url}
                        alt="Logo"
                        className="h-20 w-20 object-contain rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700"
                      />
                    ) : (
                      <div className="h-20 w-20 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
                        <PhotoIcon className="h-10 w-10 text-gray-400" />
                      </div>
                    )}
                    <div className="space-y-2">
                      <input
                        ref={logoInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        className="hidden"
                        onChange={handleLogoUpload}
                      />
                      <button
                        type="button"
                        disabled={logoUploading}
                        onClick={() => logoInputRef.current?.click()}
                        className="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors"
                      >
                        {logoUploading ? 'Yükleniyor...' : 'Logo Yükle'}
                      </button>
                      {company?.logo_url && (
                        <button
                          type="button"
                          disabled={logoUploading}
                          onClick={handleLogoRemove}
                          className="px-4 py-2 text-sm bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 disabled:opacity-50 transition-colors flex items-center gap-1"
                        >
                          <TrashIcon className="h-4 w-4" />
                          Kaldır
                        </button>
                      )}
                    </div>
                  </div>
                </div>
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  JPEG, PNG, GIF veya WebP. Maks. 2MB.
                </p>
              </div>

              {/* PDF Templates */}
              <div className="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Sözleşme Şablonu (PDF)
                  </label>
                  <div className="flex items-center gap-4">
                    <div className="flex-1">
                      {company?.contract_template_url ? (
                        <div className="flex items-center gap-2 text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-2 rounded-lg">
                          <CheckCircleIcon className="h-5 w-5" />
                          <span>Yüklendi</span>
                          <a 
                            href={company.contract_template_url} 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="text-xs underline ml-1"
                          >
                            Görüntüle
                          </a>
                        </div>
                      ) : (
                        <div className="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                          Yüklenmedi
                        </div>
                      )}
                    </div>
                    <input
                      ref={contractInputRef}
                      type="file"
                      accept="application/pdf"
                      className="hidden"
                      onChange={handleContractUpload}
                    />
                    <div className="flex flex-col gap-2">
                      <button
                        type="button"
                        disabled={contractUploading}
                        onClick={() => contractInputRef.current?.click()}
                        className="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors"
                      >
                        {contractUploading ? '...' : 'Yükle'}
                      </button>
                      {company?.contract_template_url && (
                        <button
                          type="button"
                          disabled={contractUploading}
                          onClick={handleContractRemove}
                          className="px-3 py-1.5 text-xs bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 disabled:opacity-50 transition-colors"
                        >
                          Kaldır
                        </button>
                      )}
                    </div>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Sigorta Şablonu (PDF)
                  </label>
                  <div className="flex items-center gap-4">
                    <div className="flex-1">
                      {company?.insurance_template_url ? (
                        <div className="flex items-center gap-2 text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-2 rounded-lg">
                          <CheckCircleIcon className="h-5 w-5" />
                          <span>Yüklendi</span>
                          <a 
                            href={company.insurance_template_url} 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="text-xs underline ml-1"
                          >
                            Görüntüle
                          </a>
                        </div>
                      ) : (
                        <div className="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                          Yüklenmedi
                        </div>
                      )}
                    </div>
                    <input
                      ref={insuranceInputRef}
                      type="file"
                      accept="application/pdf"
                      className="hidden"
                      onChange={handleInsuranceUpload}
                    />
                    <div className="flex flex-col gap-2">
                      <button
                        type="button"
                        disabled={insuranceUploading}
                        onClick={() => insuranceInputRef.current?.click()}
                        className="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors"
                      >
                        {insuranceUploading ? '...' : 'Yükle'}
                      </button>
                      {company?.insurance_template_url && (
                        <button
                          type="button"
                          disabled={insuranceUploading}
                          onClick={handleInsuranceRemove}
                          className="px-3 py-1.5 text-xs bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 disabled:opacity-50 transition-colors"
                        >
                          Kaldır
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-6 flex justify-end">
              <button
                type="submit"
                disabled={saving}
                className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {saving ? 'Kaydediliyor...' : 'Kaydet'}
              </button>
            </div>
          </div>
        </form>
      )}

      {/* Mail Settings Tab */}
      {activeTab === 'mail' && (
        <form onSubmit={handleMailSubmit} className="space-y-6">
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                Mail Ayarları
              </h2>
              <button
                type="button"
                onClick={handleTestConnection}
                disabled={testing || !mailForm.smtp_host}
                className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm flex items-center space-x-2"
              >
                {testing ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    <span>Test Ediliyor...</span>
                  </>
                ) : (
                  <>
                    <EnvelopeIcon className="h-4 w-4" />
                    <span>Bağlantıyı Test Et</span>
                  </>
                )}
              </button>
            </div>

            {testResult && (
              <div
                className={`mb-6 p-4 rounded-lg flex items-center space-x-2 ${
                  testResult.success
                    ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-400'
                    : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-400'
                }`}
              >
                {testResult.success ? (
                  <CheckCircleIcon className="h-5 w-5" />
                ) : (
                  <XCircleIcon className="h-5 w-5" />
                )}
                <span>{testResult.message}</span>
              </div>
            )}

            <div className="space-y-6">
              <div>
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                  SMTP Ayarları
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      SMTP Host
                    </label>
                    <input
                      type="text"
                      value={mailForm.smtp_host}
                      onChange={(e) => setMailForm({ ...mailForm, smtp_host: e.target.value })}
                      placeholder="smtp.gmail.com"
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      SMTP Port
                    </label>
                    <input
                      type="number"
                      value={mailForm.smtp_port}
                      onChange={(e) =>
                        setMailForm({ ...mailForm, smtp_port: parseInt(e.target.value) || 587 })
                      }
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      SMTP Kullanıcı Adı
                    </label>
                    <input
                      type="text"
                      value={mailForm.smtp_username}
                      onChange={(e) =>
                        setMailForm({ ...mailForm, smtp_username: e.target.value })
                      }
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      SMTP Şifre
                    </label>
                    <input
                      type="password"
                      value={mailForm.smtp_password}
                      onChange={(e) =>
                        setMailForm({ ...mailForm, smtp_password: e.target.value })
                      }
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Gönderen E-posta
                    </label>
                    <input
                      type="email"
                      value={mailForm.from_email}
                      onChange={(e) => {
                        const value = e.target.value;
                        // Only allow email format characters
                        if (value === '' || /^[^\s@]*@?[^\s@]*\.?[^\s@]*$/.test(value)) {
                          setMailForm({ ...mailForm, from_email: value });
                          if (fieldErrors.mail_from_email) {
                            const newErrors = { ...fieldErrors };
                            delete newErrors.mail_from_email;
                            setFieldErrors(newErrors);
                          }
                        }
                      }}
                      onBlur={(e) => {
                        if (e.target.value && !isValidEmail(e.target.value)) {
                          setFieldErrors({ ...fieldErrors, mail_from_email: 'Lütfen geçerli bir e-posta adresi girin.' });
                        } else {
                          const newErrors = { ...fieldErrors };
                          delete newErrors.mail_from_email;
                          setFieldErrors(newErrors);
                        }
                      }}
                      className={`w-full px-4 py-2 border ${fieldErrors.mail_from_email ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'} rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white`}
                    />
                    {fieldErrors.mail_from_email && (
                      <p className="mt-1 text-sm text-red-600 dark:text-red-400">{fieldErrors.mail_from_email}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Gönderen İsim
                    </label>
                    <input
                      type="text"
                      value={mailForm.from_name}
                      onChange={(e) => setMailForm({ ...mailForm, from_name: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      id="smtp_secure"
                      checked={mailForm.smtp_secure}
                      onChange={(e) => setMailForm({ ...mailForm, smtp_secure: e.target.checked })}
                      className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <label
                      htmlFor="smtp_secure"
                      className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                    >
                      SSL/TLS Kullan (Port 465 için)
                    </label>
                  </div>

                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      id="is_active"
                      checked={mailForm.is_active}
                      onChange={(e) => setMailForm({ ...mailForm, is_active: e.target.checked })}
                      className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <label
                      htmlFor="is_active"
                      className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                    >
                      Mail gönderimini aktif et
                    </label>
                  </div>
                </div>
              </div>

              <div>
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                  Mail Şablonları
                </h3>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Sözleşme Oluşturuldu Şablonu
                    </label>
                    <textarea
                      rows={6}
                      value={mailForm.contract_created_template}
                      onChange={(e) =>
                        setMailForm({ ...mailForm, contract_created_template: e.target.value })
                      }
                      placeholder="HTML şablon. Değişkenler: {{customer_name}}, {{contract_number}}, {{room_number}}, {{monthly_price}}, {{start_date}}, {{end_date}}, {{company_name}}"
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Ödeme Alındı Şablonu
                    </label>
                    <textarea
                      rows={6}
                      value={mailForm.payment_received_template}
                      onChange={(e) =>
                        setMailForm({ ...mailForm, payment_received_template: e.target.value })
                      }
                      placeholder="HTML şablon. Değişkenler: {{customer_name}}, {{payment_number}}, {{amount}}, {{payment_date}}, {{company_name}}"
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Sözleşme Süresi Doluyor Şablonu
                    </label>
                    <textarea
                      rows={6}
                      value={mailForm.contract_expiring_template}
                      onChange={(e) =>
                        setMailForm({ ...mailForm, contract_expiring_template: e.target.value })
                      }
                      placeholder="HTML şablon. Değişkenler: {{customer_name}}, {{contract_number}}, {{end_date}}, {{company_name}}"
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Ödeme Hatırlatması Şablonu
                    </label>
                    <textarea
                      rows={6}
                      value={mailForm.payment_reminder_template}
                      onChange={(e) =>
                        setMailForm({ ...mailForm, payment_reminder_template: e.target.value })
                      }
                      placeholder="HTML şablon. Değişkenler: {{customer_name}}, {{payment_number}}, {{amount}}, {{due_date}}, {{company_name}}"
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Hoş Geldiniz Şablonu
                    </label>
                    <textarea
                      rows={6}
                      value={mailForm.welcome_template}
                      onChange={(e) =>
                        setMailForm({ ...mailForm, welcome_template: e.target.value })
                      }
                      placeholder="HTML şablon. Değişkenler: {{customer_name}}, {{company_name}}"
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                    />
                  </div>
                </div>
              </div>

              <div>
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                  Admin Bildirim Ayarları
                </h3>
                <div className="space-y-4">
                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      id="notify_admin_on_contract"
                      checked={mailForm.notify_admin_on_contract}
                      onChange={(e) => setMailForm({ ...mailForm, notify_admin_on_contract: e.target.checked })}
                      className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <label
                      htmlFor="notify_admin_on_contract"
                      className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                    >
                      Yeni sözleşme oluşturulduğunda admine mail gönder
                    </label>
                  </div>

                  {mailForm.notify_admin_on_contract && (
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Admin Yeni Sözleşme Şablonu
                      </label>
                      <textarea
                        rows={6}
                        value={mailForm.admin_contract_created_template}
                        onChange={(e) =>
                          setMailForm({ ...mailForm, admin_contract_created_template: e.target.value })
                        }
                        placeholder="HTML şablon. Değişkenler: {{customer_name}}, {{contract_number}}, {{room_number}}, {{monthly_price}}, {{date}}"
                        className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                      />
                    </div>
                  )}

                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      id="notify_admin_on_payment"
                      checked={mailForm.notify_admin_on_payment}
                      onChange={(e) => setMailForm({ ...mailForm, notify_admin_on_payment: e.target.checked })}
                      className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <label
                      htmlFor="notify_admin_on_payment"
                      className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                    >
                      Ödeme alındığında admine mail gönder
                    </label>
                  </div>

                  {mailForm.notify_admin_on_payment && (
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Admin Ödeme Alındı Şablonu
                      </label>
                      <textarea
                        rows={6}
                        value={mailForm.admin_payment_received_template}
                        onChange={(e) =>
                          setMailForm({ ...mailForm, admin_payment_received_template: e.target.value })
                        }
                        placeholder="HTML şablon. Değişkenler: {{customer_name}}, {{payment_number}}, {{amount}}, {{payment_date}}"
                        className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                      />
                    </div>
                  )}
                </div>
              </div>
            </div>

            <div className="mt-6 flex justify-end">
              <button
                type="submit"
                disabled={saving}
                className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {saving ? 'Kaydediliyor...' : 'Kaydet'}
              </button>
            </div>
          </div>
        </form>
      )}

      {/* SMS Settings Tab */}
      {activeTab === 'sms' && (
        <form onSubmit={handleSmsSubmit} className="space-y-6">
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                NetGSM SMS API Ayarları
              </h2>
              <button
                type="button"
                onClick={handleTestSmsConnection}
                disabled={testingSms || !smsForm.username}
                className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm flex items-center space-x-2"
              >
                {testingSms ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    <span>Test Ediliyor...</span>
                  </>
                ) : (
                  <>
                    <ChatBubbleLeftRightIcon className="h-4 w-4" />
                    <span>Bağlantıyı Test Et</span>
                  </>
                )}
              </button>
            </div>

            {testSmsResult && (
              <div
                className={`mb-6 p-4 rounded-lg flex items-center space-x-2 ${
                  testSmsResult.success
                    ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-400'
                    : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-400'
                }`}
              >
                {testSmsResult.success ? (
                  <CheckCircleIcon className="h-5 w-5" />
                ) : (
                  <XCircleIcon className="h-5 w-5" />
                )}
                <span>{testSmsResult.message}</span>
              </div>
            )}

            <div className="space-y-6">
              <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p className="text-sm text-blue-800 dark:text-blue-300">
                  <strong>Bilgi:</strong> NetGSM API bilgilerinizi NetGSM panelinizden alabilirsiniz. 
                  Bu bilgiler güvenli bir şekilde saklanır ve sadece SMS gönderimi için kullanılır.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Kullanıcı Adı (Username) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={smsForm.username}
                    onChange={(e) => setSmsForm({ ...smsForm, username: e.target.value })}
                    placeholder="NetGSM Kullanıcı Adı"
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Şifre (Password) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="password"
                    required
                    value={smsForm.password}
                    onChange={(e) => setSmsForm({ ...smsForm, password: e.target.value })}
                    placeholder="NetGSM Şifresi"
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Gönderen Başlık (Sender ID) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={smsForm.sender_id}
                    onChange={(e) => setSmsForm({ ...smsForm, sender_id: e.target.value })}
                    placeholder="Örn: DEPOPAZAR"
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                  />
                  <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    SMS gönderiminde görünecek başlık (maks. 11 karakter)
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    API URL
                  </label>
                  <input
                    type="url"
                    value={smsForm.api_url}
                    onChange={(e) => setSmsForm({ ...smsForm, api_url: e.target.value })}
                    placeholder="https://api.netgsm.com.tr"
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                  />
                  <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Varsayılan: https://api.netgsm.com.tr
                  </p>
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="sms_test_mode"
                    checked={smsForm.test_mode}
                    onChange={(e) => setSmsForm({ ...smsForm, test_mode: e.target.checked })}
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  />
                  <label
                    htmlFor="sms_test_mode"
                    className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                  >
                    Test Modu (SMS testi için)
                  </label>
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="sms_is_active"
                    checked={smsForm.is_active}
                    onChange={(e) => setSmsForm({ ...smsForm, is_active: e.target.checked })}
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  />
                  <label
                    htmlFor="sms_is_active"
                    className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                  >
                    SMS gönderimini aktif et
                  </label>
                </div>
              </div>
            </div>

            <div className="mt-6 flex justify-end">
              <button
                type="submit"
                disabled={saving}
                className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {saving ? 'Kaydediliyor...' : 'Kaydet'}
              </button>
            </div>
          </div>
        </form>
      )}

      {/* PayTR Settings Tab */}
      {activeTab === 'paytr' && (
        <form onSubmit={handlePaytrSubmit} className="space-y-6">
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
              PayTR API Ayarları
            </h2>

            <div className="space-y-6">
              <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p className="text-sm text-blue-800 dark:text-blue-300">
                  <strong>Bilgi:</strong> PayTR API bilgilerinizi PayTR panelinizden alabilirsiniz. 
                  Bu bilgiler güvenli bir şekilde saklanır ve sadece ödeme işlemleri için kullanılır.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Merchant ID <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={paytrForm.merchant_id}
                    onChange={(e) => setPaytrForm({ ...paytrForm, merchant_id: e.target.value })}
                    placeholder="PayTR Merchant ID"
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Merchant Key <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="password"
                    required
                    value={paytrForm.merchant_key}
                    onChange={(e) => setPaytrForm({ ...paytrForm, merchant_key: e.target.value })}
                    placeholder="PayTR Merchant Key"
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Merchant Salt <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="password"
                    required
                    value={paytrForm.merchant_salt}
                    onChange={(e) => setPaytrForm({ ...paytrForm, merchant_salt: e.target.value })}
                    placeholder="PayTR Merchant Salt"
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                  />
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="test_mode"
                    checked={paytrForm.test_mode}
                    onChange={(e) => setPaytrForm({ ...paytrForm, test_mode: e.target.checked })}
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  />
                  <label
                    htmlFor="test_mode"
                    className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                  >
                    Test Modu (Ödeme testi için)
                  </label>
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="paytr_is_active"
                    checked={paytrForm.is_active}
                    onChange={(e) => setPaytrForm({ ...paytrForm, is_active: e.target.checked })}
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  />
                  <label
                    htmlFor="paytr_is_active"
                    className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                  >
                    PayTR ödeme sistemini aktif et
                  </label>
                </div>
              </div>
            </div>

            <div className="mt-6 flex justify-end">
              <button
                type="submit"
                disabled={saving}
                className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {saving ? 'Kaydediliyor...' : 'Kaydet'}
              </button>
            </div>
          </div>
        </form>
      )}

      {/* Bank Accounts Tab */}
      {activeTab === 'bank' && (
        <div className="space-y-6">
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                Banka Hesapları
              </h2>
              <button
                onClick={() => {
                  setEditingBankAccount(null);
                  setBankAccountForm({
                    bank_name: '',
                    account_holder_name: '',
                    account_number: '',
                    iban: '',
                    branch_name: '',
                    is_active: true,
                  });
                  setShowBankAccountModal(true);
                }}
                className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm flex items-center space-x-2"
              >
                <PlusIcon className="h-4 w-4" />
                <span>Yeni Banka Hesabı Ekle</span>
              </button>
            </div>

            {bankAccounts.length === 0 ? (
              <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                Henüz banka hesabı eklenmemiş.
              </div>
            ) : (
              <div className="space-y-4">
                {bankAccounts.map((account) => (
                  <div
                    key={account.id}
                    className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center space-x-2 mb-2">
                          <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {account.bank_name}
                          </h3>
                          {account.is_active ? (
                            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                              Aktif
                            </span>
                          ) : (
                            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300">
                              Pasif
                            </span>
                          )}
                        </div>
                        <div className="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                          <p>
                            <strong>Hesap Sahibi:</strong> {account.account_holder_name}
                          </p>
                          <p>
                            <strong>Hesap No:</strong> {account.account_number}
                          </p>
                          {account.iban && (
                            <p>
                              <strong>IBAN:</strong> {account.iban}
                            </p>
                          )}
                          {account.branch_name && (
                            <p>
                              <strong>Şube:</strong> {account.branch_name}
                            </p>
                          )}
                        </div>
                      </div>
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => {
                            setEditingBankAccount(account);
                            setBankAccountForm({
                              bank_name: account.bank_name,
                              account_holder_name: account.account_holder_name,
                              account_number: account.account_number,
                              iban: account.iban || '',
                              branch_name: account.branch_name || '',
                              is_active: account.is_active,
                            });
                            setShowBankAccountModal(true);
                          }}
                          className="p-2 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
                        >
                          <PencilIcon className="h-5 w-5" />
                        </button>
                        <button
                          onClick={async () => {
                            if (!confirm('Bu banka hesabını silmek istediğinize emin misiniz?')) {
                              return;
                            }
                            try {
                              await companiesApi.deleteBankAccount(account.id);
                              toast.success('Banka hesabı başarıyla silindi');
                              const updated = await companiesApi.getBankAccounts();
                              setBankAccounts(updated);
                            } catch (error: any) {
                              toast.error('Hata: ' + (error.response?.data?.message || error.message));
                            }
                          }}
                          className="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        >
                          <TrashIcon className="h-5 w-5" />
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Bank Account Modal */}
      {showBankAccountModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onClick={() => setShowBankAccountModal(false)} />

            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    {editingBankAccount ? 'Banka Hesabı Düzenle' : 'Yeni Banka Hesabı Ekle'}
                  </h3>
                  <button
                    onClick={() => setShowBankAccountModal(false)}
                    className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  >
                    <XCircleIcon className="h-6 w-6" />
                  </button>
                </div>

                <form
                  onSubmit={async (e) => {
                    e.preventDefault();
                    try {
                      setSaving(true);
                      if (editingBankAccount) {
                        await companiesApi.updateBankAccount(editingBankAccount.id, bankAccountForm);
                        toast.success('Banka hesabı başarıyla güncellendi');
                      } else {
                        await companiesApi.createBankAccount(bankAccountForm);
                        toast.success('Banka hesabı başarıyla eklendi');
                      }
                      const updated = await companiesApi.getBankAccounts();
                      setBankAccounts(updated);
                      setShowBankAccountModal(false);
                      setEditingBankAccount(null);
                      setBankAccountForm({
                        bank_name: '',
                        account_holder_name: '',
                        account_number: '',
                        iban: '',
                        branch_name: '',
                        is_active: true,
                      });
                    } catch (error: any) {
                      toast.error('Hata: ' + (error.response?.data?.message || error.message));
                    } finally {
                      setSaving(false);
                    }
                  }}
                  className="space-y-4"
                >
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Banka Adı <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      value={bankAccountForm.bank_name}
                      onChange={(e) => setBankAccountForm({ ...bankAccountForm, bank_name: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Hesap Sahibi Adı <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      value={bankAccountForm.account_holder_name}
                      onChange={(e) => setBankAccountForm({ ...bankAccountForm, account_holder_name: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Hesap Numarası <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      value={bankAccountForm.account_number}
                      onChange={(e) => setBankAccountForm({ ...bankAccountForm, account_number: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      IBAN
                    </label>
                    <input
                      type="text"
                      value={bankAccountForm.iban}
                      onChange={(e) => setBankAccountForm({ ...bankAccountForm, iban: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Şube Adı
                    </label>
                    <input
                      type="text"
                      value={bankAccountForm.branch_name}
                      onChange={(e) => setBankAccountForm({ ...bankAccountForm, branch_name: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      id="bank_is_active"
                      checked={bankAccountForm.is_active}
                      onChange={(e) => setBankAccountForm({ ...bankAccountForm, is_active: e.target.checked })}
                      className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <label
                      htmlFor="bank_is_active"
                      className="ml-2 block text-sm text-gray-700 dark:text-gray-300"
                    >
                      Aktif
                    </label>
                  </div>

                  <div className="flex space-x-3 pt-4">
                    <button
                      type="submit"
                      disabled={saving}
                      className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                      {saving ? 'Kaydediliyor...' : editingBankAccount ? 'Güncelle' : 'Ekle'}
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        setShowBankAccountModal(false);
                        setEditingBankAccount(null);
                      }}
                      className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                      İptal
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      )}
      {/* Backup Tab */}
      {activeTab === 'backup' && (
        <div className="space-y-6">
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                  Sistem Yedekleme
                </h2>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  Veritabanı yedeğini oluşturun ve indirin.
                </p>
              </div>
              <button
                onClick={handleCreateBackup}
                disabled={creatingBackup}
                className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm flex items-center space-x-2"
              >
                {creatingBackup ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    <span>Oluşturuluyor...</span>
                  </>
                ) : (
                  <>
                    <CloudArrowDownIcon className="h-4 w-4" />
                    <span>Yedek Oluştur</span>
                  </>
                )}
              </button>
            </div>

            <div className="space-y-4">
              {backups.length === 0 ? (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                  Henüz yedek oluşturulmamış.
                </div>
              ) : (
                <div className="overflow-hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-700/50">
                      <tr>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                          Dosya Adı
                        </th>
                        <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                          İşlem
                        </th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                      {backups.map((backup) => (
                        <tr key={backup} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                            {backup}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button
                              onClick={() => backupApi.download(backup)}
                              className="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 flex items-center justify-end w-full gap-1"
                            >
                              <ArrowDownTrayIcon className="h-4 w-4" />
                              İndir
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
