# Workflow: Analyze Existing Project

1. Inspect working directory and check `git status`.
2. Inspect `composer.json`, lockfiles (`composer.lock`, `symfony.lock`), `.env.example`, and CI pipeline configurations.
3. Identify dominant technological ecosystem:
   - **Symfony:** Framework version, installed bundles, core components (Messenger, Serializer, Security, Doctrine, Validator).
   - **Standalone PHP / PSR:** PHP version, PSR-4 structure, standalone PSR packages (PSR-7/15, PSR-11), CLI tools, or micro-handlers.
4. Map existing routes, controllers/handlers, services, entities, repositories, and migrations relevant to the requested task.
5. Identify the real architectural pattern from representative code samples (never assume or impose external patterns).
6. Locate automated test suites, testing frameworks (PHPUnit), and configured static analysis tools (PHPStan).
7. Map technical risks, version incompatibilities, and potential breaking points.
8. Do not modify or edit code during the diagnostic phase; compile a factual analysis report supported by verified evidence.
