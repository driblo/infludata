import type { Range } from './types';

export const qk = {
  me: ['me'] as const,
  health: ['health'] as const,
  connections: ['connections'] as const,
  creators: ['creators'] as const,
  creatorProfile: (id: number) => ['creators', id, 'profile'] as const,
  creatorMetrics: (id: number, range: Range) => ['creators', id, 'metrics', range] as const,
  creatorContent: (id: number) => ['creators', id, 'content'] as const,
  audience: (oauthId: number) => ['audience', oauthId] as const,
  dashboard: ['dashboard'] as const,
  xCost: ['cost', 'x'] as const,
  alerts: ['alerts'] as const,
  exportStatus: (id: number) => ['exports', id] as const,
};
