<?php

declare(strict_types=1);

/**
 * ==============================================================================
 * Symfony Engineering Agent — Disaster Recovery & Multi-Region Validator
 * ==============================================================================
 *
 * Validates enterprise Disaster Recovery (DR) policies, RTO/RPO compliance budgets,
 * Doctrine multi-region primary-replica pooling, and automated backup verification.
 *
 * Usage:
 *   php templates/disaster-recovery/dr-validator.php [options]
 *
 * Options:
 *   -h, --help            Display this help message and exit
 *   --policy=<path>       Path to DR policy YAML (default: templates/disaster-recovery/dr-policy.yaml.dist)
 *   --doctrine=<path>     Path to Doctrine multi-region YAML (default: templates/disaster-recovery/doctrine-multiregion.yaml.dist)
 *   --script=<path>       Path to backup verification script (default: templates/disaster-recovery/backup-verification.sh)
 *   --strict              Enforce zero tolerance on warnings
 *   --output-json=<file>  Save structured validation report as JSON
 */

$options = getopt('h', ['help', 'policy:', 'doctrine:', 'script:', 'strict', 'output-json:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
====================================================================
  Symfony Engineering Agent — Disaster Recovery Validator
====================================================================
Usage: php dr-validator.php [options]

Options:
  -h, --help            Display this help message and exit
  --policy=<path>       Path to DR policy YAML (default: templates/disaster-recovery/dr-policy.yaml.dist)
  --doctrine=<path>     Path to Doctrine multi-region YAML (default: templates/disaster-recovery/doctrine-multiregion.yaml.dist)
  --script=<path>       Path to backup verification script (default: templates/disaster-recovery/backup-verification.sh)
  --strict              Enforce zero tolerance on warnings
  --output-json=<file>  Save structured validation report as JSON

Documentation: docs/DISASTER_RECOVERY_AND_FAILOVER.md

HELP;
    exit(0);
}

$policyPath = (string) ($options['policy'] ?? 'templates/disaster-recovery/dr-policy.yaml.dist');
$doctrinePath = (string) ($options['doctrine'] ?? 'templates/disaster-recovery/doctrine-multiregion.yaml.dist');
$scriptPath = (string) ($options['script'] ?? 'templates/disaster-recovery/backup-verification.sh');
$strictMode = isset($options['strict']);
$outputJson = isset($options['output-json']) ? (string) $options['output-json'] : null;

echo "====================================================================\n";
echo "  Symfony Engineering Agent — Disaster Recovery Validator            \n";
echo "====================================================================\n";
echo "DR Policy File:      {$policyPath}\n";
echo "Doctrine Multi-Reg:  {$doctrinePath}\n";
echo "Backup Script:       {$scriptPath}\n";
echo "Strict Mode:         " . ($strictMode ? "ENABLED (Zero warnings tolerated)" : "DISABLED") . "\n\n";

$errors = [];
$warnings = [];
$passedChecks = [];

// -----------------------------------------------------------------------------
// 1. Disaster Recovery (DR) Policy Validation
// -----------------------------------------------------------------------------
if (!file_exists($policyPath)) {
    $errors[] = "DR policy file not found at: {$policyPath}";
} else {
    $policyContent = (string) file_get_contents($policyPath);

    // RTO and RPO limits
    if (preg_match('/tier_1:[\s\S]*?rto_seconds:\s*([0-9]+)/i', $policyContent, $mRto) &&
        preg_match('/tier_1:[\s\S]*?rpo_seconds:\s*([0-9]+)/i', $policyContent, $mRpo)
    ) {
        $rto = (int) $mRto[1];
        $rpo = (int) $mRpo[1];

        if ($rto <= 300) {
            $passedChecks[] = "Tier 1 RTO ({$rto}s) satisfies enterprise high-availability budget (<= 300s / 5m)";
        } else {
            $warnings[] = "Tier 1 RTO ({$rto}s) exceeds recommended 300s budget";
        }

        if ($rpo <= 60) {
            $passedChecks[] = "Tier 1 RPO ({$rpo}s) guarantees minimal data loss (<= 60s / 1m)";
        } else {
            $warnings[] = "Tier 1 RPO ({$rpo}s) exceeds recommended 60s budget";
        }
    } else {
        $errors[] = "DR policy missing Tier 1 RTO or RPO specifications";
    }

    // Multi-Region Topology
    if (str_contains($policyContent, 'primary_region:') && str_contains($policyContent, 'standby_region:')) {
        $passedChecks[] = "DR policy defines primary and hot standby cross-region topologies";
    } else {
        $errors[] = "DR policy missing primary_region or standby_region topologies";
    }

    // Failover triggers
    if (str_contains($policyContent, 'failover:') && str_contains($policyContent, 'triggers:')) {
        $passedChecks[] = "Automated failover triggers and health check thresholds configured";
    } else {
        $warnings[] = "DR policy missing automated failover trigger definitions";
    }

    // DNS failover TTL
    if (preg_match('/ttl_seconds:\s*([0-9]+)/i', $policyContent, $mTtl)) {
        $ttl = (int) $mTtl[1];
        if ($ttl <= 60) {
            $passedChecks[] = "DNS failover TTL ({$ttl}s) ensures rapid cross-region traffic shifting";
        } else {
            $warnings[] = "DNS failover TTL ({$ttl}s) exceeds recommended 60s for rapid recovery";
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Doctrine Multi-Region DBAL Configuration Validation
// -----------------------------------------------------------------------------
if (!file_exists($doctrinePath)) {
    $errors[] = "Doctrine multi-region file not found at: {$doctrinePath}";
} else {
    $doctrineContent = (string) file_get_contents($doctrinePath);

    if (str_contains($doctrineContent, 'PrimaryReadReplicaConnection')) {
        $passedChecks[] = "Doctrine DBAL configures PrimaryReadReplicaConnection for automatic read/write decoupling";
    } else {
        $errors[] = "Doctrine DBAL does not use PrimaryReadReplicaConnection wrapper class";
    }

    if (str_contains($doctrineContent, 'primary:') && str_contains($doctrineContent, 'replicas:')) {
        $passedChecks[] = "Doctrine DBAL defines distinct 'primary' and 'replicas' connection endpoints";
    } else {
        $errors[] = "Doctrine DBAL missing explicit 'primary' or 'replicas' configuration";
    }

    if (str_contains($doctrineContent, 'connect_timeout: 3') || str_contains($doctrineContent, 'connect_timeout')) {
        $passedChecks[] = "Connection timeout safeguards configured for rapid fault detection";
    } else {
        $warnings[] = "Doctrine DBAL does not configure explicit connect_timeout";
    }
}

// -----------------------------------------------------------------------------
// 3. Automated Backup Verification Script Validation
// -----------------------------------------------------------------------------
if (!file_exists($scriptPath)) {
    $errors[] = "Backup verification script not found at: {$scriptPath}";
} else {
    $scriptContent = (string) file_get_contents($scriptPath);

    if (str_contains($scriptContent, 'sha256sum')) {
        $passedChecks[] = "Backup script enforces cryptographic SHA256 checksum verification";
    } else {
        $warnings[] = "Backup script does not perform cryptographic checksum verification";
    }

    if (str_contains($scriptContent, 'doctrine:schema:validate')) {
        $passedChecks[] = "Backup script validates database schema integrity post-restoration";
    } else {
        $warnings[] = "Backup script does not execute schema integrity checks";
    }

    if (str_contains($scriptContent, 'REPORT_FILE') && str_contains($scriptContent, '.json')) {
        $passedChecks[] = "Backup script exports structured JSON verification report";
    } else {
        $warnings[] = "Backup script does not output structured JSON reports";
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
    echo "\nResult: FAILED — Disaster Recovery validation encountered critical errors.\n";
    exit(1);
}

if ($strictMode && !empty($warnings)) {
    echo "\nResult: FAILED — Strict mode enabled and warnings were encountered.\n";
    exit(1);
}

echo "\nResult: PASSED — Disaster Recovery, Multi-Region, and Backup verification validated successfully!\n";
exit(0);
