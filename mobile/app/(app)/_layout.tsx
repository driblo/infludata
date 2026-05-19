import { Redirect, Stack } from 'expo-router';

import { useAuthStore } from '@/auth/authStore';

export default function AppLayout() {
  const status = useAuthStore((s) => s.status);
  if (status === 'signedOut') return <Redirect href="/login" />;
  return <Stack screenOptions={{ headerShown: true }} />;
}
