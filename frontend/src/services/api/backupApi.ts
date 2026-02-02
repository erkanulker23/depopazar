import { apiClient } from './apiClient';

export const backupApi = {
  create: async (): Promise<{ message: string; filename: string }> => {
    const response = await apiClient.post('/backups');
    return response.data;
  },

  list: async (): Promise<string[]> => {
    const response = await apiClient.get('/backups');
    return response.data;
  },

  download: (filename: string) => {
    // Direct download link
    const baseURL = apiClient.defaults.baseURL;
    window.open(`${baseURL}/backups/${filename}`, '_blank');
  },

  delete: async (filename: string): Promise<void> => {
    await apiClient.delete(`/backups/${filename}`);
  }
};
