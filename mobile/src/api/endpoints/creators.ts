import { api } from '../client';
import type { ContentItem, CreatorProfile, MetricPoint, Network, Range, TrackedCreator } from '../types';

export const creatorsApi = {
  async list(): Promise<TrackedCreator[]> {
    const res = await api.get<{ data: TrackedCreator[] }>('/api/creators');
    return res.data.data;
  },
  async create(body: { network: Network; handle: string; label?: string }): Promise<{
    tracked_creator: TrackedCreator;
    creator_profile: CreatorProfile;
  }> {
    const res = await api.post<{ tracked_creator: TrackedCreator; creator_profile: CreatorProfile }>(
      '/api/creators',
      body,
    );
    return res.data;
  },
  async remove(trackedId: number): Promise<void> {
    await api.delete(`/api/creators/${trackedId}`);
  },
  async profile(id: number): Promise<{ profile: CreatorProfile; latest_snapshot: MetricPoint | null }> {
    const res = await api.get<{ profile: CreatorProfile; latest_snapshot: MetricPoint | null }>(
      `/api/creators/${id}/profile`,
    );
    return res.data;
  },
  async metrics(id: number, range: Range): Promise<MetricPoint[]> {
    const res = await api.get<{ data: MetricPoint[]; range: Range }>(`/api/creators/${id}/metrics`, {
      params: { range },
    });
    return res.data.data;
  },
  async content(id: number): Promise<ContentItem[]> {
    const res = await api.get<{ data: ContentItem[] }>(`/api/creators/${id}/content`);
    return res.data.data;
  },
};
