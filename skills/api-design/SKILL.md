---
name: api-design
description: Design RESTful APIs, HTTP semantic status codes, Problem Details RFC 7807/9457 payloads, and pagination.
---

# Skill: HTTP API Design and Implementation in Symfony (API Design)

## 1. Objective and Responsibility
Design and build robust, standardized, secure, high-performance RESTful APIs in Symfony, implementing strict payload validation, controlled response serialization, resilient pagination, and uniform error handling adhering to RFCs 7807 and 9457 (Problem Details).

## 2. Scope and Limitations
- **Scope:** Designing endpoint contracts, semantic HTTP methods (GET, POST, PUT, PATCH, DELETE), DTOs with `#[MapRequestPayload]`, query filters via `#[MapQueryParameter]`, Symfony Serializer groups, pagination, and standardized error envelopes.
- **Limitations:** Must not bypass the database layer or break established public API contracts in production.

## 3. Required Technical Knowledge
- HTTP/1.1 and HTTP/2 protocol semantics, idempotency, and headers (`Content-Type`, `Accept`, `Location`, `ETag`).
- Symfony 6.4/7+ HTTP attributes: `#[Route]`, `#[MapRequestPayload]`, `#[MapQueryParameter]`, `#[MapQueryString]`.
- Symfony Serializer: Normalizers, Encoders, CircularReferenceHandler, `#[Groups]`, `#[Ignore]`.
- RFC 7807 and RFC 9457 specifications for Problem Details for HTTP APIs.
- OpenAPI 3.0 / 3.1 specifications.

## 4. Development Standards and Conventions
- API routes must use version prefixes: `/api/v1/...`.
- Resource endpoints use plural nouns: `/api/v1/orders`, `/api/v1/customers`.
- Strictly adhere to HTTP method semantics:
  - `GET`: Safe, idempotent, read-only. Returns 200 OK.
  - `POST`: Resource creation or non-idempotent actions. Returns 201 Created with `Location` header.
  - `PUT`: Complete resource replacement. Idempotent. Returns 200 OK or 204 No Content.
  - `PATCH`: Partial resource update. Returns 200 OK.
  - `DELETE`: Resource removal. Idempotent. Returns 204 No Content.
- Validation failures must return HTTP status 422 Unprocessable Content.

## 5. Architectural Rules and Code Organization
- Request DTOs: `src/DTO/Request/` (`final readonly class` with Symfony Validator constraints).
- Response DTOs: `src/DTO/Response/` (`final readonly class` for clean data projections).
- API Controllers: `src/Controller/Api/` (extend `AbstractController` or use invocable controllers).
- Exception Listeners: `src/EventListener/ApiExceptionListener.php` to format all exceptions into uniform JSON.

## 6. PHP and Symfony Best Practices
- **Never Expose Raw Entities Directly:** Doctrine entities contain lifecycle states and lazy-loading proxies that cause circular reference errors or N+1 queries during serialization. Always use DTOs or explicit groups.
- **Mandatory Pagination:** Any endpoint returning a collection (`GET /api/v1/orders`) MUST enforce pagination (`page`, `limit`) with a strict maximum limit (e.g., maximum 100 items).
- **Validation with `#[MapRequestPayload]`:** Eliminates manual `json_decode()` and manual validation check loops.

## 7. Examples of Correct and Incorrect Implementations

### Example: Partial Resource Update (PATCH) Endpoint
**Incorrect (Unvalidated input, direct array manipulation, and circular reference risk):**
```php
// Incorrect: Accepts unvalidated input, leaks password hashes, and crashes on circular references
#[Route('/api/customers/{id}', methods: ['PATCH'])]
public function updateCustomer(int $id, Request $request, EntityManagerInterface $em): JsonResponse
{
    $customer = $em->getRepository(Customer::class)->find($id);
    $data = json_decode($request->getContent(), true);

    if (isset($data['email'])) {
        $customer->setEmail($data['email']); // No validation of email format!
    }
    if (isset($data['name'])) {
        $customer->setName($data['name']);
    }

    $em->flush();
    return new JsonResponse($customer); // Circular reference error or password hash leak!
}
```

**Correct (Validated DTO, Voter authorization, and safe DTO response):**
```php
// 1. Partial Update Request DTO
namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCustomerDTO
{
    public function __construct(
        #[Assert\Length(min: 2, max: 100)]
        public ?string $name = null,

        #[Assert\Email]
        public ?string $email = null,
    ) {}
}

// 2. Safe Response DTO
namespace App\DTO\Response;

final readonly class CustomerResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public \DateTimeImmutable $createdAt,
    ) {}
}

// 3. Controller
namespace App\Controller\Api;

use App\DTO\Request\UpdateCustomerDTO;
use App\DTO\Response\CustomerResponseDTO;
use App\Entity\Customer;
use App\Security\Voter\CustomerVoter;
use App\Service\CustomerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/customers/{id}', methods: ['PATCH'])]
final class UpdateCustomerController extends AbstractController
{
    public function __invoke(
        Customer $customer,
        #[MapRequestPayload] UpdateCustomerDTO $dto,
        CustomerService $service,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CustomerVoter::EDIT, $customer);

        $updatedCustomer = $service->update($customer, $dto);

        $responseDTO = new CustomerResponseDTO(
            id: $updatedCustomer->getId(),
            name: $updatedCustomer->getName(),
            email: $updatedCustomer->getEmail(),
            createdAt: $updatedCustomer->getCreatedAt(),
        );

        return $this->json($responseDTO);
    }
}
```

## 8. Technical Decision Criteria
- **Error Format:** Strictly adhere to Problem Details RFC 7807/9457 with `Content-Type: application/problem+json`:
  ```json
  {
    "type": "https://api.domain.com/errors/validation-failed",
    "title": "Validation Failed",
    "status": 422,
    "detail": "One or more fields contain invalid values.",
    "invalid_params": [
      {
        "name": "email",
        "reason": "Invalid email format."
      }
    ]
  }
  ```

## 9. Validation and Testing Procedures
- Functional API testing using `WebTestCase` with OpenAPI Contract Verification (`OpenApiContractTestTrait`):
  ```php
  use App\Tests\Contract\OpenApiContractTestTrait;

  final class CustomerApiTest extends WebTestCase
  {
      use OpenApiContractTestTrait;

      public function testUpdateCustomerValidationFails(): void
      {
          $client = static::createClient();
          $client->request(
              'PATCH',
              '/api/v1/customers/1',
              content: json_encode(['email' => 'invalid-email'])
          );

          // Asserts 422, application/problem+json, and validates RFC 7807 schema against OpenAPI
          $this->assertProblemDetailsMatchesOpenApi($client, '/api/v1/customers/1', 'PATCH', 422);
      }

      public function testGetCustomerConformsToOpenApiContract(): void
      {
          $client = static::createClient();
          $client->request('GET', '/api/v1/customers/1');

          // Validates status 200, Content-Type, and asserts no undocumented properties exist
          $this->assertResponseMatchesOpenApi($client, '/api/v1/customers/1', 'GET', 200);
      }
  }
  ```
- CLI Contract Verification:
  ```bash
  php templates/contracts/openapi-validator.php config/openapi.yaml --strict --check-routes
  ```

## 10. Error Handling and Exceptions
- `HttpExceptionInterface` exceptions (404, 403, 401, 400) must be formatted without leaking internal paths or stack traces.
- Unhandled 500 errors must generate a correlation ID (`trace_id`) returned to the client and logged for investigation.

## 11. Objective Quality and Completion Criteria
- All endpoints have documented contracts covered by automated tests.
- Zero Doctrine entities exposed without DTOs or serialization groups.
- All errors return standardized JSON with correct HTTP status codes.
