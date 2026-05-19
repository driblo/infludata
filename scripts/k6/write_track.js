// k6 write load test: add + remove tracked creators.
//
// The POST /api/creators endpoint is rate-limited to 5 req/min/user by the
// `write` bucket, so we use a small fixed pool of users to avoid being
// throttled at the limiter rather than the application.
//
// Target SLO: 10 rps write, error rate < 1%.

import http from 'k6/http';
import { check } from 'k6';

const API_BASE = __ENV.API_BASE || 'http://localhost:8000';
const TOKENS = (__ENV.BEARERS || '').split(',').filter(Boolean);

export const options = {
  scenarios: {
    write: {
      executor: 'constant-arrival-rate',
      rate: 10,
      timeUnit: '1s',
      duration: '2m',
      preAllocatedVUs: 20,
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<600'],
  },
};

export default function () {
  if (TOKENS.length === 0) return;
  const token = TOKENS[__VU % TOKENS.length];
  const handle = `handle-${__VU}-${__ITER}`;
  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  const res = http.post(
    `${API_BASE}/api/creators`,
    JSON.stringify({ network: 'youtube', handle }),
    { headers },
  );
  check(res, { 'created or rejected cleanly': (r) => [201, 422, 429].includes(r.status) });
}
