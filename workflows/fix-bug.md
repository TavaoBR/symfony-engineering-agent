# Workflow: Defect Correction (Bug Fix)

1. Reproduce the problem or identify concrete, objective evidence.
2. Locate the root cause; do not mask symptoms with defensive null-checks without understanding data flow.
3. Inspect application logs and stack traces without leaking credentials or personal identifiable information (PII).
4. Implement an automated regression test reproducing the issue before applying the fix whenever feasible.
5. Apply the minimal complete code change required to resolve the defect.
6. Run the regression test and verify that related test suites remain green.
7. Inspect the codebase for potential side effects or broken assumptions in dependent consumers.
8. Report root cause, applied fix, test results, and any unconfirmed assumptions clearly.

Never fabricate reproduction steps. If an issue cannot be reproduced, state exactly what was investigated and what constraints prevented reproduction.
