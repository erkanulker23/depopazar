import { apiClient } from './apiClient';
import { useAuthStore } from '../../stores/authStore';

export interface TransportationJob {
  id: string;
  company_id: string;
  customer_id: string;
  customer?: any;
  pickup_province?: string | null;
  pickup_district?: string | null;
  pickup_neighborhood?: string | null;
  pickup_address?: string | null;
  pickup_floor_status?: string | null;
  pickup_elevator_status?: string | null;
  pickup_room_count?: number | null;
  delivery_province?: string | null;
  delivery_district?: string | null;
  delivery_neighborhood?: string | null;
  delivery_address?: string | null;
  delivery_floor_status?: string | null;
  delivery_elevator_status?: string | null;
  delivery_room_count?: number | null;
  staff?: Array<{ id: string; user: any }>;
  price?: number | null;
  vat_rate?: number;
  price_includes_vat: boolean;
  contract_pdf_url?: string | null;
  notes?: string | null;
  status: string;
  job_date?: string | null;
  job_type?: string | null;
  is_paid: boolean;
  created_at: string;
  updated_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export const transportationJobsApi = {
  getAll: async (params?: { page?: number; limit?: number; year?: number; month?: number }) => {
    const sp = new URLSearchParams();
    if (params?.page != null) sp.set('page', String(params.page));
    if (params?.limit != null) sp.set('limit', String(params.limit));
    if (params?.year != null) sp.set('year', String(params.year));
    if (params?.month != null) sp.set('month', String(params.month));
    const q = sp.toString();
    const response = await apiClient.get<PaginatedResponse<TransportationJob>>(
      '/transportation-jobs' + (q ? `?${q}` : '')
    );
    return response.data;
  },

  getById: async (id: string) => {
    const response = await apiClient.get<TransportationJob>(`/transportation-jobs/${id}`);
    return response.data;
  },

  getByCustomerId: async (customerId: string) => {
    const response = await apiClient.get<TransportationJob[]>(`/transportation-jobs/customer/${customerId}`);
    return response.data;
  },

  create: async (data: Partial<TransportationJob>) => {
    const response = await apiClient.post<TransportationJob>('/transportation-jobs', data);
    return response.data;
  },

  update: async (id: string, data: Partial<TransportationJob>) => {
    const response = await apiClient.patch<TransportationJob>(`/transportation-jobs/${id}`, data);
    return response.data;
  },

  delete: async (id: string) => {
    await apiClient.delete(`/transportation-jobs/${id}`);
  },

  uploadPdf: async (id: string, file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    const baseURL = apiClient.defaults.baseURL;
    const token = useAuthStore.getState().accessToken;
    const res = await fetch(`${baseURL}/transportation-jobs/${id}/upload-pdf`, {
      method: 'POST',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      body: formData,
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw { response: { data: { message: err.message || 'PDF yüklenemedi' } } };
    }
    return res.json();
  },
};
