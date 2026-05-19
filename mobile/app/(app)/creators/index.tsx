import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, View } from 'react-native';
import {
  Appbar,
  Avatar,
  Button,
  Card,
  Chip,
  Dialog,
  FAB,
  IconButton,
  List,
  Portal,
  Text,
  TextInput,
  useTheme,
} from 'react-native-paper';

import { creatorsApi } from '@/api/endpoints/creators';
import { qk } from '@/api/queryKeys';
import { NETWORKS, type Network } from '@/api/types';
import { compactNumber } from '@/lib/format';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';

export default function CreatorsScreen() {
  const qc = useQueryClient();
  const router = useRouter();
  const theme = useTheme();
  const [open, setOpen] = useState(false);
  const [network, setNetwork] = useState<Network>('youtube');
  const [handle, setHandle] = useState('');
  const [label, setLabel] = useState('');

  const list = useQuery({ queryKey: qk.creators, queryFn: () => creatorsApi.list() });
  const create = useMutation({
    mutationFn: () => creatorsApi.create({ network, handle: handle.trim(), label: label || undefined }),
    onSuccess: () => {
      setOpen(false);
      setHandle('');
      setLabel('');
      qc.invalidateQueries({ queryKey: qk.creators });
      qc.invalidateQueries({ queryKey: qk.dashboard });
    },
    onError: (e) => Alert.alert('Could not track creator', (e as Error).message),
  });
  const untrack = useMutation({
    mutationFn: (id: number) => creatorsApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.creators }),
  });

  return (
    <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
      <Appbar.Header>
        <Appbar.BackAction onPress={() => router.back()} />
        <Appbar.Content title="Tracked creators" />
      </Appbar.Header>

      <ScrollView contentContainerStyle={styles.body}>
        {list.isLoading ? (
          <LoadingState />
        ) : list.isError ? (
          <ErrorState message={(list.error as Error).message} />
        ) : list.data && list.data.length > 0 ? (
          list.data.map((t) => {
            const p = t.creator_profile;
            return (
              <Card key={t.id} mode="contained" style={styles.card}>
                <List.Item
                  title={p?.display_name ?? t.handle}
                  description={
                    `${t.network} · @${t.handle}` +
                    (p ? `  ·  ${compactNumber(p.follower_count)} followers` : '')
                  }
                  left={() =>
                    p?.avatar_url ? (
                      <Avatar.Image size={40} source={{ uri: p.avatar_url }} />
                    ) : (
                      <Avatar.Text size={40} label={t.network.charAt(0).toUpperCase()} />
                    )
                  }
                  right={() => (
                    <IconButton icon="delete-outline" onPress={() => untrack.mutate(t.id)} />
                  )}
                  onPress={() => router.push(`/creators/${t.creator_profile_id}`)}
                />
              </Card>
            );
          })
        ) : (
          <EmptyState title="No tracked creators yet." hint="Tap + to track your first creator." />
        )}
      </ScrollView>

      <FAB icon="plus" label="Add" onPress={() => setOpen(true)} style={styles.fab} />

      <Portal>
        <Dialog visible={open} onDismiss={() => setOpen(false)}>
          <Dialog.Title>Track a creator</Dialog.Title>
          <Dialog.Content>
            <Text variant="labelMedium" style={styles.fieldLabel}>
              Network
            </Text>
            <View style={styles.chips}>
              {NETWORKS.map((n) => (
                <Chip key={n} selected={network === n} onPress={() => setNetwork(n)} style={styles.chip}>
                  {n}
                </Chip>
              ))}
            </View>
            <TextInput
              label="Handle (without @)"
              mode="outlined"
              value={handle}
              onChangeText={setHandle}
              autoCapitalize="none"
              style={styles.input}
            />
            <TextInput
              label="Label (optional)"
              mode="outlined"
              value={label}
              onChangeText={setLabel}
              style={styles.input}
            />
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setOpen(false)}>Cancel</Button>
            <Button mode="contained" onPress={() => create.mutate()} loading={create.isPending}>
              Track
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
    </View>
  );
}

const styles = StyleSheet.create({
  body: { padding: 16, paddingBottom: 96 },
  card: { marginBottom: 8 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  fieldLabel: { marginBottom: 6, marginTop: 8, opacity: 0.7 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 12 },
  chip: { marginRight: 4 },
  input: { marginBottom: 8 },
});
