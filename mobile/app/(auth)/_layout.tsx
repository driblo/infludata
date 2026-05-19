import { Redirect, Stack } from 'expo-router';

import { useAuthStore } from '@/auth/authStore';

export default function AuthLayout() {
  const status = useAuthStore((s) => s.status);
  if (status === 'signedIn') return <Redirect href="/dashboard" />;
  return <Stack screenOptions={{ headerShown: false }} />;
}
