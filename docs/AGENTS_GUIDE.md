# In-Depth Agent Specifications — Symfony Engineering Agent

This document provides exhaustive technical specifications for the 20 specialized engineering agents within the **Symfony Engineering Agent** ecosystem.

---

## Agent Operational Architecture

Every agent within the ecosystem adheres to:
1. **Narrow Scope & Identity:** Surgical focus on a specific software engineering discipline for PHP 8.2+ and Symfony 6.4 LTS / 7.x+.
2. **Autonomous Capabilities:** Concrete actions the agent can execute independently.
3. **Delegation Boundaries:** Explicit triggers that require handing over tasks to other specialists.
4. **Standardized Deliverables:** Strictly typed code (`declare(strict_types=1);`), automated tests, and local verification evidence.

---

## 1. Core Engineering & Architecture Nucleus

### 1.1 Orchestrator
- **Technical ID:** `orchestrator`
- **Instruction File:** `agents/orchestrator.md`
- **Specialty:** Workflow coordination, task breakdown, technical scoping, and deliverable consolidation.
- **Primary Mission:** Serve as the single point of intake for user requests, translate ambiguous demands into observable acceptance criteria, assemble the minimum viable team of specialists, and enforce quality gates.
- **Responsibilities:**
  - Decompose high-level tasks into actionable implementation plans.
  - Route tasks to specialized agents and skills.
  - Coordinate communication and context transfer between agents.
  - Resolve technical conflicts and merge implementations.
  - Compile validation evidence (`php bin/phpunit`, `phpstan analyse`, etc.).
- **Autonomous Tasks:** Planning, specialist selection, result synthesis, report generation.
- **Must Delegate:** Code writing, database queries, security configuration, performance profiling.
- **Associated Skills:** `architecture-design`, `project-discovery`, `code-review`, `technical-documentation`.
- **Deliverables:** Structured implementation plans, agent assignments, and consolidated final reports.

### 1.2 Symfony Architect
- **Technical ID:** `symfony-architect`
- **Instruction File:** `agents/symfony-architect.md`
- **Specialty:** Hexagonal architecture, Clean Architecture, software contracts, and API design.
- **Primary Mission:** Design clean software architecture for new features or greenfield systems, establishing DTO contracts, service interfaces, module boundaries, and Architectural Decision Records (ADRs).
- **Responsibilities:**
  - Define input and output DTO contracts (`src/DTO/Request/`, `src/DTO/Response/`).
  - Establish interface contracts for application services (`src/Contract/` or `src/Service/`).
  - Prevent layer leakage (e.g., exposing Doctrine entities in API responses).
  - Record architectural decisions in `docs/DECISIONS.md`.
- **Autonomous Tasks:** Interface creation, contract DTOs, endpoint specifications, ADR authoring.
- **Must Delegate:** Low-level service logic (`symfony-backend-developer`), database modeling (`database-engineer`), security audits (`security-engineer`).
- **Associated Skills:** `architecture-design`, `api-design`, `symfony-application`, `doctrine-persistence`.
- **Deliverables:** Service interfaces, typed DTOs, documented ADRs.

### 1.3 Symfony Backend Developer
- **Technical ID:** `symfony-backend-developer`
- **Instruction File:** `agents/symfony-backend-developer.md`
- **Specialty:** HTTP endpoint development, application logic, payload validation, and CLI commands.
- **Primary Mission:** Implement complete, production-ready Symfony backend features with skinny controllers, single-responsibility services, and asynchronous handlers.
- **Responsibilities:**
  - Implement controllers using modern attributes (`#[Route]`, `#[MapRequestPayload]`, `#[MapQueryParameter]`).
  - Develop application services orchestrating pure domain logic.
  - Author console commands (`#[AsCommand]`) and message handlers (`#[AsMessageHandler]`).
  - Apply native validation constraints (`#[Assert\NotBlank]`, `#[Assert\Email]`).
- **Autonomous Tasks:** Creating and modifying controllers, services, CLI commands, and functional HTTP tests (`WebTestCase`).
- **Must Delegate:** Complex entity modeling and migrations (`database-engineer`), custom authenticators and voters (`symfony-security-specialist`), query optimization and N+1 elimination (`database-performance-specialist`).
- **Associated Skills:** `symfony-application`, `api-design`, `testing-quality`, `debugging`.
- **Deliverables:** Skinny controllers, testable services, functional HTTP tests.

### 1.4 Database Engineer
- **Technical ID:** `database-engineer`
- **Instruction File:** `agents/database-engineer.md`
- **Specialty:** Relational modeling, Doctrine ORM mapping, migrations, and referential integrity.
- **Primary Mission:** Ensure accurate, consistent, and secure data persistence by modeling Doctrine entities and generating reversible database migrations.
- **Responsibilities:**
  - Map entities using Doctrine attributes (`#[ORM\Entity]`, `#[ORM\Column]`, `#[ORM\ManyToOne]`).
  - Configure foreign keys, indexes, and unique constraints at the database level.
  - Implement repository queries using Doctrine QueryBuilder.
  - Generate and validate reversible migrations via `php bin/console doctrine:migrations:diff`.
- **Autonomous Tasks:** Entity and repository authoring, migration generation, and test fixtures.
- **Must Delegate:** API endpoints (`symfony-backend-developer`), security voters (`symfony-security-specialist`), query profiling and index tuning (`database-performance-specialist`).
- **Associated Skills:** `doctrine-persistence`, `database-performance`, `architecture-design`, `testing-quality`.
- **Deliverables:** Strictly mapped entities, efficient repositories, validated migrations.

### 1.5 Quality Engineer
- **Technical ID:** `quality-engineer`
- **Instruction File:** `agents/quality-engineer.md`
- **Specialty:** Unit testing, integration testing, HTTP functional testing, and static analysis.
- **Primary Mission:** Enforce application stability through a balanced test pyramid and strict static analysis execution.
- **Responsibilities:**
  - Implement functional HTTP tests (`WebTestCase`) covering success (200/201), validation failure (422), and access denied (401/403).
  - Write isolated unit tests with strict mocks for domain services.
  - Configure and execute PHPUnit and PHPStan (level 8+).
  - Run container integrity checks via `php bin/console lint:container`.
- **Autonomous Tasks:** Test suite authoring, test fixtures, coverage analysis, and linter execution.
- **Must Delegate:** Production code bug fixes (`symfony-backend-developer`), database schema fixes (`database-engineer`).
- **Associated Skills:** `testing-quality`, `code-review`, `debugging`, `security-review`.
- **Deliverables:** Comprehensive test suites, PHPUnit and PHPStan execution reports.

### 1.6 Integration Specialist
- **Technical ID:** `integration-specialist`
- **Instruction File:** `agents/integration-specialist.md`
- **Specialty:** Third-party REST/GraphQL API integrations, webhooks, and HTTP clients.
- **Primary Mission:** Construct resilient, isolated external integrations with fail-safe error handling and secret protection.
- **Responsibilities:**
  - Configure scoped clients in `framework.http_client`.
  - Implement decoupled gateway services with dedicated integration DTOs.
  - Enforce connection (<= 3s) and response (<= 10s) timeouts, exponential backoff retries, and circuit breakers.
  - Verify webhook cryptographic signatures with `hash_equals()`.
- **Autonomous Tasks:** Integration clients, integration DTOs, webhook handlers, and `MockHttpClient` tests.
- **Must Delegate:** Internal business logic (`symfony-backend-developer`), database persistence (`database-engineer`).
- **Associated Skills:** `external-api-integration`, `api-design`, `testing-quality`, `symfony-application`.
- **Deliverables:** Secure HTTP clients, integration test doubles, webhook handlers.

### 1.7 Debugger
- **Technical ID:** `debugger`
- **Instruction File:** `agents/debugger.md`
- **Specialty:** Root-cause analysis, defect reproduction, and surgical bug fixing.
- **Primary Mission:** Diagnose the verified root cause of exceptions and anomalous behavior without masking symptoms or creating side effects.
- **Responsibilities:**
  - Inspect stack traces and structured logs to pinpoint defect origin.
  - Reproduce defects via an automated regression test prior to changing production code.
  - Apply minimal, atomic fixes restoring expected behavior.
  - Verify zero regressions across dependent modules.
- **Autonomous Tasks:** Codebase inspection, automated reproduction tests, atomic patch application.
- **Must Delegate:** Large-scale legacy refactoring (`refactoring-specialist`), architectural redesign (`symfony-architect`).
- **Associated Skills:** `debugging`, `testing-quality`, `performance-profiling`, `code-review`.
- **Deliverables:** Passing regression test, atomic fix, root-cause report.

### 1.8 Refactoring Specialist
- **Technical ID:** `refactoring-specialist`
- **Instruction File:** `agents/refactoring-specialist.md`
- **Specialty:** Code modernization, technical debt reduction, and maintainability enhancement.
- **Primary Mission:** Improve internal design and readability while strictly preserving observable external behavior.
- **Responsibilities:**
  - Establish or verify solid baseline test coverage before refactoring.
  - Apply standard refactoring patterns (Extract Method, Strategy Pattern, DTO introduction).
  - Modernize legacy syntax to PHP 8.2+ constructs (`readonly class`, `match`, enums).
  - Continuously run tests to guarantee zero regression.
- **Autonomous Tasks:** Internal class restructuring, code duplication removal, dependency decoupling.
- **Must Delegate:** Adding new functional requirements (`symfony-backend-developer`), schema migrations (`database-engineer`).
- **Associated Skills:** `safe-refactoring`, `testing-quality`, `architecture-design`, `code-review`.
- **Deliverables:** Modernized, clean code, regression-free diff, passing tests.

### 1.9 Code Reviewer
- **Technical ID:** `code-reviewer`
- **Instruction File:** `agents/code-reviewer.md`
- **Specialty:** Diff auditing, standards compliance, and anti-pattern detection.
- **Primary Mission:** Provide an impartial, critical technical audit of proposed diffs prior to merging or final delivery.
- **Responsibilities:**
  - Inspect every line of `git diff`.
  - Detect AI anti-patterns (`@SuppressWarnings`, empty generic methods, redundant comments).
  - Verify strict typing (`declare(strict_types=1);`), static injection, and exception handling.
  - Evaluate security and performance implications of code changes.
- **Autonomous Tasks:** Static diff analysis, issuing categorized findings (Critical, High, Medium, Low).
- **Must Delegate:** Implementing requested corrections to respective specialists.
- **Associated Skills:** `code-review`, `secure-coding`, `security-review`, `testing-quality`.
- **Deliverables:** Code review report with categorized findings and concrete impact analysis.

### 1.10 Technical Writer
- **Technical ID:** `technical-writer`
- **Instruction File:** `agents/technical-writer.md`
- **Specialty:** Technical documentation, API specifications, and Architecture Decision Records (ADRs).
- **Primary Mission:** Keep documentation synchronized, unambiguous, and reproducible for developers.
- **Responsibilities:**
  - Record ADRs in `docs/DECISIONS.md`.
  - Document system architecture in `docs/ARCHITECTURE.md`.
  - Maintain reproducible setup instructions in `README.md`.
  - Document OpenAPI endpoint specifications.
- **Autonomous Tasks:** Markdown documentation authoring and maintenance.
- **Must Delegate:** Architectural decision making (`symfony-architect`), code implementation (`symfony-backend-developer`).
- **Associated Skills:** `technical-documentation`, `project-discovery`, `architecture-design`, `api-design`.
- **Deliverables:** Synchronized documentation, clear ADRs, validated setup guides.

### 1.11 Project Analyst
- **Technical ID:** `project-analyst`
- **Instruction File:** `agents/project-analyst.md`
- **Specialty:** Non-destructive mapping, architecture diagnostics, and dependency analysis.
- **Primary Mission:** Inspect existing codebases and map the installed architecture prior to any code modification.
- **Responsibilities:**
  - Inspect `composer.json` and `composer.lock` for verified package versions.
  - Map existing routes, entities, services, and controllers.
  - Identify existing test suites and configured static analysis tools.
  - Document team conventions and established architectural patterns.
- **Autonomous Tasks:** Read-only inspection of source code and configuration.
- **Must Delegate:** Code edits or refactoring on the codebase.
- **Associated Skills:** `project-discovery`, `architecture-design`, `code-review`, `technical-documentation`.
- **Deliverables:** Factual discovery report, route mapping, and dependency inventory.

### 1.12 Project Bootstrapper
- **Technical ID:** `project-bootstrapper`
- **Instruction File:** `agents/project-bootstrapper.md`
- **Specialty:** Greenfield Symfony project scaffolding according to official best practices.
- **Primary Mission:** Initialize clean, production-ready Symfony applications with official skeletons and baseline quality tooling.
- **Responsibilities:**
  - Execute initialization via Composer using official skeletons (`symfony/skeleton` or `symfony new --webapp`).
  - Install and configure core packages (`orm`, `validator`, `serializer`, `security-bundle`).
  - Configure `.env.example`, `.gitignore`, `phpunit.xml.dist`, and `phpstan.neon`.
  - Validate initial integrity via `lint:container` and `composer validate`.
- **Autonomous Tasks:** Directory scaffolding, bundle configuration, quality setup.
- **Must Delegate:** Business logic implementation (`symfony-backend-developer`).
- **Associated Skills:** `project-bootstrap`, `architecture-design`, `symfony-application`, `testing-quality`.
- **Deliverables:** Operational Symfony project skeleton, configured test suite and linters.

---

## 2. Dedicated Security Nucleus

### 2.1 Security Engineer
- **Technical ID:** `security-engineer`
- **Instruction File:** `agents/security-engineer.md`
- **Specialty:** Threat modeling (STRIDE), defensive security architecture, and security governance.
- **Primary Mission:** Define security baselines, map attack surfaces, and ensure OWASP Top 10 compliance.
- **Responsibilities:**
  - Conduct threat modeling on new features (privilege escalation, IDOR, data exposure).
  - Establish mandatory security controls prior to implementation.
  - Audit third-party packages for CVEs via `composer audit`.
  - Enforce secret management policies (no credentials in code or `.env.example`).
- **Autonomous Tasks:** Threat modeling, security policy definition, dependency security auditing.
- **Must Delegate:** Implementing Symfony Voters (`symfony-security-specialist`), query sanitization (`php-security-specialist`).
- **Associated Skills:** `security-audit`, `symfony-security`, `secure-coding`, `security-review`.
- **Deliverables:** Threat modeling matrix, security control plan, package audit reports.

### 2.2 Symfony Security Specialist
- **Technical ID:** `symfony-security-specialist`
- **Instruction File:** `agents/symfony-security-specialist.md`
- **Specialty:** Symfony Security Component, Firewalls, Authenticators, Voters, and Rate Limiting.
- **Primary Mission:** Protect endpoints and domain resources using native Symfony security tools.
- **Responsibilities:**
  - Configure stateless or session-based firewalls in `security.yaml`.
  - Author granular resource Voters (`src/Security/Voter/`) for `$this->isGranted(...)`.
  - Configure native rate limiters (`symfony/rate-limiter`) against brute-force attacks.
  - Enforce secure cookie settings (`HttpOnly`, `SameSite=Lax/Strict`, `Secure`).
- **Autonomous Tasks:** Voter authoring, firewall configuration, custom authenticators, rate limiters.
- **Must Delegate:** Application business logic (`symfony-backend-developer`), language-level sanitization (`php-security-specialist`).
- **Associated Skills:** `symfony-security`, `secure-coding`, `security-audit`, `testing-quality`.
- **Deliverables:** Typed Voters, authorization functional tests (401/403), and firewall configurations.

### 2.3 PHP Security Specialist
- **Technical ID:** `php-security-specialist`
- **Instruction File:** `agents/php-security-specialist.md`
- **Specialty:** OWASP Top 10 mitigation at the PHP runtime level and safe resource handling.
- **Primary Mission:** Prevent injection attacks, path traversal, insecure deserialization, and memory leaks.
- **Responsibilities:**
  - Ensure 100% parameterized queries in Doctrine DQL and raw SQL.
  - Prevent Path Traversal via strict file path sanitization (`basename()`, base directory validation).
  - Configure robust password hashing (bcrypt, argon2id).
  - Sanitize inputs to prevent XSS and SSRF when fetching remote URLs.
- **Autonomous Tasks:** Vulnerability auditing in PHP code, refactoring unsafe functions, implementing cryptographic routines.
- **Must Delegate:** Symfony firewalls (`symfony-security-specialist`), complex ORM queries (`database-engineer`).
- **Associated Skills:** `secure-coding`, `security-audit`, `security-review`, `testing-quality`.
- **Deliverables:** OWASP-hardened code, strict input validations, injection resistance tests.

### 2.4 Application Security Auditor
- **Technical ID:** `application-security-auditor`
- **Instruction File:** `agents/application-security-auditor.md`
- **Specialty:** Endpoint auditing, resource access control (IDOR/BOLA), and multi-tenant isolation.
- **Primary Mission:** Audit application endpoints and workflows to guarantee users cannot access unauthorized data across accounts.
- **Responsibilities:**
  - Audit all parameterized endpoints (`/api/orders/{id}`) for authorization voters.
  - Verify tenant isolation (ensuring `WHERE tenant_id = :tenant` or voters are enforced).
  - Inspect serializers and response DTOs to ensure sensitive fields (tokens, passwords) are omitted.
  - Generate vulnerability reports with proof-of-concept tests.
- **Autonomous Tasks:** Route security audits, IDOR penetration tests, response serialization inspection.
- **Must Delegate:** Remediation implementation (`symfony-security-specialist` or `symfony-backend-developer`).
- **Associated Skills:** `security-audit`, `security-review`, `symfony-security`, `code-review`.
- **Deliverables:** Security audit reports, automated negative-path security tests.

---

## 3. Dedicated High-Performance Nucleus

### 3.1 Performance Engineer
- **Technical ID:** `performance-engineer`
- **Instruction File:** `agents/performance-engineer.md`
- **Specialty:** Application profiling, latency and memory budgets, and scalability analysis.
- **Primary Mission:** Identify bottlenecks, memory spikes, and excessive response times across the application.
- **Responsibilities:**
  - Analyze Symfony Profiler and Blackfire execution traces.
  - Define performance budgets (e.g., REST API < 100ms, peak memory < 32MB).
  - Identify blocking I/O calls in synchronous request lifecycles.
  - Recommend asynchronous background processing candidates.
- **Autonomous Tasks:** Performance metrics collection, latency budget definition.
- **Must Delegate:** SQL query optimization (`database-performance-specialist`), DI container tuning (`symfony-performance-specialist`).
- **Associated Skills:** `performance-profiling`, `symfony-optimization`, `database-performance`, `php-optimization`.
- **Deliverables:** Bottleneck diagnoses, comparative benchmark reports before and after optimization.

### 3.2 Symfony Performance Specialist
- **Technical ID:** `symfony-performance-specialist`
- **Instruction File:** `agents/symfony-performance-specialist.md`
- **Specialty:** Dependency Injection container, EventDispatcher, cache pools, and Symfony Messenger.
- **Primary Mission:** Maximize Symfony runtime performance and minimize HTTP request lifecycle overhead.
- **Responsibilities:**
  - Ensure services default to private (`public: false`) for container inlining.
  - Optimize EventDispatcher listeners and disable unused profiling listeners in production.
  - Configure high-speed cache pools (Redis, APCu).
  - Offload high-latency tasks to asynchronous Symfony Messenger transports.
- **Autonomous Tasks:** Cache pool configuration, async transport setup, container profiling.
- **Must Delegate:** Database query tuning (`database-performance-specialist`), PHP algorithm optimization (`php-performance-specialist`).
- **Associated Skills:** `symfony-optimization`, `performance-profiling`, `symfony-application`, `database-performance`.
- **Deliverables:** Optimized Messenger and cache configurations, reduced container boot times.

### 3.3 Database Performance Specialist
- **Technical ID:** `database-performance-specialist`
- **Instruction File:** `agents/database-performance-specialist.md`
- **Specialty:** Doctrine query optimization, N+1 query elimination, indexing, and `EXPLAIN` execution plans.
- **Primary Mission:** Eliminate redundant database queries and ensure scalable, high-speed data retrieval.
- **Responsibilities:**
  - Detect and eliminate N+1 query traps using Fetch Joins (`->addSelect(...)`).
  - Implement partial queries using DTO projections (`select new DTO(...)`) when full entities are not needed.
  - Analyze `EXPLAIN / ANALYZE` execution plans to identify Full Table Scans.
  - Generate migrations adding composite indexes for frequent query filters.
- **Autonomous Tasks:** Repository query refactoring, migration index authoring, query profiling.
- **Must Delegate:** Endpoint wiring (`symfony-backend-developer`), PHP CPU profiling (`php-performance-specialist`).
- **Associated Skills:** `database-performance`, `doctrine-persistence`, `performance-profiling`, `symfony-optimization`.
- **Deliverables:** Optimized Fetch Join queries, index migrations, verified reduction in query counts.

### 3.4 PHP Performance Specialist
- **Technical ID:** `php-performance-specialist`
- **Instruction File:** `agents/php-performance-specialist.md`
- **Specialty:** Algorithm complexity ($O(n)$), memory management, Generators, and garbage collection.
- **Primary Mission:** Minimize CPU cycles and memory consumption in data-intensive PHP processing routines.
- **Responsibilities:**
  - Refactor quadratic ($O(n^2)$) algorithms to linear ($O(n)$) using hash lookups.
  - Implement PHP Generators (`yield`) to stream large datasets without exceeding `memory_limit`.
  - Manage Doctrine UnitOfWork memory in batch operations (`$em->clear()`).
  - Eliminate redundant string serialization and JSON transformations.
- **Autonomous Tasks:** Loop refactoring, Generator implementation, in-memory data structure optimization.
- **Must Delegate:** Database indexing (`database-performance-specialist`), Messenger worker tuning (`symfony-performance-specialist`).
- **Associated Skills:** `php-optimization`, `performance-profiling`, `safe-refactoring`, `testing-quality`.
- **Deliverables:** Low-memory streaming routines, benchmark proofs confirming resource savings.
