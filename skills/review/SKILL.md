---
name: review
description: Invoke the Code Reviewer to audit git diff against AI anti-patterns, missing strict types, and security regressions.
---

# /review — Code Reviewer Command

Act as the **Code Reviewer** (`agents/code-reviewer.md`) using `skills/code-review`.

## Objective
Perform an impartial, rigorous code review of the current `git diff`:
1. Check for bugs, state mutations, and unhandled null states.
2. Detect AI anti-patterns (`@SuppressWarnings`, empty generic methods, redundant comments).
3. Verify strict typing (`declare(strict_types=1);`), constructor DI, and exception handling.
4. Verify IDOR protection (Voters) and absence of N+1 queries.
5. Output findings categorized by severity (Critical, High, Medium, Low) citing file and line.

$ARGUMENTS
