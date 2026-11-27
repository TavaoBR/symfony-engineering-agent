<?php

declare(strict_types=1);

/**
 * ==============================================================================
 * Symfony Engineering Agent — Observability & Telemetry Architecture Validator
 * ==============================================================================
 *
 * Validates OpenTelemetry distributed tracing configs, Prometheus metrics exporters,
 * OTel Collector pipelines, and production Grafana dashboard specifications.
 *
 * Usage:
 *   php templates/observability/observability-validator.php [options]
 *
 * Options:
 *   -h, --help            Display this help message and exit
 *   --otel=<path>         Path to OpenTelemetry config (default: templates/observability/otel.yaml.dist)
 *   --prometheus=<path>   Path to Prometheus config (default: templates/observability/prometheus.yaml.dist)
 *   --collector=<path>    Path to OTel Collector config (default: templates/observability/otel-collector-config.yaml.dist)
 *   --dashboard=<path>    Path to Grafana dashboard JSON (default: templates/observability/grafana/symfony-dashboard.json)
 *   --strict              Enforce zero tolerance on warnings
 *   --output-json=<file>  Save structured report to JSON
 */

$options = getopt('h', ['help', 'otel:', 'prometheus:', 'collector:', 'dashboard:', 'strict', 'output-json:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
====================================================================
  Symfony Engineering Agent — Observability Architecture Validator
====================================================================
Usage: php observability-validator.php [options]

Options:
  -h, --help            Display this help message and exit
  --otel=<path>         Path to OpenTelemetry config (default: templates/observability/otel.yaml.dist)
  --prometheus=<path>   Path to Prometheus config (default: templates/observability/prometheus.yaml.dist)
  --collector=<path>    Path to OTel Collector config (default: templates/observability/otel-collector-config.yaml.dist)
  --dashboard=<path>    Path to Grafana dashboard JSON (default: templates/observability/grafana/symfony-dashboard.json)
  --strict              Enforce zero tolerance on warnings
  --output-json=<file>  Save structured validation report as JSON

Documentation: docs/OBSERVABILITY_AND_TELEMETRY.md

HELP;
    exit(0);
}

$otelPath = (string) ($options['otel'] ?? 'templates/observability/otel.yaml.dist');
$promPath = (string) ($options['prometheus'] ?? 'templates/observability/prometheus.yaml.dist');
$collectorPath = (string) ($options['collector'] ?? 'templates/observability/otel-collector-config.yaml.dist');
$dashboardPath = (string) ($options['dashboard'] ?? 'templates/observability/grafana/symfony-dashboard.json');
$strictMode = isset($options['strict']);
$outputJson = isset($options['output-json']) ? (string) $options['output-json'] : null;

echo "====================================================================\n";
echo "  Symfony Engineering Agent — Observability Architecture Validator   \n";
echo "====================================================================\n";
echo "OpenTelemetry Config:  {$otelPath}\n";
echo "Prometheus Config:     {$promPath}\n";
echo "OTel Collector Config: {$collectorPath}\n";
echo "Grafana Dashboard:     {$dashboardPath}\n";
echo "Strict Mode:           " . ($strictMode ? "ENABLED (Zero warnings tolerated)" : "DISABLED") . "\n\n";

$errors = [];
$warnings = [];
$passedChecks = [];

// -----------------------------------------------------------------------------
// 1. OpenTelemetry Tracing Configuration Validation
// -----------------------------------------------------------------------------
if (!file_exists($otelPath)) {
    $errors[] = "OpenTelemetry configuration file not found at: {$otelPath}";
} else {
    $otelContent = (string) file_get_contents($otelPath);

    if (str_contains($otelContent, 'tracing:') && str_contains($otelContent, 'enabled: true')) {
        $passedChecks[] = "OpenTelemetry distributed tracing explicitly enabled";
    } else {
        $errors[] = "OpenTelemetry configuration missing 'tracing.enabled: true'";
    }

    if (str_contains($otelContent, 'sampler:') && str_contains($otelContent, 'parent_based')) {
        $passedChecks[] = "OpenTelemetry sampler configured with ParentBased strategy";
    } else {
        $warnings[] = "OpenTelemetry sampler does not configure ParentBased strategy";
    }

    if (str_contains($otelContent, 'exporter:') && str_contains($otelContent, 'otlp')) {
        $passedChecks[] = "OpenTelemetry exporter configured for OTLP transport";
    } else {
        $errors[] = "OpenTelemetry exporter missing standard OTLP transport configuration";
    }

    // Auto-instrumentation spans
    $instrumentations = ['http', 'doctrine', 'messenger', 'http_client'];
    foreach ($instrumentations as $inst) {
        if (preg_match('/' . preg_quote($inst, '/') . ':\s*[\r\n]+\s+enabled:\s*true/i', $otelContent) ||
            str_contains($otelContent, "{$inst}:")
        ) {
            $passedChecks[] = "Auto-instrumentation span enabled for: '{$inst}'";
        } else {
            $warnings[] = "Missing auto-instrumentation configuration for: '{$inst}'";
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Prometheus Metrics Configuration Validation
// -----------------------------------------------------------------------------
if (!file_exists($promPath)) {
    $errors[] = "Prometheus configuration file not found at: {$promPath}";
} else {
    $promContent = (string) file_get_contents($promPath);

    if (str_contains($promContent, "endpoint: '/metrics'") || str_contains($promContent, 'endpoint: "/metrics"')) {
        $passedChecks[] = "Prometheus scraper endpoint configured at standard '/metrics'";
    } else {
        $warnings[] = "Prometheus metrics endpoint does not use standard '/metrics'";
    }

    $requiredMetrics = [
        'http_requests_total' => 'HTTP RED Rate & Counter metric',
        'http_request_duration_seconds' => 'HTTP Latency Histogram with percentile buckets',
        'database_queries_total' => 'Doctrine DBAL SQL query counter',
        'database_query_duration_seconds' => 'Database query latency histogram',
        'messenger_messages_total' => 'Symfony Messenger message processed counter',
        'messenger_queue_size' => 'Symfony Messenger backlog queue gauge',
        'memory_peak_bytes' => 'PHP runtime peak memory gauge',
        'opcache_hit_ratio' => 'PHP OPcache shared memory hit ratio gauge',
    ];

    foreach ($requiredMetrics as $metric => $desc) {
        if (str_contains($promContent, $metric)) {
            $passedChecks[] = "Prometheus metric defined: {$metric} ({$desc})";
        } else {
            $errors[] = "Missing mandatory Prometheus metric: {$metric} ({$desc})";
        }
    }
}

// -----------------------------------------------------------------------------
// 3. OpenTelemetry Collector Pipeline Configuration Validation
// -----------------------------------------------------------------------------
if (!file_exists($collectorPath)) {
    $errors[] = "OTel Collector config file not found at: {$collectorPath}";
} else {
    $collectorContent = (string) file_get_contents($collectorPath);

    if (str_contains($collectorContent, 'receivers:') && str_contains($collectorContent, 'otlp:')) {
        $passedChecks[] = "OTel Collector defines OTLP receivers (gRPC/HTTP)";
    } else {
        $errors[] = "OTel Collector missing OTLP receiver configuration";
    }

    if (str_contains($collectorContent, 'memory_limiter:') && str_contains($collectorContent, 'batch:')) {
        $passedChecks[] = "OTel Collector defines batching and memory_limiter safety processors";
    } else {
        $warnings[] = "OTel Collector missing batch or memory_limiter processors";
    }

    if (str_contains($collectorContent, 'pipelines:') && str_contains($collectorContent, 'traces:') && str_contains($collectorContent, 'metrics:')) {
        $passedChecks[] = "OTel Collector defines active service pipelines for traces and metrics";
    } else {
        $errors[] = "OTel Collector missing service pipelines for traces and metrics";
    }
}

// -----------------------------------------------------------------------------
// 4. Grafana Dashboard Specification Validation
// -----------------------------------------------------------------------------
if (!file_exists($dashboardPath)) {
    $errors[] = "Grafana dashboard file not found at: {$dashboardPath}";
} else {
    $dashboardJson = (string) file_get_contents($dashboardPath);
    $dashboardData = json_decode($dashboardJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "Grafana dashboard is not valid JSON: " . json_last_error_msg();
    } else {
        $passedChecks[] = "Grafana dashboard JSON syntax parsed successfully";

        if (!empty($dashboardData['title']) && !empty($dashboardData['uid'])) {
            $passedChecks[] = "Grafana dashboard specifies title ('{$dashboardData['title']}') and UID ('{$dashboardData['uid']}')";
        } else {
            $errors[] = "Grafana dashboard missing title or uid attribute";
        }

        $panels = $dashboardData['panels'] ?? [];
        if (count($panels) >= 6) {
            $passedChecks[] = "Grafana dashboard defines " . count($panels) . " visualization panels across golden signals";
        } else {
            $warnings[] = "Grafana dashboard defines fewer than 6 panels";
        }

        // Validate PromQL metric queries
        $jsonString = json_encode($dashboardData);
        if (str_contains($jsonString, 'symfony_http_requests_total') && str_contains($jsonString, 'symfony_http_request_duration_seconds')) {
            $passedChecks[] = "Grafana dashboard panels query official Symfony Prometheus metrics";
        } else {
            $errors[] = "Grafana dashboard does not reference symfony_http_requests_total metrics";
        }
    }
}

// -----------------------------------------------------------------------------
// Results & Report Generation
// -----------------------------------------------------------------------------
echo "Validation Results:\n";
foreach ($passedChecks as $pass) {
    echo "  [PASS] {$pass}\n";
}

if (!empty($warnings)) {
    echo "\nWarnings (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "  [WARN] {$w}\n";
    }
}

if (!empty($errors)) {
    echo "\nErrors (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  [FAIL] {$e}\n";
    }
}

$report = [
    'timestamp' => date('c'),
    'strict_mode' => $strictMode,
    'passed_checks' => count($passedChecks),
    'warnings_count' => count($warnings),
    'errors_count' => count($errors),
    'checks' => $passedChecks,
    'warnings' => $warnings,
    'errors' => $errors,
    'status' => (empty($errors) && (!$strictMode || empty($warnings))) ? 'PASSED' : 'FAILED',
];

if ($outputJson !== null) {
    file_put_contents($outputJson, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "\nStructured JSON report written to: {$outputJson}\n";
}

echo "\nSummary: " . count($passedChecks) . " passed, " . count($warnings) . " warnings, " . count($errors) . " errors.\n";

if (!empty($errors)) {
    echo "\nResult: FAILED — Observability validation encountered critical errors.\n";
    exit(1);
}

if ($strictMode && !empty($warnings)) {
    echo "\nResult: FAILED — Strict mode enabled and warnings were encountered.\n";
    exit(1);
}

echo "\nResult: PASSED — Observability, OpenTelemetry, and Grafana architecture validated successfully!\n";
exit(0);
