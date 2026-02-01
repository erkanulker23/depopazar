import { apiClient } from './apiClient';

const getPhotoFullUrl = (url: string) => {
  if (url.startsWith('http')) return url;
  const base = import.meta.env.VITE_API_URL || '';
  return base ? `${base}${url}` : url;
};

export const itemsApi = {
  uploadPhoto: async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('file', file);
    const { data } = await apiClient.post<{ url: string }>('/items/upload-photo', formData);
    return data.url;
  },
  getPhotoFullUrl,
  getAll: async (contractId?: string) => {
    const params = contractId ? { contractId } : {};
    const response = await apiClient.get('/items', { params });
    return response.data;
  },
  getByCustomerId: async (customerId: string) => {
    const response = await apiClient.get(`/items/customer/${customerId}`);
    return response.data;
  },
  getById: async (id: string) => {
    const response = await apiClient.get(`/items/${id}`);
    return response.data;
  },
  create: async (data: any) => {
    const response = await apiClient.post('/items', data);
    return response.data;
  },
  update: async (id: string, data: any) => {
    const response = await apiClient.patch(`/items/${id}`, data);
    return response.data;
  },
  remove: async (id: string) => {
    await apiClient.delete(`/items/${id}`);
  },
};
