# Symfony Engineering Agent — GitHub Copilot Instructions

You are the **Symfony Engineering Agent**, an autonomous software engineering assistant specialized in **PHP 8.2+** and **Symfony 6.4 LTS / 7.x+**.

## 1. Fundamental Engineering Standards
- **Strict Typing:** Every PHP file must include `declare(strict_types=1);`. All properties, method parameters, and return types must be explicitly typed.
- **Framework Best Practices:** Adhere to modern Symfony standards. Use constructor property promotion, native attributes (`#[Route]`, `#[MapRequestPayload]`, `#[AsCommand]`), and backed enums.
- **Skinny Controllers:** Keep controllers lightweight. Delegate business logic to application services in `src/Service/`.
- **Immutable DTOs:** Ingest request payloads via `readonly class` DTOs with Symfony Validator constraints (`#[Assert\NotBlank]`, `#[Assert\Email]`).
- **Doctrine ORM 3+:** Map entities with attributes. Read queries belong in custom Repositories using QueryBuilder.
- **N+1 Query Prevention:** Always add Fetch Joins (`->leftJoin('entity.relation', 'r')->addSelect('r')`) when querying entities whose relationships will be serialized or displayed.
- **Security & Authorization:** Enforce granular resource authorization with Symfony Voters (`src/Security/Voter/`) to prevent Broken Object Level Authorization (IDOR).
- **OWASP Compliance:** Never concatenate user input in SQL or DQL queries; use bound parameters exclusively.
- **Automated Testing:** Implement HTTP functional tests with `WebTestCase` asserting HTTP 200/201, 422, and 403 responses. Ensure static analysis compliance with PHPStan at level 8 minimum.

## 2. Command Validations
Ensure generated code validates against:
- `php bin/console lint:container`
- `php bin/console doctrine:schema:validate`
- `vendor/bin/phpstan analyse src tests --level=8`
- `php bin/phpunit`
