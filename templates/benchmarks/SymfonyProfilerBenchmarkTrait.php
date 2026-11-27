<?php

declare(strict_types=1);

namespace App\Tests\Benchmark;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\DataCollector\MemoryDataCollector;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * Trait SymfonyProfilerBenchmarkTrait
 *
 * Provides automated latency, memory, and database query profiling assertions
 * for Symfony functional tests (WebTestCase) using the native Symfony Profiler.
 */
trait SymfonyProfilerBenchmarkTrait
{
    /**
     * Enables the Symfony Profiler for the upcoming HTTP client request.
     * Must be called before $client->request().
     */
    protected function enableSymfonyProfiler(KernelBrowser $client): void
    {
        $client->enableProfiler();
    }

    /**
     * Extracts the Symfony Profiler instance from the current request.
     */
    protected function getSymfonyProfile(KernelBrowser $client): Profile
    {
        $profile = $client->getProfile();

        if (false === $profile) {
            $this->fail(
                'Symfony Profiler is not available or was not enabled for this request. ' .
                'Ensure $client->enableProfiler() was called before $client->request(), ' .
                'and that framework.profiler.collect is enabled in config/packages/test/web_profiler.yaml.'
            );
        }

        return $profile;
    }

    /**
     * Extracts consolidated telemetry metrics from the Profiler collectors.
     *
     * @return array{
     *     duration_ms: float,
     *     init_time_ms: float,
     *     memory_bytes: int,
     *     memory_mb: float,
     *     query_count: int,
     *     query_time_ms: float,
     *     duplicate_queries: int
     * }
     */
    protected function getProfilerMetrics(KernelBrowser $client): array
    {
        $profile = $this->getSymfonyProfile($client);

        // 1. Time Collector
        $durationMs = 0.0;
        $initTimeMs = 0.0;
        if ($profile->hasCollector('time')) {
            /** @var TimeDataCollector $timeCollector */
            $timeCollector = $profile->getCollector('time');
            $durationMs = round($timeCollector->getDuration(), 2);
            $initTimeMs = round($timeCollector->getInitTime(), 2);
        }

        // 2. Memory Collector
        $memoryBytes = 0;
        $memoryMb = 0.0;
        if ($profile->hasCollector('memory')) {
            /** @var MemoryDataCollector $memoryCollector */
            $memoryCollector = $profile->getCollector('memory');
            $memoryBytes = $memoryCollector->getMemory();
            $memoryMb = round($memoryBytes / (1024 * 1024), 2);
        }

        // 3. Database Collector (Doctrine)
        $queryCount = 0;
        $queryTimeMs = 0.0;
        $duplicateQueries = 0;
        if ($profile->hasCollector('db')) {
            /** @var DoctrineDataCollector $dbCollector */
            $dbCollector = $profile->getCollector('db');
            $queryCount = $dbCollector->getQueryCount();
            $queryTimeMs = round($dbCollector->getTime() * 1000, 2);

            // Compute duplicate queries to mathematically detect N+1 issues
            $queries = $dbCollector->getQueries();
            $seenSql = [];
            foreach ($queries as $qGroup) {
                if (is_array($qGroup)) {
                    foreach ($qGroup as $q) {
                        $sql = $q['sql'] ?? '';
                        if (!empty($sql)) {
                            $seenSql[$sql] = ($seenSql[$sql] ?? 0) + 1;
                        }
                    }
                }
            }
            foreach ($seenSql as $count) {
                if ($count > 1) {
                    $duplicateQueries += ($count - 1);
                }
            }
        }

        return [
            'duration_ms' => $durationMs,
            'init_time_ms' => $initTimeMs,
            'memory_bytes' => $memoryBytes,
            'memory_mb' => $memoryMb,
            'query_count' => $queryCount,
            'query_time_ms' => $queryTimeMs,
            'duplicate_queries' => $duplicateQueries,
        ];
    }

    /**
     * Asserts that total HTTP execution duration is below the performance budget.
     */
    protected function assertDurationLessThan(
        KernelBrowser $client,
        float $maxDurationMs,
        ?string $message = null
    ): void {
        $metrics = $this->getProfilerMetrics($client);
        $actualDuration = $metrics['duration_ms'];

        $defaultMessage = sprintf(
            'Response latency budget exceeded: expected duration < %.2fms, actual was %.2fms.',
            $maxDurationMs,
            $actualDuration
        );

        $this->assertLessThan($maxDurationMs, $actualDuration, $message ?? $defaultMessage);
    }

    /**
     * Asserts that peak memory consumption remains below the memory budget.
     */
    protected function assertPeakMemoryLessThan(
        KernelBrowser $client,
        float $maxMemoryMb,
        ?string $message = null
    ): void {
        $metrics = $this->getProfilerMetrics($client);
        $actualMemoryMb = $metrics['memory_mb'];

        $defaultMessage = sprintf(
            'Memory consumption budget exceeded: expected memory < %.2fMB, actual peak was %.2fMB.',
            $maxMemoryMb,
            $actualMemoryMb
        );

        $this->assertLessThan($maxMemoryMb, $actualMemoryMb, $message ?? $defaultMessage);
    }

    /**
     * Asserts that the total number of executed database queries is below the budget.
     * Prevents runaway query counts in listing or detail endpoints.
     */
    protected function assertDatabaseQueryCountLessThan(
        KernelBrowser $client,
        int $maxQueries,
        ?string $message = null
    ): void {
        $metrics = $this->getProfilerMetrics($client);
        $actualQueries = $metrics['query_count'];

        $defaultMessage = sprintf(
            'Database query budget exceeded: expected total queries < %d, actual was %d.',
            $maxQueries,
            $actualQueries
        );

        $this->assertLessThan($maxQueries, $actualQueries, $message ?? $defaultMessage);
    }

    /**
     * Mathematically guarantees zero N+1 queries by asserting zero duplicate SQL executions.
     */
    protected function assertZeroDuplicateQueries(
        KernelBrowser $client,
        ?string $message = null
    ): void {
        $metrics = $this->getProfilerMetrics($client);
        $duplicates = $metrics['duplicate_queries'];

        $defaultMessage = sprintf(
            'N+1 Query Trap Detected: found %d duplicate queries executed for this request. ' .
            'Ensure explicit Fetch Joins (addSelect) are used in your Doctrine QueryBuilder.',
            $duplicates
        );

        $this->assertSame(0, $duplicates, $message ?? $defaultMessage);
    }
}
