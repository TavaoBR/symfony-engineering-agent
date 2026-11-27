# Workflow: Create Symfony Project from Scratch

## 1. Environment and Prerequisite Verification
- Verify target directory and ensure it does not overwrite existing files without explicit authorization.
- Execute `php -v` and `composer --version`.
- Verify mandatory PHP extensions (`pdo`, `ctype`, `iconv`, `json`, `tokenizer`, `xml`, `mbstring`, `intl`).
- Identify target stable version: Symfony 6.4 LTS or Symfony 7.x+.

## 2. Initialize Official Skeleton
- For REST APIs or headless microservices:
  ```bash
  composer create-project symfony/skeleton:^7.0 <directory>
  cd <directory>
  composer require orm validator serializer security-bundle
  composer require --dev test-pack phpstan/phpstan
  ```
- For full-stack web applications with Twig:
  ```bash
  symfony new <directory> --webapp
  ```

## 3. Structure Code Architecture
- Create clean baseline directory structure with strict separation of concerns:
  - `src/Controller/Api/`
  - `src/DTO/Request/` and `src/DTO/Response/`
  - `src/Service/`
  - `src/Entity/` and `src/Repository/`
  - `src/Security/Voter/`
- Avoid creating empty placeholder folders or speculative abstractions without immediate purpose.

## 4. Persistence Setup and Environment Configuration
- Configure `.env.example` with structured `DATABASE_URL` placeholder for the required DBMS (PostgreSQL or MySQL) without real credentials.
- Ensure `.env.local` is strictly listed in `.gitignore`.

## 5. Baseline Quality and Security Configuration
- Configure `phpunit.xml.dist` pointing to `.env.test`.
- Configure `phpstan.neon` at level 8 minimum.
- Validate security firewalls in `config/packages/security.yaml`.

## 6. Initial Documentation
- Create `README.md` containing real system prerequisites, `composer install` steps, test database setup, and test execution commands.
- Create `docs/ARCHITECTURE.md` documenting established architectural layers.
- Create `docs/DECISIONS.md` recording ADR 001 (selection of Symfony version, PHP version, and DBMS).

## 7. Integrity Verification
- Execute mandatory local verification checks:
  ```bash
  composer validate
  php bin/console about
  php bin/console lint:container
  php bin/console lint:yaml config
  php bin/phpunit
  ```

## 8. Delivery Report
Present created file tree, installed PHP and Symfony versions, validated commands, and exact instructions for initial application startup.
