import { useQuery } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Appbar, Card, Chip, IconButton, Text, useTheme } from 'react-native-paper';

import { costApi } from '@/api/endpoints/cost';
import { dashboardApi } from '@/api/endpoints/dashboard';
import { qk } from '@/api/queryKeys';
import { useAuthStore } from '@/auth/authStore';
import { compactNumber } from '@/lib/format';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';

export default function DashboardScreen() {
  const router = useRouter();
  const theme = useTheme();
  const setSignedOut = useAuthStore((s) => s.setSignedOut);
  const user = useAuthStore((s) => s.user);

  const data = useQuery({ queryKey: qk.dashboard, queryFn: () => dashboardApi.get() });
  const cost = useQuery({ queryKey: qk.xCost, queryFn: () => costApi.getXCost() });

  const logout = async () => {
    await secureStore.remove(TOKEN_KEY);
    setSignedOut();
    router.replace('/login');
  };

  return (
    <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
      <Appbar.Header>
        <Appbar.Content title="infludata" subtitle={user?.email ?? undefined} />
        <Appbar.Action icon="account-multiple" onPress={() => router.push('/creators')} accessibilityLabel="Creators" />
        <Appbar.Action icon="link-variant" onPress={() => router.push('/accounts')} accessibilityLabel="Accounts" />
        <Appbar.Action icon="bell-outline" onPress={() => router.push('/alerts')} accessibilityLabel="Alerts" />
        <Appbar.Action icon="cog-outline" onPress={() => router.push('/settings')} accessibilityLabel="Settings" />
        <Appbar.Action icon="logout" onPress={logout} accessibilityLabel="Sign out" />
      </Appbar.Header>

      <ScrollView contentContainerStyle={styles.body}>
        {data.isLoading ? (
          <LoadingState />
        ) : data.isError ? (
          <ErrorState message={(data.error as Error).message} />
        ) : !data.data ? (
          <EmptyState title="No data" />
        ) : (
          <View>
            <View style={styles.tiles}>
              <KpiTile label="Tracked" value={`${data.data.totals.tracked_count}`} />
              <KpiTile label="Total followers" value={compactNumber(data.data.totals.total_followers)} />
            </View>

            <Text variant="titleMedium" style={styles.h2}>
              Top movers (7d)
            </Text>

            {data.data.top_movers.length === 0 ? (
              <EmptyState title="No data yet" hint="Add creators and wait for the next sync." />
            ) : (
              data.data.top_movers.map((m) => (
                <Card key={m.creator_profile_id} mode="contained" style={styles.mover} onPress={() => router.push(`/creators/${m.creator_profile_id}`)}>
                  <Card.Title
                    title={`Creator #${m.creator_profile_id}`}
                    subtitle={`${compactNumber(m.followers)} followers`}
                    right={() => (
                      <Chip
                        compact
                        style={[
                          styles.deltaChip,
                          { backgroundColor: m.delta_7d >= 0 ? '#103D2A' : '#3B1421' },
                        ]}
                        textStyle={{ color: m.delta_7d >= 0 ? '#22C55E' : '#FF6B83', fontWeight: '700' }}
                      >
                        {`${m.delta_7d >= 0 ? '+' : ''}${compactNumber(m.delta_7d)}`}
                      </Chip>
                    )}
                  />
                </Card>
              ))
            )}
          </View>
        )}

        {cost.data ? (
          <Card mode="contained" style={styles.costCard}>
            <Card.Title
              title="X API spend today"
              subtitle={
                cost.data.kill_switch
                  ? 'Kill switch active — X disabled'
                  : `$${cost.data.spent_today_usd.toFixed(3)} spent · $${cost.data.remaining_today_usd.toFixed(3)} remaining`
              }
              left={(props) => <IconButton {...props} icon="cash" onPress={undefined} />}
            />
          </Card>
        ) : null}
      </ScrollView>
    </View>
  );
}

function KpiTile({ label, value }: { label: string; value: string }) {
  return (
    <Card mode="contained" style={styles.tile}>
      <Card.Content>
        <Text variant="labelMedium" style={{ opacity: 0.7 }}>
          {label}
        </Text>
        <Text variant="headlineSmall" style={{ marginTop: 4 }}>
          {value}
        </Text>
      </Card.Content>
    </Card>
  );
}

const styles = StyleSheet.create({
  body: { padding: 16 },
  tiles: { flexDirection: 'row', gap: 12, marginVertical: 8 },
  tile: { flex: 1 },
  h2: { marginTop: 16, marginBottom: 8 },
  mover: { marginBottom: 8 },
  deltaChip: { marginRight: 12, alignSelf: 'center' },
  costCard: { marginTop: 24 },
});
