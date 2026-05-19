import { useQuery } from '@tanstack/react-query';
import { useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { audienceApi } from '@/api/endpoints/audience';
import { qk } from '@/api/queryKeys';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';
import { Screen } from '@/ui/Screen';

export default function AudienceScreen() {
  const { oauthAccountId } = useLocalSearchParams<{ oauthAccountId: string }>();
  const id = Number(oauthAccountId);
  const q = useQuery({
    queryKey: qk.audience(id),
    queryFn: () => audienceApi.get(id),
    enabled: Number.isFinite(id),
  });

  return (
    <Screen>
      <ScrollView>
        {q.isLoading ? (
          <LoadingState />
        ) : q.isError ? (
          <ErrorState message={(q.error as Error).message} />
        ) : q.data && Object.keys(q.data).length > 0 ? (
          Object.entries(q.data).map(([dim, buckets]) => (
            <View key={dim} style={styles.group}>
              <Text style={styles.h2}>{dim}</Text>
              {buckets.map((b) => (
                <View key={b.bucket} style={styles.row}>
                  <Text style={styles.bucket}>{b.bucket}</Text>
                  <View style={styles.bar}>
                    <View style={[styles.barFill, { width: `${Math.min(100, b.value_pct)}%` }]} />
                  </View>
                  <Text style={styles.pct}>{b.value_pct.toFixed(1)}%</Text>
                </View>
              ))}
            </View>
          ))
        ) : (
          <EmptyState title="No demographics yet" hint="Wait for the next sync." />
        )}
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  group: { marginBottom: 16 },
  h2: { color: '#fff', fontWeight: '700', fontSize: 16, marginBottom: 8, textTransform: 'capitalize' },
  row: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  bucket: { color: '#C8CDE8', width: 80 },
  bar: { flex: 1, height: 8, backgroundColor: '#1A1D40', borderRadius: 4, overflow: 'hidden', marginHorizontal: 8 },
  barFill: { height: 8, backgroundColor: '#7B61FF' },
  pct: { color: '#C8CDE8', width: 50, textAlign: 'right' },
});
