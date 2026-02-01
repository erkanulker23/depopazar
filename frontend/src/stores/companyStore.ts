import { create } from 'zustand';
import { companiesApi, Company } from '../services/api/companiesApi';

interface CompanyState {
  company: Company | null;
  projectName: string;
  logoUrl: string | null;
  isLoading: boolean;
  loadCompany: () => Promise<void>;
  updateCompany: (company: Company) => void;
}

export const useCompanyStore = create<CompanyState>((set) => ({
  company: null,
  projectName: 'DepoPazar',
  logoUrl: null,
  isLoading: false,
  loadCompany: async () => {
    try {
      set({ isLoading: true });
      const company = await companiesApi.getCurrent();
      set({
        company,
        projectName: company.project_name?.trim() || 'DepoPazar',
        logoUrl: company.logo_url || null,
        isLoading: false,
      });
    } catch (error) {
      console.error('Failed to load company:', error);
      set({ isLoading: false });
    }
  },
  updateCompany: (company: Company) => {
    set({
      company,
      projectName: company.project_name?.trim() || 'DepoPazar',
      logoUrl: company.logo_url || null,
    });
  },
}));
