import axios, { type InternalAxiosRequestConfig } from 'axios';
import { useAuthStore } from '../../stores/authStore';

// Domain bazlı: build’te VITE_API_URL set edilir; yoksa çalışılan origin + /api (aynı domain)
const API_URL =
  import.meta.env.VITE_API_URL ||
  (typeof window !== 'undefined' ? `${window.location.origin}/api` : '/api');

export const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

let refreshPromise: Promise<boolean> | null = null;

// Request interceptor: add auth token
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const { accessToken } = useAuthStore.getState();
    if (accessToken) {
      config.headers.Authorization = `Bearer ${accessToken}`;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

// Response interceptor: on 401, try refresh then retry; else logout
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };
    if (error.response?.status !== 401) {
      return Promise.reject(error);
    }
    const url = originalRequest?.url ?? '';
    if (url.includes('/auth/login') || url.includes('/auth/refresh')) {
      useAuthStore.getState().logout();
      window.location.href = '/giris';
      return Promise.reject(error);
    }
    if (originalRequest._retry) {
      useAuthStore.getState().logout();
      window.location.href = '/giris';
      return Promise.reject(error);
    }
    refreshPromise ??= useAuthStore.getState().refreshAccessToken();
    const ok = await refreshPromise;
    refreshPromise = null;
    if (!ok) {
      window.location.href = '/giris';
      return Promise.reject(error);
    }
    originalRequest._retry = true;
    const token = useAuthStore.getState().accessToken;
    if (token) {
      originalRequest.headers.Authorization = `Bearer ${token}`;
    }
    return apiClient.request(originalRequest);
  },
);
