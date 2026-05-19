# infludata — Mobile (React Native + Expo)

iOS, Android, and Web client for the infludata API. Single codebase via
`react-native-web`.

## Stack

- **Expo SDK 52** (Managed) + **Expo Router** (file-based routing)
- **TypeScript** strict
- **TanStack Query v5** + **axios** for server state
- **Zustand** for the small auth store
- **react-hook-form** + **zod**
- **react-native-web** for the web target
- Charts: `victory-native` (XL/Skia) on native, `recharts` on web
- Auth storage: `expo-secure-store` (native) / `localStorage` shim (web)
- Errors: `@sentry/react-native`

## Quick start

```sh
cd mobile
cp .env.example .env       # then set EXPO_PUBLIC_API_BASE_URL
npm install
npx expo start             # press i / a / w
```

For Phyllo Connect (and any other native module), build a dev client:

```sh
npx expo prebuild
npx expo run:ios
npx expo run:android
```

Web build smoke:

```sh
npx expo export -p web
npx serve dist
```

## Scripts

```sh
npm run typecheck   # tsc --noEmit
npm run lint        # eslint .
npm run test        # jest
npm run export:web  # static web build
```

## Layout

```
app/
  _layout.tsx              QueryClient + Sentry + auth bootstrap
  index.tsx                redirect on boot
  (auth)/{login,register}.tsx
  (app)/{dashboard,accounts,alerts,settings}.tsx
  (app)/creators/{index,[id]/index,[id]/content}.tsx
  (app)/audience/[oauthAccountId].tsx
src/
  api/{client,errors,queryKeys,types}.ts + endpoints/*
  auth/{authStore,useAuthBootstrap}.ts
  storage/secureStore.{native,web}.ts
  features/<feature>/...
  ui/{Screen,Button,TextField,Avatar,EmptyState,ErrorState,LoadingState}.tsx
  lib/{sentry,format}.ts
__tests__/...
```

## Notes

- Native module `react-native-phyllo-connect` only works in an Expo dev
  client (not Expo Go); see the prebuild command above.
- Web Phyllo SDK is loaded dynamically; install it explicitly when you
  build the production web bundle: `npm i @phyllo/connect-web`.
- The auth interceptor performs a single-flight token refresh on 401
  before clearing the session.
