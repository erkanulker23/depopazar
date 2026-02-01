import { apiClient } from './apiClient';

export const warehousesApi = {
  getAll: async () => {
    const response = await apiClient.get('/warehouses');
    return response.data;
  },
  getById: async (id: string) => {
    const response = await apiClient.get(`/warehouses/${id}`);
    return response.data;
  },
  create: async (data: any) => {
    const response = await apiClient.post('/warehouses', data);
    return response.data;
  },
  update: async (id: string, data: any) => {
    const response = await apiClient.patch(`/warehouses/${id}`, data);
    return response.data;
  },
  remove: async (id: string) => {
    await apiClient.delete(`/warehouses/${id}`);
  },
};
