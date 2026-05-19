import { useQuery } from '@tanstack/react-query';
import { useLocalSearchParams } from 'expo-router';
import { Image, ScrollView, StyleSheet, Text, View } from 'react-native';

import { creatorsApi } from '@/api/endpoints/creators';
import { qk } from '@/api/queryKeys';
import { formatDate } from '@/lib/format';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';
import { Screen } from '@/ui/Screen';

export default function ContentListScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const creatorId = Number(id);
  const list = useQuery({
    queryKey: qk.creatorContent(creatorId),
    queryFn: () => creatorsApi.content(creatorId),
    enabled: Number.isFinite(creatorId),
  });

  return (
    <Screen>
      {list.isLoading ? (
        <LoadingState />
      ) : list.isError ? (
        <ErrorState message={(list.error as Error).message} />
      ) : list.data && list.data.length > 0 ? (
        <ScrollView>
          {list.data.map((c) => (
            <View key={c.id} style={styles.row}>
              {c.thumbnail_url ? (
                <Image source={{ uri: c.thumbnail_url }} style={styles.thumb} accessibilityIgnoresInvertColors />
              ) : (
                <View style={[styles.thumb, styles.thumbFallback]} />
              )}
              <View style={{ flex: 1, marginLeft: 12 }}>
                <Text style={styles.title}>{c.title ?? `(untitled ${c.kind})`}</Text>
                <Text style={styles.body}>
                  {c.kind}
                  {c.published_at ? ` · ${formatDate(c.published_at)}` : ''}
                </Text>
              </View>
            </View>
          ))}
        </ScrollView>
      ) : (
        <EmptyState title="No content fetched yet." />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#1A1D40',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  thumb: { width: 56, height: 56, borderRadius: 8, backgroundColor: '#2A2E55' },
  thumbFallback: { backgroundColor: '#2A2E55' },
  title: { color: '#fff', fontWeight: '600' },
  body: { color: '#C8CDE8' },
});
