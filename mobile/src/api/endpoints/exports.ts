import { api } from '../client';
import type { ExportKind, ExportRequest } from '../types';

export const exportsApi = {
  async create(kind: ExportKind): Promise<ExportRequest> {
    const res = await api.post<ExportRequest>('/api/exports', { kind });
    return res.data;
  },
  async get(id: number): Promise<ExportRequest> {
    const res = await api.get<ExportRequest>(`/api/exports/${id}`);
    return res.data;
  },
};
