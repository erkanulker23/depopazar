import { apiClient } from './apiClient';

export const reportsApi = {
  getOccupancy: async () => {
    const response = await apiClient.get('/reports/occupancy');
    return response.data;
  },
  getRevenue: async (year: number, month: number) => {
    const response = await apiClient.get('/reports/revenue', {
      params: { year, month },
    });
    return response.data;
  },
  getBankAccountPaymentsByCustomer: async (options?: {
    bankAccountId?: string;
    startDate?: string;
    endDate?: string;
  }) => {
    const params: Record<string, string> = {};
    if (options?.bankAccountId) params.bank_account_id = options.bankAccountId;
    if (options?.startDate) params.start_date = options.startDate;
    if (options?.endDate) params.end_date = options.endDate;
    const response = await apiClient.get('/reports/bank-account-payments-by-customer', {
      params,
    });
    return response.data;
  },
};
