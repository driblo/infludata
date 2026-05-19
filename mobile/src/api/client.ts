import axios, { type AxiosError, type AxiosInstance } from 'axios';
import Constants from 'expo-constants';

import { useAuthStore } from '@/auth/authStore';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';

import { appErrorFromAxios } from './errors';

const apiBaseUrl =
  (Constants.expoConfig?.extra?.apiBaseUrl as string | undefined) ??
  process.env.EXPO_PUBLIC_API_BASE_URL ??
  'http://localhost:8000';

export const api: AxiosInstance = axios.create({
  baseURL: apiBaseUrl,
  timeout: 30_000,
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers = config.headers ?? {};
    (config.headers as Record<string, string>).Authorization = `Bearer ${token}`;
  }
  return config;
});

/**
 * Single-flight refresh: when one in-flight 401 fires the refresh, every
 * subsequent 401 awaits the same promise. On success we retry the original
 * request once. On failure we clear the token and let the layout-level
 * redirect bounce the user to /login.
 */
let refreshInflight: Promise<string | null> | null = null;

async function attemptRefresh(): Promise<string | null> {
  if (refreshInflight) return refreshInflight;
  refreshInflight = (async () => {
    try {
      const token = useAuthStore.getState().token;
      if (!token) return null;
      const res = await axios.post<{ token: string }>(
        `${apiBaseUrl}/api/auth/refresh`,
        { device_name: 'rn' },
        { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } },
      );
      const next = res.data?.token;
      if (!next) return null;
      const user = useAuthStore.getState().user;
      await secureStore.set(TOKEN_KEY, next);
      useAuthStore.getState().setSignedIn(next, user ?? ({ id: 0, name: '', email: '' } as never));
      return next;
    } catch {
      await secureStore.remove(TOKEN_KEY);
      useAuthStore.getState().setSignedOut();
      return null;
    } finally {
      refreshInflight = null;
    }
  })();
  return refreshInflight;
}

api.interceptors.response.use(
  (r) => r,
  async (error: AxiosError) => {
    const status = error.response?.status;
    const url = error.config?.url ?? '';
    const isAuthRoute = url.includes('/api/auth/');

    if (status === 401 && !isAuthRoute && !(error.config as { _retried?: boolean })._retried) {
      const next = await attemptRefresh();
      if (next && error.config) {
        (error.config as { _retried?: boolean })._retried = true;
        error.config.headers = error.config.headers ?? {};
        (error.config.headers as Record<string, string>).Authorization = `Bearer ${next}`;
        return api.request(error.config);
      }
    }

    return Promise.reject(appErrorFromAxios(error));
  },
);
