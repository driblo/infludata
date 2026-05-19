# k6 load tests

Two scenarios:

| Script             | Purpose                          | Target SLO                    |
|--------------------|----------------------------------|-------------------------------|
| `read_creator.js`  | Read-heavy traffic on charts     | p95 < 400ms at 100 rps        |
| `write_track.js`   | Tracked-creator add throughput   | 10 rps writes, errors < 1%    |

## Quick start

```sh
# 1) Get a sanctum token from staging.
curl -X POST $API_BASE/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"loadtest@infludata.local","password":"..."}'

# 2) Read test:
API_BASE=https://staging.api.infludata BEARER=<token> CREATOR_ID=42 k6 run scripts/k6/read_creator.js

# 3) Write test (provide multiple tokens to avoid the 5/min write limiter):
API_BASE=https://staging.api.infludata BEARERS=t1,t2,t3,t4,t5,t6 k6 run scripts/k6/write_track.js
```

Output goes to stdout; pipe to `k6 run --out json=run.json` for Grafana ingestion.
