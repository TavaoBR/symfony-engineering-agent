# Workflow: Symfony Feature Delivery with Specialists

## 1. Intake and Scoping (Orchestrator)
- Interprets user requirements, translates them into observable behavior, and defines objective acceptance criteria.
- Identifies the required specialized agents and skills based on task complexity and system impact.

## 2. Discovery and Diagnostics (Project Analyst)
- For existing codebases, executes non-destructive inspection (`project-discovery` skill) to map existing routes, entities, contracts, and configured testing tools.

## 3. Architecture Design and Contracts (Symfony Architect)
- Evaluates architectural impact, designs DTO contracts (`src/DTO/Request/`, `src/DTO/Response/`), service interfaces, and decoupling boundaries.

## 4. Upfront Security Requirements (Security Specialists)
- `symfony-security-specialist` specifies granular authorization Voter requirements (`src/Security/Voter/`) for entity-level access control.
- `php-security-specialist` defines input sanitization rules, validation constraints, and injection defense mechanisms.

## 5. Technical Implementation (Backend Developer & Database Engineer)
- `symfony-backend-developer` implements skinny controllers with `#[MapRequestPayload]` and application service layer.
- `database-engineer` implements Doctrine entity mapping, relationship constraints, custom repository queries, and generates migrations via `doctrine:migrations:diff`.

## 6. Performance Optimization (Performance Specialists)
- `database-performance-specialist` inspects repository queries to guarantee Fetch Joins (`addSelect`) are used, eliminating N+1 query traps.
- `symfony-performance-specialist` ensures high-latency I/O operations (notifications, emails, remote calls) are dispatched via asynchronous Symfony Messenger.

## 7. Mandatory Automated Testing (Quality Engineer)
- Implements and executes unit tests for isolated domain logic and functional HTTP tests (`WebTestCase`) covering:
  - Happy path scenario (HTTP 200/201).
  - Validation failure scenario (HTTP 422 Unprocessable Entity).
  - Unauthorized and forbidden access scenarios (HTTP 401/403).
- Runs static analysis via `vendor/bin/phpstan analyse`.

## 8. Security Audit and Diff Review (Security Auditor & Code Reviewer)
- `application-security-auditor` verifies absence of IDOR vulnerabilities and verifies that no sensitive data leaks in JSON payloads.
- `code-reviewer` inspects the complete `git diff` against AI anti-patterns, error suppressions, and framework standard adherence.

## 9. Documentation and Handoff (Technical Writer & Orchestrator)
- `technical-writer` updates `docs/DECISIONS.md` (ADR) if significant architectural choices were made.
- `orchestrator` validates test execution evidence, compiles the implementation report, and presents the completed deliverable to the user.
