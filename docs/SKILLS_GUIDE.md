# In-Depth Skill Specifications — Symfony Engineering Agent

This document provides operational specifications for all 20 technical skills within the **Symfony Engineering Agent** ecosystem, categorized by engineering domain.

---

## 1. Architecture & System Design

### 1.1 `skills/architecture-design`
- **Name:** Architecture Design
- **Path:** `skills/architecture-design/SKILL.md`
- **Objective:** Establish layer boundaries, domain isolation, and software contracts in Symfony applications.
- **Technical Stack:** Clean Architecture, Ports & Adapters, SOLID principles, immutable DTOs, Service Contracts.
- **Mandatory Standards:**
  - Skinny controllers delegating all domain logic to application services.
  - Mandatory DTOs for request payloads and response outputs.
  - Never expose Doctrine entities directly as API controller returns.
- **When to Use:** At the start of new features, module creation, or microservice architecture definition.
- **Caller Agents:** `symfony-architect`, `orchestrator`, `refactoring-specialist`, `project-analyst`.
- **Validation Criteria:** Interface contracts in place; dependencies pointing strictly inward toward abstractions.

### 1.2 `skills/project-bootstrap`
- **Name:** Project Bootstrap
- **Path:** `skills/project-bootstrap/SKILL.md`
- **Objective:** Guide standardized greenfield Symfony application creation using official skeletons.
- **Technical Stack:** Composer, Symfony Flex, official skeleton (`symfony/skeleton:^7.0`), core bundles.
- **Mandatory Standards:**
  - Never invent unofficial skeletons.
  - Structured `.env.example` without real secrets.
  - Immediate setup of PHPUnit and PHPStan (level 8).
- **When to Use:** Initializing a brand-new Symfony application from scratch.
- **Caller Agents:** `project-bootstrapper`.
- **Validation Criteria:** `composer validate` passes; `php bin/console about` outputs operational environment.

### 1.3 `skills/project-discovery`
- **Name:** Project Discovery
- **Path:** `skills/project-discovery/SKILL.md`
- **Objective:** Non-destructive mapping and diagnostic inspection of existing codebases.
- **Technical Stack:** Inspection of `composer.json`, `composer.lock`, route mapping, service inspection.
- **Mandatory Standards:**
  - Zero code modifications during the diagnostic phase.
  - Factual collection of versions (PHP, Symfony, packages).
- **When to Use:** First encounter with an existing codebase before any code modification.
- **Caller Agents:** `project-analyst`, `orchestrator`, `debugger`, `refactoring-specialist`.
- **Validation Criteria:** Comprehensive discovery report with confirmed stack, route inventory, and conventions.

---

## 2. Backend & Symfony Application Development

### 2.1 `skills/symfony-application`
- **Name:** Symfony Application
- **Path:** `skills/symfony-application/SKILL.md`
- **Objective:** Build routes, modern controllers, services with constructor DI, and console commands.
- **Technical Stack:** PHP attributes (`#[Route]`, `#[MapRequestPayload]`, `#[AsCommand]`), autowiring, Symfony Serializer.
- **Mandatory Standards:**
  - Controllers must only delegate to application services.
  - Constructor dependency injection with strictly typed properties.
  - Immutable DTOs (`readonly class`).
- **When to Use:** Implementing new endpoints, business rules, or CLI commands.
- **Caller Agents:** `symfony-backend-developer`, `symfony-architect`, `integration-specialist`.
- **Validation Criteria:** `php bin/console lint:container` passes without warnings.

### 2.2 `skills/api-design`
- **Name:** API Design
- **Path:** `skills/api-design/SKILL.md`
- **Objective:** Standardize HTTP status codes, error payloads (RFC 7807/9457), and pagination.
- **Technical Stack:** RESTful design, RFC 7807/9457 (Problem Details), OpenAPI 3.0, JSON content negotiation.
- **Mandatory Standards:**
  - 4xx/5xx errors must return `application/problem+json`.
  - Semantic HTTP status codes (200 OK, 201 Created, 204 No Content, 422 Unprocessable Entity).
- **When to Use:** Creating or modifying public or private API endpoints.
- **Caller Agents:** `symfony-architect`, `symfony-backend-developer`, `integration-specialist`.
- **Validation Criteria:** Functional tests asserting response payloads and status codes.

### 2.3 `skills/external-api-integration`
- **Name:** External API Integration
- **Path:** `skills/external-api-integration/SKILL.md`
- **Objective:** Communicate with third-party APIs and webhooks with resilience, isolation, and security.
- **Technical Stack:** `HttpClientInterface`, scoped clients, exponential backoff retries, `MockHttpClient`.
- **Mandatory Standards:**
  - Mandatory connection (<= 3s) and response (<= 10s) timeouts.
  - Webhooks must verify cryptographic signatures using `hash_equals()`.
  - Never log authorization headers or sensitive payload fields.
- **When to Use:** Integrating payment gateways, email providers, or external microservices.
- **Caller Agents:** `integration-specialist`, `symfony-backend-developer`.
- **Validation Criteria:** Tests with `MockHttpClient` covering success, timeout, and partner 5xx error scenarios.

---

## 3. Persistence & Database Layer (Doctrine ORM)

### 3.1 `skills/doctrine-persistence`
- **Name:** Doctrine Persistence
- **Path:** `skills/doctrine-persistence/SKILL.md`
- **Objective:** Entity mapping, consistent relationships, and reversible database migrations.
- **Technical Stack:** Doctrine ORM 3+, mapping attributes, QueryBuilder, Doctrine Migrations.
- **Mandatory Standards:**
  - Entity IDs using UUID v7 or appropriate integers.
  - Bidirectional relationships mapped only when strictly necessary.
  - Read queries encapsulated in dedicated Repositories, never in controllers.
- **When to Use:** Creating or altering tables, entities, relationships, and queries.
- **Caller Agents:** `database-engineer`, `database-performance-specialist`, `symfony-backend-developer`.
- **Validation Criteria:** `php bin/console doctrine:schema:validate` passes with mapping and sync green.

---

## 4. Quality, Testing & Maintenance

### 4.1 `skills/testing-quality`
- **Name:** Testing Quality
- **Path:** `skills/testing-quality/SKILL.md`
- **Objective:** Author and execute unit, integration, and HTTP functional tests.
- **Technical Stack:** PHPUnit 10+, `WebTestCase`, `KernelTestCase`, PHPStan (level 8+).
- **Mandatory Standards:**
  - Arrange-Act-Assert pattern across all tests.
  - Test happy path, validation failures (422), and access denied (403).
  - Never suppress strict type checking in tests.
- **When to Use:** Any new feature, bug fix, or refactoring.
- **Caller Agents:** `quality-engineer`, `symfony-backend-developer`, `debugger`, `refactoring-specialist`.
- **Validation Criteria:** `php bin/phpunit` 100% green; `phpstan` clean at level 8+.

### 4.2 `skills/debugging`
- **Name:** Debugging
- **Path:** `skills/debugging/SKILL.md`
- **Objective:** Identify root causes with empirical evidence and create reproduction tests.
- **Technical Stack:** Stack trace inspection, Monolog structured logs, deterministic reproduction.
- **Mandatory Standards:**
  - Do not apply fixes without reproducing the defect.
  - Create an automated test that fails prior to the fix and passes after.
- **When to Use:** Investigating exceptions, anomalies, or corrupted data.
- **Caller Agents:** `debugger`, `quality-engineer`, `symfony-backend-developer`.
- **Validation Criteria:** Passing regression test and verified root-cause analysis.

### 4.3 `skills/safe-refactoring`
- **Name:** Safe Refactoring
- **Path:** `skills/safe-refactoring/SKILL.md`
- **Objective:** Restructure existing code while strictly preserving observable behavior.
- **Technical Stack:** Martin Fowler refactoring patterns, impact analysis, baseline characterization tests.
- **Mandatory Standards:**
  - Never add new features during refactoring.
  - Execute tests incrementally after each micro-edit.
- **When to Use:** Reducing technical debt, simplifying complex classes, modernizing to PHP 8.2+.
- **Caller Agents:** `refactoring-specialist`, `php-performance-specialist`.
- **Validation Criteria:** Zero regressions in existing test suites; demonstrably cleaner design.

### 4.4 `skills/code-review`
- **Name:** Code Review
- **Path:** `skills/code-review/SKILL.md`
- **Objective:** Inspect diffs to detect architectural risks, AI anti-patterns, and regressions.
- **Technical Stack:** Differential code analysis, AI anti-pattern detection, Symfony coding standards.
- **Mandatory Standards:**
  - Evaluate security, performance, and maintainability impacts.
  - Cite file, line number, and concrete impact for every finding.
- **When to Use:** Prior to finalizing any feature, pull request, or release.
- **Caller Agents:** `code-reviewer`, `application-security-auditor`, `orchestrator`.
- **Validation Criteria:** Code review report with findings categorized by severity.

### 4.5 `skills/technical-documentation`
- **Name:** Technical Documentation
- **Path:** `skills/technical-documentation/SKILL.md`
- **Objective:** Produce reliable, clear, and synchronized technical documentation.
- **Technical Stack:** Architecture Decision Records (ADRs), OpenAPI documentation, architecture specs.
- **Mandatory Standards:**
  - Record only significant, real technical decisions.
  - Keep setup and execution instructions strictly reproducible.
- **When to Use:** Making significant architectural choices or completing major deliverables.
- **Caller Agents:** `technical-writer`, `orchestrator`, `symfony-architect`.
- **Validation Criteria:** Documentation accurately reflects codebase reality.

---

## 5. Security Engineering Nucleus

### 5.1 `skills/symfony-security`
- **Name:** Symfony Security
- **Path:** `skills/symfony-security/SKILL.md`
- **Objective:** Implement firewalls, authenticators, authorization voters, and rate limiters.
- **Technical Stack:** Symfony SecurityBundle, Voters, Access Control, Rate Limiter.
- **Mandatory Standards:**
  - Granular resource Voters for entity authorization (`POST_EDIT`, `PROJECT_DELETE`).
  - Stateless firewalls without active sessions for REST APIs.
- **When to Use:** Creating authenticated routes, protecting resources, or rate limiting endpoints.
- **Caller Agents:** `symfony-security-specialist`, `security-engineer`, `application-security-auditor`.
- **Validation Criteria:** Functional tests asserting 401 for unauthenticated and 403 for unauthorized requests.

### 5.2 `skills/secure-coding`
- **Name:** Secure Coding
- **Path:** `skills/secure-coding/SKILL.md`
- **Objective:** Mitigate OWASP Top 10 vulnerabilities (SQLi, XSS, SSRF, Path Traversal) in PHP.
- **Technical Stack:** Parameterized queries, escaping functions, strict type validation, secure hashing.
- **Mandatory Standards:**
  - Zero interpolation of untrusted input in SQL or DQL queries.
  - Strict type validation across all input properties.
- **When to Use:** Any code processing user input, handling file uploads, or querying remote URLs.
- **Caller Agents:** `php-security-specialist`, `security-engineer`, `code-reviewer`.
- **Validation Criteria:** Static security analysis passes; injection tests confirm defenses.

### 5.3 `skills/security-audit`
- **Name:** Security Audit
- **Path:** `skills/security-audit/SKILL.md`
- **Objective:** Audit endpoints for Broken Object Level Authorization (IDOR) and data exposure.
- **Technical Stack:** Vulnerability analysis, access control auditing, multi-tenant isolation verification.
- **Mandatory Standards:**
  - Verify that User from Tenant A cannot access records belonging to Tenant B.
  - Verify response DTOs omit sensitive fields (passwords, tokens, internal keys).
- **When to Use:** Pre-release security reviews or periodic security audits.
- **Caller Agents:** `application-security-auditor`, `security-engineer`.
- **Validation Criteria:** Audit report proving absence of IDOR and verified tenant isolation.

### 5.4 `skills/security-review`
- **Name:** Security Review
- **Path:** `skills/security-review/SKILL.md`
- **Objective:** Audit diffs for security regressions and evaluate third-party Composer packages.
- **Technical Stack:** `composer audit`, CVE databases, permission checks, session security.
- **Mandatory Standards:**
  - `composer audit` must run clean without unmitigated critical or high CVEs.
- **When to Use:** Prior to authorizing code merges involving sensitive flows or dependencies.
- **Caller Agents:** `security-engineer`, `application-security-auditor`, `code-reviewer`.
- **Validation Criteria:** `composer audit` passing with zero unaddressed vulnerabilities.

---

## 6. High-Performance Engineering Nucleus

### 6.1 `skills/performance-profiling`
- **Name:** Performance Profiling
- **Path:** `skills/performance-profiling/SKILL.md`
- **Objective:** Measure execution latency, memory footprint, and I/O bottlenecks.
- **Technical Stack:** Symfony Profiler, Blackfire, latency metric collection, memory tracking.
- **Mandatory Standards:**
  - Measure metrics before and after applying optimization edits.
  - Distinguish whether bottlenecks reside in database queries, CPU routines, or external I/O.
- **When to Use:** Investigating user-reported latency or prior to high-traffic releases.
- **Caller Agents:** `performance-engineer`, `symfony-performance-specialist`, `database-performance-specialist`.
- **Validation Criteria:** Quantitative proof of latency reduction and memory savings.

### 6.2 `skills/symfony-optimization`
- **Name:** Symfony Optimization
- **Path:** `skills/symfony-optimization/SKILL.md`
- **Objective:** Optimize DI container, EventDispatcher, cache pools, and async messaging.
- **Technical Stack:** Symfony Cache (`cache.adapter.redis`), asynchronous Symfony Messenger, container compilation.
- **Mandatory Standards:**
  - High-latency tasks (emails, webhooks) must be dispatched asynchronously.
  - Services configured as private (`public: false`).
- **When to Use:** Optimizing application boot time and request throughput.
- **Caller Agents:** `symfony-performance-specialist`, `performance-engineer`.
- **Validation Criteria:** Reduced request latency and verified background worker processing.

### 6.3 `skills/database-performance`
- **Name:** Database Performance
- **Path:** `skills/database-performance/SKILL.md`
- **Objective:** Eliminate N+1 query traps, optimize indexes, and inspect SQL `EXPLAIN` plans.
- **Technical Stack:** Doctrine Fetch Joins (`addSelect`), `EXPLAIN ANALYZE`, composite indexing.
- **Mandatory Standards:**
  - Queries loading related entities for display must use explicit Fetch Joins.
  - List queries must enforce pagination (`setMaxResults()`).
- **When to Use:** Loading entities with ManyToOne or OneToMany relationships in lists or APIs.
- **Caller Agents:** `database-performance-specialist`, `database-engineer`, `performance-engineer`.
- **Validation Criteria:** Query count per request reduced to a constant ($O(1)$) regardless of dataset size.

### 6.4 `skills/php-optimization`
- **Name:** PHP Optimization
- **Path:** `skills/php-optimization/SKILL.md`
- **Objective:** Reduce algorithmic complexity, save memory, and stream data via Generators.
- **Technical Stack:** PHP Generators (`yield`), Big-O complexity, `$em->clear()`, memory buffers.
- **Mandatory Standards:**
  - Batch exports and data processing must use `yield` and periodic UnitOfWork clearing.
  - Avoid $O(n)$ function calls inside loops.
- **When to Use:** Mass data import/export scripts or heavy matrix processing.
- **Caller Agents:** `php-performance-specialist`, `performance-engineer`.
- **Validation Criteria:** Batch processing runs within memory budgets without triggering memory limit errors.
