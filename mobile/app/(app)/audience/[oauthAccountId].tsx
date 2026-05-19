import { useQuery } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Appbar, Card, ProgressBar, Text, useTheme } from 'react-native-paper';

import { audienceApi } from '@/api/endpoints/audience';
import { qk } from '@/api/queryKeys';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';

export default function AudienceScreen() {
  const { oauthAccountId } = useLocalSearchParams<{ oauthAccountId: string }>();
  const id = Number(oauthAccountId);
  const router = useRouter();
  const theme = useTheme();
  const q = useQuery({
    queryKey: qk.audience(id),
    queryFn: () => audienceApi.get(id),
    enabled: Number.isFinite(id),
  });

  return (
    <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
      <Appbar.Header>
        <Appbar.BackAction onPress={() => router.back()} />
        <Appbar.Content title="Audience" />
      </Appbar.Header>

      <ScrollView contentContainerStyle={styles.body}>
        {q.isLoading ? (
          <LoadingState />
        ) : q.isError ? (
          <ErrorState message={(q.error as Error).message} />
        ) : q.data && Object.keys(q.data).length > 0 ? (
          Object.entries(q.data).map(([dim, buckets]) => (
            <Card key={dim} mode="contained" style={styles.group}>
              <Card.Content>
                <Text variant="titleMedium" style={styles.h2}>
                  {dim}
                </Text>
                {buckets.map((b) => (
                  <View key={b.bucket} style={styles.row}>
                    <Text variant="bodyMedium" style={styles.bucket}>
                      {b.bucket}
                    </Text>
                    <View style={styles.bar}>
                      <ProgressBar progress={Math.min(1, b.value_pct / 100)} />
                    </View>
                    <Text variant="bodyMedium" style={styles.pct}>
                      {b.value_pct.toFixed(1)}%
                    </Text>
                  </View>
                ))}
              </Card.Content>
            </Card>
          ))
        ) : (
          <EmptyState title="No demographics yet" hint="Wait for the next sync." />
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  body: { padding: 16 },
  group: { marginBottom: 12 },
  h2: { marginBottom: 8, textTransform: 'capitalize' },
  row: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  bucket: { width: 80 },
  bar: { flex: 1, marginHorizontal: 8 },
  pct: { width: 56, textAlign: 'right' },
});
