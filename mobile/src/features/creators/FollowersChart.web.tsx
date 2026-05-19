import { Text, View } from 'react-native';

import type { MetricPoint } from '@/api/types';

/**
 * Web chart. Uses Recharts when running on web. We dynamically require
 * the module so the native bundles don't try to resolve it.
 */
export function FollowersChart({ points }: { points: MetricPoint[] }) {
  if (points.length === 0) {
    return (
      <View style={{ padding: 24, alignItems: 'center' }}>
        <Text style={{ color: '#7C82A1' }}>No data yet — refresh after the next sync.</Text>
      </View>
    );
  }

  try {
     
    const recharts = require('recharts') as {
      ResponsiveContainer: React.ComponentType<{ width: string | number; height: number; children: React.ReactNode }>;
      LineChart: React.ComponentType<{ data: object[]; children: React.ReactNode }>;
      Line: React.ComponentType<{ type: string; dataKey: string; stroke: string; dot: boolean; strokeWidth: number }>;
      XAxis: React.ComponentType<{ dataKey: string; hide?: boolean }>;
      YAxis: React.ComponentType<{ hide?: boolean }>;
      Tooltip: React.ComponentType<unknown>;
    };
    const { ResponsiveContainer, LineChart, Line, XAxis, YAxis, Tooltip } = recharts;

    const data = points.map((p) => ({ t: p.captured_at, followers: p.followers }));
    return (
      <View style={{ height: 240 }}>
        <ResponsiveContainer width="100%" height={240}>
          <LineChart data={data}>
            <XAxis dataKey="t" hide />
            <YAxis hide />
            <Tooltip />
            <Line type="monotone" dataKey="followers" stroke="#7B61FF" dot={false} strokeWidth={3} />
          </LineChart>
        </ResponsiveContainer>
      </View>
    );
  } catch {
    return (
      <View style={{ padding: 16 }}>
        <Text style={{ color: '#C8CDE8' }}>Chart library not installed. `npm i recharts`.</Text>
      </View>
    );
  }
}
