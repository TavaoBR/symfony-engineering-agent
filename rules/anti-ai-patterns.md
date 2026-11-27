# Anti-AI Generation Patterns

Strictly avoid:

1. **Overengineering:** Do not introduce layers, abstractions, or design patterns without a concrete, demonstrable need.
2. **Excessive Boilerplate:** Do not create empty classes, interfaces, or directories "for the future".
3. **Opportunistic Refactoring:** Do not reformat, rewrite, or touch unrelated files outside the scope of the request.
4. **Speculative Code:** Do not implement unrequested features, imaginary endpoints, or presumed business logic.
5. **Duplication:** Always inspect existing services, helpers, and utilities before creating new ones.
6. **Premature Generalization:** Do not build internal mini-frameworks or dynamic engines for a single straightforward use case.
7. **Silent Exception Swallowing:** Never suppress errors with empty `catch` blocks or artificial success returns.
8. **Useless Comments:** Do not write comments stating the obvious or repeating method names.
9. **Scenic / Mock-Only Tests:** Test real rules, state mutations, and outcomes; do not merely verify that mocks were invoked.
10. **Convenience Dependencies:** Do not add third-party Composer packages for simple tasks already supported by core PHP/Symfony.
11. **Split Configuration:** Do not create competing sources of truth for the same configuration setting.
12. **Vague Naming:** Avoid generic names such as `Manager`, `Helper`, `Processor`, `Utils`, or `Data` without distinct domain meaning.
13. **Dead Code:** Never leave unused imports, variables, methods, flags, or placeholder files.
14. **Suppression Annotations:** Do not use `@`, `@phpstan-ignore`, `mixed`, or `TODO` to mask defects without documented technical justification.
15. **False Completion Claims:** Never claim that code compiled, tested, integrated, or passed validation without having actually run the corresponding verification command.

Always prefer the simplest complete solution that fully satisfies real requirements and follows established project standards.
