---
name: testing-quality
description: Write unit tests, WebTestCase functional HTTP suites, PHPUnit execution, and PHPStan level 8+ static analysis.
---

# Skill: Automated Testing and Code Quality (Testing & Quality)

## 1. Objective and Responsibility
Design, implement, and execute automated test suites (unit, integration, functional/HTTP) in Symfony applications, integrating strict static analysis (PHPStan) and code style linters (PHP-CS-Fixer) to guarantee zero regressions and continuous technical compliance.

## 2. Scope and Limitations
- **Scope:** Creating and maintaining tests using PHPUnit (extending `TestCase`, `KernelTestCase`, `WebTestCase`), setting up test fixtures and factories, mocking boundary dependencies, executing static analysis, and enforcing styling rules.
- **Limitations:** Never points tests to production or shared databases; requires an isolated test database (`.env.test`).

## 3. Required Technical Knowledge
- **PHPUnit 10.x / 11.x:** Assertions, Data Providers (`#[DataProvider]`), test lifecycle (`setUp`, `tearDown`), attributes.
- **Symfony Test Harness:** `KernelTestCase` for container integration tests; `WebTestCase` with `createClient()` for simulated HTTP requests.
- **Test Database Management:** Creating schemas via `doctrine:database:create --env=test`, migrations, and factories using `zenstruck/foundry` or DAMADoctrineTestBundle.
- **Static Analysis and Linters:** Configuring and interpreting PHPStan (levels 6 to 9) and PHP-CS-Fixer / PER-CS 2.0.

## 4. Development Standards and Conventions
- Test suites must reflect codebase structure:
  - Unit tests: `tests/Unit/`
  - Integration tests: `tests/Integration/`
  - Functional and API tests: `tests/Functional/` or `tests/Controller/`
- Test method names must be descriptive and expressive:
  `public function testShouldReturnCreatedWhenPayloadIsValid(): void`.
- Every feature or bug fix MUST be accompanied by at least one automated test reproducing the scenario.

## 5. Architectural Rules and Code Organization
- Unit tests verify domain logic in isolation without booting the Symfony kernel (execution time under 10ms).
- Functional HTTP tests (`WebTestCase`) test applications end-to-end: routing, security, validation, persistence, and JSON responses.
- Isolate external network calls using Symfony's `MockHttpClient`.

## 6. PHP and Symfony Best Practices
- **AAA Pattern (Arrange, Act, Assert):** Explicitly structure the three phases of every test.
- **Avoid Scenic Tests:** Do not write tests that only verify that a mock was invoked once without verifying actual state mutations or returned values.
- **Transactional Test Database:** Wrap each functional test in a database transaction rolled back at `tearDown()` using DAMADoctrineTestBundle for fast, isolated test runs.

## 7. Examples of Correct and Incorrect Implementations

### Example: API Functional Test with WebTestCase
**Incorrect (Scenic test with weak assertions):**
```php
// Incorrect: Only checks status 200, without verifying payload, headers, or database side effects
public function testGetUsers(): void
{
    $client = static::createClient();
    $client->request('GET', '/api/users');
    
    // Fragile: returns true even if an empty array [] was returned!
    $this->assertEquals(200, $client->getResponse()->getStatusCode());
}
```

**Correct (Robust functional test validating JSON contracts, headers, and database persistence):**
```php
namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CreateUserControllerTest extends WebTestCase
{
    public function testShouldCreateUserAndPersistInDatabase(): void
    {
        // 1. Arrange
        $client = static::createClient();
        $payload = [
            'email' => 'new.user@company.com',
            'password' => 'StrongPassword123!',
        ];

        // 2. Act
        $client->request(
            'POST',
            '/api/users',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        // 3. Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertSame('new.user@company.com', $responseData['email']);

        // Verify real database side effect via repository
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $persistedUser = $userRepository->findOneBy(['email' => 'new.user@company.com']);

        $this->assertNotNull($persistedUser);
        $this->assertNotSame('StrongPassword123!', $persistedUser->getPassword(), 'Passwords must be securely hashed!');
    }

    public function testShouldReturnUnprocessableEntityWhenEmailIsInvalid(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/users',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'invalid-email', 'password' => '123'])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains([
            'status' => 422,
            'title' => 'Validation Failed',
        ]);
    }
}
```

## 8. Technical Decision Criteria
- **Unit vs Integration vs Functional:**
  - Unit: For classes with mathematical calculations, string parsing, entity domain invariants, and Value Objects.
  - Integration: For services relying on real Doctrine queries or container service wirings.
  - Functional (`WebTestCase`): For all public or authenticated HTTP API endpoints.

## 9. Validation and Testing Procedures
- Run targeted tests:
  ```bash
  php bin/phpunit tests/Controller/CreateUserControllerTest.php
  ```
- Run full suite:
  ```bash
  php bin/phpunit --testdox
  ```
- Run mutation testing (Infection PHP):
  ```bash
  php templates/testing/mutation-runner.php --diff-only --min-msi=70.0
  ```
- Run static analysis:
  ```bash
  ./vendor/bin/phpstan analyse -l 8 src tests
  ```
- Run code linter:
  ```bash
  ./vendor/bin/php-cs-fixer fix --dry-run --diff
  ```

## 10. Error Handling and Exceptions
- Test database connection failures must report clear environment errors (`DATABASE_URL` in `.env.test.local`), without modifying production configs.

## 11. Objective Quality and Completion Criteria
- Automated test suites passing 100% (zero failures, zero errors).
- Mutation Score Indicator (MSI) satisfies configured thresholds (>= 70.0%).
- PHPStan level 8 passing without errors or unjustified suppressions.
- Zero coding style violations reported by PHP-CS-Fixer.
