import { apiClient } from './apiClient';
import { useAuthStore } from '../../stores/authStore';

export interface Company {
  id: string;
  name: string;
  slug: string;
  project_name?: string | null;
  logo_url?: string | null;
  email?: string;
  phone?: string;
  whatsapp_number?: string;
  address?: string;
  mersis_number?: string;
  trade_registry_number?: string;
  tax_office?: string;
  primary_color?: string;
  secondary_color?: string;
  package_type: string;
  max_warehouses: number;
  max_rooms: number;
  max_customers: number;
  is_active: boolean;
  subscription_expires_at?: string;
  created_at: string;
  updated_at: string;
}

export interface MailSettings {
  id: string;
  company_id: string;
  smtp_host?: string;
  smtp_port?: number;
  smtp_secure: boolean;
  smtp_username?: string;
  smtp_password?: string;
  from_email?: string;
  from_name?: string;
  contract_created_template?: string;
  payment_received_template?: string;
  contract_expiring_template?: string;
  payment_reminder_template?: string;
  welcome_template?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface PaytrSettings {
  id: string;
  company_id: string;
  merchant_id?: string;
  merchant_key?: string;
  merchant_salt?: string;
  is_active: boolean;
  test_mode: boolean;
  created_at: string;
  updated_at: string;
}

export interface SmsSettings {
  id: string;
  company_id: string;
  username?: string;
  password?: string;
  sender_id?: string;
  api_url?: string;
  is_active: boolean;
  test_mode: boolean;
  created_at: string;
  updated_at: string;
}

export interface BankAccount {
  id: string;
  company_id: string;
  bank_name: string;
  account_holder_name: string;
  account_number: string;
  iban?: string | null;
  branch_name?: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export const companiesApi = {
  /** Tüm şirketleri listeler (sadece super_admin) */
  getAll: async (): Promise<Company[]> => {
    const response = await apiClient.get('/companies');
    return response.data;
  },

  getCurrent: async (): Promise<Company> => {
    const response = await apiClient.get('/companies/current/company');
    return response.data;
  },

  update: async (data: Partial<Company>): Promise<Company> => {
    const response = await apiClient.patch('/companies/current/company', data);
    return response.data;
  },

  uploadLogo: async (file: File): Promise<Company> => {
    const form = new FormData();
    form.append('logo', file);
    const baseURL = apiClient.defaults.baseURL;
    const token = useAuthStore.getState().accessToken;
    const res = await fetch(`${baseURL}/companies/current/logo`, {
      method: 'POST',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      body: form,
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw { response: { data: { message: err.message || 'Logo yüklenemedi' } } };
    }
    return res.json();
  },

  deleteLogo: async (): Promise<Company> => {
    const response = await apiClient.delete('/companies/current/logo');
    return response.data;
  },

  getMailSettings: async (): Promise<MailSettings> => {
    const response = await apiClient.get('/companies/current/mail-settings');
    return response.data;
  },

  updateMailSettings: async (data: Partial<MailSettings>): Promise<MailSettings> => {
    const response = await apiClient.patch('/companies/current/mail-settings', data);
    return response.data;
  },

  testMailConnection: async (): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/companies/current/mail-settings/test');
    return response.data;
  },

  getPaytrSettings: async (): Promise<PaytrSettings> => {
    const response = await apiClient.get('/companies/current/paytr-settings');
    return response.data;
  },

  updatePaytrSettings: async (data: Partial<PaytrSettings>): Promise<PaytrSettings> => {
    const response = await apiClient.patch('/companies/current/paytr-settings', data);
    return response.data;
  },

  getSmsSettings: async (): Promise<SmsSettings> => {
    const response = await apiClient.get('/companies/current/sms-settings');
    return response.data;
  },

  updateSmsSettings: async (data: Partial<SmsSettings>): Promise<SmsSettings> => {
    const response = await apiClient.patch('/companies/current/sms-settings', data);
    return response.data;
  },

  testSmsConnection: async (): Promise<{ success: boolean; message: string }> => {
    const response = await apiClient.post('/companies/current/sms-settings/test');
    return response.data;
  },

  // Bank Accounts
  getBankAccounts: async (): Promise<BankAccount[]> => {
    const response = await apiClient.get('/bank-accounts');
    return response.data;
  },

  getActiveBankAccounts: async (): Promise<BankAccount[]> => {
    const response = await apiClient.get('/bank-accounts/active');
    return response.data;
  },

  createBankAccount: async (data: Partial<BankAccount>): Promise<BankAccount> => {
    const response = await apiClient.post('/bank-accounts', data);
    return response.data;
  },

  updateBankAccount: async (id: string, data: Partial<BankAccount>): Promise<BankAccount> => {
    const response = await apiClient.patch(`/bank-accounts/${id}`, data);
    return response.data;
  },

  deleteBankAccount: async (id: string): Promise<void> => {
    await apiClient.delete(`/bank-accounts/${id}`);
  },
};
