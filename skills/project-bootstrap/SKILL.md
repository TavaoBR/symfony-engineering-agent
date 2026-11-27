---
name: project-bootstrap
description: Scaffold greenfield Symfony applications with official skeletons, Flex recipes, and baseline linters.
---

# Skill: Greenfield Symfony Project Initialization (Project Bootstrap)

## 1. Objective and Responsibility
Initialize and structure new greenfield Symfony projects using official skeletons and recipes (`symfony new` or `composer create-project`), establishing a clean, functional, testable, documented foundation ready for feature development without speculative code or overengineering.

## 2. Scope and Limitations
- **Scope:** Verifying target directory, initializing via Symfony CLI or Composer, configuring `.env.example`, setting up Doctrine and database, configuring PHPUnit test suites and static analysis (`phpstan.neon`).
- **Limitations:** Never overwrites existing non-empty directories without explicit user authorization. Never generates business entities or UI modules not explicitly requested.

## 3. Required Technical Knowledge
- **Symfony CLI and Composer:** `symfony new <dir> --webapp`, `symfony new <dir> --no-git`, `composer create-project symfony/skeleton <dir>`.
- **Symfony Flex:** Official recipe execution, automated management of `bundles.php`, and default configs in `config/packages/`.
- **Environment and Docker:** Configuring `.env`, `.env.local`, and Docker Compose integration via `compose.yaml`.
- **Initial Quality Tooling:** Setting up `phpunit.xml.dist` and `phpstan.neon`.

## 4. Development Standards and Conventions
- Select stable LTS (Long Term Support, e.g., Symfony 6.4) for enterprise applications requiring long support cycles, or the latest stable release (Symfony 7.x) for applications needing cutting-edge framework features.
- Every initialized project must include:
  - `README.md` with verified setup, execution, and test commands.
  - `.env.example` structured without production secrets.
  - Standard Symfony `.gitignore` ignoring `vendor/`, `var/`, `.env.local`.

## 5. Architectural Rules and Code Organization
- For a **headless API**, initialize with the lean skeleton (`symfony/skeleton`) and install only required components:
  `composer require orm validator serializer security-bundle`.
- For a **fullstack web application**, initialize with `--webapp` (includes Twig, AssetMapper, Mailer, WebProfiler).
- Do not create dozens of empty directory trees; add directories only when code is created.

## 6. PHP and Symfony Best Practices
- **Database Configuration:** Configure `DATABASE_URL` in `.env` matching the requested RDBMS (PostgreSQL or MySQL) with `utf8mb4` charset and compatible server version.
- **Container Validation:** Run `php bin/console lint:container` and `php bin/console lint:yaml config` immediately after bootstrap to ensure all recipes applied cleanly.

## 7. Examples of Correct and Incorrect Implementations

### Example: API Project Initialization
**Incorrect (Bloated setup with unnecessary frontend packages for an API):**
```bash
# Incorrect: Installs full webapp skeleton with Twig and AssetMapper for a JSON-only API
symfony new my-api --webapp
# Manually creates empty files without official Flex recipes
```

**Correct (Lean, precise, proportional initialization for a REST API):**
```bash
# 1. Create base project with lean official skeleton
composer create-project symfony/skeleton:^7.0 my-api
cd my-api

# 2. Add only components needed for modern REST APIs
composer require orm validator serializer security-bundle
composer require --dev test-pack phpstan/phpstan

# 3. Validate setup
php bin/console about
php bin/console lint:container
```

## 8. Technical Decision Criteria
- **Lean Skeleton (`symfony/skeleton`):** Default choice for REST APIs, microservices, queue workers, and CLI tools.
- **Webapp Skeleton (`symfony/webapp`):** Recommended when server-side HTML rendering (Twig, Turbo/Hotwire) is required.

## 9. Validation and Testing Procedures
- Validate project configuration:
  ```bash
  composer validate
  php bin/console about
  php bin/console lint:container
  php bin/console lint:yaml config
  ```
- Run initial smoke test:
  ```bash
  php bin/phpunit
  ```

## 10. Error Handling and Exceptions
- If Flex recipe conflicts occur, inspect `config/bundles.php` and modified files in `config/packages/`, resolving discrepancies before proceeding.

## 11. Objective Quality and Completion Criteria
- Application initialized and executing `php bin/console about` successfully.
- Database and ORM configured in `.env.example`.
- Initial smoke test running and passing in PHPUnit.
- `README.md` generated with verified execution instructions.
