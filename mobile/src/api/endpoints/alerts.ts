import { api } from '../client';
import type { Alert, AlertKind } from '../types';

export const alertsApi = {
  async list(): Promise<Alert[]> {
    const res = await api.get<{ data: Alert[] }>('/api/alerts');
    return res.data.data;
  },
  async create(body: {
    target_type: 'creator' | 'own';
    target_id: number;
    kind: AlertKind;
    threshold: Record<string, unknown>;
    channel?: 'email' | 'push';
  }): Promise<Alert> {
    const res = await api.post<Alert>('/api/alerts', body);
    return res.data;
  },
  async remove(id: number): Promise<void> {
    await api.delete(`/api/alerts/${id}`);
  },
};
