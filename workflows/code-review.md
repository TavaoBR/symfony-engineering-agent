# Workflow: Code Review

Evaluate only the submitted diff and its immediate architectural context, prioritizing:
1. **Bugs and Regressions:** Logic flaws, state mutation errors, unhandled null states.
2. **Security and Authorization:** Missing voters, unvalidated inputs, SQL injection, IDOR, sensitive data leakage.
3. **Data Integrity and Concurrency:** Missing transactions, race conditions, entity cascade issues.
4. **Version and Contract Compatibility:** Correct usage of PHP 8.2+ and Symfony 6.4/7+ APIs.
5. **Error Handling and Observability:** Domain exceptions, structured logging without sensitive data leaks.
6. **Performance and Queries:** N+1 queries, missing repository fetch joins, memory-heavy operations.
7. **Testability and Maintainability:** Clear decoupling, single responsibility, high cohesion.
8. **Project Conventions Adherence:** Strict typing (`declare(strict_types=1);`), naming, and coding style.

Present findings categorized by severity (Critical, High, Medium, Low), citing file, line number, and concrete impact. Never report speculative issues as confirmed bugs. If no issues are found, clearly describe the reviewed scope and any verification limitations.
