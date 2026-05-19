import Constants from 'expo-constants';

let initialized = false;

export async function initSentry(): Promise<void> {
  if (initialized) return;
  const dsn = (Constants.expoConfig?.extra?.sentryDsn as string | undefined) ?? '';
  if (!dsn) return;
  try {
    const Sentry = await import('@sentry/react-native');
    Sentry.init({ dsn, tracesSampleRate: 0.1, enableAutoSessionTracking: true });
    initialized = true;
  } catch (e) {
    // Best-effort: if Sentry fails to load (web bundle without native), don't crash the app.
    if (typeof console !== 'undefined') console.warn('Sentry init skipped:', e);
  }
}
