# infludata

Social-stats analytics platform. Laravel 13 backend + React Native (Expo)
client that fetch, store, and visualize statistics from YouTube, Instagram,
TikTok, Twitter/X, and Facebook Pages. The mobile codebase targets iOS,
Android, and web via `react-native-web`.

Two account models coexist:

1. **Own-account analytics** — a user OAuths their own social accounts
   (via Phyllo Connect) and we expose deep insights including demographics.
2. **Public-creator tracking** — a user adds arbitrary handles and we track
   public stats over time.

Primary data source is the [Phyllo](https://www.getphyllo.com) unified API.
Direct platform APIs (YouTube Data v3, Meta Graph, X v2) supplement where
Phyllo can't reach or where direct calls are cheaper.

## Stack

| Layer    | Choice                                                    |
|----------|-----------------------------------------------------------|
| Backend  | Laravel 13, PHP 8.3, Sanctum, Horizon                     |
| Storage  | PostgreSQL 16 + TimescaleDB (see `docs/adr/0001-...`)     |
| Queue    | Redis (Horizon dashboard at `/horizon`)                   |
| Mobile   | React Native + Expo SDK 52 (iOS + Android + Web), Expo Router, TanStack Query, Zustand, react-hook-form + zod, Recharts/victory-native |
| Observ.  | Sentry (backend + RN), structured JSON logs               |
| Dev      | docker-compose, Makefile                                  |

## Quick start

```sh
git clone <repo> infludata
cd infludata
make up         # starts the dev stack and runs migrations
```

On success:

- API:       http://localhost:8000/api/health
- Horizon:   http://localhost:8000/horizon
- Mailpit:   http://localhost:8025
- MinIO:     http://localhost:9001  (user: `minio`, pass: `miniominio`)

### Mobile

```sh
cd mobile
cp .env.example .env       # set EXPO_PUBLIC_API_BASE_URL
npm install
npx expo start             # press i (iOS), a (Android), w (web)
```

Defaults to `http://localhost:8000`. Override via `EXPO_PUBLIC_API_BASE_URL`.
Native modules (Phyllo Connect) require an Expo dev client: `npx expo prebuild`
then `npx expo run:ios` / `run:android`.

## Layout

```
backend/        Laravel 13 API
  app/
    Http/Controllers/Api/   versioned controllers (Auth, Connections, Creators, ...)
    Jobs/                   queued ingestion + alert jobs
    Services/Phyllo/        Phyllo HTTP + SDK token + webhook verifier
    Services/Platforms/     direct platform clients (YouTube, IG, X, FB, TikTok)
  database/migrations/      core schema + TimescaleDB hypertables
mobile/         Expo + React Native app (iOS + Android + Web)
  app/                      Expo Router file-based routes (auth + app groups)
  src/api/                  axios client + endpoint modules + types
  src/auth/                 Zustand authStore + bootstrap hook
  src/features/             auth, connections, creators, dashboard, alerts, settings
  src/ui/                   Screen, Button, TextField, Avatar, …
  __tests__/                Jest + @testing-library/react-native
docker/         Dockerfiles + nginx config used by docker-compose
docs/adr/       Architecture Decision Records
.github/workflows/ci.yml    backend + mobile CI
```

## Roadmap

See `/root/.claude/plans/create-a-plan-to-validated-avalanche.md` for the
full plan. Milestone status:

- [x] **M0** Scaffolding + DX
- [x] **M1** Auth + Phyllo own-account for YouTube & Instagram
- [x] **M2** Public-creator lookup + ingestion (YT, IG)
- [x] **M3** TikTok, X, Facebook Pages
- [x] **M4** Analytics UI (dashboard, content, charts)
- [x] **M5** Alerts, exports, admin, GDPR — code-complete
- [x] **RN-port** Mobile rewritten from Flutter → React Native + Expo (iOS, Android, web)

> **Operational follow-ups for beta launch** (not code work):
>
> - Provision real Phyllo sandbox + production credentials
> - Submit Meta App Review for Instagram permissions (2–4 wk)
> - Wire FCM/APNs and notification delivery
> - Run k6 load tests (`scripts/k6/`) against staging
> - TestFlight + Play internal track upload

## Useful commands

```sh
make test          # backend Pest tests
make stan          # phpstan (larastan level 7)
make lint          # pint --test
make format        # pint
make artisan c="tinker"
make psql
make mobile-test
make ci            # everything
```
