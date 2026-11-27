---
name: php-optimization
description: Optimize algorithmic complexity, stream large datasets with Generators (yield), and manage memory.
---

# Skill: PHP Runtime and Algorithmic Optimization (PHP Optimization)

## 1. Objective and Responsibility
Optimize native PHP code execution by eliminating unnecessary algorithmic complexity (Big-O), reducing memory allocations, and leveraging streaming pipelines via Generators and native C-level functions, alongside runtime tuning (OPcache/JIT).

## 2. Scope and Limitations
- **Scope:** Big-O asymptotic analysis, hash tables for $O(1)$ lookups, `Generator` iterators (`yield`), native stream processing, and OPcache preloading.
- **Limitations:** Does not replace database indexing or asynchronous background queues.

## 3. Required Technical Knowledge
- Algorithmic analysis: $O(1)$, $O(n)$, $O(n \log n)$, $O(n^2)$.
- Data structure optimizations: associative hash lookups vs linear array scans.
- Streaming and Generators: `yield`, `yield from`, `SplFixedArray`.
- Modern PHP 8.2+ performance features: `readonly class`, constructor promotion, `opcache.preload`.
- Telemetry: `hrtime(true)`, `memory_get_peak_usage(true)`.

## 4. Development Standards and Conventions
- Avoid nested loops calling `in_array()` or `array_search()` (producing $O(n^2)$ complexity); flip lookup arrays to associative keys for $O(1)$ lookups.
- Never use `file()` or `file_get_contents()` for large files; use streams and generators.
- Enforce strict typing (`declare(strict_types=1);`).

## 5. Architectural Rules and Code Organization
- Bulk processing services must expose methods returning `\Generator` or `iterable` instead of large arrays.
- DTOs and Value Objects should be `final readonly class` to reduce mutability overhead.

## 6. PHP and Symfony Best Practices
- **Generator Pipelines:** When filtering, transforming, and exporting large datasets, chain generators so items are processed one by one, keeping memory usage constant regardless of dataset size.
- **`isset()` vs `in_array()`:** `isset($map[$key])` runs in constant time $O(1)$, whereas `in_array($val, $list)` performs a linear scan in $O(n)$ time.

## 7. Examples of Correct and Incorrect Implementations

### Example 1: Eliminating Quadratic Complexity $O(n^2)$
**Incorrect ($O(n \times m)$ complexity with linear search in loop):**
```php
// Incorrect: For 10,000 products and 5,000 exclusions, performs 50,000,000 comparisons!
public function filterAvailableProducts(array $products, array $excludedIds): array
{
    $filtered = [];
    foreach ($products as $product) {
        if (!in_array($product->getId(), $excludedIds, true)) {
            $filtered[] = $product;
        }
    }
    return $filtered;
}
```

**Correct (Linear complexity $O(n + m)$ using hash map lookup $O(1)$):**
```php
public function filterAvailableProducts(array $products, array $excludedIds): array
{
    // Convert exclusion list to hash table in O(m)
    $excludedMap = array_flip($excludedIds);
    $filtered = [];

    // Iterate products in O(n) with O(1) hash lookups
    foreach ($products as $product) {
        if (!isset($excludedMap[$product->getId()])) {
            $filtered[] = $product;
        }
    }

    return $filtered;
}
```

### Example 2: Streaming Large Files with Generators
**Incorrect (Loading entire file into RAM):**
```php
// Incorrect: Loads 500MB CSV into memory, triggering out-of-memory fatal error
public function parseBigCsv(string $filePath): array
{
    $lines = file($filePath); // Out of memory!
    $data = [];
    foreach ($lines as $line) {
        $data[] = str_getcsv($line);
    }
    return $data;
}
```

**Correct (Generator streaming row by row with constant memory < 2MB):**
```php
/**
 * @return \Generator<int, array<string>>
 */
public function parseBigCsv(string $filePath): \Generator
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        throw new \RuntimeException(sprintf('Cannot open file: %s', $filePath));
    }

    try {
        while (($row = fgetcsv($handle)) !== false) {
            yield $row; // Yields row and frees previous row from memory
        }
    } finally {
        fclose($handle);
    }
}
```

## 8. Technical Decision Criteria
- **When to use Generators:** Whenever a function produces an iterable sequence of unknown or large volume (> 1,000 items).
- **When to use OPcache Preload:** In containerized production environments with immutable deployments.

## 9. Validation and Testing Procedures
- Benchmark memory and execution time in PHPUnit:
  ```php
  public function testMemoryIsConstant(): void
  {
      $memBefore = memory_get_usage();
      $generator = $this->parser->parseBigCsv('fixtures/100k_rows.csv');

      foreach ($generator as $row) {
          // Process stream
      }

      $memAfter = memory_get_usage();
      $this->assertLessThan(2 * 1024 * 1024, $memAfter - $memBefore, 'Memory usage exceeded 2MB.');
  }
  ```

## 10. Error Handling and Exceptions
- Streams opened with `fopen` must always be closed in `finally` blocks to prevent operating system file descriptor leaks.

## 11. Objective Quality and Completion Criteria
- Zero unnecessary $O(n^2)$ operations across large collections.
- Constant or linear memory consumption verified in batch processing.
- 100% strictly typed code with `declare(strict_types=1);`.
