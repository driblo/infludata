import { useQuery } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Appbar, Avatar, Button, Card, Text, useTheme } from 'react-native-paper';

import { creatorsApi } from '@/api/endpoints/creators';
import { qk } from '@/api/queryKeys';
import type { Range } from '@/api/types';
import { FollowersChart } from '@/features/creators/FollowersChart';
import { RangeSegmented } from '@/features/creators/RangeSegmented';
import { compactNumber } from '@/lib/format';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';

export default function CreatorDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const creatorId = Number(id);
  const router = useRouter();
  const theme = useTheme();
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

  if (profile.isLoading) {
    return (
      <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
        <Appbar.Header>
          <Appbar.BackAction onPress={() => router.back()} />
          <Appbar.Content title="Creator" />
        </Appbar.Header>
        <LoadingState />
      </View>
    );
  }
  if (profile.isError) {
    return (
      <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
        <Appbar.Header>
          <Appbar.BackAction onPress={() => router.back()} />
          <Appbar.Content title="Creator" />
        </Appbar.Header>
        <ErrorState message={(profile.error as Error).message} />
      </View>
    );
  }
  if (!profile.data) {
    return (
      <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
        <Appbar.Header>
          <Appbar.BackAction onPress={() => router.back()} />
          <Appbar.Content title="Creator" />
        </Appbar.Header>
        <ErrorState message="Profile not found" />
      </View>
    );
  }

  const p = profile.data.profile;

  return (
    <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
      <Appbar.Header>
        <Appbar.BackAction onPress={() => router.back()} />
        <Appbar.Content title={p.display_name ?? p.handle} subtitle={`${p.network} · @${p.handle}`} />
      </Appbar.Header>

      <ScrollView contentContainerStyle={styles.body}>
        <Card mode="contained" style={styles.headerCard}>
          <Card.Title
            title={p.display_name ?? p.handle}
            subtitle={`${p.network} · @${p.handle}`}
            left={() =>
              p.avatar_url ? (
                <Avatar.Image size={48} source={{ uri: p.avatar_url }} />
              ) : (
                <Avatar.Text size={48} label={p.network.charAt(0).toUpperCase()} />
              )
            }
            right={() => (
              <Text variant="titleMedium" style={styles.followers}>
                {compactNumber(p.follower_count)} followers
              </Text>
            )}
          />
        </Card>

        <View style={styles.rangeRow}>
          <Text variant="titleMedium">Followers</Text>
          <RangeSegmented value={range} onChange={setRange} />
        </View>

        <Card mode="contained" style={styles.chartCard}>
          <Card.Content>
            {metrics.isLoading ? (
              <LoadingState />
            ) : metrics.isError ? (
              <ErrorState message={(metrics.error as Error).message} />
            ) : (
              <FollowersChart points={metrics.data ?? []} />
            )}
          </Card.Content>
        </Card>

        <Button
          mode="outlined"
          icon="format-list-bulleted"
          onPress={() => router.push(`/creators/${creatorId}/content`)}
          style={styles.btn}
        >
          View content
        </Button>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  body: { padding: 16 },
  headerCard: { marginBottom: 12 },
  followers: { marginRight: 12, alignSelf: 'center' },
  rangeRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8, marginTop: 8 },
  chartCard: { marginBottom: 16 },
  btn: { marginTop: 8 },
});
