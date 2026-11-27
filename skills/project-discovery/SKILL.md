---
name: project-discovery
description: Non-destructive discovery of dependencies, routes, entities, and test setups in existing Symfony codebases.
---

# Skill: Symfony Project Discovery and Analysis (Project Discovery)

## 1. Objective and Responsibility
Perform factual, structural, non-destructive discovery and diagnostic mapping of an existing Symfony codebase before making changes, identifying exact PHP and bundle versions, adopted architecture, coding patterns, dependencies, routes, persistence, test tooling, and potential technical risks.

## 2. Scope and Limitations
- **Scope:** Non-destructive inspection of `composer.json`, `composer.lock`, `symfony.lock`, `config/packages/`, `src/`, route definitions, entities, and CI/test setups.
- **Limitations:** Never edits or touches files during discovery. Never assumes versions or packages without direct file evidence.

## 3. Required Technical Knowledge
- Symfony Flex conventions and standard directory layout (`assets`, `bin`, `config`, `migrations`, `public`, `src`, `templates`, `tests`, `var`, `vendor`).
- Semver version constraints in `composer.json` and locked hashes in `composer.lock`.
- YAML and PHP service configuration in `config/`.
- Symfony Console diagnostic commands (`about`, `debug:router`, `debug:container`, `debug:autowiring`).

## 4. Development Standards and Conventions
- Analysis must be strictly evidence-based:
  1. Exact PHP version: declared in `composer.json` and active in `php -v`.
  2. Symfony version: confirmed via `symfony.lock` and `composer.lock`.
  3. Installed bundles: verified in `config/bundles.php`.
  4. Persistence layer: Doctrine ORM, ODM, or raw DBAL.
  5. Test suite: PHPUnit, Pest, or Behat configured in `phpunit.xml.dist`.

## 5. Architectural Rules and Code Organization
- Discovery maps the actual architectural conventions used by the project (Hexagonal, MVC, DDD, CQRS) rather than imposing an unrequested theoretical pattern.

## 6. PHP and Symfony Best Practices
- **Never Print Secrets:** Never output credentials, API keys, or raw `.env` / `.env.local` contents during inspection.
- **Bundle Verification:** Check for key bundles:
  - `DoctrineBundle` / `DoctrineMigrationsBundle` (Database)
  - `SecurityBundle` (Auth/Access Control)
  - `FrameworkBundle` (Messenger, HttpClient, Cache)
  - `MonologBundle` (Logging)
  - `MakerBundle` (Dev code generation)

## 7. Examples of Correct and Incorrect Implementations

### Example: Version and Convention Diagnostics
**Incorrect (Unverified assumption):**
```text
Incorrect: "I assume the project uses Symfony 7 and MySQL because that's typical."
Risk: If the project is actually on Symfony 6.4 LTS with PostgreSQL, new code will break in production!
```

**Correct (Evidence-backed factual report):**
```text
Verified Facts:
1. PHP: "php": ">=8.2" declared in composer.json; local runtime is PHP 8.2.14.
2. Symfony: v6.4.12 LTS verified in composer.lock and symfony.lock.
3. Database: PostgreSQL 15 identified in config/packages/doctrine.yaml (server_version: '15').
4. Routing Structure: Native PHP 8 attributes (#[Route]) in src/Controller/Api/.
5. Testing: PHPUnit 10 configured with phpunit.xml.dist and DAMADoctrineTestBundle active.
6. Identified Risks: composer.lock is 4 months old; 2 dependencies have known CVEs in composer audit.
```

## 8. Technical Decision Criteria
- If Symfony 6.4 LTS is detected: adhere to stable PHP 8.2 features and 6.4-compatible components.
- If Symfony 7.x is detected: leverage modern PHP 8.2/8.3+ features and Symfony 7 components (`#[MapQueryString]`, `#[MapUploadedFile]`).

## 9. Validation and Testing Procedures
- Run standard console diagnostic commands:
  ```bash
  php -v
  composer --version
  composer validate --no-check-publish
  php bin/console about
  php bin/console debug:router --show-controllers
  ```

## 10. Error Handling and Exceptions
- If `bin/console` cannot execute (e.g., missing `vendor/`), perform static inspection of `composer.json` and clearly document the missing vendor in the report.

## 11. Objective Quality and Completion Criteria
- Discovery report produced containing: verified versions, active bundles, mapped architecture, test commands, and risk matrix.
- Zero files altered during discovery.
