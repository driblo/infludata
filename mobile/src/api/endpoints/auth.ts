import { api } from '../client';
import type { AuthResponse, User } from '../types';

export const authApi = {
  async register(body: { name: string; email: string; password: string }): Promise<AuthResponse> {
    const res = await api.post<AuthResponse>('/api/auth/register', body);
    return res.data;
  },
  async login(body: { email: string; password: string; device_name?: string }): Promise<AuthResponse> {
    const res = await api.post<AuthResponse>('/api/auth/login', body);
    return res.data;
  },
  async logout(): Promise<void> {
    await api.post('/api/auth/logout');
  },
  async refresh(): Promise<{ token: string }> {
    const res = await api.post<{ token: string }>('/api/auth/refresh', { device_name: 'rn' });
    return res.data;
  },
  async me(): Promise<User> {
    const res = await api.get<User>('/api/me');
    return res.data;
  },
  async deleteAccount(): Promise<void> {
    await api.delete('/api/me');
  },
};
