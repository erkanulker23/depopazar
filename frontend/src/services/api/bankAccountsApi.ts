import { apiClient } from './apiClient';

export const bankAccountsApi = {
  getAll: async () => {
    const response = await apiClient.get('/bank-accounts');
    return response.data;
  },
  getActive: async () => {
    const response = await apiClient.get('/bank-accounts/active');
    return response.data;
  },
  getById: async (id: string) => {
    const response = await apiClient.get(`/bank-accounts/${id}`);
    return response.data;
  },
  create: async (data: any) => {
    const response = await apiClient.post('/bank-accounts', data);
    return response.data;
  },
  update: async (id: string, data: any) => {
    const response = await apiClient.patch(`/bank-accounts/${id}`, data);
    return response.data;
  },
  delete: async (id: string) => {
    const response = await apiClient.delete(`/bank-accounts/${id}`);
    return response.data;
  },
};
