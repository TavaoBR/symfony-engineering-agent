# Workflow: New Symfony Project Delivery with Specialists

1. **Intake and Scoping (Orchestrator):**
   - Identifies functional scope, target Symfony version (6.4 LTS vs 7.x), and chosen database management system.
2. **Environment and Prerequisites (Project Bootstrapper):**
   - Verifies PHP runtime, Composer version, required native extensions, and confirms clean destination directory.
3. **Architecture Design (Symfony Architect):**
   - Defines architectural layers, DTO conventions, and records ADR 001 in `docs/DECISIONS.md`.
4. **Skeleton Setup and Dependencies (Project Bootstrapper):**
   - Executes official Composer initialization and applies verified Symfony Flex recipes.
5. **Persistence Configuration (Database Engineer):**
   - Configures Doctrine ORM, database connection variables, and initial migration templates in `.env.example`.
6. **Perimeter Security Configuration (Security Engineer & Symfony Security Specialist):**
   - Configures firewalls in `security.yaml`, secure password hashers, session cookies, and rate limiting.
7. **Performance Baseline (Symfony Performance Specialist):**
   - Ensures services default to private (`public: false`), configures cache pools, and configures asynchronous Messenger transports.
8. **QA & Static Analysis Setup (Quality Engineer):**
   - Configures `phpunit.xml.dist`, sets `phpstan.neon` to level 8, and writes initial smoke tests.
9. **Setup Documentation (Technical Writer):**
   - Writes reproducible `README.md` and establishes `docs/ARCHITECTURE.md`.
10. **Independent Review and Validation (Code Reviewer & Orchestrator):**
    - Executes `composer validate`, `php bin/console lint:container`, `php bin/phpunit`, and presents verified deliverable to user with concrete execution evidence.
