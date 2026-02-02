import { apiClient } from './apiClient';

export const notificationsApi = {
  getAll: async () => {
    const response = await apiClient.get('/notifications');
    return response.data;
  },
  getById: async (id: string) => {
    const response = await apiClient.get(`/notifications/${id}`);
    return response.data;
  },
  markAsRead: async (id: string) => {
    const response = await apiClient.patch(`/notifications/${id}/read`);
    return response.data;
  },
  markAllAsRead: async () => {
    const response = await apiClient.patch('/notifications/mark-all-read');
    return response.data;
  },
  deleteAll: async () => {
    const response = await apiClient.delete('/notifications/delete-all');
    return response.data;
  },
};
