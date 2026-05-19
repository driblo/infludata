import { useQuery } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Image, ScrollView, StyleSheet, View } from 'react-native';
import { Appbar, Card, List, useTheme } from 'react-native-paper';

import { creatorsApi } from '@/api/endpoints/creators';
import { qk } from '@/api/queryKeys';
import { formatDate } from '@/lib/format';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';

export default function ContentListScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const creatorId = Number(id);
  const router = useRouter();
  const theme = useTheme();
  const list = useQuery({
    queryKey: qk.creatorContent(creatorId),
    queryFn: () => creatorsApi.content(creatorId),
    enabled: Number.isFinite(creatorId),
  });

  return (
    <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
      <Appbar.Header>
        <Appbar.BackAction onPress={() => router.back()} />
        <Appbar.Content title="Content" />
      </Appbar.Header>

      {list.isLoading ? (
        <LoadingState />
      ) : list.isError ? (
        <ErrorState message={(list.error as Error).message} />
      ) : list.data && list.data.length > 0 ? (
        <ScrollView contentContainerStyle={styles.body}>
          {list.data.map((c) => (
            <Card key={c.id} mode="contained" style={styles.card}>
              <List.Item
                title={c.title ?? `(untitled ${c.kind})`}
                description={c.kind + (c.published_at ? ` · ${formatDate(c.published_at)}` : '')}
                left={() =>
                  c.thumbnail_url ? (
                    <Image
                      source={{ uri: c.thumbnail_url }}
                      style={styles.thumb}
                      accessibilityIgnoresInvertColors
                    />
                  ) : (
                    <View style={[styles.thumb, { backgroundColor: theme.colors.surfaceVariant }]} />
                  )
                }
              />
            </Card>
          ))}
        </ScrollView>
      ) : (
        <EmptyState title="No content fetched yet." />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  body: { padding: 16 },
  card: { marginBottom: 8 },
  thumb: { width: 56, height: 56, borderRadius: 8, marginLeft: 8 },
});
