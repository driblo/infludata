import { useEffect, useRef } from 'react';

import { authApi } from '@/api/endpoints/auth';
import { useAuthStore } from '@/auth/authStore';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';

/**
 * Reads the persisted bearer from SecureStore on mount, validates it with
 * GET /api/me, and resolves the auth store status to either signedIn or
 * signedOut. Runs exactly once per app session.
 */
export function useAuthBootstrap(): void {
  const ranRef = useRef(false);

  useEffect(() => {
    if (ranRef.current) return;
    ranRef.current = true;

    void (async () => {
      const token = await secureStore.get(TOKEN_KEY);
      if (!token) {
        useAuthStore.getState().setSignedOut();
        return;
      }
      useAuthStore.setState({ token });
      try {
        const user = await authApi.me();
        useAuthStore.getState().setSignedIn(token, user);
      } catch {
        await secureStore.remove(TOKEN_KEY);
        useAuthStore.getState().setSignedOut();
      }
    })();
  }, []);
}
