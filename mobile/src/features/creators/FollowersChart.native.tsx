import { StyleSheet, Text, View } from 'react-native';

import type { MetricPoint } from '@/api/types';

/**
 * Placeholder native chart implementation.
 *
 * Real chart wiring uses `victory-native` XL (Skia). Kept as a typed
 * placeholder so the rest of the app compiles cleanly until the chart
 * dependency is finalized for the dev client.
 */
export function FollowersChart({ points }: { points: MetricPoint[] }) {
  if (points.length === 0) {
    return (
      <View style={styles.empty}>
        <Text style={styles.emptyText}>No data yet — refresh after the next sync.</Text>
      </View>
    );
  }
  const first = points[0]!.followers;
  const last = points[points.length - 1]!.followers;
  const delta = last - first;
  return (
    <View style={styles.wrap}>
      <Text style={styles.first}>{first}</Text>
      <Text style={styles.last}>{last}</Text>
      <Text style={[styles.delta, { color: delta >= 0 ? '#22C55E' : '#D62F4E' }]}>
        Δ {delta >= 0 ? '+' : ''}
        {delta}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { padding: 16, backgroundColor: '#1A1D40', borderRadius: 12 },
  empty: { padding: 24, alignItems: 'center' },
  emptyText: { color: '#7C82A1' },
  first: { color: '#7C82A1' },
  last: { color: '#fff', fontWeight: '700', fontSize: 18, marginTop: 4 },
  delta: { fontWeight: '700', marginTop: 8 },
});
