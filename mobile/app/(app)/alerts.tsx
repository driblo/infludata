import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { alertsApi } from '@/api/endpoints/alerts';
import { qk } from '@/api/queryKeys';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';
import { Screen } from '@/ui/Screen';

export default function AlertsScreen() {
  const qc = useQueryClient();
  const list = useQuery({ queryKey: qk.alerts, queryFn: () => alertsApi.list() });
  const remove = useMutation({
    mutationFn: (id: number) => alertsApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.alerts }),
  });

  return (
    <Screen>
      <Text style={styles.h1}>Alerts</Text>
      <ScrollView>
        {list.isLoading ? (
          <LoadingState />
        ) : list.isError ? (
          <ErrorState message={(list.error as Error).message} />
        ) : list.data && list.data.length > 0 ? (
          list.data.map((a) => (
            <View key={a.id} style={styles.row}>
              <View style={{ flex: 1 }}>
                <Text style={styles.title}>
                  {a.kind} · {a.target_type} #{a.target_id}
                </Text>
                <Text style={styles.body}>{JSON.stringify(a.threshold)}</Text>
              </View>
              <Pressable onPress={() => remove.mutate(a.id)}>
                <Text style={styles.removeLink}>Delete</Text>
              </Pressable>
            </View>
          ))
        ) : (
          <EmptyState title="No alerts yet." />
        )}
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  h1: { color: '#fff', fontSize: 20, fontWeight: '700', marginBottom: 12 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#1A1D40',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  title: { color: '#fff', fontWeight: '600' },
  body: { color: '#C8CDE8', fontSize: 12, marginTop: 4 },
  removeLink: { color: '#D62F4E', fontWeight: '600' },
});
