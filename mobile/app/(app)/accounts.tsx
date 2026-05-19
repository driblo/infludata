import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, View } from 'react-native';
import { Appbar, Avatar, Button, Card, IconButton, List, Text, useTheme } from 'react-native-paper';

import { connectionsApi } from '@/api/endpoints/connections';
import { qk } from '@/api/queryKeys';
import { NETWORKS } from '@/api/types';
import { usePhylloConnect } from '@/features/connections/usePhylloConnect';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';

export default function AccountsScreen() {
  const qc = useQueryClient();
  const router = useRouter();
  const theme = useTheme();
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
    <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
      <Appbar.Header>
        <Appbar.BackAction onPress={() => router.back()} />
        <Appbar.Content title="Accounts" />
      </Appbar.Header>

      <ScrollView contentContainerStyle={styles.body}>
        <Text variant="titleMedium" style={styles.h2}>
          Connected accounts
        </Text>

        {list.isLoading ? (
          <LoadingState />
        ) : list.isError ? (
          <ErrorState message={(list.error as Error).message} />
        ) : list.data && list.data.length > 0 ? (
          list.data.map((acc) => (
            <Card key={acc.id} mode="contained" style={styles.card}>
              <List.Item
                title={acc.external_handle ?? acc.network}
                description={`${acc.network} · ${acc.status}`}
                left={() => <Avatar.Text size={40} label={acc.network.charAt(0).toUpperCase()} />}
                right={() => (
                  <IconButton
                    icon="link-off"
                    accessibilityLabel="Disconnect"
                    onPress={() => remove.mutate(acc.id)}
                  />
                )}
              />
            </Card>
          ))
        ) : (
          <EmptyState title="No connected accounts yet." />
        )}

        <Text variant="titleMedium" style={[styles.h2, { marginTop: 24 }]}>
          Connect a new account
        </Text>

        {NETWORKS.map((n) => (
          <Card key={n} mode="contained" style={styles.card}>
            <Card.Title
              title={n.charAt(0).toUpperCase() + n.slice(1)}
              left={() => <Avatar.Text size={40} label={n.charAt(0).toUpperCase()} />}
              right={() => (
                <Button
                  mode="outlined"
                  onPress={() => onConnect(n)}
                  loading={busyNetwork === n}
                  compact
                  style={{ marginRight: 12 }}
                >
                  Connect
                </Button>
              )}
            />
          </Card>
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  body: { padding: 16 },
  h2: { marginBottom: 8 },
  card: { marginBottom: 8 },
});
