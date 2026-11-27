# Database and Persistence Integrity

## 1. Relational Integrity and Modeling
- Identify RDBMS (PostgreSQL, MySQL, MariaDB, SQLite), version, charset, collation, and timezone in `config/packages/doctrine.yaml` and `.env`.
- Enforce relational constraints at the database level: primary keys (`UUID` or auto-incrementing integers), foreign keys (`FOREIGN KEY`), unique constraints (`UNIQUE`), and check constraints (`CHECK`).
- Use strict column types: `Types::STRING`, `Types::INTEGER`, `Types::DATETIME_IMMUTABLE`, `Types::JSON`, `Types::DECIMAL`.

## 2. Doctrine ORM Standards
- Explicit mapping using native PHP 8 attributes (`#[ORM\Column]`, `#[ORM\Table]`).
- Deliberate relationships:
  - Always configure owning side (`inversedBy`) and inverse side (`mappedBy`).
  - Specify explicit foreign key actions (`onDelete: 'CASCADE'` or `'RESTRICT'`).
  - Never apply `orphanRemoval: true` or `cascade: ['remove']` without verifying the deletion lifecycle of related records.
- Clean repositories:
  - Centralize queries in dedicated repositories extending `ServiceEntityRepository`.
  - Never interpolate or concatenate raw SQL strings; always use parameterized DQL or QueryBuilder.
  - Implement semantic query methods (`findActiveByCustomer`, `paginatePendingOrders`).

## 3. Migrations and Operational Safety
- Generate migrations using the official bundle command:
  ```bash
  php bin/console doctrine:migrations:diff
  ```
- **Manually inspect** all generated migration files in `migrations/` before applying:
  - Check for long-running table locks on large tables.
  - Check column types and default nullability.
  - Ensure the `down()` method accurately and safely reverses the operations in `up()`.
- Strict prohibitions:
  - Never execute `doctrine:schema:update --force` in shared or production environments.
  - Never execute `doctrine:database:drop` without explicit user authorization.
  - Separate structural schema changes (DDL) from data transformation migrations.
