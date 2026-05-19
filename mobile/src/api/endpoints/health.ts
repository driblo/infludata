import { api } from '../client';

export type HealthResponse = {
  status: 'ok' | 'degraded';
  checks: { app: string; db: string; redis: string };
  version: string;
};

export const healthApi = {
  async get(): Promise<HealthResponse> {
    const res = await api.get<HealthResponse>('/api/health');
    return res.data;
  },
};
