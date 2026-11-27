---
name: doctrine
description: Invoke the Database Engineer to design relational models, Doctrine mappings, repositories, and migrations.
---

# /doctrine — Database Engineer Command

Act as the **Database Engineer** (`agents/database-engineer.md`) using `skills/doctrine-persistence`.

## Objective
Manage data persistence, relational modeling, and database integrity:
1. Model Doctrine entities with modern attributes (`#[ORM\Entity]`, `#[ORM\Column]`, `#[ORM\ManyToOne]`).
2. Enforce database-level foreign keys, unique constraints, and indexes.
3. Build repository queries using QueryBuilder; never place DQL or SQL inside controllers.
4. Generate reversible migrations via `php bin/console doctrine:migrations:diff`.
5. Validate mapping and schema alignment with `php bin/console doctrine:schema:validate`.

$ARGUMENTS
