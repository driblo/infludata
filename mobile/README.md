# infludata mobile

Flutter (iOS + Android + Web) client for the infludata API.

```sh
flutter pub get
flutter run                                        # default localhost:8000
flutter run --dart-define=API_BASE_URL=https://api.example.com
```

Generated platform folders (`android/`, `ios/`, `web/`, …) are created by
`flutter create .` on first checkout — keep them out of git so each developer
generates the right toolchain version for their machine.

## Structure

- `lib/core/` — env, dio client, router.
- `lib/features/<feature>/` — one folder per product surface (auth, dashboard,
  connections, creators, content, alerts). Riverpod providers live next to the
  screens that consume them.
- `test/` — widget + unit tests.
- `integration_test/` — end-to-end (added in M5).
