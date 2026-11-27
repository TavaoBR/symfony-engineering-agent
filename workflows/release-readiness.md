# Workflow: Release Readiness Assessment

1. **Project Analysis (Project Analyst):** Inspects stack, CI status, environment configuration, and repository health.
2. **Code Review (Code Reviewer):** Reviews `git diff` and recent changes against anti-patterns and performance standards.
3. **Security Audit (Security Auditor):** Audits attack surfaces, authorization voters, and data exposure risks in scope.
4. **Quality & Test Verification (Quality Engineer):** Runs automated test suites, PHPStan static analysis, and container linters.
5. **Database Review (Database Engineer):** Reviews pending migrations and schema compatibility risks without applying changes to live databases.
6. **Documentation Audit (Technical Writer):** Verifies accuracy of setup instructions, API contracts, and ADR records.
7. **Consolidation (Orchestrator):** Consolidates blockers, residual risks, and verification evidence into a clear report.

This assessment does not authorize production deployment. Never declare an application "production ready" without verifying infrastructure, secrets management, database backups, observability, rollback procedures, capacity, and operational runbooks.
