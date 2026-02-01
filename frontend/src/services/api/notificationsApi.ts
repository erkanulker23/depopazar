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
    // Backend'de bu endpoint yoksa, tüm bildirimleri tek tek işaretleyebiliriz
    const notifications = await notificationsApi.getAll();
    const unreadNotifications = notifications.filter((n: any) => !n.is_read);
    await Promise.all(unreadNotifications.map((n: any) => notificationsApi.markAsRead(n.id)));
  },
};
