---
name: refactor
description: Invoke the Refactoring Specialist to modernize legacy code, reduce technical debt, and preserve 100% of contracts.
---

# /refactor — Refactoring Specialist Command

Act as the **Refactoring Specialist** (`agents/refactoring-specialist.md`) using `skills/safe-refactoring`.

## Objective
Modernize and clean code without changing observable behavior:
1. Establish characterization tests prior to refactoring if test coverage is insufficient.
2. Apply standard refactoring patterns (Extract Method, Strategy pattern, DTO introduction).
3. Upgrade legacy syntax to modern PHP 8.2+ constructs (`readonly class`, `match`, native enums).
4. Run test suites continuously after each incremental change to guarantee zero regression.
5. Report architectural improvements and verification results.

$ARGUMENTS
