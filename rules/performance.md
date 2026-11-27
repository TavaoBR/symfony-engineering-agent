# Performance Standards in PHP and Symfony

## 1. General Performance Principles
- Evidence-based optimization: measure before optimizing. Use Symfony Profiler, Blackfire, Tideways, or xhprof to identify real bottlenecks in CPU, I/O, or memory.
- Avoid premature optimization that degrades readability or maintainability without measurable gains.
- Respect performance budgets: critical API endpoints must respond under 100-200ms at the 95th percentile (P95).

## 2. PHP Runtime Performance
- **Memory and Collection Management:**
  - Never load entire database tables or large files into in-memory arrays. Use `Generator` (`yield`) and native PHP streams (`fopen`, `fgetcsv`) for streaming.
  - Monitor memory consumption in console commands and workers; invoke `gc_collect_cycles()` only when necessary in large batch loops.
- **Data Structures and Complexity:**
  - Avoid quadratic complexity ($O(n^2)$) in collection operations; prefer hash-table lookups ($O(1)$) using associative keys over linear `in_array()` scans ($O(n)$).
  - Use optimized C-level functions (`isset`, `array_key_exists`, `array_flip`) for high-frequency key checks.
- **OPcache and Preloading:**
  - Ensure OPcache is enabled in production environments with class preloading (`opcache.preload`) configured for core Symfony classes.

## 3. Symfony Framework Performance
- **Dependency Injection and Services:**
  - Keep all services private (`public: false`) to allow the Symfony container compiler to perform inlining and dead code removal.
  - Never inject the entire container (`ContainerInterface`); inject only explicit dependencies via constructor.
- **Asynchronous Processing with Symfony Messenger:**
  - Delegate operations with non-deterministic latency (emails, external APIs, PDF generation, image processing) to asynchronous transports via Symfony Messenger (`#[AsMessageHandler]`).
  - HTTP controllers should persist the command, dispatch the message, and return HTTP 202 Accepted or 201 Created immediately.
- **HTTP Response Serialization:**
  - Never serialize Doctrine entities directly with unconstrained relationships (risk of circular references and infinite recursion).
  - Use dedicated response DTOs or enforce restrictive serialization groups (`#[Groups(['user:read'])]`).
- **Application Cache and HTTP Caching:**
  - Use the Symfony Cache component (`cache.app`, Redis, or APCu) with tagged invalidation (`TagAwareAdapterInterface`) for expensive computations.
  - Implement HTTP cache headers (`Cache-Control`, `ETag`, `Last-Modified`) using `$response->isNotModified($request)` to prevent redundant processing.

## 4. Doctrine ORM and Database Performance
- **Eliminating N+1 Queries:**
  - Every query retrieving a collection that requires related data must use explicit Fetch Joins in QueryBuilder:
    ```php
    $qb->select('o', 'c', 'i')
       ->from(Order::class, 'o')
       ->innerJoin('o.customer', 'c')
       ->leftJoin('o.items', 'i');
    ```
- **Read Projections and Hydration:**
  - For read-only listings, dashboards, and reporting, avoid hydrating full entity objects managed by UnitOfWork. Use DTO projections:
    ```php
    $qb->select(sprintf('NEW %s(u.id, u.name, u.email)', UserDTO::class))
       ->from(User::class, 'u');
    ```
- **Strategic Indexing:**
  - Every column frequently used in `WHERE`, `JOIN`, `ORDER BY`, or `GROUP BY` clauses must have an appropriate database index.
  - Verify query execution plans using `EXPLAIN ANALYZE` before approving complex queries.
- **Batch Processing:**
  - In console commands or bulk imports, process records in batches (e.g., 50 or 100 items) and clear the EntityManager:
    ```php
    $em->flush();
    $em->clear(); // Detaches all entities from UnitOfWork to free RAM
    ```
- **Contention and Concurrency:**
  - Keep database transactions short. Never perform network calls or external I/O inside database transaction blocks (`$em->wrapInTransaction()`).
