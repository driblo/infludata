# ADR 0001 — PostgreSQL 16 with TimescaleDB for primary storage

Date: 2026-05-19
Status: Accepted

## Context

`infludata` ingests and serves timeseries metrics from five social networks.
The bulk of stored rows live in two tables — `metric_snapshots` (per
creator/day) and `content_metrics` (per content/hour). A v1 reasonable upper
bound at 10k tracked creators × 365 days × 24 hourly content rows per creator
puts us well into hundreds of millions of rows in year one.

We need:

- Efficient append + range scan on `(creator_id, captured_at DESC)`.
- Automatic partitioning so a single chunk stays small.
- Cheap rollups (daily / weekly) for chart endpoints.
- Reasonable JSONB indexing for raw provider payloads.
- A retention story (purge raw beyond 18 months).

## Decision

Use **PostgreSQL 16** as the only OLTP store. Enable the **TimescaleDB**
extension for `metric_snapshots` and `content_metrics`. Everything else uses
normal Postgres tables.

- `metric_snapshots`: hypertable, chunk interval 7 days, compression after 7
  days, retention 540 days.
- `content_metrics`: hypertable, chunk interval 14 days, compression after 14
  days, retention 540 days.
- Continuous aggregates `metric_daily` and `content_daily` refresh every 30
  minutes and back the dashboard chart endpoints.
- All other tables (`users`, `oauth_accounts`, `creator_profiles`,
  `content_items`, …) use plain Postgres tables — no hypertable overhead for
  low-volume data.

## Alternatives considered

- **MySQL with native partitioning** — works, but partition management is
  manual, continuous aggregates have no equivalent, and JSONB indexing is
  weaker. Rejected.
- **ClickHouse for timeseries + Postgres for OLTP** — best raw analytics
  performance, but two stores doubles the operational surface for a v1 we
  haven't validated. Defer to a post-v1 ADR if scale demands it.
- **Pure Postgres with declarative partitioning** — feasible, but
  TimescaleDB's chunk management, compression, and retention policies are
  already-built versions of what we'd write. The extension is permissively
  licensed for our needs.

## Consequences

- Local dev runs the official `timescale/timescaledb:latest-pg16` image (see
  `docker-compose.yml`).
- Production must use a Postgres host that supports the TimescaleDB extension
  (Timescale Cloud, self-hosted, or AWS RDS via the open-source build).
- Migrations enable the extension and create hypertables in
  `2026_05_19_000002_create_timescale_hypertables.php`. The migration falls
  back to plain tables when the driver is not pgsql, so unit tests can run on
  sqlite if we ever need that.
