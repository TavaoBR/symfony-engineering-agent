---
name: performance-profiling
description: Measure latency, memory consumption, inspect Symfony Profiler traces, and identify system bottlenecks.
---

# Skill: Performance Profiling and Diagnosis (Performance Profiling)

## 1. Objective and Responsibility
Accurately diagnose response time bottlenecks (CPU and I/O) and memory consumption in Symfony applications using profiling tools (Symfony Profiler, Blackfire, Tideways, xhprof) to guide evidence-based optimizations.

## 2. Scope and Limitations
- **Scope:** Analyzing the Symfony Web Profiler (`/_profiler`), measuring millisecond latency, detecting memory leaks in workers, analyzing call graphs and flamegraphs, and identifying slow queries.
- **Limitations:** Focuses on diagnostic telemetry and benchmark verification (code refactorings are coordinated with author agents).

## 3. Required Technical Knowledge
- Symfony Profiler panels: Time, Doctrine, Events, Memory, Messenger, HTTP Client.
- Code instrumentation using `hrtime(true)` and `Symfony\Component\Stopwatch\Stopwatch`.
- Interpreting flamegraphs, call graphs, wall-clock time vs CPU time.
- Memory monitoring: `memory_get_usage(true)` and `memory_get_peak_usage(true)`.

## 4. Development Standards and Conventions
- Never optimize without establishing a reproducible baseline before the change.
- Default Performance Budgets for API endpoints:
  - Total response time: < 150ms (P95).
  - Symfony framework overhead: < 25ms.
  - Database queries: < 50ms (maximum 5 queries per request).
  - Peak memory: < 16MB per typical web request.

## 5. Architectural Rules and Code Organization
- Temporary diagnostic instrumentation should use the container-injected `Stopwatch`, integrating with the Symfony Profiler timeline.
- Profiling and debug tools must be disabled in production (`debug: false` in `prod`).

## 6. PHP and Symfony Best Practices
- **Doctrine Profiler Panel:** Inspect the "Duplicate Queries" section. If a query is executed 10+ times with varying parameters, it represents an N+1 query defect.
- **Worker Memory Inspection:** In long-running consumers (`messenger:consume`), monitor memory after each processed message to verify UnitOfWork entity clearing.

## 7. Examples of Correct and Incorrect Implementations

### Example: Diagnostic Instrumentation in Critical Service
**Incorrect (Naïve microtime logging polluting domain code):**
```php
// Incorrect: Uses imprecise microtime, echoes output, and pollutes domain code
public function processCatalog(array $items): void
{
    $start = microtime(true);
    $this->executeBatch($items);
    echo "Time: " . (microtime(true) - $start) . " seconds";
}
```

**Correct (Professional Symfony Stopwatch integration):**
```php
namespace App\Service;

use Symfony\Component\Stopwatch\Stopwatch;

final class CatalogProcessor
{
    public function __construct(
        private readonly ?Stopwatch $stopwatch = null,
    ) {}

    public function processCatalog(array $items): void
    {
        $this->stopwatch?->start('catalog.processing', 'app');

        try {
            $this->executeBatch($items);
        } finally {
            $this->stopwatch?->stop('catalog.processing');
        }
    }

    private function executeBatch(array $items): void
    {
        // ...
    }
}
```

## 8. Technical Decision Criteria
- **Time dominated by "Doctrine":** Delegate to `database-performance-specialist` (missing indexes or N+1).
- **Time dominated by "Events":** Delegate to `symfony-performance-specialist` (heavy listeners in request lifecycle).
- **Time dominated by CPU computation:** Delegate to `php-performance-specialist` (quadratic loops or inefficient string processing).

## 9. Validation and Testing Procedures
- Measure time and memory via console command or WebTestCase using `SymfonyProfilerBenchmarkTrait`:
  ```php
  use App\Tests\Benchmark\SymfonyProfilerBenchmarkTrait;

  final class ProductBenchmarkTest extends WebTestCase
  {
      use SymfonyProfilerBenchmarkTrait;

      public function testPerformanceBudget(): void
      {
          $client = static::createClient();
          $this->enableSymfonyProfiler($client);

          $client->request('GET', '/api/products');

          // Automated Profiler Assertions
          $this->assertDurationLessThan($client, 150.0);
          $this->assertPeakMemoryLessThan($client, 24.0);
          $this->assertDatabaseQueryCountLessThan($client, 5);
          $this->assertZeroDuplicateQueries($client);
      }
  }
  ```
- Use the standalone runner (`php templates/benchmarks/benchmark-runner.php`) for P50/P95 latency percentiles.
- Refer to `docs/PROFILER_BENCHMARKS.md` for full implementation guidelines.

## 10. Error Handling and Exceptions
- Profiler timeouts and diagnostic errors must be handled without disrupting production traffic.

## 11. Objective Quality and Completion Criteria
- Profiling report generated with total time, framework overhead, query time, and peak memory.
- Bottlenecks documented with verified before-vs-after optimization metrics.
