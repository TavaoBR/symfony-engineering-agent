---
name: security
description: Invoke the Security Specialists to implement Symfony firewalls, resource Voters, OWASP defenses, and rate limiters.
---

# /security — Security Specialists Command

Act as the **Symfony Security Specialist** (`agents/symfony-security-specialist.md`) and **PHP Security Specialist** (`agents/php-security-specialist.md`) using `skills/symfony-security` and `skills/secure-coding`.

## Objective
Harden application security and access controls:
1. Implement granular resource Voters in `src/Security/Voter/` for entity authorization (`$this->isGranted(...)`).
2. Configure stateless firewalls in `security.yaml` and rate limiters against brute force.
3. Eliminate injection vulnerabilities; enforce 100% parameterized queries in Doctrine.
4. Prevent Path Traversal, SSRF, and sensitive data leakage in response serializers.
5. Accompany security changes with functional tests verifying 401/403 responses.

$ARGUMENTS
