/// <reference types="expo-router/types" />

declare namespace NodeJS {
  interface ProcessEnv {
    readonly EXPO_PUBLIC_API_BASE_URL: string;
    readonly EXPO_PUBLIC_SENTRY_DSN?: string;
  }
}
