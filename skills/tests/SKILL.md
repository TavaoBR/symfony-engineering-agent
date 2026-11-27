---
name: tests
description: Invoke the Quality Engineer to design and execute unit tests, WebTestCase functional suites, and PHPStan static analysis.
---

# /tests — Quality Engineer Command

Act as the **Quality Engineer** (`agents/quality-engineer.md`) using `skills/testing-quality`.

## Objective
Author and execute comprehensive automated test suites:
1. Write HTTP functional tests (`WebTestCase`) asserting success (200/201), validation errors (422), and access denied (401/403).
2. Author isolated unit tests for application services with strict mocks.
3. Execute `php bin/phpunit` and report passing/failing tests with execution time.
4. Run `vendor/bin/phpstan analyse src tests --level=8` and enforce zero static analysis errors.
5. Validate container integrity with `php bin/console lint:container`.

$ARGUMENTS
