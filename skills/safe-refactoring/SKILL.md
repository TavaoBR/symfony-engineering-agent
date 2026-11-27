---
name: safe-refactoring
description: Refactor legacy code safely with characterization tests, Strategy pattern, and modern PHP 8.2+ constructs.
---

# Skill: Safe Refactoring and Modernization (Safe Refactoring)

## 1. Objective and Responsibility
Refactor, decouple, modularize, and modernize PHP and Symfony codebases (migrating to PHP 8.2+ and modern Symfony versions), eliminating technical debt and accidental complexity without introducing observable behavioral regressions.

## 2. Scope and Limitations
- **Scope:** Migrating annotations to native attributes (`#[Route]`, `#[ORM]`), converting associative arrays to immutable DTOs, constructor dependency injection with property promotion, code deduplication, extracting methods/classes, and automating transformations with Rector.
- **Limitations:** Does not introduce new business features or alter domain rules during refactoring. Never breaks public API contracts.

## 3. Required Technical Knowledge
- **Modern PHP (8.0 -> 8.4):** Strict typing (`declare(strict_types=1);`), constructor promotion, `readonly class`, match expressions, first-class callables, native attributes.
- **Automated Refactoring Tooling:** Rector (`rector/rector`), PHP-CS-Fixer, PHPStan.
- **Martin Fowler's Refactoring Patterns:** Extract Method, Introduce Parameter Object, Decompose Conditional, Invert Dependencies.
- **Characterization Testing:** Constructing test baselines to record legacy behavior prior to modifying code.

## 4. Development Standards and Conventions
- Never refactor code without a green automated test baseline.
- Execute changes in small, incremental, atomic steps, running tests and linters after each step.
- If a class lacks tests, write characterization tests before refactoring.

## 5. Architectural Rules and Code Organization
- Eliminate temporal coupling and hidden static dependencies (e.g., global state, service locators).
- Relocate scattered domain logic into cohesive application services or entity methods.

## 6. PHP and Symfony Best Practices
- **Switch to Match Migration:** Native PHP 8 `match` expressions use strict type comparisons (`===`) and avoid fallthrough bugs common in `switch`.
- **Native Attributes:** Replace legacy `@Route` and `@ORM\Column` PHPDoc annotations with first-class PHP attributes `#[Route]`, `#[ORM\Column]`.

## 7. Examples of Correct and Incorrect Implementations

### Example: Legacy Service Modernization
**Incorrect (Legacy PHP 7 code with untyped associative arrays, fragile switch, and setter injection):**
```php
// Incorrect: Weak typing, array without contract, and mutable setter injection
namespace App\Legacy;

class CommissionCalculator
{
    private $orderRepository;

    public function setOrderRepository($repo)
    {
        $this->orderRepository = $repo;
    }

    public function calculate($orderData)
    {
        $rate = 0.0;
        switch ($orderData['type']) {
            case 'standard':
                $rate = 0.05;
                break;
            case 'premium':
                $rate = 0.10;
                break;
        }

        return $orderData['amount'] * $rate;
    }
}
```

**Correct (Modernized with strict types, immutable DTO, native Enum, match, and constructor promotion):**
```php
declare(strict_types=1);

namespace App\Service;

use App\Enum\CustomerTier;
use App\Repository\OrderRepository;

// 1. Typed Enum
namespace App\Enum;

enum CustomerTier: string
{
    case Standard = 'standard';
    case Premium = 'premium';
    case Vip = 'vip';

    public function commissionRate(): float
    {
        return match ($this) {
            self::Standard => 0.05,
            self::Premium => 0.10,
            self::Vip => 0.15,
        };
    }
}

// 2. Immutable Input DTO
namespace App\DTO;

use App\Enum\CustomerTier;

final readonly class CommissionCalculationInput
{
    public function __construct(
        public CustomerTier $tier,
        public int $amountInCents,
    ) {}
}

// 3. Modern Refactored Service
namespace App\Service;

use App\DTO\CommissionCalculationInput;
use App\Repository\OrderRepository;

final readonly class CommissionCalculator
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {}

    public function calculate(CommissionCalculationInput $input): int
    {
        return (int) round($input->amountInCents * $input->tier->commissionRate());
    }
}
```

## 8. Technical Decision Criteria
- **When to use Rector:** For mechanical batch transformations across the repository (e.g., migrating annotations to attributes, PHP 8.2 upgrade rules).
- **When to refactor manually:** For domain boundary redesigns, DTO extractions, and interface adjustments.

## 9. Validation and Testing Procedures
- Verify green baseline before starting:
  ```bash
  php bin/phpunit
  ```
- Run knowledge graph blast-radius refactoring impact analysis:
  ```bash
  php templates/rag/semantic-indexer.php --strict
  php templates/rag/rag-query.php --query "OrderRepository" --blast-radius --strict
  ```
- Run Rector in dry-run mode to inspect diffs:
  ```bash
  ./vendor/bin/rector process --dry-run
  ```
- Validate tests and PHPStan after each step:
  ```bash
  php bin/phpunit && ./vendor/bin/phpstan analyse -l 8 src
  ```

## 10. Error Handling and Exceptions
- Refactoring must preserve exception types and error messages thrown to callers.
- Refer to [docs/SEMANTIC_MEMORY_AND_KNOWLEDGE_GRAPH_RAG.md](file:///d:/gow/symfony-engineering-agent-v2.0/symfony-engineering-agent/docs/SEMANTIC_MEMORY_AND_KNOWLEDGE_GRAPH_RAG.md) and [workflows/knowledge-graph-codebase-navigation.md](file:///d:/gow/symfony-engineering-agent-v2.0/symfony-engineering-agent/workflows/knowledge-graph-codebase-navigation.md) for architectural blast-radius assessment before modifying core domain services.

## 11. Objective Quality and Completion Criteria
- 100% of unit and functional tests pass green.
- Measurable reduction in cyclomatic complexity.
- Inspected `git diff` confirms zero accidental behavioral alterations.
