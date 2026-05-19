export type Network = 'youtube' | 'instagram' | 'tiktok' | 'x' | 'facebook';

export const NETWORKS: Network[] = ['youtube', 'instagram', 'tiktok', 'x', 'facebook'];

export type User = {
  id: number;
  name: string;
  email: string;
  is_admin?: boolean;
};

export type AuthResponse = {
  token: string;
  user: User;
};

export type OauthAccount = {
  id: number;
  network: Network;
  external_handle: string | null;
  status: string;
  connected_at: string | null;
  last_synced_at: string | null;
};

export type CreatorProfile = {
  id: number;
  network: Network;
  handle: string;
  display_name: string | null;
  avatar_url: string | null;
  follower_count: number;
  is_verified: boolean;
};

export type TrackedCreator = {
  id: number;
  creator_profile_id: number;
  network: Network;
  handle: string;
  label: string | null;
  creator_profile?: CreatorProfile;
};

export type MetricPoint = {
  captured_at: string;
  followers: number;
  following: number;
  total_likes: number;
  total_views: number;
  engagement_rate: number | null;
};

export type ContentItem = {
  id: number;
  kind: string;
  title: string | null;
  url: string | null;
  thumbnail_url: string | null;
  duration_s: number | null;
  published_at: string | null;
};

export type DashboardData = {
  totals: { tracked_count: number; total_followers: number };
  top_movers: { creator_profile_id: number; followers: number; delta_7d: number }[];
};

export type XCost = {
  spent_today_usd: number;
  remaining_today_usd: number;
  kill_switch: boolean;
};

export type Range = '7d' | '30d' | '90d' | '1y';

export const RANGES: Range[] = ['7d', '30d', '90d', '1y'];

export type AlertKind = 'follower_milestone' | 'engagement_drop' | 'new_content';

export type Alert = {
  id: number;
  user_id: number;
  target_type: 'creator' | 'own';
  target_id: number;
  kind: AlertKind;
  threshold: Record<string, unknown>;
  channel: 'email' | 'push';
  enabled: boolean;
};

export type AudienceData = Record<string, { bucket: string; value_pct: number }[]>;

export type ExportKind = 'csv' | 'json' | 'gdpr';

export type ExportRequest = {
  id: number;
  kind: ExportKind;
  status: 'pending' | 'running' | 'completed' | 'failed';
  file_url: string | null;
};
