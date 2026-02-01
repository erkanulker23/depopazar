import { apiClient } from './apiClient';

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export type ContractStatusFilter = 'all' | 'active' | 'terminated';
export type ContractPaymentFilter = 'all' | 'has_payment' | 'no_payment';
export type ContractDebtFilter = 'all' | 'has_debt' | 'no_debt';

export interface ContractListParams {
  page?: number;
  limit?: number;
  status?: ContractStatusFilter;
  paymentStatus?: ContractPaymentFilter;
  debtStatus?: ContractDebtFilter;
}

export const contractsApi = {
  getAll: async (params?: ContractListParams) => {
    const sp = new URLSearchParams();
    if (params?.page != null) sp.set('page', String(params.page));
    if (params?.limit != null) sp.set('limit', String(params.limit));
    if (params?.status && params.status !== 'all') sp.set('status', params.status);
    if (params?.paymentStatus && params.paymentStatus !== 'all') sp.set('paymentStatus', params.paymentStatus);
    if (params?.debtStatus && params.debtStatus !== 'all') sp.set('debtStatus', params.debtStatus);
    const q = sp.toString();
    const response = await apiClient.get<PaginatedResponse<any>>('/contracts' + (q ? `?${q}` : ''));
    return response.data;
  },
  getById: async (id: string) => {
    const response = await apiClient.get(`/contracts/${id}`);
    return response.data;
  },
  create: async (data: any) => {
    const response = await apiClient.post('/contracts', data);
    return response.data;
  },
  update: async (id: string, data: any) => {
    const response = await apiClient.patch(`/contracts/${id}`, data);
    return response.data;
  },
  delete: async (id: string) => {
    const response = await apiClient.delete(`/contracts/${id}`);
    return response.data;
  },
  getTotalDebt: async (id: string) => {
    const response = await apiClient.get(`/contracts/${id}/total-debt`);
    return response.data;
  },
  terminate: async (id: string) => {
    const response = await apiClient.post(`/contracts/${id}/terminate`);
    return response.data;
  },
  bulkDelete: async (ids: string[]) => {
    const response = await apiClient.post('/contracts/bulk-delete', { ids });
    return response.data;
  },
  bulkTerminate: async (ids: string[]) => {
    const response = await apiClient.post('/contracts/bulk-terminate', { ids });
    return response.data;
  },
  getCustomersWithMultipleContracts: async () => {
    const response = await apiClient.get('/contracts/customers-with-multiple-contracts');
    return response.data;
  },
  checkPaymentConsistency: async (id: string) => {
    const response = await apiClient.get(`/contracts/${id}/payment-consistency`);
    return response.data;
  },
};
