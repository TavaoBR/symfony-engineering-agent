# Workflow: Safe Code Refactoring

1. Confirm refactoring objectives and identify behavior that must remain preserved.
2. Inspect consumers, dependent modules, existing test coverage, and public contracts.
3. Establish a baseline test suite before refactoring to detect regressions immediately.
4. Refactor incrementally in small, focused steps without introducing new features or unrequested behavior.
5. Preserve public API contracts unless an explicit contract update is requested.
6. Avoid coupling architectural changes with business rule modifications simultaneously.
7. Run the test suite after each step and review the generated diff.
8. Report concrete architectural gains, potential risks, and validation results.

Never refactor purely out of aesthetic preference or personal disagreement with working conventions.
