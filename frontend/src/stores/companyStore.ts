import { create } from 'zustand';
import { companiesApi, Company, PublicBrand } from '../services/api/companiesApi';

const DEFAULT_PROJECT_NAME = 'DepoPazar';

interface CompanyState {
  company: Company | null;
  projectName: string;
  logoUrl: string | null;
  /** Giriş yapmadan login sayfası / SEO için (public API). */
  publicBrand: PublicBrand | null;
  isLoading: boolean;
  loadCompany: () => Promise<void>;
  loadPublicBrand: () => Promise<void>;
  updateCompany: (company: Company) => void;
}

export const useCompanyStore = create<CompanyState>((set, get) => ({
  company: null,
  projectName: DEFAULT_PROJECT_NAME,
  logoUrl: null,
  publicBrand: null,
  isLoading: false,
  loadCompany: async () => {
    try {
      set({ isLoading: true });
      const company = await companiesApi.getCurrent();
      set({
        company,
        projectName: company.project_name?.trim() || DEFAULT_PROJECT_NAME,
        logoUrl: company.logo_url || null,
        isLoading: false,
      });
    } catch (error) {
      console.error('Failed to load company:', error);
      set({ isLoading: false });
    }
  },
  loadPublicBrand: async () => {
    try {
      const publicBrand = await companiesApi.getPublicBrand();
      set({ publicBrand });
    } catch {
      set({ publicBrand: null });
    }
  },
  updateCompany: (company: Company) => {
    set({
      company,
      projectName: company.project_name?.trim() || DEFAULT_PROJECT_NAME,
      logoUrl: company.logo_url || null,
    });
  },
}));

/** Proje adı: giriş yapılmışsa company’den, değilse public brand’den, yoksa varsayılan. */
export function getDisplayProjectName(): string {
  const { projectName, publicBrand } = useCompanyStore.getState();
  if (projectName && projectName !== DEFAULT_PROJECT_NAME) return projectName;
  const name = publicBrand?.project_name?.trim();
  return name || DEFAULT_PROJECT_NAME;
}
