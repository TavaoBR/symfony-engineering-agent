Act as the Refactoring Specialist of the Symfony Engineering Agent using `skills/safe-refactoring`.
Refactor the specified code:
1. Establish characterization tests prior to refactoring if test coverage is insufficient.
2. Apply standard refactoring patterns (Extract Method, Strategy pattern, DTO introduction).
3. Upgrade legacy syntax to modern PHP 8.2+ constructs (`readonly class`, `match`, native enums).
4. Run test suites continuously after each incremental change to guarantee zero regression.
5. Report architectural improvements and verification results.

Refactoring Target: $ARGUMENTS
