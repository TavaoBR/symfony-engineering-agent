---
name: symfony-optimization
description: Optimize Symfony DI container, compile private services, configure cache pools, and async Messenger.
---

# Skill: Symfony Framework Optimization (Symfony Optimization)

## 1. Objective and Responsibility
Optimize internal Symfony components (Dependency Injection container, EventDispatcher, Messenger, Serializer, Routing, Cache), eliminating framework overhead and maximizing application throughput (RPS).

## 2. Scope and Limitations
- **Scope:** Service visibility, asynchronous decoupling with Symfony Messenger, cache pool tuning (Redis/APCu), serialization groups, and HTTP caching headers.
- **Limitations:** Does not cover raw SQL query tuning (delegated to `database-performance`).

## 3. Required Technical Knowledge
- Dependency Injection Container: `CompilerPassInterface`, service inlining, dead code removal.
- Symfony Messenger: Transport configuration (`doctrine`, `redis`, `amqp`), retry strategies.
- Symfony Cache: `TagAwareAdapterInterface`, `CacheItemPoolInterface`, tag invalidation, stale-while-revalidate.
- HTTP Cache: `Cache-Control`, `ETag`, `Last-Modified`, conditional validation via `Response::isNotModified()`.

## 4. Development Standards and Conventions
- All services must be private (`services._defaults.public: false` in `services.yaml`).
- Inject dependencies strictly through class constructors with `readonly` properties.
- Never dispatch emails, slow reports, or webhooks synchronously in HTTP controllers.

## 5. Architectural Rules and Code Organization
- Asynchronous messages reside in `src/Message/` as immutable DTOs (`final readonly class`) holding only scalar identifiers.
- Handlers reside in `src/MessageHandler/` marked with `#[AsMessageHandler]`.
- Controllers dispatch messages via `MessageBusInterface` and return `202 Accepted` or `201 Created` immediately.

## 6. PHP and Symfony Best Practices
- **Tag-Based Cache Invalidation:** Use tags to associate cached data with domain entities (e.g., `product_123`). Invalidate only affected tags on updates (`$cache->invalidateTags(['product_123'])`).
- **Conditional HTTP Caching:** In read-only endpoints, validate client `ETag` headers to return `304 Not Modified` without re-generating response bodies.

## 7. Examples of Correct and Incorrect Implementations

### Example: Asynchronous Decoupling via Symfony Messenger
**Incorrect (Blocking synchronous operations in HTTP controller):**
```php
// Incorrect: Client waits 4 seconds while PDF and email are generated synchronously
#[Route('/api/orders', methods: ['POST'])]
public function createOrder(
    #[MapRequestPayload] CreateOrderDTO $dto,
    OrderService $service,
    PdfGenerator $pdf,
    MailerInterface $mailer
): JsonResponse {
    $order = $service->create($dto);

    // Blocking I/O during user HTTP request:
    $pdfContent = $pdf->generateInvoicePdf($order);
    $mailer->sendInvoiceEmail($order, $pdfContent);

    return $this->json($order, 201);
}
```

**Correct (Immediate asynchronous dispatch via Messenger):**
```php
#[Route('/api/orders', methods: ['POST'])]
public function createOrder(
    #[MapRequestPayload] CreateOrderDTO $dto,
    OrderService $service,
    MessageBusInterface $bus
): JsonResponse {
    $order = $service->create($dto);

    // Dispatches message to background queue; responds in milliseconds
    $bus->dispatch(new OrderCreatedMessage($order->getId()));

    return $this->json(
        $order,
        status: Response::HTTP_CREATED,
        context: ['groups' => ['order:read']]
    );
}

// In src/Message/OrderCreatedMessage.php:
final readonly class OrderCreatedMessage
{
    public function __construct(public int $orderId) {}
}

// In src/MessageHandler/OrderCreatedHandler.php:
#[AsMessageHandler]
final readonly class OrderCreatedHandler
{
    public function __construct(
        private OrderRepository $orders,
        private PdfGenerator $pdf,
        private MailerInterface $mailer,
    ) {}

    public function __invoke(OrderCreatedMessage $message): void
    {
        $order = $this->orders->find($message->orderId);
        if (!$order) {
            return;
        }

        $pdfContent = $this->pdf->generateInvoicePdf($order);
        $this->mailer->sendInvoiceEmail($order, $pdfContent);
    }
}
```

## 8. Technical Decision Criteria
- **Redis vs APCu:**
  - APCu: Local in-memory caching on single web nodes for immutable data between deploys.
  - Redis: Shared caching across multiple nodes requiring centralized tag invalidation.

## 9. Validation and Testing Procedures
- Inspect compiled container and routes:
  ```bash
  php bin/console debug:container --show-private
  php bin/console debug:messenger
  ```
- Functional tests with `WebTestCase` verifying asynchronous message queuing.

## 10. Error Handling and Exceptions
- Configure `retry_strategy` in Messenger with exponential backoff before sending failed messages to the dead-letter queue (`failed`).
- Audit Messenger architecture and prevent synchronous bottlenecks:
  ```bash
  php templates/messenger/messenger-worker-verifier.php --strict
  ```
- Validate high-concurrency load and throughput limits:
  ```bash
  php templates/load-testing/k6-runner.php --vus=20 --duration=30s --max-p95=200
  ```

## 11. Objective Quality and Completion Criteria
- Zero blocking network calls executed synchronously in HTTP controllers.
- 100% of services compiled as private in the container.
- Dead letter queue (DLQ) verified and active via `messenger-worker-verifier.php`.
- Tag-based caching active with defined expiration policies.
