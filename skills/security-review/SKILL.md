---
name: security-review
description: Audit third-party dependencies for known CVEs using composer audit and review security permissions.
---

# Skill: Security Review and Governance (Security Review)

## 1. Objective and Responsibility
Coordinate overarching security governance and review across Symfony applications, serving as the unifying procedure orchestrating vulnerability audits, framework defenses, and secure PHP coding practices.

## 2. Scope and Limitations
- **Scope:** End-to-end security review: authentication, resource authorization, input sanitization, OWASP injection prevention, secrets management, and regulatory compliance.
- **Limitations:** Delegates specialized implementations to companion skills: `symfony-security` (authenticators/voters), `secure-coding` (low-level PHP defenses), and `security-audit` (endpoint matrices and CVSS reports).

## 3. Required Technical Knowledge
- OWASP Top 10 and ASVS (Application Security Verification Standard).
- Symfony security architecture and cryptographic standards.
- SAST/DAST report interpretation and vulnerability classification.

## 4. Development Standards and Conventions
- No feature touching user data can be considered complete without passing verification under this skill.
- All state-mutating operations require explicit authentication and contextual authorization via Voters.

## 5. Architectural Rules and Code Organization
- Multi-tier layered defense:
  1. Perimeter: Firewall in `security.yaml` and Rate Limiting.
  2. Protocol: Strictly validated DTOs in controllers.
  3. Application: Granular Voters checking resource instances.
  4. Persistence: Parameterized queries with database relational constraints.

## 6. PHP and Symfony Best Practices
- Execute `composer audit` regularly to guarantee no dependencies contain known CVEs.
- Store API keys as cryptographic hashes (e.g., SHA-256) in the database, never in plain text.

## 7. Examples of Correct and Incorrect Implementations

### Example: API Key Storage and Constant-Time Verification
**Incorrect (Plain text storage and timing-attack-vulnerable check):**
```php
// Incorrect: Plain text key in database; vulnerable to database dumps and timing attacks
public function authenticate(string $apiKey): ?User
{
    return $this->userRepository->findOneBy(['apiKey' => $apiKey]);
}
```

**Correct (SHA-256 hash storage and constant-time comparison):**
```php
public function authenticate(string $rawApiKey): ?User
{
    $hashedKey = hash('sha256', $rawApiKey);

    $user = $this->userRepository->findOneBy(['apiKeyHash' => $hashedKey]);
    if (!$user) {
        return null;
    }

    // Additional constant-time comparison
    if (!hash_equals($user->getApiKeyHash(), $hashedKey)) {
        return null;
    }

    return $user;
}
```

## 8. Technical Decision Criteria
- **Risk Gate:** Block release deployments if any vulnerability of High or Critical severity is identified.

## 9. Validation and Testing Procedures
- Run automated security test suites:
  ```bash
  composer audit
  php bin/phpunit --testsuite=security
  ```

## 10. Error Handling and Exceptions
- Error responses must never leak stack traces or database schema details in production.

## 11. Objective Quality and Completion Criteria
- Security review checklist approved for all modified routes and components.
- Zero unresolved High or Critical security findings.
