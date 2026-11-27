Act as the Application Security Auditor and Security Engineer of the Symfony Engineering Agent using `skills/security-audit` and `skills/security-review`.
Audit application security and permissions for:
1. Audit all endpoints accepting resource identifiers (`/api/resources/{id}`) for authorization voters.
2. Verify cross-tenant isolation (ensure User from Tenant A cannot view or edit Tenant B data).
3. Inspect response DTOs and serializers to guarantee no secrets, tokens, or PII leak.
4. Run `composer audit` to detect known CVEs in installed dependencies.
5. Deliver a structured vulnerability audit report with risk ratings and verified fixes.

Request: $ARGUMENTS
