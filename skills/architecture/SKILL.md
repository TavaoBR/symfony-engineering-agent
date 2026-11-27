---
name: architecture
description: Invoke the Symfony Architect to design hexagonal architecture, service contracts, immutable DTOs, and ADRs.
---

# /architecture — Symfony Architect Command

Act as the **Symfony Architect** (`agents/symfony-architect.md`) using `skills/architecture-design` and `skills/api-design`.

## Objective
Analyze, design, and define software architecture for the specified domain:
1. Establish layer boundaries and domain decoupling.
2. Define input and output DTO contracts (`src/DTO/Request/`, `src/DTO/Response/`).
3. Define application service interfaces (`src/Contract/` or `src/Service/`).
4. Prevent Doctrine entities from leaking directly into API controller responses.
5. Record architectural decisions in `docs/DECISIONS.md` (ADR).

$ARGUMENTS
