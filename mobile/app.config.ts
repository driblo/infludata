import type { ExpoConfig } from 'expo/config';

const config: ExpoConfig = {
  name: 'infludata',
  slug: 'infludata',
  version: '0.1.0',
  scheme: 'infludata',
  orientation: 'portrait',
  userInterfaceStyle: 'automatic',
  icon: './assets/icon.png',
  splash: {
    image: './assets/splash.png',
    resizeMode: 'contain',
    backgroundColor: '#0F1226',
  },
  newArchEnabled: true,
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.infludata.app',
  },
  android: {
    package: 'com.infludata.app',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: '#0F1226',
    },
  },
  web: {
    bundler: 'metro',
    output: 'static',
    favicon: './assets/icon.png',
  },
  plugins: [
    'expo-router',
    'expo-secure-store',
    [
      '@sentry/react-native/expo',
      {
        organization: process.env.SENTRY_ORG,
        project: process.env.SENTRY_PROJECT,
      },
    ],
  ],
  experiments: {
    typedRoutes: true,
  },
  extra: {
    apiBaseUrl: process.env.EXPO_PUBLIC_API_BASE_URL ?? 'http://localhost:8000',
    sentryDsn: process.env.EXPO_PUBLIC_SENTRY_DSN ?? '',
  },
};

export default config;
