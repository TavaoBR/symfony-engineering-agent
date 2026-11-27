---
name: external-api-integration
description: Integrate third-party REST/GraphQL APIs with HttpClientInterface, retries, and HMAC webhook verification.
---

# Skill: External API Integration and Webhooks (External API Integration)

## 1. Objective and Responsibility
Integrate third-party payment gateways, partner APIs, microservices, and incoming webhooks in the Symfony ecosystem using decoupled HTTP clients (`HttpClientInterface`), scoped clients, resilience patterns (timeouts, circuit breakers, retries with jitter), and strict cryptographic webhook signature validation.

## 2. Scope and Limitations
- **Scope:** Building Anti-Corruption Layer adapters, configuring scoped clients in `framework.yaml`, authenticated request dispatching (OAuth2, mTLS, Bearer, HMAC), webhook signature verification, and mock testing.
- **Limitations:** Never executes real external transactions or financial mutations against live third-party production environments during development or testing cycles.

## 3. Required Technical Knowledge
- **Symfony HttpClient Component:** `HttpClientInterface`, `ScopingHttpClient`, `MockHttpClient`, `MockResponse`.
- **Network Resilience:** Connection and execution timeouts, exponential backoff with jitter (`RetryableHttpClient`).
- **Webhook Security:** HMAC-SHA256 signature verification using `hash_equals()`, replay attack prevention via timestamps.
- **Anti-Corruption Layer (ACL):** Mapping foreign vendor data models into internal domain DTOs and entities.

## 4. Development Standards and Conventions
- Never use raw `curl_*` functions or legacy libraries; always inject `Symfony\Contracts\HttpClient\HttpClientInterface`.
- Isolate each external service inside a dedicated gateway class (e.g., `src/Integration/Stripe/StripeGateway.php`) implementing an internal domain interface.
- Never log credentials, API keys, or sensitive payload data (card numbers, tokens).

## 5. Architectural Rules and Code Organization
- Domain gateway interface: `src/Domain/Payment/PaymentGatewayInterface.php`.
- Concrete adapter: `src/Infrastructure/Payment/StripePaymentGateway.php`.
- External DTOs: `src/Integration/Stripe/DTO/`.
- Webhook controllers: `src/Controller/Webhook/` returning immediate HTTP 200 responses and delegating processing asynchronously via Symfony Messenger.

## 6. PHP and Symfony Best Practices
- **Scoped Clients in `framework.yaml`:** Configure base URIs and timeouts per provider in framework configuration:
  ```yaml
  # config/packages/framework.yaml
  framework:
      http_client:
          scoped_clients:
              stripe.client:
                  base_uri: 'https://api.stripe.com/v1/'
                  headers:
                      Authorization: 'Bearer %env(STRIPE_SECRET_KEY)%'
                  timeout: 5.0
                  max_duration: 15.0
  ```
- **Constant-Time Comparison:** Always use `hash_equals()` to compare incoming webhook signatures against computed HMAC hashes.

## 7. Examples of Correct and Incorrect Implementations

### Example: Webhook Signature Verification and Asynchronous Processing
**Incorrect (No signature verification and blocking synchronous execution):**
```php
// Incorrect: Accepts forged payloads and blocks the HTTP request during processing
#[Route('/webhook/payment', methods: ['POST'])]
public function handleWebhook(Request $request): Response
{
    $payload = json_decode($request->getContent(), true);

    // Critical security flaw: does not verify that the request originated from the provider!
    if ($payload['event'] === 'payment_success') {
        $this->orderService->completeOrder($payload['order_id']); // Blocks HTTP worker!
    }

    return new Response('OK');
}
```

**Correct (HMAC-SHA256 verification with hash_equals and asynchronous Messenger dispatch):**
```php
namespace App\Controller\Webhook;

use App\Message\ProcessWebhookPaymentMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhooks/payment', methods: ['POST'])]
final class PaymentWebhookController extends AbstractController
{
    public function __construct(
        private readonly string $webhookSecret,
        private readonly MessageBusInterface $bus,
    ) {}

    public function __invoke(Request $request): Response
    {
        $signature = $request->headers->get('X-Signature');
        $payload = $request->getContent();

        if (empty($signature) || empty($payload)) {
            throw new BadRequestHttpException('Missing signature or payload.');
        }

        // Calculate expected HMAC-SHA256 signature
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        // Constant-time comparison immune to timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            throw new BadRequestHttpException('Invalid webhook signature.');
        }

        $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        // Dispatch to background queue; return HTTP 200 immediately
        $this->bus->dispatch(new ProcessWebhookPaymentMessage(
            eventId: $data['id'] ?? '',
            eventType: $data['type'] ?? '',
            payload: $data['data'] ?? []
        ));

        return new Response('Webhook received successfully.', Response::HTTP_OK);
    }
}
```

## 8. Technical Decision Criteria
- **Timeout Defaults:**
  - Connection timeout: maximum 3.0 seconds.
  - Read `max_duration`: maximum 10.0 seconds.
- **Idempotency:** State-mutating external requests (charges, transfers) MUST send unique `Idempotency-Key` UUID headers.

## 9. Validation and Testing Procedures
- Test integrations using `MockHttpClient` and `MockResponse`:
  ```php
  public function testGatewayHandlesSuccessResponse(): void
  {
      $mockResponse = new MockResponse(json_encode(['status' => 'succeeded']));
      $mockClient = new MockHttpClient([$mockResponse]);

      $gateway = new StripePaymentGateway($mockClient);
      $result = $gateway->charge(1000, 'BRL');

      $this->assertTrue($result->isSuccess());
  }
  ```

## 10. Error Handling and Exceptions
- HttpClient exceptions (`TransportExceptionInterface`, `ClientExceptionInterface`, `ServerExceptionInterface`) must be caught and mapped into clear domain exceptions (`GatewayUnavailableException`).

## 11. Objective Quality and Completion Criteria
- 100% of outbound HTTP requests have configured timeouts.
- Webhook signatures verified using `hash_equals()`.
- Client unit tests cover success, timeout, and remote 500 error scenarios.
