import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import {
  Alert,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';

import { creatorsApi } from '@/api/endpoints/creators';
import { qk } from '@/api/queryKeys';
import { NETWORKS, type Network } from '@/api/types';
import { compactNumber } from '@/lib/format';
import { Avatar } from '@/ui/Avatar';
import { Button } from '@/ui/Button';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';
import { Screen } from '@/ui/Screen';
import { TextField } from '@/ui/TextField';

export default function CreatorsScreen() {
  const qc = useQueryClient();
  const router = useRouter();
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
    <Screen>
      <View style={styles.header}>
        <Text style={styles.h1}>Tracked creators</Text>
        <Button label="Add" onPress={() => setOpen(true)} />
      </View>

      <ScrollView>
        {list.isLoading ? (
          <LoadingState />
        ) : list.isError ? (
          <ErrorState message={(list.error as Error).message} />
        ) : list.data && list.data.length > 0 ? (
          list.data.map((t) => {
            const p = t.creator_profile;
            return (
              <Pressable
                key={t.id}
                onPress={() => router.push(`/creators/${t.creator_profile_id}`)}
                style={styles.row}
              >
                <Avatar uri={p?.avatar_url ?? null} fallback={t.network} />
                <View style={{ flex: 1, marginLeft: 12 }}>
                  <Text style={styles.title}>{p?.display_name ?? t.handle}</Text>
                  <Text style={styles.body}>
                    {t.network} · @{t.handle}
                    {p ? ` · ${compactNumber(p.follower_count)} followers` : ''}
                  </Text>
                </View>
                <Pressable onPress={() => untrack.mutate(t.id)}>
                  <Text style={styles.removeLink}>Remove</Text>
                </Pressable>
              </Pressable>
            );
          })
        ) : (
          <EmptyState title="No tracked creators yet." hint="Tap Add to track your first creator." />
        )}
      </ScrollView>

      <Modal visible={open} animationType="slide" transparent onRequestClose={() => setOpen(false)}>
        <View style={styles.backdrop}>
          <View style={styles.sheet}>
            <Text style={styles.h2}>Track a creator</Text>

            <Text style={styles.fieldLabel}>Network</Text>
            <View style={styles.chips}>
              {NETWORKS.map((n) => (
                <TouchableOpacity
                  key={n}
                  onPress={() => setNetwork(n)}
                  style={[styles.chip, network === n && styles.chipActive]}
                >
                  <Text style={[styles.chipText, network === n && styles.chipTextActive]}>{n}</Text>
                </TouchableOpacity>
              ))}
            </View>

            <TextField label="Handle (without @)" value={handle} onChangeText={setHandle} autoCapitalize="none" />
            <TextField label="Label (optional)" value={label} onChangeText={setLabel} />

            <View style={styles.modalActions}>
              <Button label="Cancel" variant="outlined" onPress={() => setOpen(false)} />
              <Button label="Track" onPress={() => create.mutate()} loading={create.isPending} />
            </View>
          </View>
        </View>
      </Modal>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 },
  h1: { color: '#fff', fontSize: 20, fontWeight: '700' },
  h2: { color: '#fff', fontSize: 18, fontWeight: '700', marginBottom: 12 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#1A1D40',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  title: { color: '#fff', fontWeight: '600' },
  body: { color: '#C8CDE8' },
  removeLink: { color: '#D62F4E', fontWeight: '600' },
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: '#0F1226', padding: 24, borderTopLeftRadius: 16, borderTopRightRadius: 16 },
  fieldLabel: { color: '#C8CDE8', marginBottom: 6, fontWeight: '500' },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 12 },
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#2A2E55',
  },
  chipActive: { backgroundColor: '#7B61FF', borderColor: '#7B61FF' },
  chipText: { color: '#C8CDE8', textTransform: 'capitalize' },
  chipTextActive: { color: '#fff', fontWeight: '700' },
  modalActions: { flexDirection: 'row', gap: 8, marginTop: 8 },
});
