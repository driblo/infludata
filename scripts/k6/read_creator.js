// k6 read load test for infludata.
//
// Hits the creator-detail + metrics endpoints from a logged-in user. Run
// against staging, not prod. Target SLO: p95 < 400ms at 100 rps.
//
// Run:
//   API_BASE=https://staging.api.infludata BEARER=... CREATOR_ID=1 k6 run scripts/k6/read_creator.js

import http from 'k6/http';
import { check, sleep } from 'k6';

const API_BASE = __ENV.API_BASE || 'http://localhost:8000';
const BEARER = __ENV.BEARER || '';
const CREATOR_ID = __ENV.CREATOR_ID || '1';

export const options = {
  stages: [
    { duration: '30s', target: 25 },
    { duration: '1m', target: 100 },
    { duration: '2m', target: 100 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<400'],
    http_req_failed: ['rate<0.01'],
  },
};

const headers = {
  Authorization: `Bearer ${BEARER}`,
  Accept: 'application/json',
};

export default function () {
  const profile = http.get(`${API_BASE}/api/creators/${CREATOR_ID}/profile`, { headers });
  check(profile, { 'profile 200': (r) => r.status === 200 });

  const metrics = http.get(`${API_BASE}/api/creators/${CREATOR_ID}/metrics?range=30d`, { headers });
  check(metrics, { 'metrics 200': (r) => r.status === 200 });

  sleep(0.2);
}
