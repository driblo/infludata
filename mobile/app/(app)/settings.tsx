import { useMutation } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Appbar, Button, Card, Dialog, List, Portal, Snackbar, Text, useTheme } from 'react-native-paper';

import { authApi } from '@/api/endpoints/auth';
import { exportsApi } from '@/api/endpoints/exports';
import { useAuthStore } from '@/auth/authStore';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';

export default function SettingsScreen() {
  const router = useRouter();
  const theme = useTheme();
  const setSignedOut = useAuthStore((s) => s.setSignedOut);
  const [snack, setSnack] = useState<string | null>(null);
  const [confirmDelete, setConfirmDelete] = useState(false);

  const exportJson = useMutation({
    mutationFn: () => exportsApi.create('json'),
    onSuccess: () => setSnack('JSON export queued.'),
    onError: (e) => setSnack(`Export failed: ${(e as Error).message}`),
  });
  const exportGdpr = useMutation({
    mutationFn: () => exportsApi.create('gdpr'),
    onSuccess: () => setSnack('GDPR archive queued.'),
    onError: (e) => setSnack(`Export failed: ${(e as Error).message}`),
  });

  const doDelete = async () => {
    setConfirmDelete(false);
    try {
      await authApi.deleteAccount();
    } finally {
      await secureStore.remove(TOKEN_KEY);
      setSignedOut();
      router.replace('/login');
    }
  };

  return (
    <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
      <Appbar.Header>
        <Appbar.BackAction onPress={() => router.back()} />
        <Appbar.Content title="Settings" />
      </Appbar.Header>

      <ScrollView contentContainerStyle={styles.body}>
        <Card mode="contained" style={styles.card}>
          <List.Item
            title="Export my data (JSON)"
            left={(props) => <List.Icon {...props} icon="file-download-outline" />}
            onPress={() => exportJson.mutate()}
          />
          <List.Item
            title="GDPR export"
            description="All your data in one archive"
            left={(props) => <List.Icon {...props} icon="shield-account-outline" />}
            onPress={() => exportGdpr.mutate()}
          />
        </Card>

        <Card mode="contained" style={[styles.card, styles.danger]}>
          <List.Item
            title="Delete account"
            titleStyle={{ color: theme.colors.error }}
            left={(props) => <List.Icon {...props} icon="delete-forever" color={theme.colors.error} />}
            onPress={() => setConfirmDelete(true)}
          />
        </Card>
      </ScrollView>

      <Portal>
        <Dialog visible={confirmDelete} onDismiss={() => setConfirmDelete(false)}>
          <Dialog.Title>Delete account?</Dialog.Title>
          <Dialog.Content>
            <Text>
              This permanently removes your account, connections, alerts, and exports. Shared creator
              profiles (no PII) are retained.
            </Text>
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setConfirmDelete(false)}>Cancel</Button>
            <Button mode="contained" buttonColor={theme.colors.error} onPress={doDelete}>
              Delete
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <Snackbar visible={!!snack} onDismiss={() => setSnack(null)} duration={3000}>
        {snack ?? ''}
      </Snackbar>
    </View>
  );
}

const styles = StyleSheet.create({
  body: { padding: 16 },
  card: { marginBottom: 12 },
  danger: { borderWidth: 1, borderColor: '#311523' },
});
