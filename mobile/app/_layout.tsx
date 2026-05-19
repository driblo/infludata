import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useMemo } from 'react';
import { Platform } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { PaperProvider } from 'react-native-paper';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { useAuthBootstrap } from '@/auth/useAuthBootstrap';
import { initSentry } from '@/lib/sentry';
import { paperTheme } from '@/lib/theme';

export default function RootLayout() {
  useEffect(() => {
    void initSentry();
  }, []);

  useAuthBootstrap();

  const client = useMemo(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 30_000,
            retry: (failureCount, error) => {
              const e = error as { status?: number };
              if (e?.status === 401 || e?.status === 403 || e?.status === 404) return false;
              return failureCount < 1;
            },
            refetchOnWindowFocus: Platform.OS === 'web',
            refetchOnReconnect: true,
          },
          mutations: { retry: 0 },
        },
      }),
    [],
  );

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <PaperProvider theme={paperTheme}>
          <QueryClientProvider client={client}>
            <StatusBar style="light" />
            <Stack screenOptions={{ headerShown: false }} />
          </QueryClientProvider>
        </PaperProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
