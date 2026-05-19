import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Appbar, Card, IconButton, List, useTheme } from 'react-native-paper';

import { alertsApi } from '@/api/endpoints/alerts';
import { qk } from '@/api/queryKeys';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';

function iconFor(kind: string): string {
  switch (kind) {
    case 'follower_milestone':
      return 'trophy-outline';
    case 'engagement_drop':
      return 'trending-down';
    case 'new_content':
      return 'new-box';
    default:
      return 'bell-outline';
  }
}

export default function AlertsScreen() {
  const qc = useQueryClient();
  const router = useRouter();
  const theme = useTheme();
  const list = useQuery({ queryKey: qk.alerts, queryFn: () => alertsApi.list() });
  const remove = useMutation({
    mutationFn: (id: number) => alertsApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.alerts }),
  });

  return (
    <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
      <Appbar.Header>
        <Appbar.BackAction onPress={() => router.back()} />
        <Appbar.Content title="Alerts" />
      </Appbar.Header>

      <ScrollView contentContainerStyle={styles.body}>
        {list.isLoading ? (
          <LoadingState />
        ) : list.isError ? (
          <ErrorState message={(list.error as Error).message} />
        ) : list.data && list.data.length > 0 ? (
          list.data.map((a) => (
            <Card key={a.id} mode="contained" style={styles.card}>
              <List.Item
                title={`${a.kind} · ${a.target_type} #${a.target_id}`}
                description={JSON.stringify(a.threshold)}
                left={(props) => <List.Icon {...props} icon={iconFor(a.kind)} />}
                right={() => <IconButton icon="delete-outline" onPress={() => remove.mutate(a.id)} />}
              />
            </Card>
          ))
        ) : (
          <EmptyState title="No alerts yet." />
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  body: { padding: 16 },
  card: { marginBottom: 8 },
});
