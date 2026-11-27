# System Architecture — Symfony Engineering Agent

## 1. Context and Overview
- **Objective:** Provide an autonomous, specialized team of AI engineering agents for PHP 8.2+ and Symfony 6.4 LTS / 7.x+.
- **Application Type:** Multi-agent AI software engineering ecosystem with governance, actionable skills, and event-driven orchestration.
- **Scope:** Backend development, RESTful APIs, Doctrine ORM persistence, automated testing, performance optimization, and OWASP defensive security.

## 2. Verified Technology Stack
- **Language:** PHP 8.2+ with strict typing (`declare(strict_types=1);`), `readonly class`, native attributes, and backed enums.
- **Framework:** Symfony 6.4 LTS / Symfony 7.x+.
- **Persistence:** Doctrine ORM 3+, Doctrine Migrations, PostgreSQL / MySQL.
- **Testing & Quality:** PHPUnit 10+, `WebTestCase`, PHPStan (level 8+), Symfony Container Linters.

## 3. Layer Separation & Responsibilities
1. **Governance (`rules/`):** 13 mandatory policy files defining operational boundaries, anti-AI patterns, and code standards.
2. **Operational Procedures (`skills/`):** 20 standardized skills in 11 sections with practical examples and quality criteria.
3. **Specialized Roles (`agents/`):** 20 roles structured in 6 sections, divided across Core Engineering, Security, and Performance.
4. **Workflows (`workflows/`):** 11 end-to-end operational routines guiding implementations, refactoring, bug fixes, and reviews.
5. **Templates (`templates/`):** Standardized templates for plans, reports, ADRs, and architecture.
6. **Documentation (`docs/`):** In-depth reference manuals for developers and ecosystem administrators.

## 4. Security & Isolation Controls
- Mitigation against OWASP Top 10 vulnerabilities.
- Mandatory granular authorization via Voters on all endpoints accepting resource identifiers.
- Strict input validation via typed DTOs with Symfony Validator constraints.

## 5. Performance & Scalability
- Zero tolerance for N+1 queries via explicit Fetch Joins in Doctrine repositories.
- Decoupling of high-latency operations via asynchronous Symfony Messenger.
- Native cache pools and empirical profiling verification.
