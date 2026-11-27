---
name: symfony-application
description: Implement Symfony controllers, application services, input/output DTOs, console commands, and message handlers.
---

# Skill: Symfony Application Development (Symfony Application)

## 1. Objective and Responsibility
Guide the complete implementation of features in the Symfony ecosystem (6.4 / 7.x+): HTTP controllers, input and output DTOs, application services, console commands, asynchronous processing, and events, ensuring clean, testable, and highly cohesive code.

## 2. Scope and Limitations
- **Scope:** Creating and modifying Controllers, Services, Commands (`#[AsCommand]`), DTOs with validation, Event Listeners (`#[AsEventListener]`), and Message Handlers (`#[AsMessageHandler]`).
- **Limitations:** Must not mix direct persistence concerns (e.g., raw SQL in controllers) or act as a substitute for database modeling (delegated to `doctrine-persistence`).

## 3. Required Technical Knowledge
- **Symfony Framework Core:** HTTP request lifecycle (`HttpKernel`), autowiring, static constructor dependency injection.
- **Validation and Mapping:** `#[MapRequestPayload]`, `#[MapQueryParameter]`, Symfony Validator (`#[Assert\NotBlank]`, `#[Assert\Email]`).
- **Serialization:** Symfony Serializer, normalization contexts, and groups (`#[Groups]`).
- **Messaging and Events:** Symfony Messenger (`MessageBusInterface`), EventDispatcher (`EventDispatcherInterface`).
- **Console:** `Symfony\Component\Console\Command\Command`, `SymfonyStyle` injection.

## 4. Development Standards and Conventions
- Controllers must be marked `final` and use native PHP 8 attributes: `#[Route('/api/...', methods: ['...'])]`.
- Controllers must remain skinny: validate input via DTOs, delegate to application services, and return an appropriate HTTP `Response`.
- Every DTO must be declared as `final readonly class` with typed properties.
- All dependencies must be injected strictly via class constructors with property promotion.

## 5. Architectural Rules and Code Organization
- Controllers: `src/Controller/Api/` or `src/Controller/Web/`.
- Use cases and application logic: `src/Service/` or `src/Application/UseCase/`.
- Input/output contracts: `src/DTO/Request/` and `src/DTO/Response/`.
- CLI commands: `src/Command/`.
- Messages and Handlers: `src/Message/` and `src/MessageHandler/`.

## 6. PHP and Symfony Best Practices
- Use `#[MapRequestPayload]` directly on controller action parameters; Symfony automatically deserializes incoming JSON payloads into the DTO and executes validation constraints (`#[Assert\...]`), returning HTTP 422 on failure before entering the controller body.
- Return explicit response types: `JsonResponse` for REST APIs or mapped DTO objects.
- Use constructor property promotion to keep classes concise.

## 7. Examples of Correct and Incorrect Implementations

### Example: User Registration REST API Endpoint
**Incorrect (Fat Controller with business logic, direct persistence, and missing DTO):**
```php
// Incorrect: Mixes manual validation, persistence, and business rules in the controller
#[Route('/api/users', methods: ['POST'])]
public function createUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (empty($data['email']) || empty($data['password'])) {
        return new JsonResponse(['error' => 'Invalid data'], 400);
    }

    $user = new User();
    $user->setEmail($data['email']);
    $user->setPassword($hasher->hashPassword($user, $data['password']));

    $em->persist($user);
    $em->flush();

    return new JsonResponse(['id' => $user->getId(), 'email' => $user->getEmail()], 201);
}
```

**Correct (Skinny Controller, validated DTO via MapRequestPayload, and decoupled Service):**
```php
// 1. Immutable, Validated Input DTO
namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required.')]
        #[Assert\Email(message: 'Invalid email format.')]
        public string $email,

        #[Assert\NotBlank(message: 'Password is required.')]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long.')]
        public string $password,
    ) {}
}

// 2. Application Service (Use Case)
namespace App\Service;

use App\DTO\Request\CreateUserDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserRegistrationService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function register(CreateUserDTO $dto): User
    {
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw new UserAlreadyExistsException(sprintf('Email %s is already registered.', $dto->email));
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setPassword($this->hasher->hashPassword($user, $dto->password));

        $this->userRepository->save($user, flush: true);

        return $user;
    }
}

// 3. Skinny Invocable Controller
namespace App\Controller\Api;

use App\DTO\Request\CreateUserDTO;
use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users', methods: ['POST'])]
final class CreateUserController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] CreateUserDTO $dto,
        UserRegistrationService $service,
    ): JsonResponse {
        $user = $service->register($dto);

        return $this->json(
            $user,
            status: Response::HTTP_CREATED,
            context: ['groups' => ['user:read']]
        );
    }
}
```

## 8. Technical Decision Criteria
- **Single Action Controller (`__invoke`) vs Multi-Action Controller:**
  - Prefer Single Action Controllers for REST APIs; each endpoint represents an isolated class, improving testability and dependency hygiene.
- **Synchronous Execution vs Messenger:**
  - Any side effect not required for the immediate client response (emails, notifications, analytics) MUST be dispatched asynchronously via Symfony Messenger.

## 9. Validation and Testing Procedures
- Verify services and routes:
  ```bash
  php bin/console lint:container
  php bin/console debug:router
  ```
- Write functional tests using `WebTestCase` validating:
  - 201 Created on valid payload.
  - 422 Unprocessable Content on malformed email or short password.
  - 409 Conflict on duplicate email registration.

## 10. Error Handling and Exceptions
- Custom domain exceptions (such as `UserAlreadyExistsException`) must be mapped to appropriate HTTP status codes (e.g., 409 Conflict) using Symfony exception listeners or exception attributes.

## 11. Objective Quality and Completion Criteria
- Zero direct database persistence calls (`$em->flush()`) inside controllers.
- 100% of input parameters validated with typed constraints.
- Automated tests passing with clean green execution in CI.
