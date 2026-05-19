import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Alert, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { connectionsApi } from '@/api/endpoints/connections';
import { qk } from '@/api/queryKeys';
import { usePhylloConnect } from '@/features/connections/usePhylloConnect';
import { NETWORKS } from '@/api/types';
import { Avatar } from '@/ui/Avatar';
import { Button } from '@/ui/Button';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';
import { Screen } from '@/ui/Screen';

export default function AccountsScreen() {
  const qc = useQueryClient();
  const [busyNetwork, setBusyNetwork] = useState<string | null>(null);

  const list = useQuery({ queryKey: qk.connections, queryFn: () => connectionsApi.list() });
  const remove = useMutation({
    mutationFn: (id: number) => connectionsApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.connections }),
  });

  const startConnect = usePhylloConnect(() => qc.invalidateQueries({ queryKey: qk.connections }));

  const onConnect = async (network: string) => {
    setBusyNetwork(network);
    try {
      await startConnect();
    } catch (e) {
      Alert.alert('Phyllo Connect', (e as Error).message);
    } finally {
      setBusyNetwork(null);
    }
  };

  return (
    <Screen>
      <ScrollView>
        <Text style={styles.h1}>Connected accounts</Text>
        {list.isLoading ? (
          <LoadingState />
        ) : list.isError ? (
          <ErrorState message={(list.error as Error).message} />
        ) : list.data && list.data.length > 0 ? (
          list.data.map((acc) => (
            <View key={acc.id} style={styles.row}>
              <Avatar fallback={acc.network} />
              <View style={{ flex: 1, marginLeft: 12 }}>
                <Text style={styles.rowTitle}>{acc.external_handle ?? acc.network}</Text>
                <Text style={styles.rowBody}>
                  {acc.network} · {acc.status}
                </Text>
              </View>
              <Pressable onPress={() => remove.mutate(acc.id)}>
                <Text style={styles.removeLink}>Remove</Text>
              </Pressable>
            </View>
          ))
        ) : (
          <EmptyState title="No connected accounts yet." />
        )}

        <Text style={[styles.h1, { marginTop: 24 }]}>Connect a new account</Text>
        {NETWORKS.map((n) => (
          <View key={n} style={styles.connectRow}>
            <Avatar fallback={n} />
            <Text style={styles.connectLabel}>{n}</Text>
            <Button
              label={busyNetwork === n ? 'Opening…' : 'Connect'}
              variant="outlined"
              onPress={() => onConnect(n)}
              loading={busyNetwork === n}
            />
          </View>
        ))}
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  h1: { color: '#fff', fontSize: 18, fontWeight: '700', marginBottom: 8 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#1A1D40',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  rowTitle: { color: '#fff', fontWeight: '600' },
  rowBody: { color: '#C8CDE8', textTransform: 'capitalize' },
  removeLink: { color: '#D62F4E', fontWeight: '600' },
  connectRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 8,
  },
  connectLabel: { color: '#fff', textTransform: 'capitalize', flex: 1, marginLeft: 12 },
});
