import { apiClient } from './apiClient';

export interface User {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  phone?: string;
  role: string;
  is_active: boolean;
  created_at: string;
  last_login_at?: string;
  company_id?: string;
}

export const usersApi = {
  getAll: async (): Promise<User[]> => {
    const response = await apiClient.get('/users');
    return response.data;
  },

  getById: async (id: string): Promise<User> => {
    const response = await apiClient.get(`/users/${id}`);
    return response.data;
  },

  update: async (id: string, data: Partial<User>): Promise<User> => {
    const response = await apiClient.patch(`/users/${id}`, data);
    return response.data;
  },

  delete: async (id: string): Promise<void> => {
    await apiClient.delete(`/users/${id}`);
  },

  resetPassword: async (id: string, password?: string): Promise<{ message: string; password?: string }> => {
    const response = await apiClient.patch(`/users/${id}/reset-password`, { password });
    return response.data;
  }
};
