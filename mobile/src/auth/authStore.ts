import { create } from 'zustand';

import type { User } from '@/api/types';

export type AuthStatus = 'loading' | 'signedIn' | 'signedOut';

type State = {
  status: AuthStatus;
  token: string | null;
  user: User | null;
  setSignedIn: (token: string, user: User) => void;
  setSignedOut: () => void;
  setUser: (user: User) => void;
};

export const useAuthStore = create<State>((set) => ({
  status: 'loading',
  token: null,
  user: null,
  setSignedIn: (token, user) => set({ status: 'signedIn', token, user }),
  setSignedOut: () => set({ status: 'signedOut', token: null, user: null }),
  setUser: (user) => set({ user }),
}));

export const authStoreSnapshot = (): State => useAuthStore.getState();
