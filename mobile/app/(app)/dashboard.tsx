import { useQuery } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { costApi } from '@/api/endpoints/cost';
import { dashboardApi } from '@/api/endpoints/dashboard';
import { qk } from '@/api/queryKeys';
import { useAuthStore } from '@/auth/authStore';
import { compactNumber } from '@/lib/format';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';
import { EmptyState } from '@/ui/EmptyState';
import { ErrorState } from '@/ui/ErrorState';
import { LoadingState } from '@/ui/LoadingState';
import { Screen } from '@/ui/Screen';

export default function DashboardScreen() {
  const router = useRouter();
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
    <Screen>
      <ScrollView>
        <View style={styles.header}>
          <Text style={styles.h1}>infludata</Text>
          <Pressable onPress={logout} accessibilityRole="button">
            <Text style={styles.link}>Sign out</Text>
          </Pressable>
        </View>

        {user ? <Text style={styles.user}>{user.email}</Text> : null}

        <View style={styles.nav}>
          <NavItem label="Creators" onPress={() => router.push('/creators')} />
          <NavItem label="Accounts" onPress={() => router.push('/accounts')} />
          <NavItem label="Alerts" onPress={() => router.push('/alerts')} />
          <NavItem label="Settings" onPress={() => router.push('/settings')} />
        </View>

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
            <Text style={styles.h2}>Top movers (7d)</Text>
            {data.data.top_movers.length === 0 ? (
              <EmptyState title="No data yet" hint="Add creators and wait for the next sync." />
            ) : (
              data.data.top_movers.map((m) => (
                <Pressable
                  key={m.creator_profile_id}
                  onPress={() => router.push(`/creators/${m.creator_profile_id}`)}
                  style={styles.mover}
                >
                  <Text style={styles.moverTitle}>Creator #{m.creator_profile_id}</Text>
                  <Text style={styles.moverBody}>{compactNumber(m.followers)} followers</Text>
                  <Text
                    style={[styles.delta, { color: m.delta_7d >= 0 ? '#22C55E' : '#D62F4E' }]}
                  >
                    {m.delta_7d >= 0 ? '+' : ''}
                    {compactNumber(m.delta_7d)}
                  </Text>
                </Pressable>
              ))
            )}
          </View>
        )}

        {cost.data ? (
          <View style={styles.cost}>
            <Text style={styles.h3}>X API spend today</Text>
            <Text style={styles.body}>
              {cost.data.kill_switch
                ? 'Kill switch active — X disabled'
                : `$${cost.data.spent_today_usd.toFixed(3)} spent · $${cost.data.remaining_today_usd.toFixed(3)} remaining`}
            </Text>
          </View>
        ) : null}
      </ScrollView>
    </Screen>
  );
}

function NavItem({ label, onPress }: { label: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={styles.navItem} accessibilityRole="button">
      <Text style={styles.navLabel}>{label}</Text>
    </Pressable>
  );
}

function KpiTile({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.tile}>
      <Text style={styles.tileLabel}>{label}</Text>
      <Text style={styles.tileValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  h1: { color: '#fff', fontSize: 22, fontWeight: '700' },
  link: { color: '#7B61FF', fontWeight: '600' },
  user: { color: '#C8CDE8', marginBottom: 8 },
  nav: { flexDirection: 'row', gap: 8, flexWrap: 'wrap', marginVertical: 12 },
  navItem: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    backgroundColor: '#1A1D40',
  },
  navLabel: { color: '#fff' },
  tiles: { flexDirection: 'row', gap: 12, marginVertical: 12 },
  tile: { flex: 1, padding: 16, backgroundColor: '#1A1D40', borderRadius: 12 },
  tileLabel: { color: '#7C82A1', fontSize: 12 },
  tileValue: { color: '#fff', fontSize: 22, fontWeight: '700', marginTop: 4 },
  h2: { color: '#fff', fontWeight: '700', fontSize: 16, marginTop: 16, marginBottom: 8 },
  h3: { color: '#fff', fontWeight: '700', marginBottom: 4 },
  mover: {
    padding: 12,
    backgroundColor: '#1A1D40',
    borderRadius: 12,
    marginBottom: 8,
  },
  moverTitle: { color: '#fff', fontWeight: '600' },
  moverBody: { color: '#C8CDE8' },
  delta: { fontWeight: '700', marginTop: 4 },
  cost: { marginTop: 24, padding: 16, backgroundColor: '#1A1D40', borderRadius: 12 },
  body: { color: '#C8CDE8' },
});
