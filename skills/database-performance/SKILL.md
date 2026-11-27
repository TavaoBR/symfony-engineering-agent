---
name: database-performance
description: Eliminate N+1 queries using explicit Fetch Joins with addSelect, optimize indexes, and inspect EXPLAIN plans.
---

# Skill: Database and Doctrine Performance (Database Performance)

## 1. Objetivo and Responsibility
Eliminate database bottlenecks in Symfony and Doctrine ORM/DBAL: extinguish N+1 queries, design optimal indexes, implement read DTO projections, optimize execution plans (`EXPLAIN`), and structure memory-stable batch processing.

## 2. Scope and Limitations
- **Scope:** QueryBuilder and DQL optimization, explicit fetch joins, composite and partial indexes, hydration modes, CLI batch iterations with `$em->clear()`, and transaction concurrency control.
- **Limitations:** Does not alter domain business logic or bypass database relational constraints.

## 3. Required Technical Knowledge
- Doctrine ORM: `UnitOfWork` lifecycle, Identity Map, `LAZY` vs `EAGER` loading, fetch joins with `addSelect()`.
- Hydration modes: `Query::HYDRATE_ARRAY`, DTO Projections (`NEW App\DTO\...`).
- Relational RDBMS (PostgreSQL / MySQL): `EXPLAIN ANALYZE` plans, Seq Scan vs Index Scan costs, index selectivity.
- Locking: Pessimistic locks (`SELECT ... FOR UPDATE`) vs Optimistic locks (`#[ORM\Version]`).

## 4. Development Standards and Conventions
- Every query returning an entity collection that accesses related records MUST use explicit Fetch Joins in the repository.
- Read-only endpoints and reporting must use DTO Projections, avoiding UnitOfWork hydration overhead.
- Every column used in frequent `WHERE`, `ORDER BY`, or `JOIN` clauses must have an index.

## 5. Architectural Rules and Code Organization
- Optimized queries reside inside entity `Repository` classes in `src/Repository/`.
- Read projections reside in `src/DTO/Response/` as `readonly` classes.
- In CLI batch commands, chunk operations into batches and invoke `$em->clear()` at each iteration.

## 6. PHP and Symfony Best Practices
- **Eliminate N+1 Queries:** When mapping OneToMany or ManyToOne relationships, never access relationships inside loops without eager pre-loading.
- **Safe Batch Processing:** In CLI commands iterating thousands of rows, use `toIterable()` or ID-based pagination instead of buffering arrays via `getResult()`.

## 7. Examples of Correct and Incorrect Implementations

### Example 1: Eliminating N+1 Queries with Fetch Joins
**Incorrect (Classic N+1 query problem):**
```php
// Incorrect: Fires 1 query for orders + 100 queries for customers and items!
public function findRecentOrders(): array
{
    return $this->createQueryBuilder('o')
        ->orderBy('o.createdAt', 'DESC')
        ->setMaxResults(100)
        ->getQuery()
        ->getResult();
    // Iterating $order->getCustomer()->getName() triggers separate queries per order!
}
```

**Correct (Explicit Fetch Join loading data in 1 single SQL query):**
```php
public function findRecentOrders(): array
{
    return $this->createQueryBuilder('o')
        ->addSelect('c', 'i') // Forces eager join in single query
        ->innerJoin('o.customer', 'c')
        ->leftJoin('o.items', 'i')
        ->orderBy('o.createdAt', 'DESC')
        ->setMaxResults(100)
        ->getQuery()
        ->getResult();
}
```

### Example 2: Batch Processing with Constant Memory Footprint
**Incorrect (Memory leak causing out-of-memory crash):**
```php
// Incorrect: Buffers 50,000 objects in UnitOfWork until RAM exhausts
public function updateExpiredSubscriptions(): void
{
    $subscriptions = $this->repository->findAll(); // 50,000 entities in RAM!
    foreach ($subscriptions as $sub) {
        $sub->expire();
    }
    $this->em->flush(); // Fatal Error: Allowed memory size exhausted
}
```

**Correct (Streaming iteration with batch flushing and UnitOfWork clearing):**
```php
public function updateExpiredSubscriptions(): void
{
    $batchSize = 100;
    $i = 0;

    $query = $this->repository->createQueryBuilder('s')
        ->where('s.status = :active')
        ->andWhere('s.expiresAt < :now')
        ->setParameter('active', 'ACTIVE')
        ->setParameter('now', new \DateTimeImmutable())
        ->getQuery();

    // toIterable streams rows without buffering the entire table in memory
    foreach ($query->toIterable() as $subscription) {
        $subscription->expire();
        ++$i;

        if (($i % $batchSize) === 0) {
            $this->em->flush();
            $this->em->clear(); // Frees managed entities from memory
        }
    }

    $this->em->flush();
    $this->em->clear();
}
```

## 8. Technical Decision Criteria
- **DTO Projection vs Entity Hydration:**
  - Use DTO Projections for read-only listings and dashboard queries.
  - Use Entity Hydration when modifying and persisting records via `$em->flush()`.

## 9. Validation and Testing Procedures
- Inspect execution plans in PostgreSQL/MySQL:
  ```sql
  EXPLAIN ANALYZE SELECT o.*, c.* FROM orders o INNER JOIN customers c ON o.customer_id = c.id WHERE o.status = 'PENDING';
  ```
- Automated query count test with `WebTestCase`:
  ```php
  $this->assertLessThanOrEqual(2, $profile->getCollector('db')->getQueryCount());
  ```

## 10. Error Handling and Exceptions
- Deadlock exceptions (`DeadlockException`) should be handled with controlled retries (maximum 3 attempts).

## 11. Objective Quality and Completion Criteria
- Zero N+1 queries detected in collection endpoints.
- Validated `EXPLAIN` execution plans showing index scans on filtered columns.
- Batch processing executes with constant memory usage.
