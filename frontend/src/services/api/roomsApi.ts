import { apiClient } from './apiClient';

export const roomsApi = {
  getAll: async () => {
    const response = await apiClient.get('/rooms');
    return response.data;
  },
  getById: async (id: string) => {
    const response = await apiClient.get(`/rooms/${id}`);
    return response.data;
  },
  create: async (data: any) => {
    const response = await apiClient.post('/rooms', data);
    return response.data;
  },
  update: async (id: string, data: any) => {
    const response = await apiClient.patch(`/rooms/${id}`, data);
    return response.data;
  },
  remove: async (id: string) => {
    await apiClient.delete(`/rooms/${id}`);
  },
  bulkDelete: async (ids: string[]) => {
    const response = await apiClient.post('/rooms/bulk-delete', { ids });
    return response.data;
  },
};
