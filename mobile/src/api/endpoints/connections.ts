import { api } from '../client';
import type { OauthAccount } from '../types';

export const connectionsApi = {
  async list(): Promise<OauthAccount[]> {
    const res = await api.get<{ data: OauthAccount[] }>('/api/connections');
    return res.data.data;
  },
  async mintPhylloToken(): Promise<{ sdk_token: string; phyllo_user_id: string; expires_at: string }> {
    const res = await api.post<{ sdk_token: string; phyllo_user_id: string; expires_at: string }>(
      '/api/connections/phyllo-token',
    );
    return res.data;
  },
  async remove(id: number): Promise<void> {
    await api.delete(`/api/connections/${id}`);
  },
};
