import { apiClient } from './apiClient';
import { Customer } from './customersApi';
import { Service } from './servicesApi';

export interface ProposalItem {
  id: string;
  proposal_id: string;
  service_id?: string | null;
  service?: Service | null;
  name: string;
  description?: string;
  quantity: number;
  unit_price: number;
  total_price: number;
}

export interface Proposal {
  id: string;
  customer_id?: string | null;
  customer?: Customer | null;
  title: string;
  status: 'draft' | 'sent' | 'accepted' | 'rejected' | 'expired';
  total_amount: number;
  currency: string;
  valid_until?: string | null;
  notes?: string | null;
  pdf_url?: string | null;
  items?: ProposalItem[];
  created_at: string;
  updated_at: string;
}

export const proposalsApi = {
  getAll: async (): Promise<Proposal[]> => {
    const response = await apiClient.get('/proposals');
    return response.data;
  },

  getById: async (id: string): Promise<Proposal> => {
    const response = await apiClient.get(`/proposals/${id}`);
    return response.data;
  },

  create: async (data: any): Promise<Proposal> => {
    const response = await apiClient.post('/proposals', data);
    return response.data;
  },

  update: async (id: string, data: any): Promise<Proposal> => {
    const response = await apiClient.patch(`/proposals/${id}`, data);
    return response.data;
  },

  delete: async (id: string): Promise<void> => {
    await apiClient.delete(`/proposals/${id}`);
  },
};
