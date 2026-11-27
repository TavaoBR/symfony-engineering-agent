<?php

declare(strict_types=1);

/**
 * ==============================================================================
 * Symfony Engineering Agent — Docker & Container Architecture Validator
 * ==============================================================================
 *
 * Validates multi-stage Dockerfiles, Docker Compose orchestrations, and Caddyfile
 * configurations against production readiness, security policies, and performance.
 *
 * Usage:
 *   php templates/docker/docker-validator.php [options]
 *
 * Options:
 *   -h, --help            Display this help message and exit
 *   --dockerfile=<path>   Path to Dockerfile (default: templates/docker/Dockerfile.frankenphp)
 *   --compose=<path>      Path to Docker Compose file (default: templates/docker/compose.yaml.dist)
 *   --caddyfile=<path>    Path to Caddyfile (default: templates/docker/Caddyfile.dist)
 *   --strict              Enforce zero tolerance on warnings
 *   --output-json=<file>  Save structured report to JSON
 */

$options = getopt('h', ['help', 'dockerfile:', 'compose:', 'caddyfile:', 'strict', 'output-json:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
====================================================================
  Symfony Engineering Agent — Container Architecture Validator
====================================================================
Usage: php docker-validator.php [options]

Options:
  -h, --help            Display this help message and exit
  --dockerfile=<path>   Path to Dockerfile (default: templates/docker/Dockerfile.frankenphp)
  --compose=<path>      Path to Docker Compose (default: templates/docker/compose.yaml.dist)
  --caddyfile=<path>    Path to Caddyfile (default: templates/docker/Caddyfile.dist)
  --strict              Enforce zero tolerance on warnings
  --output-json=<file>  Save structured validation report as JSON

Documentation: docs/DOCKER_CONTAINERIZATION.md

HELP;
    exit(0);
}

$dockerfilePath = (string) ($options['dockerfile'] ?? 'templates/docker/Dockerfile.frankenphp');
$composePath = (string) ($options['compose'] ?? 'templates/docker/compose.yaml.dist');
$caddyfilePath = (string) ($options['caddyfile'] ?? 'templates/docker/Caddyfile.dist');
$strictMode = isset($options['strict']);
$outputJson = isset($options['output-json']) ? (string) $options['output-json'] : null;

echo "====================================================================\n";
echo "  Symfony Engineering Agent — Container Architecture Validator      \n";
echo "====================================================================\n";
echo "Dockerfile:         {$dockerfilePath}\n";
echo "Docker Compose:     {$composePath}\n";
echo "Caddyfile:          {$caddyfilePath}\n";
echo "Strict Mode:        " . ($strictMode ? "ENABLED (Zero warnings tolerated)" : "DISABLED") . "\n\n";

$errors = [];
$warnings = [];
$passedChecks = [];

// -----------------------------------------------------------------------------
// 1. Dockerfile Multi-Stage & Security Validation
// -----------------------------------------------------------------------------
if (!file_exists($dockerfilePath)) {
    $errors[] = "Dockerfile not found at path: {$dockerfilePath}";
} else {
    $content = (string) file_get_contents($dockerfilePath);

    // Stage targets
    $stages = ['base', 'builder', 'dev', 'prod'];
    foreach ($stages as $stage) {
        if (preg_match('/FROM\s+[^\s]+\s+AS\s+' . preg_quote($stage, '/') . '/i', $content)) {
            $passedChecks[] = "Dockerfile defines mandatory multi-stage target: '{$stage}'";
        } else {
            $errors[] = "Dockerfile missing required multi-stage target: '{$stage}'";
        }
    }

    // Non-root USER in prod
    if (preg_match('/FROM\s+[^\s]+\s+AS\s+prod[\s\S]*?USER\s+(www-data|[0-9]{3,})/i', $content)) {
        $passedChecks[] = "Production target enforces non-root USER execution";
    } else {
        $errors[] = "Production target does NOT enforce a non-root USER directive (Security Violation: OWASP Container Hardening)";
    }

    // HEALTHCHECK directive in prod
    if (preg_match('/FROM\s+[^\s]+\s+AS\s+prod[\s\S]*?HEALTHCHECK/i', $content)) {
        $passedChecks[] = "Production target defines automated container HEALTHCHECK";
    } else {
        $errors[] = "Production target missing HEALTHCHECK instruction";
    }

    // OPcache tuning
    if (str_contains($content, 'opcache.enable=1') && str_contains($content, 'opcache.memory_consumption')) {
        $passedChecks[] = "OPcache configured with high-performance memory consumption tuning";
    } else {
        $warnings[] = "OPcache configuration does not explicitly assert memory_consumption tuning";
    }

    // Expose PHP header leak prevention
    if (str_contains($content, 'expose_php = Off')) {
        $passedChecks[] = "PHP info leak prevented via 'expose_php = Off'";
    } else {
        $warnings[] = "PHP configuration does not disable 'expose_php'";
    }
}

// -----------------------------------------------------------------------------
// 2. Docker Compose Orchestration Validation
// -----------------------------------------------------------------------------
if (!file_exists($composePath)) {
    $errors[] = "Docker Compose file not found at path: {$composePath}";
} else {
    $composeContent = (string) file_get_contents($composePath);

    // Services check
    $requiredServices = ['app', 'database', 'redis'];
    foreach ($requiredServices as $service) {
        if (preg_match('/^\s{2}' . preg_quote($service, '/') . ':\s*$/m', $composeContent)) {
            $passedChecks[] = "Docker Compose defines service: '{$service}'";
        } else {
            $errors[] = "Docker Compose missing required service: '{$service}'";
        }
    }

    // Healthchecks for services
    if (str_contains($composeContent, 'pg_isready')) {
        $passedChecks[] = "PostgreSQL service defines database healthcheck probe ('pg_isready')";
    } else {
        $warnings[] = "PostgreSQL service missing automated healthcheck probe ('pg_isready')";
    }

    if (str_contains($composeContent, 'redis-cli') && str_contains($composeContent, 'ping')) {
        $passedChecks[] = "Redis service defines cache healthcheck probe ('redis-cli ping')";
    } else {
        $warnings[] = "Redis service missing automated healthcheck probe";
    }

    // App depends_on condition: service_healthy
    if (str_contains($composeContent, 'condition: service_healthy')) {
        $passedChecks[] = "App service depends on database and redis health conditions";
    } else {
        $warnings[] = "App service does not enforce 'condition: service_healthy' on dependencies";
    }

    // HTTP/3 QUIC port mapping
    if (str_contains($composeContent, '443:443/udp')) {
        $passedChecks[] = "App service exposes HTTP/3 QUIC UDP port (443/udp)";
    } else {
        $warnings[] = "App service does not expose HTTP/3 QUIC UDP port (443:443/udp)";
    }
}

// -----------------------------------------------------------------------------
// 3. Caddyfile Validation
// -----------------------------------------------------------------------------
if (!file_exists($caddyfilePath)) {
    $errors[] = "Caddyfile not found at path: {$caddyfilePath}";
} else {
    $caddyContent = (string) file_get_contents($caddyfilePath);

    // Worker mode
    if (str_contains($caddyContent, 'worker') && str_contains($caddyContent, 'public/index.php')) {
        $passedChecks[] = "FrankenPHP configured in high-performance Worker Mode";
    } else {
        $warnings[] = "Caddyfile does not specify FrankenPHP worker mode for public/index.php";
    }

    // Compression
    if (str_contains($caddyContent, 'encode zstd gzip') || str_contains($caddyContent, 'encode gzip')) {
        $passedChecks[] = "Response compression enabled (zstd / gzip)";
    } else {
        $warnings[] = "Caddyfile missing HTTP response compression directive ('encode zstd gzip')";
    }

    // Security Headers
    $headers = [
        'Strict-Transport-Security',
        'X-Content-Type-Options',
        'X-Frame-Options',
        'Referrer-Policy',
    ];
    $missingHeaders = [];
    foreach ($headers as $header) {
        if (!str_contains($caddyContent, $header)) {
            $missingHeaders[] = $header;
        }
    }

    if (empty($missingHeaders)) {
        $passedChecks[] = "All mandatory HTTP security headers configured (HSTS, nosniff, DENY, Referrer-Policy)";
    } else {
        $errors[] = "Caddyfile missing security headers: " . implode(', ', $missingHeaders);
    }

    // Healthcheck endpoint
    if (str_contains($caddyContent, 'respond /up 200')) {
        $passedChecks[] = "Lightweight healthcheck endpoint '/up' configured";
    } else {
        $warnings[] = "Caddyfile missing healthcheck endpoint ('respond /up 200')";
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
    echo "\nResult: FAILED — Container validation found critical errors.\n";
    exit(1);
}

if ($strictMode && !empty($warnings)) {
    echo "\nResult: FAILED — Strict mode enabled and warnings were encountered.\n";
    exit(1);
}

echo "\nResult: PASSED — Container architecture validated successfully!\n";
exit(0);
