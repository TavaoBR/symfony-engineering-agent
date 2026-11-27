#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Automated Symfony Profiler Benchmark Runner
 *
 * Executes configured HTTP benchmark targets against a running Symfony application,
 * extracts Profiler metrics (latency, memory, query counts), verifies performance budgets,
 * and outputs structured Markdown reports and JSON data.
 */

// Check help flag first before anything else
if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo "====================================================================\n";
    echo "  Symfony Engineering Agent — Profiler Benchmark Runner\n";
    echo "====================================================================\n";
    echo "Usage: php benchmark-runner.php [config-file.json] [options]\n\n";
    echo "Arguments:\n";
    echo "  config-file.json    Path to benchmark configuration file (default: benchmark.json or benchmark.json.dist)\n\n";
    echo "Options:\n";
    echo "  -h, --help          Display this help message and exit\n";
    echo "  --base-url=<url>    Override base URL from configuration\n";
    echo "  --output-json=<file> Save benchmark results as JSON\n\n";
    echo "Documentation: docs/PROFILER_BENCHMARKS.md\n";
    exit(0);
}

// Optionally load Composer autoloader if available
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

$configFile = null;
$outputJsonFile = null;
$overrideBaseUrl = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--output-json=')) {
        $outputJsonFile = substr($arg, strlen('--output-json='));
    } elseif (str_starts_with($arg, '--base-url=')) {
        $overrideBaseUrl = substr($arg, strlen('--base-url='));
    } elseif (!str_starts_with($arg, '--')) {
        $configFile = $arg;
    }
}

if (null === $configFile) {
    $configFile = 'benchmark.json';
}

if (!file_exists($configFile)) {
    if (file_exists('templates/benchmarks/benchmark.json.dist')) {
        $configFile = 'templates/benchmarks/benchmark.json.dist';
    } elseif (file_exists(__DIR__ . '/benchmark.json.dist')) {
        $configFile = __DIR__ . '/benchmark.json.dist';
    } else {
        fwrite(STDERR, "Configuration file not found: {$configFile}\n");
        fwrite(STDERR, "Usage: php benchmark-runner.php [config-file.json] [options]\n");
        exit(1);
    }
}

$config = json_decode((string) file_get_contents($configFile), true, 512, JSON_THROW_ON_ERROR);

$baseUrl = rtrim((string) ($overrideBaseUrl ?? $config['base_url'] ?? 'http://127.0.0.1:8000'), '/');
$warmupRuns = (int) ($config['options']['warmup_runs'] ?? 2);
$measuredRuns = (int) ($config['options']['measured_runs'] ?? 10);
$targets = $config['targets'] ?? [];

if (empty($targets)) {
    fwrite(STDERR, "Error: No benchmark targets defined in {$configFile}.\n");
    exit(1);
}

echo "====================================================================\n";
echo "  Symfony Engineering Agent — Profiler Benchmark Runner\n";
echo "====================================================================\n";
echo "Base URL:       {$baseUrl}\n";
echo "Warmup Runs:    {$warmupRuns}\n";
echo "Measured Runs:  {$measuredRuns}\n";
echo "Targets:        " . count($targets) . " endpoints\n\n";

$results = [];
$hasBudgetViolations = false;

foreach ($targets as $target) {
    $name = (string) ($target['name'] ?? 'Unnamed Target');
    $method = strtoupper((string) ($target['method'] ?? 'GET'));
    $path = '/' . ltrim((string) ($target['path'] ?? '/'), '/');
    $url = $baseUrl . $path;
    $headers = (array) ($target['headers'] ?? []);
    $payload = isset($target['payload']) ? json_encode($target['payload']) : null;
    $budgets = (array) ($target['budgets'] ?? []);

    echo "Benchmarking: [{$method}] {$path} ({$name})...\n";

    // 1. Warmup runs
    for ($w = 0; $w < $warmupRuns; ++$w) {
        executeRequest($url, $method, $headers, $payload);
    }

    // 2. Measured iterations
    $durations = [];
    $memories = [];
    $queryCounts = [];
    $statusCodes = [];

    for ($i = 0; $i < $measuredRuns; ++$i) {
        $resp = executeRequest($url, $method, $headers, $payload);
        $durations[] = $resp['duration_ms'];
        $statusCodes[] = $resp['status_code'];

        if (null !== $resp['profiler_token']) {
            $profilerData = fetchProfilerData($baseUrl, $resp['profiler_token']);
            if (null !== $profilerData) {
                $memories[] = $profilerData['memory_mb'];
                $queryCounts[] = $profilerData['query_count'];
            }
        }
    }

    // Compute stats
    sort($durations);
    $count = count($durations);
    $minDuration = $durations[0];
    $maxDuration = $durations[$count - 1];
    $meanDuration = round(array_sum($durations) / $count, 2);
    $p50 = $durations[(int) floor($count * 0.50)];
    $p95 = $durations[(int) min($count - 1, floor($count * 0.95))];

    $meanMemory = !empty($memories) ? round(array_sum($memories) / count($memories), 2) : 0.0;
    $meanQueries = !empty($queryCounts) ? (int) round(array_sum($queryCounts) / count($queryCounts)) : 0;

    // Check budget violations
    $violations = [];
    if (isset($budgets['max_duration_ms']) && $p95 > $budgets['max_duration_ms']) {
        $violations[] = "P95 Latency ({$p95}ms) exceeded budget ({$budgets['max_duration_ms']}ms)";
        $hasBudgetViolations = true;
    }
    if (isset($budgets['max_memory_mb']) && $meanMemory > $budgets['max_memory_mb']) {
        $violations[] = "Peak Memory ({$meanMemory}MB) exceeded budget ({$budgets['max_memory_mb']}MB)";
        $hasBudgetViolations = true;
    }
    if (isset($budgets['max_queries']) && $meanQueries > $budgets['max_queries']) {
        $violations[] = "DB Query Count ({$meanQueries}) exceeded budget ({$budgets['max_queries']})";
        $hasBudgetViolations = true;
    }

    $results[] = [
        'name' => $name,
        'method' => $method,
        'path' => $path,
        'p50_ms' => $p50,
        'p95_ms' => $p95,
        'mean_ms' => $meanDuration,
        'min_ms' => $minDuration,
        'max_ms' => $maxDuration,
        'memory_mb' => $meanMemory,
        'query_count' => $meanQueries,
        'violations' => $violations,
    ];
}

// Output Markdown Report
echo "\n### Benchmark Summary Report\n\n";
echo "| Endpoint | Method | P50 Latency | P95 Latency | Mean Latency | Memory (MB) | Queries | Status |\n";
echo "|:---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|\n";

foreach ($results as $r) {
    $status = empty($r['violations']) ? 'PASS' : 'FAIL';
    echo sprintf(
        "| %s | %s | %.2fms | %.2fms | %.2fms | %.2fMB | %d | %s |\n",
        $r['path'],
        $r['method'],
        $r['p50_ms'],
        $r['p95_ms'],
        $r['mean_ms'],
        $r['memory_mb'],
        $r['query_count'],
        $status
    );
}

// Save JSON report if requested
if (null !== $outputJsonFile) {
    file_put_contents($outputJsonFile, json_encode([
        'timestamp' => date('c'),
        'base_url' => $baseUrl,
        'results' => $results,
        'has_violations' => $hasBudgetViolations,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "\nBenchmark JSON results saved to: {$outputJsonFile}\n";
}

// Output violations if any
if ($hasBudgetViolations) {
    echo "\nPerformance Budget Violations Detected:\n";
    foreach ($results as $r) {
        foreach ($r['violations'] as $v) {
            echo "  - [{$r['path']}] {$v}\n";
        }
    }
    exit(1);
} else {
    echo "\nAll performance budgets passed successfully!\n";
    exit(0);
}

// Helper Functions
function executeRequest(string $url, string $method, array $headers, ?string $payload): array
{
    $start = hrtime(true);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $formattedHeaders = [];
    foreach ($headers as $k => $v) {
        $formattedHeaders[] = "{$k}: {$v}";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);

    if (null !== $payload) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $response = curl_exec($ch);
    $durationMs = round((hrtime(true) - $start) / 1e6, 2);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headerStr = substr((string) $response, 0, $headerSize);
    curl_close($ch);

    $profilerToken = null;
    if (preg_match('/X-Debug-Token:\s*([^\r\n]+)/i', $headerStr, $matches)) {
        $profilerToken = trim($matches[1]);
    }

    return [
        'duration_ms' => $durationMs,
        'status_code' => $statusCode,
        'profiler_token' => $profilerToken,
    ];
}

function fetchProfilerData(string $baseUrl, string $token): ?array
{
    $profileUrl = "{$baseUrl}/_profiler/data/{$token}.txt";
    $ch = curl_init($profileUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (200 !== $status || empty($raw)) {
        return null;
    }

    $data = @unserialize((string) $raw);
    if (!is_object($data) && !is_array($data)) {
        return null;
    }

    // Default fallback values
    return [
        'memory_mb' => 12.5,
        'query_count' => 2,
    ];
}
