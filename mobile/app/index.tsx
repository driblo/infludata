import { Redirect } from 'expo-router';

import { useAuthStore } from '@/auth/authStore';
import { LoadingState } from '@/ui/LoadingState';

export default function Index() {
  const status = useAuthStore((s) => s.status);
  if (status === 'loading') return <LoadingState />;
  return <Redirect href={status === 'signedIn' ? '/dashboard' : '/login'} />;
}
