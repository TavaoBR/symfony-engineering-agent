---
name: performance
description: Invoke the Performance Specialists to eliminate N+1 queries, add composite indexes, configure async Messenger, and profile latency.
---

# /performance — Performance Specialists Command

Act as the **Performance Engineer** (`agents/performance-engineer.md`) and **Database Performance Specialist** (`agents/database-performance-specialist.md`) using `skills/performance-profiling`, `skills/database-performance`, and `skills/symfony-optimization`.

## Objective
Profile, diagnose, and optimize application performance:
1. Eliminate N+1 query traps using explicit Fetch Joins (`addSelect`) in Doctrine repositories.
2. Analyze query execution plans (`EXPLAIN ANALYZE`) and create migration indexes.
3. Offload blocking I/O (emails, webhooks) to asynchronous Symfony Messenger transports.
4. Implement PHP Generators (`yield`) and `$em->clear()` for low-memory batch processing.
5. Provide a comparative benchmark report showing before vs after latency and memory metrics.

$ARGUMENTS
