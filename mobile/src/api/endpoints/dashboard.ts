import { api } from '../client';
import type { DashboardData } from '../types';

export const dashboardApi = {
  async get(): Promise<DashboardData> {
    const res = await api.get<DashboardData>('/api/dashboard');
    return res.data;
  },
};
