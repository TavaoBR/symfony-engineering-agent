# Workflow: Implement Feature

1. Translate user requirements into observable behavior and concrete acceptance criteria.
2. Identify provided business rules and separate confirmed logic from unverified assumptions.
3. Inspect existing similar implementations and established architectural conventions.
4. Map affected files, endpoints, persistence schemas, security permissions, and side effects.
5. Implement the minimal complete solution, adhering strictly to existing contracts and strict typing.
6. Create or update unit and functional tests covering happy paths, validation failures, and unauthorized access.
7. Update documentation and generate migrations when database schema changes are required.
8. Run local validations: `lint:container`, PHPStan, and test suites.
9. Review `git diff` to ensure no out-of-scope changes or debug leftovers were introduced.
10. Report implementation results, executed validations, and any technical limitations.

Never create unspecified database fields, entity states, business calculations, or permissions without explicit requirements.
