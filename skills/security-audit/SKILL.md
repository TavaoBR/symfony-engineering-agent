---
name: security-audit
description: Audit application endpoints for IDOR/BOLA vulnerabilities, verify tenant isolation, and inspect response data.
---

# Skill: Application Security Auditing (Security Audit)

## 1. Objective and Responsibility
Execute systematic, static, and dynamic security audits across code, HTTP endpoints, data models, and business workflows in Symfony applications, identifying authorization bypasses, data exposure vulnerabilities, multi-tenant isolation flaws, and issuing formal risk reports scored with CVSS.

## 2. Scope and Limitations
- **Scope:** Route and controller auditing, permission matrices, IDOR/BOLA verification in Doctrine repositories, multi-tenant isolation, dependency auditing via `composer audit`, and sensitive data serialization checks.
- **Limitations:** Focuses on diagnosis, proof-of-concept testing, and remediation plans (remediation implementation is coordinated with author agents).

## 3. Required Technical Knowledge
- OWASP Web Security Testing Guide (WSTG).
- Common Vulnerability Scoring System (CVSS v3.1 / v4.0).
- PHP Static Analysis and AST security inspection tools.
- Doctrine SQL filters for multi-tenant isolation (`SQLFilter`).
- Dependency auditing tooling (`composer audit`).

## 4. Development Standards and Conventions
- Audit reports must rank findings into: Critical, High, Medium, Low, or Informational.
- Every reported vulnerability must include:
  1. Clear defect title and CWE classification.
  2. Exact location (file and line numbers).
  3. Reproducible Proof of Concept (PoC).
  4. Business impact analysis.
  5. Precise technical remediation steps.

## 5. Architectural Rules and Code Organization
- Audits must map the complete access chain:
  `[HTTP Route] -> [Method] -> [Firewall] -> [IsGranted / Voter] -> [Tenant Filter in Repository]`.
- If an endpoint accepts an ID in its path (e.g., `{id}`, `{uuid}`) and loads the entity without a Voter or tenant scope, mark it as a HIGH risk IDOR vulnerability.

## 6. PHP and Symfony Best Practices
- **Serialization Audits:** Check serialized entities in controllers. Ensure fields like `password`, `salt`, `resetToken`, and `ssn` use `#[Ignore]` to prevent accidental exposure in API responses.
- **Multi-Tenant Isolation:** Verify that Doctrine queries either apply explicit `tenant_id` parameters or use an active `Doctrine\ORM\Query\Filter\SQLFilter`.

## 7. Examples of Correct and Incorrect Implementations

### Example: Multi-Tenant Data Isolation Audit
**Incorrect (Critical multi-tenant data leak):**
```php
// Incorrect: Allows viewing reports of any organization by changing the ID in the URL
#[Route('/api/organizations/{orgId}/reports/{reportId}', methods: ['GET'])]
public function getReport(int $reportId, ReportRepository $repository): JsonResponse
{
    // Security flaw: ignores orgId and queries strictly by global primary key!
    $report = $repository->find($reportId);
    if (!$report) {
        throw new NotFoundHttpException();
    }
    return $this->json($report);
}
```

**Correct (Strict organization ownership validation and scoped query):**
```php
#[Route('/api/organizations/{orgId}/reports/{reportId}', methods: ['GET'])]
public function getReport(
    int $orgId,
    int $reportId,
    ReportRepository $repository,
    #[CurrentUser] User $user
): JsonResponse {
    // 1. Verify authenticated user belongs to requested organization
    if (!$user->belongsToOrganization($orgId)) {
        throw new AccessDeniedHttpException('Access denied to specified organization.');
    }

    // 2. Query scoped strictly to report ID AND organization ID
    $report = $repository->findOneBy([
        'id' => $reportId,
        'organization' => $orgId,
    ]);

    if (!$report) {
        throw new NotFoundHttpException('Report not found in specified organization.');
    }

    return $this->json($report, context: ['groups' => ['report:read']]);
}
```

## 8. Technical Decision Criteria
- **Severity Ranking:**
  - **Critical:** Remote Code Execution (RCE), direct SQL Injection, unauthenticated admin bypass.
  - **High:** IDOR with read/write access to sensitive tenant data, authorization bypass.
  - **Medium:** CSRF on non-critical actions, missing rate limits on login, production stack trace leaks.
  - **Low:** Missing security headers (HSTS, CSP), subtle user enumeration.

## 9. Validation and Testing Procedures
- Run automated SAST security scan (Semgrep / AST):
  ```bash
  php templates/security/sast-scanner.php --strict
  ```
- Run automated dependency CVE audit and remediation plan:
  ```bash
  php templates/security/cve-remediator.php
  # Or official Composer audit directly:
  composer audit
  ```
- Follow the 5-phase operational workflow for vulnerability remediation:
  - See `workflows/cve-remediation.md` and `docs/SAST_AND_CVE_REMEDIATION.md`.
- Inspect routes and firewalls:
  ```bash
  php bin/console debug:router
  php bin/console debug:firewall
  ```
- Execute regression security tests reproducing vulnerabilities before and after remediation.

## 10. Error Handling and Exceptions
- Document findings with objective logs and reproduction tests without modifying production data.

## 11. Objective Quality and Completion Criteria
- 100% of public and authenticated endpoints documented in the audit matrix.
- All Critical and High findings accompanied by reproducible PoC tests.
- Prioritized remediation plan validated with development specialists.
