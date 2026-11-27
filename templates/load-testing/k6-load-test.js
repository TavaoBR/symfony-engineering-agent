import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom Performance Metrics
export const errorRate = new Rate('api_error_rate');
export const listProjectsDuration = new Trend('list_projects_duration_ms');
export const createProjectDuration = new Trend('create_project_duration_ms');

// Configurable Target Host
const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';

// Performance SLA Thresholds
export const options = {
  scenarios: {
    // 1. Steady Baseline Load Scenario
    baseline_load: {
      executor: 'ramping-vus',
      startVUs: 1,
      stages: [
        { duration: '10s', target: 10 }, // Ramp-up
        { duration: '30s', target: 25 }, // Steady peak traffic
        { duration: '10s', target: 0 },  // Ramp-down
      ],
      gracefulRampDown: '5s',
    },
    // 2. High-Concurrency Stress Spike Scenario
    stress_spike: {
      executor: 'ramping-arrival-rate',
      startRate: 5,
      timeUnit: '1s',
      preAllocatedVUs: 20,
      maxVUs: 100,
      stages: [
        { duration: '10s', target: 20 }, // 20 req/s
        { duration: '15s', target: 60 }, // Spike to 60 req/s
        { duration: '10s', target: 5 },  // Recovery
      ],
      startTime: '55s', // Runs after baseline scenario
    },
  },
  thresholds: {
    // SLA 1: 95% of all requests must complete within 200ms
    http_req_duration: ['p(95)<200', 'p(99)<400'],
    // SLA 2: Maximum acceptable error rate is below 1%
    http_req_failed: ['rate<0.01'],
    api_error_rate: ['rate<0.01'],
    // SLA 3: Custom endpoint duration budgets
    list_projects_duration_ms: ['p(95)<150'],
    create_project_duration_ms: ['p(95)<250'],
  },
};

const JSON_HEADERS = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
};

export default function () {
  // Test Case 1: Paginated Listing
  {
    const res = http.get(`${BASE_URL}/api/v1/projects?page=1&limit=20`, { headers: JSON_HEADERS });
    const success = check(res, {
      'List status is 200': (r) => r.status === 200,
      'List returns JSON content': (r) => r.headers['Content-Type'] && r.headers['Content-Type'].includes('application/json'),
      'List response has data array': (r) => {
        try {
          const body = JSON.parse(r.body);
          return Array.isArray(body.data);
        } catch {
          return false;
        }
      },
    });

    errorRate.add(!success);
    listProjectsDuration.add(res.timings.duration);
  }

  sleep(0.2);

  // Test Case 2: Entity Detail Retrieval
  {
    const res = http.get(`${BASE_URL}/api/v1/projects/1`, { headers: JSON_HEADERS });
    const success = check(res, {
      'Detail status is 200 or 404': (r) => r.status === 200 || r.status === 404,
    });
    errorRate.add(!success);
  }

  sleep(0.3);

  // Test Case 3: Resource Creation Mutation
  {
    const payload = JSON.stringify({
      name: `Load Test Project ${Date.now()}-${__VU}`,
      status: 'active',
      budget: 15000.50,
    });

    const res = http.post(`${BASE_URL}/api/v1/projects`, payload, { headers: JSON_HEADERS });
    const success = check(res, {
      'Create status is 201 or 422': (r) => r.status === 201 || r.status === 422,
    });

    errorRate.add(!success);
    createProjectDuration.add(res.timings.duration);
  }

  sleep(0.5);
}
