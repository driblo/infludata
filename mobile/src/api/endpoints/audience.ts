import { api } from '../client';
import type { AudienceData } from '../types';

export const audienceApi = {
  async get(oauthAccountId: number): Promise<AudienceData> {
    const res = await api.get<{ data: AudienceData }>(`/api/audience/${oauthAccountId}`);
    return res.data.data;
  },
};
