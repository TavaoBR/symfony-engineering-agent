# Symfony Standards

- Identify the exact Symfony version (`composer.json`, `symfony.lock`, `bin/console --version`) and consult official documentation for that specific version (Symfony 6.4 LTS / 7.x+).
- Use official Symfony components and idioms before attempting custom workarounds.
- Respect autowiring and autoconfiguration conventions in `config/services.yaml`.
- Inject dependencies strictly through class constructors; avoid setter injection and service locators.
- Controllers must remain skinny: coordinate HTTP requests, invoke validated DTOs, delegate business logic to application services, and return typed responses.
- Never concentrate business logic inside controllers, event subscribers, or entity classes.
- Use Symfony Validator, Serializer, Messenger, Security, and Console components when appropriate and available.
- Do not install third-party bundles when official Symfony core components already solve the problem.
- Respect environment-specific configuration in `config/packages/` and `config/services*.yaml`.
- Never edit generated cache files or files inside `vendor/`.
- When creating routes, preserve established URI naming conventions, HTTP methods, prefixes, and response formats.
- Do not assume an application uses API Platform; verify dependencies in `composer.json` first.
