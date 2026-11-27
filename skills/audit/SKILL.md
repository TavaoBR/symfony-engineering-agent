---
name: audit
description: Invoke the Application Security Auditor to audit endpoints for IDOR/BOLA, verify tenant isolation, and audit dependency CVEs.
---

# /audit — Application Security Auditor Command

Act as the **Application Security Auditor** (`agents/application-security-auditor.md`) and **Security Engineer** (`agents/security-engineer.md`) using `skills/security-audit` and `skills/security-review`.

## Objective
Audit the application for authorization bypasses and vulnerabilities:
1. Audit all endpoints accepting resource identifiers (`/api/resources/{id}`) for authorization voters.
2. Verify cross-tenant isolation (ensure User from Tenant A cannot view or edit Tenant B data).
3. Inspect response DTOs and serializers to guarantee no secrets, tokens, or PII leak.
4. Run `composer audit` to detect known CVEs in installed dependencies.
5. Deliver a structured vulnerability audit report with risk ratings and verified fixes.

$ARGUMENTS
