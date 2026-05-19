import { useQuery } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { creatorsApi } from '@/api/endpoints/creators';
import { qk } from '@/api/queryKeys';
import type { Range } from '@/api/types';
import { FollowersChart } from '@/features/creators/FollowersChart';
import { RangeSegmented } from '@/features/creators/RangeSegmented';
import { compactNumber } from '@/lib/format';
import { Avatar } from '@/ui/Avatar';
import { Button } from '@/ui/Button';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';
import { Screen } from '@/ui/Screen';

export default function CreatorDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const creatorId = Number(id);
  const router = useRouter();
  const [range, setRange] = useState<Range>('30d');

  const profile = useQuery({
    queryKey: qk.creatorProfile(creatorId),
    queryFn: () => creatorsApi.profile(creatorId),
    enabled: Number.isFinite(creatorId),
  });
  const metrics = useQuery({
    queryKey: qk.creatorMetrics(creatorId, range),
    queryFn: () => creatorsApi.metrics(creatorId, range),
    enabled: Number.isFinite(creatorId),
  });

  if (profile.isLoading) return <Screen><LoadingState /></Screen>;
  if (profile.isError) return <Screen><ErrorState message={(profile.error as Error).message} /></Screen>;
  if (!profile.data) return <Screen><ErrorState message="Profile not found" /></Screen>;

  const p = profile.data.profile;

  return (
    <Screen>
      <ScrollView>
        <View style={styles.header}>
          <Avatar uri={p.avatar_url} fallback={p.network} size={64} />
          <View style={{ flex: 1, marginLeft: 16 }}>
            <Text style={styles.title}>{p.display_name ?? p.handle}</Text>
            <Text style={styles.body}>{p.network} · @{p.handle}</Text>
            <Text style={styles.followers}>{compactNumber(p.follower_count)} followers</Text>
          </View>
        </View>

        <View style={styles.rangeRow}>
          <Text style={styles.h2}>Followers</Text>
          <RangeSegmented value={range} onChange={setRange} />
        </View>

        {metrics.isLoading ? (
          <LoadingState />
        ) : metrics.isError ? (
          <ErrorState message={(metrics.error as Error).message} />
        ) : (
          <FollowersChart points={metrics.data ?? []} />
        )}

        <View style={{ marginTop: 24 }}>
          <Button
            label="View content"
            variant="outlined"
            onPress={() => router.push(`/creators/${creatorId}/content`)}
          />
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', marginBottom: 16 },
  title: { color: '#fff', fontSize: 20, fontWeight: '700' },
  body: { color: '#C8CDE8' },
  followers: { color: '#C8CDE8', marginTop: 4 },
  rangeRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  h2: { color: '#fff', fontWeight: '700', fontSize: 16 },
});
