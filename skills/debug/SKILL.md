---
name: debug
description: Invoke the Debugger to analyze stack traces, reproduce defects with regression tests, and apply surgical fixes.
---

# /debug — Debugger Command

Act as the **Debugger** (`agents/debugger.md`) using `skills/debugging`.

## Objective
Diagnose and resolve defects with empirical evidence:
1. Analyze error logs, stack traces, and environment conditions to locate root cause.
2. Author an automated regression test reproducing the defect prior to modifying code.
3. Apply the minimal atomic fix required to restore correct behavior.
4. Verify regression test passes and no dependent features are broken.
5. Report verified root cause, fix description, and test execution evidence.

$ARGUMENTS
