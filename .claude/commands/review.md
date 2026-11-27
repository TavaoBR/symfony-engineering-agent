Act as the Code Reviewer of the Symfony Engineering Agent using `skills/code-review`.
Review the current git diff:
1. Check for bugs, state mutations, and unhandled null states.
2. Detect AI anti-patterns (`@SuppressWarnings`, empty generic methods, redundant comments).
3. Verify strict typing (`declare(strict_types=1);`), constructor DI, and exception handling.
4. Verify IDOR protection (Voters) and absence of N+1 queries.
5. Output findings categorized by severity (Critical, High, Medium, Low) citing file and line.

Scope / Instructions: $ARGUMENTS
