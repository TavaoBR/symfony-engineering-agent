---
name: debugging
description: Diagnose exceptions, stack traces, create automated regression tests, and apply atomic bug fixes.
---

# Skill: Defect Investigation and Debugging (Debugging)

## 1. Objective and Responsibility
Diagnose and solve software defects, unhandled exceptions, regressions, and concurrency bugs in Symfony applications with technical precision, isolating root causes with objective evidence and ensuring fixes are accompanied by automated regression tests to prevent reoccurrence.

## 2. Scope and Limitations
- **Scope:** Stack trace inspection, Monolog log analysis (`var/log/`), deterministic reproduction in test environments, controlled instrumentation, and applying minimal, surgical code fixes.
- **Limitations:** Never masks exceptions with empty `catch` blocks or artificial success returns. Does not modify code outside the defect area without authorization.

## 3. Required Technical Knowledge
- Exception handling mechanisms in PHP 8.2+ and Symfony HttpKernel (`ErrorHandler`, `FlattenException`).
- Monolog logging configuration and log parsing (`dev.log`, `prod.log`).
- Doctrine query debugging via `SqlLogger` and Symfony Web Profiler.
- Code bisection and commit defect hunting with Git (`git bisect`).
- Writing automated regression tests with PHPUnit.

## 4. Development Standards and Conventions
- The mandatory debugging lifecycle:
  1. **Reproduce:** Build an automated test or deterministic script reproducing the failure.
  2. **Isolate:** Identify the root cause (not merely the surface symptom).
  3. **Fix:** Apply the minimal complete, safe code change.
  4. **Verify:** Execute the regression test (which must now pass) and the full test suite.

## 5. Architectural Rules and Code Organization
- Bug fixes must not alter public contracts, existing method signatures, or database schemas without explicit justification.
- Regression tests must be added to the official test suite (`tests/Functional/` or `tests/Unit/`).

## 6. PHP and Symfony Best Practices
- **Never Swallow Exceptions:** Never use `try { ... } catch (\Throwable $e) {}` merely to make code "stop crashing". This masks critical defects and corrupts persistent state.
- **Doctrine Lazy-Loading Issues:** When an entity causes `EntityNotFoundException` or unexpected null properties, check whether Doctrine proxies were initialized or whether the entity was detached from the EntityManager.

## 7. Examples of Correct and Incorrect Implementations

### Example: Fixing an Inventory Concurrency Bug
**Incorrect (Swallowing exception and masking symptoms):**
```php
// Incorrect: Swallows exception and allows negative stock balances under concurrency!
public function decrementStock(Product $product, int $quantity): void
{
    try {
        $product->setStock($product->getStock() - $quantity);
        $this->em->flush();
    } catch (\Exception $e) {
        // Anti-pattern: swallows error and pretends it succeeded!
        return;
    }
}
```

**Correct (Pessimistic locking and automated regression test):**
```php
// 1. Root cause fix using Doctrine pessimistic locking
public function decrementStock(int $productId, int $quantity): void
{
    $this->em->wrapInTransaction(function () use ($productId, $quantity) {
        // Locks database row with SELECT ... FOR UPDATE to prevent race conditions
        $product = $this->em->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);

        if (!$product) {
            throw new ProductNotFoundException('Product not found.');
        }

        if ($product->getStock() < $quantity) {
            throw new InsufficientStockException(sprintf(
                'Insufficient stock for product %s. Available: %d, Requested: %d',
                $product->getName(),
                $product->getStock(),
                $quantity
            ));
        }

        $product->decrementStock($quantity);
    });
}

// 2. Mandatory Regression Test
namespace App\Tests\Integration;

use App\Entity\Product;
use App\Service\StockService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StockServiceTest extends KernelTestCase
{
    public function testShouldThrowExceptionWhenStockIsInsufficient(): void
    {
        self::bootKernel();
        $stockService = static::getContainer()->get(StockService::class);

        $product = $this->createProductWithStock(5);

        $this->expectException(InsufficientStockException::class);
        $stockService->decrementStock($product->getId(), 10);
    }
}
```

## 8. Technical Decision Criteria
- **When to log vs throw exceptions:**
  - Throw exceptions for domain invariant violations and errors preventing workflow completion.
  - Log recoverable operational anomalies or degradation warnings.

## 9. Validation and Testing Procedures
- Follow real-time local logs:
  ```bash
  tail -f var/log/dev.log
  ```
- Run targeted regression tests:
  ```bash
  php bin/phpunit tests/Integration/StockServiceTest.php
  ```

## 10. Error Handling and Exceptions
- When re-throwing exceptions, chain the original cause using the `$previous` argument of `Exception`:
  ```php
  throw new PaymentProcessingException('Payment processing failed.', 0, $previousException);
  ```

## 11. Objective Quality and Completion Criteria
- Root cause identified and documented with log or profiler evidence.
- Automated regression test written and passing green.
- Zero regressions across the full test suite.
