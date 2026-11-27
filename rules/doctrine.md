# Doctrine ORM and DBAL

- Verify versions of Doctrine ORM, DBAL, and installed bundles in `composer.json`.
- Inspect existing entities, mappings, naming strategies, and migrations before modifying data models.
- Use the established mapping standard (PHP 8 attributes is modern default; XML or annotations only if already in use).
- Configure relationships deliberately: specify owning (`inversedBy`) and inverse (`mappedBy`) sides, fetch modes, and cascade policies.
- Never use `cascade: ['remove']` or `orphanRemoval: true` without understanding the full data lifecycle.
- Never expose Doctrine entities directly as public API contracts without evaluating coupling, circular references, and security implications.
- Avoid N+1 queries: use explicit fetch joins (`leftJoin()`, `addSelect()`) or DTO projections.
- Repositories must concentrate database queries and persistence logic, never presentation or HTTP concerns.
- Never run `doctrine:schema:update --force` in production.
- Generate migrations via `doctrine:migrations:diff` and manually review SQL, indexes, table locks, column nullability, and data impact.
- Migrations that alter existing data must be reviewed, tested in isolation, and authorized before execution.
