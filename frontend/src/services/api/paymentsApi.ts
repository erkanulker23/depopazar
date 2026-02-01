import { apiClient } from './apiClient';

export const paymentsApi = {
  getAll: async () => {
    const response = await apiClient.get('/payments');
    return response.data;
  },
  getById: async (id: string) => {
    const response = await apiClient.get(`/payments/${id}`);
    return response.data;
  },
  create: async (data: any) => {
    const response = await apiClient.post('/payments', data);
    return response.data;
  },
  update: async (id: string, data: any) => {
    const response = await apiClient.patch(`/payments/${id}`, data);
    return response.data;
  },
  markAsPaid: async (id: string, paymentMethod?: string, transactionId?: string, notes?: string, bankAccountId?: string) => {
    const response = await apiClient.post(`/payments/${id}/mark-as-paid`, {
      payment_method: paymentMethod,
      transaction_id: transactionId,
      notes: notes,
      bank_account_id: bankAccountId,
    });
    return response.data;
  },
  initiatePaytr: async (paymentId: string, customerId: string) => {
    const response = await apiClient.post('/payments/paytr/initiate', {
      payment_id: paymentId,
      customer_id: customerId,
    });
    return response.data;
  },
  delete: async (id: string) => {
    const response = await apiClient.delete(`/payments/${id}`);
    return response.data;
  },
  deleteMany: async (ids: string[]) => {
    const response = await apiClient.delete('/payments/bulk/delete', {
      data: { ids },
    });
    return response.data;
  },
};
