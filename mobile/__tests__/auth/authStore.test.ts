import { useAuthStore } from '@/auth/authStore';

beforeEach(() => {
  useAuthStore.setState({ status: 'loading', token: null, user: null });
});

describe('authStore', () => {
  it('starts in the loading state', () => {
    expect(useAuthStore.getState().status).toBe('loading');
  });

  it('transitions to signedIn on setSignedIn', () => {
    useAuthStore.getState().setSignedIn('tok-1', { id: 1, name: 'a', email: 'a@b' });
    const s = useAuthStore.getState();
    expect(s.status).toBe('signedIn');
    expect(s.token).toBe('tok-1');
    expect(s.user?.email).toBe('a@b');
  });

  it('clears on setSignedOut', () => {
    useAuthStore.getState().setSignedIn('tok-1', { id: 1, name: 'a', email: 'a@b' });
    useAuthStore.getState().setSignedOut();
    const s = useAuthStore.getState();
    expect(s.status).toBe('signedOut');
    expect(s.token).toBeNull();
    expect(s.user).toBeNull();
  });
});
