---
name: technical-documentation
description: Maintain technical documentation, Architecture Decision Records (ADRs), and OpenAPI specifications.
---

# Skill: Engineering Technical Documentation (Technical Documentation)

## 1. Objective and Responsibility
Author, maintain, and update Symfony engineering documentation — including `README.md`, architecture documentation (`docs/ARCHITECTURE.md`), Architecture Decision Records (`docs/DECISIONS.md`), API contracts, and operational runbooks —, ensuring documentation is precise, reproducible, and strictly reflects actual code.

## 2. Scope and Limitations
- **Scope:** Creating and maintaining `README.md`, `docs/ARCHITECTURE.md`, `docs/DECISIONS.md`, environment variable documentation in `.env.example`, and troubleshooting guides.
- **Limitations:** Never documents non-existent features or future plans as if completed. Never includes real credentials or personal data.

## 3. Required Technical Knowledge
- Markdown and GitHub Flavored Markdown (GFM).
- Architecture Decision Records (ADRs) using the Michael Nygard format (Context, Decision, Consequences, Alternatives).
- Execution command specifications and real prerequisites (PHP, Docker, Composer).
- OpenAPI / Swagger contract specifications.

## 4. Development Standards and Conventions
- Every documented command must be tested in the environment to ensure it works exactly as written.
- Every significant architectural decision (database selection, asynchronous messaging, authentication strategy) MUST be recorded in `docs/DECISIONS.md`.
- Maintain concise, objective technical language without decorative filler.

## 5. Architectural Rules and Code Organization
- General documentation and setup: `README.md` at repository root.
- Architecture and decisions: `docs/` (`docs/ARCHITECTURE.md`, `docs/DECISIONS.md`).
- API documentation: OpenAPI at `docs/openapi.yaml` or generated via NelmioApiDocBundle.

## 6. PHP and Symfony Best Practices
- **Documented `.env.example`:** Every variable must include comments explaining its purpose and a safe default value:
  ```bash
  ###> doctrine/doctrine-bundle ###
  # Example PostgreSQL: postgresql://app_user:app_password@127.0.0.1:5432/app_db?serverVersion=15&charset=utf8
  DATABASE_URL="postgresql://app_user:app_password@127.0.0.1:5432/app_db?serverVersion=15&charset=utf8"
  ###< doctrine/doctrine-bundle ###
  ```
- **Mermaid Diagrams:** Use `mermaid` fenced blocks for complex asynchronous flows and entity lifecycle states.

## 7. Examples of Correct and Incorrect Implementations

### Example: Architecture Decision Record (ADR)
**Incorrect (Informal note without context or technical trade-offs):**
```markdown
# Decision: We will use Messenger
We decided to use Symfony Messenger because it is modern.
```

**Correct (Formal ADR following Nygard format in docs/DECISIONS.md):**
```markdown
# ADR 003: Adoption of Symfony Messenger for Asynchronous Billing Processing

## Status
Approved

## Context
Order billing and invoice generation previously performed synchronous calls to external payment gateways and mailer services. During traffic spikes, response times for `POST /api/v1/orders` exceeded 4,000ms, causing client HTTP timeouts and cascading worker exhaustion.

## Decision
We adopt Symfony Messenger with asynchronous transport (Doctrine Transport in local dev, Redis/AMQP in production). The controller will persist the order entity and immediately dispatch `ProcessOrderBillingMessage`, returning HTTP 202 Accepted to the client.

## Consequences
### Positive:
- API response time reduced from ~4,000ms to < 120ms (P95).
- Resilience against temporary gateway downtime via automatic retries (`retry_strategy`).
### Negative:
- Operational overhead: requires running and monitoring background workers (`messenger:consume`) in production.
- Eventual consistency: clients must poll or listen to webhooks for final order confirmation status.
```

## 8. Technical Decision Criteria
- **When to create an ADR:** Whenever a technical choice is difficult to reverse, impacts hosting cost, alters developer workflows, or replaces a core architectural library.

## 9. Validation and Testing Procedures
- Verify all relative markdown file links are valid.
- Run setup instructions from `README.md` in a clean environment to ensure reproduction works without undocumented steps.

## 10. Error Handling and Exceptions
- Include a "Troubleshooting" section in `README.md` with resolutions for common setup errors (e.g., permissions in `var/cache/`, missing PHP extensions).

## 11. Objective Quality and Completion Criteria
- `README.md` allows cloning, installing, and executing tests in under 10 minutes.
- `docs/ARCHITECTURE.md` describes actual implemented layers and data flows.
- `docs/DECISIONS.md` contains current ADRs for all major system choices.
