import { apiClient } from './apiClient';

export interface ServiceCategory {
  id: string;
  name: string;
  description?: string;
  created_at: string;
  updated_at: string;
}

export interface Service {
  id: string;
  category_id: string;
  category?: ServiceCategory;
  name: string;
  description?: string;
  unit_price: number;
  unit?: string;
  created_at: string;
  updated_at: string;
}

export const servicesApi = {
  // Categories
  getCategories: async (): Promise<ServiceCategory[]> => {
    const response = await apiClient.get('/service-categories');
    return response.data;
  },

  createCategory: async (data: Partial<ServiceCategory>): Promise<ServiceCategory> => {
    const response = await apiClient.post('/service-categories', data);
    return response.data;
  },

  updateCategory: async (id: string, data: Partial<ServiceCategory>): Promise<ServiceCategory> => {
    const response = await apiClient.patch(`/service-categories/${id}`, data);
    return response.data;
  },

  deleteCategory: async (id: string): Promise<void> => {
    await apiClient.delete(`/service-categories/${id}`);
  },

  // Services
  getServices: async (): Promise<Service[]> => {
    const response = await apiClient.get('/services');
    return response.data;
  },

  createService: async (data: Partial<Service>): Promise<Service> => {
    const response = await apiClient.post('/services', data);
    return response.data;
  },

  updateService: async (id: string, data: Partial<Service>): Promise<Service> => {
    const response = await apiClient.patch(`/services/${id}`, data);
    return response.data;
  },

  deleteService: async (id: string): Promise<void> => {
    await apiClient.delete(`/services/${id}`);
  },
};
