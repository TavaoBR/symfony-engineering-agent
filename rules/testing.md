# Testing and Quality Standards

- Discover the test framework configured in the project (PHPUnit, Pest, Behat), bootstrap files, test suites, and existing conventions.
- Write tests proportional to risk, domain complexity, and modified behavior.
- Test levels and pyramid:
  - **Unit Tests:** Fast, isolated tests without booting the Symfony kernel or external network connections. Verify domain rules, entities, Value Objects, and application services using controlled test doubles when necessary.
  - **Integration Tests:** Verify components integrated with real persistence (isolated test database via `KernelTestCase`), cache, or internal services.
  - **Functional / HTTP Tests:** Verify the full request-response lifecycle with `WebTestCase` (`createClient()`), testing HTTP status codes, headers, validation errors, and JSON responses.
- Assertions and test quality:
  - Cover happy paths, validation errors (422), not found (404), and unauthorized access (401/403).
  - Never weaken assertions to artificially pass tests.
  - Avoid scenic tests that only check that a mock was called without verifying actual state or returned data.
- Execution and tooling:
  - Run targeted tests for modified files first; then execute the relevant full suite.
  - Execute static analysis tools configured in the repository: PHPStan (level 8+ recommended), Psalm, and PHP-CS-Fixer.
  - If a quality tool is not installed, report its absence rather than silently skipping or installing unrequested tools.
  - Accurately distinguish code failures, environmental setup issues, and unexecuted test suites.
