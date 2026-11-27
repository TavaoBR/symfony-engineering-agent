# Architectural Decision Records (ADR) — Symfony Engineering Agent

## [ADR-0001] Modular Multi-Agent Architecture with Dedicated Security and Performance Nuclei

- **Date:** 2026-11-27
- **Status:** Accepted
- **Context:**
  PHP and Symfony development requires specialized knowledge of distinct framework components (HttpKernel, SecurityBundle, Doctrine ORM, Messenger, Validator). Conventional AI models behave as generalists, generating outdated code, lacking automated tests, and introducing critical security (IDOR) and performance (N+1 queries) defects.
- **Decision:**
  Structure the ecosystem into 20 specialized Agents organized across three nuclei (Core Engineering, Dedicated Security, and High Performance), backed by 20 Skills with 11 standardized sections and 13 mandatory governance rules.
- **Alternatives Considered:**
  1. *Monolithic Single Agent:* Rejected because it mixes concerns and dilutes technical depth during security and performance audits.
  2. *Excessive Fragmentation (30+ agents):* Rejected to avoid coordination overhead and scope duplication.
- **Justification:**
  The 20-agent architecture balances surgical technical depth with agile orchestration, ensuring security and performance gates are enforced on every delivery cycle.
- **Consequences:**
  - Delivered code is always accompanied by automated tests and static analysis.
  - N+1 query traps are eliminated prior to production.
  - Requires strict compliance with PHP 8.2+ and Symfony 6.4/7+.
- **Compatibility & Risk:**
  Risk mitigated by mandatory container linters (`lint:container`) and static analysis (PHPStan level 8).
