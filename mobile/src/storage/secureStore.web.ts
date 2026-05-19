/**
 * Web fallback using localStorage. The web build is intended for
 * authenticated dashboard use on trusted devices; treat the token as
 * equivalent to a session cookie.
 */
export const secureStore = {
  async get(key: string): Promise<string | null> {
    if (typeof window === 'undefined') return null;
    return window.localStorage.getItem(key);
  },
  async set(key: string, value: string): Promise<void> {
    if (typeof window === 'undefined') return;
    window.localStorage.setItem(key, value);
  },
  async remove(key: string): Promise<void> {
    if (typeof window === 'undefined') return;
    window.localStorage.removeItem(key);
  },
};

export const TOKEN_KEY = 'sanctum_token';
