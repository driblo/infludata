import { useMutation } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { Alert, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { authApi } from '@/api/endpoints/auth';
import { exportsApi } from '@/api/endpoints/exports';
import { useAuthStore } from '@/auth/authStore';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';
import { Screen } from '@/ui/Screen';

export default function SettingsScreen() {
  const router = useRouter();
  const setSignedOut = useAuthStore((s) => s.setSignedOut);

  const exportJson = useMutation({
    mutationFn: () => exportsApi.create('json'),
    onSuccess: () => Alert.alert('Export queued', 'Check /api/exports/{id} for the file URL.'),
  });
  const exportGdpr = useMutation({
    mutationFn: () => exportsApi.create('gdpr'),
    onSuccess: () => Alert.alert('Export queued', 'GDPR archive queued. Open /api/exports/{id} when ready.'),
  });

  const confirmDelete = () => {
    Alert.alert(
      'Delete account?',
      'This permanently removes your account, connections, alerts, and exports.',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            try {
              await authApi.deleteAccount();
            } finally {
              await secureStore.remove(TOKEN_KEY);
              setSignedOut();
              router.replace('/login');
            }
          },
        },
      ],
    );
  };

  return (
    <Screen>
      <Text style={styles.h1}>Settings</Text>
      <ScrollView>
        <Row label="Export my data (JSON)" onPress={() => exportJson.mutate()} />
        <Row label="GDPR export" onPress={() => exportGdpr.mutate()} />
        <View style={styles.divider} />
        <Pressable onPress={confirmDelete} style={styles.dangerRow}>
          <Text style={styles.dangerLabel}>Delete account</Text>
        </Pressable>
      </ScrollView>
    </Screen>
  );
}

function Row({ label, onPress }: { label: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  h1: { color: '#fff', fontSize: 20, fontWeight: '700', marginBottom: 12 },
  row: { padding: 14, backgroundColor: '#1A1D40', borderRadius: 12, marginBottom: 8 },
  rowLabel: { color: '#fff', fontWeight: '500' },
  divider: { height: 1, backgroundColor: '#2A2E55', marginVertical: 12 },
  dangerRow: { padding: 14, backgroundColor: '#311523', borderRadius: 12 },
  dangerLabel: { color: '#D62F4E', fontWeight: '700' },
});
