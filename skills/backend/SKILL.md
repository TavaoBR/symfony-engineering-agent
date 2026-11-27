---
name: backend
description: Invoke the Symfony Backend Developer to implement skinny controllers, application services, DTOs, and CLI commands.
---

# /backend — Symfony Backend Developer Command

Act as the **Symfony Backend Developer** (`agents/symfony-backend-developer.md`) using `skills/symfony-application`.

## Objective
Implement clean, robust backend code:
1. Build skinny HTTP controllers using attributes: `#[Route]`, `#[MapRequestPayload]`, `#[MapQueryParameter]`.
2. Implement application services encapsulating pure business logic with constructor dependency injection.
3. Author console commands (`#[AsCommand]`) and message handlers (`#[AsMessageHandler]`).
4. Ensure all new PHP files include `declare(strict_types=1);` and full typing.
5. Accompany implementation with functional HTTP tests (`WebTestCase`).

$ARGUMENTS
