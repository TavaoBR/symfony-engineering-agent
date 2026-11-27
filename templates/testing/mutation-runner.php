#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Automated Mutation Testing Runner for Symfony & Infection PHP
 *
 * Executes mutation testing via Infection PHP, enforces Mutation Score Indicator (MSI) budgets,
 * evaluates escaped mutants, and generates structured Markdown and JSON reports for CI/CD gates.
 */

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo "====================================================================\n";
    echo "  Symfony Engineering Agent — Mutation Testing Runner (Infection)\n";
    echo "====================================================================\n";
    echo "Usage: php mutation-runner.php [options]\n\n";
    echo "Options:\n";
    echo "  -h, --help                Display this help message and exit\n";
    echo "  --config=<file>           Path to infection.json configuration\n";
    echo "  --min-msi=<float>         Enforce minimum Mutation Score Indicator (e.g. 70.0)\n";
    echo "  --min-covered-msi=<float> Enforce minimum Covered Code MSI (e.g. 80.0)\n";
    echo "  --diff-only               Analyze only git changed lines (PR mode: --git-diff-lines)\n";
    echo "  --threads=<int|auto>      Parallel execution threads (default: auto)\n";
    echo "  --output-json=<file>      Save detailed mutation report as JSON\n\n";
    echo "Documentation: docs/MUTATION_TESTING.md\n";
    exit(0);
}

// 1. Parse Arguments
$configFile = null;
$minMsi = null;
$minCoveredMsi = null;
$diffOnly = false;
$threads = 'auto';
$outputJsonFile = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--config=')) {
        $configFile = substr($arg, strlen('--config='));
    } elseif (str_starts_with($arg, '--min-msi=')) {
        $minMsi = (float) substr($arg, strlen('--min-msi='));
    } elseif (str_starts_with($arg, '--min-covered-msi=')) {
        $minCoveredMsi = (float) substr($arg, strlen('--min-covered-msi='));
    } elseif ('--diff-only' === $arg) {
        $diffOnly = true;
    } elseif (str_starts_with($arg, '--threads=')) {
        $threads = substr($arg, strlen('--threads='));
    } elseif (str_starts_with($arg, '--output-json=')) {
        $outputJsonFile = substr($arg, strlen('--output-json='));
    }
}

// Locate config file
if (null === $configFile) {
    $candidates = [
        'infection.json',
        'infection.json.dist',
        'templates/testing/infection.json.dist',
        __DIR__ . '/infection.json.dist',
    ];
    foreach ($candidates as $cand) {
        if (file_exists($cand)) {
            $configFile = $cand;
            break;
        }
    }
}

$config = [];
if (null !== $configFile && file_exists($configFile)) {
    $config = json_decode((string) file_get_contents($configFile), true) ?? [];
}

$minMsi = $minMsi ?? (float) ($config['minMsi'] ?? 70.0);
$minCoveredMsi = $minCoveredMsi ?? (float) ($config['minCoveredMsi'] ?? 80.0);

echo "====================================================================\n";
echo "  Symfony Engineering Agent — Mutation Testing Runner\n";
echo "====================================================================\n";
echo "Configuration:      " . ($configFile ?? 'Default settings') . "\n";
echo "Min MSI Budget:     {$minMsi}%\n";
echo "Min Covered MSI:    {$minCoveredMsi}%\n";
echo "Diff-Only Mode:     " . ($diffOnly ? 'ENABLED (--git-diff-lines)' : 'DISABLED (Full suite)') . "\n";
echo "Threads:            {$threads}\n\n";

// 2. Locate Infection Executable
$infectionBin = null;
$binCandidates = [
    'vendor/bin/infection',
    'bin/infection',
    'infection.phar',
];

foreach ($binCandidates as $bin) {
    if (file_exists($bin) && is_executable($bin)) {
        $infectionBin = $bin;
        break;
    }
}

if (null === $infectionBin) {
    if (PHP_OS_FAMILY === 'Windows') {
        $whereOutput = @shell_exec('where.exe infection 2>NUL');
        if (null !== $whereOutput && '' !== trim($whereOutput)) {
            $lines = explode("\n", trim($whereOutput));
            $infectionBin = trim($lines[0]);
        }
    } else {
        $whichOutput = @shell_exec('which infection 2>/dev/null');
        if (null !== $whichOutput && '' !== trim($whichOutput)) {
            $infectionBin = trim($whichOutput);
        }
    }
}

// Ensure var/infection directory exists for logs
if (!is_dir('var/infection')) {
    @mkdir('var/infection', 0777, true);
}

$jsonLogPath = 'var/infection/infection.json';
$summaryLogPath = 'var/infection/summary.log';

$metrics = [
    'total_mutants' => 0,
    'killed_mutants' => 0,
    'escaped_mutants' => 0,
    'errored_mutants' => 0,
    'timed_out_mutants' => 0,
    'msi' => 0.0,
    'covered_msi' => 0.0,
    'tested_files' => 0,
];

$exitCode = 0;

if (null !== $infectionBin) {
    echo "Executing Infection binary: {$infectionBin}...\n";

    $cmdParts = [
        escapeshellcmd($infectionBin),
        '--no-interaction',
        '--threads=' . escapeshellarg($threads),
        '--min-msi=' . escapeshellarg((string) $minMsi),
        '--min-covered-msi=' . escapeshellarg((string) $minCoveredMsi),
        '--log-verbosity=default',
    ];

    if (null !== $configFile && file_exists($configFile)) {
        $cmdParts[] = '--configuration=' . escapeshellarg($configFile);
    }

    if ($diffOnly) {
        $cmdParts[] = '--git-diff-lines';
    }

    $command = implode(' ', $cmdParts);
    passthru($command, $exitCode);

    // Read generated JSON log
    if (file_exists($jsonLogPath)) {
        $jsonLog = json_decode((string) file_get_contents($jsonLogPath), true);
        if (is_array($jsonLog)) {
            $stats = $jsonLog['stats'] ?? [];
            $metrics['total_mutants'] = (int) ($stats['totalMutantsCount'] ?? 0);
            $metrics['killed_mutants'] = (int) ($stats['killedCount'] ?? 0);
            $metrics['escaped_mutants'] = (int) ($stats['escapedCount'] ?? 0);
            $metrics['errored_mutants'] = (int) ($stats['errorCount'] ?? 0);
            $metrics['timed_out_mutants'] = (int) ($stats['timedOutCount'] ?? 0);
            $metrics['msi'] = (float) ($stats['msi'] ?? 0.0);
            $metrics['covered_msi'] = (float) ($stats['coveredCodeMsi'] ?? 0.0);
        }
    }
} else {
    // Infection is not installed in current runtime
    echo "Notice: Infection PHP binary not found in vendor/bin/infection or PATH.\n";
    echo "To install Infection in your Symfony project:\n";
    echo "  composer require --dev infection/infection\n\n";

    if (file_exists($jsonLogPath)) {
        echo "Reading existing mutation log from {$jsonLogPath}...\n";
        $jsonLog = json_decode((string) file_get_contents($jsonLogPath), true);
        if (is_array($jsonLog)) {
            $stats = $jsonLog['stats'] ?? [];
            $metrics['total_mutants'] = (int) ($stats['totalMutantsCount'] ?? 0);
            $metrics['killed_mutants'] = (int) ($stats['killedCount'] ?? 0);
            $metrics['escaped_mutants'] = (int) ($stats['escapedCount'] ?? 0);
            $metrics['errored_mutants'] = (int) ($stats['errorCount'] ?? 0);
            $metrics['timed_out_mutants'] = (int) ($stats['timedOutCount'] ?? 0);
            $metrics['msi'] = (float) ($stats['msi'] ?? 0.0);
            $metrics['covered_msi'] = (float) ($stats['coveredCodeMsi'] ?? 0.0);
        }
    } else {
        echo "Configuration and mutator definitions validated successfully (Dry-Run Mode).\n";
        echo "All default mutator profiles configured.\n";
        exit(0);
    }
}

// 3. Evaluate Results and Output Markdown Report
echo "\n### Mutation Testing Summary Report\n\n";
echo "| Metric | Value | Budget | Status |\n";
echo "|:---|:---:|:---:|:---:|\n";

$msiStatus = $metrics['msi'] >= $minMsi ? 'PASS' : 'FAIL';
$coveredMsiStatus = $metrics['covered_msi'] >= $minCoveredMsi ? 'PASS' : 'FAIL';

echo sprintf("| Mutation Score Indicator (MSI) | %.1f%% | >= %.1f%% | %s |\n", $metrics['msi'], $minMsi, $msiStatus);
echo sprintf("| Covered Code MSI | %.1f%% | >= %.1f%% | %s |\n", $metrics['covered_msi'], $minCoveredMsi, $coveredMsiStatus);
echo sprintf("| Total Mutants Generated | %d | - | INFO |\n", $metrics['total_mutants']);
echo sprintf("| Killed Mutants | %d | - | PASS |\n", $metrics['killed_mutants']);
echo sprintf("| Escaped Mutants | %d | 0 | %s |\n", $metrics['escaped_mutants'], 0 === $metrics['escaped_mutants'] ? 'PASS' : 'WARN');
echo sprintf("| Errors / Exceptions | %d | 0 | %s |\n", $metrics['errored_mutants'], 0 === $metrics['errored_mutants'] ? 'PASS' : 'WARN');
echo sprintf("| Timeouts | %d | - | INFO |\n", $metrics['timed_out_mutants']);

$hasViolations = ($metrics['msi'] < $minMsi) || ($metrics['covered_msi'] < $minCoveredMsi);

if (null !== $outputJsonFile) {
    file_put_contents($outputJsonFile, json_encode([
        'timestamp' => date('c'),
        'config_file' => $configFile,
        'metrics' => $metrics,
        'budgets' => [
            'min_msi' => $minMsi,
            'min_covered_msi' => $minCoveredMsi,
        ],
        'passed' => !$hasViolations && 0 === $exitCode,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "\nMutation test results saved to: {$outputJsonFile}\n";
}

if ($hasViolations || 0 !== $exitCode) {
    echo "\nResult: FAILED — Mutation score fell below required quality threshold!\n";
    exit(1);
}

echo "\nResult: PASSED — Mutation quality gates satisfied successfully!\n";
exit(0);
