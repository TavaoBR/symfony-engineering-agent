# Application Architecture in Symfony

## 1. General Principles
- The existing project architecture is the primary authority in established codebases. For new projects, design a structure proportional to domain complexity, fully documented, with clean separation of concerns.
- Avoid overengineering: do not introduce abstractions without demonstrable technical value (e.g., creating empty interfaces and passthrough repositories that do nothing but forward calls).

## 2. Canonical Symfony Application Structure
- **`src/Controller/`:** HTTP protocol boundary. Receives the request, invokes payload validation, delegates business logic to application services, and returns an appropriate HTTP `Response`. Must never contain heavy domain logic.
- **`src/DTO/Request/` and `src/DTO/Response/`:** Immutable input and output contracts. Contain typed properties (`readonly class`) and validation constraints (`#[Assert\...]`).
- **`src/Service/` or `src/Application/`:** Use cases and application services. Orchestrate domain logic, database transactions, events, and communication with external gateways.
- **`src/Entity/`:** Domain and persistence models mapped via Doctrine Attributes (`#[ORM\Entity]`). Encapsulate and protect their own state invariants.
- **`src/Repository/`:** Persistence abstractions extending `ServiceEntityRepository`. Centralize DQL, QueryBuilder queries, pagination, and data lookup.
- **`src/Security/`:** Application security components: authorization Voters, custom Authenticators, and User Providers.
- **`src/Message/` and `src/MessageHandler/`:** Asynchronous or synchronous messages (Commands/Events) and their corresponding handlers executed by Symfony Messenger.
- **`src/Event/` and `src/EventListener/`:** Decoupled lifecycle event listeners processed by EventDispatcher.
- **`src/Command/`:** CLI console commands (`#[AsCommand]`) for scheduled tasks, batch operations, and administrative tooling.

## 3. Dependency Inversion and Coupling
- Dependencies must point toward stable abstractions: use cases must not directly depend on low-level infrastructure details (such as third-party SDKs or transport mechanisms).
- Use interfaces at external I/O boundaries (payment gateways, mailers, partner API clients).
- Prevent circular dependencies between services; extract shared concerns into separate services or use events/messages.
- Transactions must be bounded within the application layer: use `$entityManager->wrapInTransaction()` to ensure composite operations are atomic.
