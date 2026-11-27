# Workflow: Bug Fix Delivery with Specialists

1. **Defect Scoping (Orchestrator):**
   - Defines observed incorrect behavior, reproduction steps, and expected outcome.
2. **Investigation & Root Cause Analysis (Debugger):**
   - Collects logs, stack traces, and environment conditions to locate the verified root cause.
3. **Targeted Implementation (Backend Developer or Database Engineer):**
   - Implements minimal, complete fix in the affected architectural layer without side effects.
4. **Regression Testing & Verification (Quality Engineer):**
   - Writes automated test that reproduces the bug, verifies it fails prior to the fix and passes after, and runs the test suite.
5. **Impact and Review (Code Reviewer):**
   - Inspects the diff for regressions, error suppression, and performance impacts.
6. **Delivery Report (Orchestrator):**
   - Summarizes confirmed root cause, code changes, and test execution evidence.

If the root cause cannot be confirmed, never present an unverified hypothesis as a verified fact.
