import { api } from '../client';
import type { XCost } from '../types';

export const costApi = {
  async getXCost(): Promise<XCost> {
    const res = await api.get<{ data: { x: XCost } }>('/api/cost');
    return res.data.data.x;
  },
};
