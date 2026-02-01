import { apiClient } from './apiClient';

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export const customersApi = {
  getAll: async (params?: { page?: number; limit?: number }) => {
    const sp = new URLSearchParams();
    if (params?.page != null) sp.set('page', String(params.page));
    if (params?.limit != null) sp.set('limit', String(params.limit));
    const q = sp.toString();
    const response = await apiClient.get<PaginatedResponse<any>>('/customers' + (q ? `?${q}` : ''));
    return response.data;
  },
  getById: async (id: string) => {
    const response = await apiClient.get(`/customers/${id}`);
    return response.data;
  },
  create: async (data: any) => {
    const response = await apiClient.post('/customers', data);
    return response.data;
  },
  getDebtInfo: async (id: string) => {
    const response = await apiClient.get(`/customers/${id}`);
    const customer = response.data;
    const contracts = customer.contracts || [];
    const allPayments = [];
    
    for (const contract of contracts) {
      if (contract.is_active) {
        const payments = contract.payments || [];
        allPayments.push(...payments);
      }
    }
    
    const unpaidPayments = allPayments.filter(
      (p: any) => p.status === 'pending' || p.status === 'overdue'
    );
    
    // Calculate total debt from unpaid payments
    // Make sure to parse amounts correctly
    const totalDebt = unpaidPayments.reduce((sum: number, p: any) => {
      const amount = Number(p.amount) || 0;
      return sum + amount;
    }, 0);
    
    // Use backend's debtInfo if available, otherwise use calculated values
    const backendDebtInfo = customer.debtInfo || null;
    const finalTotalDebt = backendDebtInfo?.totalDebt !== undefined 
      ? Number(backendDebtInfo.totalDebt) 
      : totalDebt;
    
    return {
      customer,
      totalDebt: finalTotalDebt,
      unpaidPayments,
      contracts: contracts.filter((c: any) => c.is_active),
      debtInfo: backendDebtInfo,
    };
  },
  remove: async (id: string) => {
    await apiClient.delete(`/customers/${id}`);
  },
  bulkDelete: async (ids: string[]) => {
    const response = await apiClient.post('/customers/bulk-delete', { ids });
    return response.data;
  },
};
