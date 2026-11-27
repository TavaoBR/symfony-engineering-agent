---
name: architecture-design
description: Design clean software architecture, establish layer boundaries, define service interfaces, and immutable DTOs.
---

# Skill: Symfony Architecture Design (Architecture Design)

## 1. Objective and Responsibility
Design and govern application technical architecture in Symfony applications and microservices, establishing domain boundaries, interface contracts, package layouts, and formal Architecture Decision Records (ADRs).

## 2. Scope and Limitations
- **Scope:** Designing directory layouts, decoupling domain from infrastructure, modularization, DTO/Gateway contracts, transaction management, and documentation (`docs/ARCHITECTURE.md`, `docs/DECISIONS.md`).
- **Limitations:** Avoids overengineering. Does not force Clean Architecture or DDD for straightforward CRUD applications where standard Symfony conventions are simpler and superior.

## 3. Required Technical Knowledge
- Architectural patterns: Layered Architecture, Ports & Adapters (Hexagonal), Domain-Driven Design (DDD Bounded Contexts), simple CQRS.
- SOLID principles and clean code in PHP 8.2+.
- Asynchronous messaging architectures with Symfony Messenger.
- Formal documentation using Architecture Decision Records (ADRs).

## 4. Development Standards and Conventions
- The dependency rule: inner domain layers must never import or depend on outer infrastructure details (HTTP controllers, Doctrine mappings, external SDKs).
- Core business rules reside in the Domain (Entities and Value Objects) or Application layer (Use Cases / Services).
- Infrastructure concerns (database persistence, HTTP clients, message brokers) reside strictly in Infrastructure adapters.

## 5. Architectural Rules and Code Organization
- **Structure for Modern Symfony Applications:**
  - `src/Domain/`: Domain entities, Value Objects, Enums, and Repository/Gateway interfaces.
  - `src/Application/`: Use cases, input/output DTOs, orchestration services.
  - `src/Infrastructure/`: Concrete Doctrine repositories implementing domain interfaces, external API clients, listeners.
  - `src/Controller/`: HTTP controllers receiving requests, invoking use cases, and returning responses.

## 6. PHP and Symfony Best Practices
- **Interfaces with Concrete Purpose:** Define interfaces for boundaries with interchangeable implementations or external systems (e.g., `PaymentGatewayInterface`). Do not create boilerplate interfaces for internal classes that only ever have a single implementation.
- **Atomic Transactions:** Wrap composite use cases in `$em->wrapInTransaction()` to ensure that if any step fails, all mutations are rolled back.

## 7. Examples of Correct and Incorrect Implementations

### Example: Gateway Decoupling and Dependency Inversion
**Incorrect (Business service tightly coupled to concrete third-party SDK):**
```php
// Incorrect: Domain logic directly depends on third-party Stripe SDK
namespace App\Service;

use Stripe\StripeClient; // Direct external SDK dependency!

class BillingService
{
    private StripeClient $stripe;

    public function chargeCustomer(User $user, int $amount): void
    {
        // Vendor lock-in: switching payment providers breaks the entire core!
        $this->stripe->charges->create([
            'amount' => $amount,
            'currency' => 'usd',
            'customer' => $user->getStripeCustomerId(),
        ]);
    }
}
```

**Correct (Dependency Inversion via Domain Interface and Infrastructure Adapter):**
```php
// 1. In Domain (src/Domain/Payment/PaymentGatewayInterface.php)
namespace App\Domain\Payment;

interface PaymentGatewayInterface
{
    public function charge(string $customerId, int $amountInCents, string $currency): PaymentResult;
}

// 2. In Infrastructure (src/Infrastructure/Payment/StripePaymentAdapter.php)
namespace App\Infrastructure\Payment;

use App\Domain\Payment\PaymentGatewayInterface;
use App\Domain\Payment\PaymentResult;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class StripePaymentAdapter implements PaymentGatewayInterface
{
    public function __construct(
        private HttpClientInterface $stripeClient,
    ) {}

    public function charge(string $customerId, int $amountInCents, string $currency): PaymentResult
    {
        $response = $this->stripeClient->request('POST', 'charges', [
            'body' => [
                'customer' => $customerId,
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
            ],
        ]);

        return new PaymentResult(isSuccess: $response->getStatusCode() === 200);
    }
}

// 3. In Application Use Case (src/Application/UseCase/ChargeCustomerUseCase.php)
namespace App\Application\UseCase;

use App\Domain\Payment\PaymentGatewayInterface;

final readonly class ChargeCustomerUseCase
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway, // Depends only on domain interface!
    ) {}

    public function execute(string $customerId, int $amount): void
    {
        $result = $this->paymentGateway->charge($customerId, $amount, 'USD');
        if (!$result->isSuccess()) {
            throw new PaymentFailedException('Payment processing failed.');
        }
    }
}
```

## 8. Technical Decision Criteria
- **When to use Hexagonal Architecture:** In complex domains with multiple external integrations and long-term maintainability needs.
- **When to use Standard Layered Symfony:** In straightforward CRUD applications and MVPs prioritizing rapid delivery.

## 9. Validation and Testing Procedures
- Validate layer decoupling using Deptrac:
  ```bash
  vendor/bin/deptrac analyse
  ```
- Validate container compilation:
  ```bash
  php bin/console lint:container
  ```
- Enforce Hexagonal architectural boundaries using Deptrac:
  ```bash
  php templates/architecture/architecture-validator.php --strict
  ```
- Validate cloud-native Kubernetes Helm and GitOps architecture manifests:
  ```bash
  php templates/kubernetes/k8s-validator.php --strict
  ```
- Refer to `docs/KUBERNETES_AND_GITOPS.md` and `workflows/kubernetes-gitops-deployment.md` for production cloud deployment.

## 10. Error Handling and Exceptions
- Domain exceptions must extend `\DomainException` and reflect business rule violations independent of HTTP.

## 11. Objective Quality and Completion Criteria
- Architecture documented in `docs/ARCHITECTURE.md`.
- Architectural choices recorded in `docs/DECISIONS.md`.
- Zero forbidden layer dependencies detected by `architecture-validator.php` (Deptrac).
- Domain layer free of third-party transport or external SDK imports.
