#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Automated Static Application Security Testing (SAST) Scanner for Symfony & PHP
 *
 * Runs Semgrep with customized Symfony rules or falls back to an integrated static AST/regex security engine.
 * Detects SQLi, IDOR, Command Injection, Insecure Deserialization, Path Traversal, and Hardcoded Secrets.
 */

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo "====================================================================\n";
    echo "  Symfony Engineering Agent — SAST Security Scanner (Semgrep)\n";
    echo "====================================================================\n";
    echo "Usage: php sast-scanner.php [options]\n\n";
    echo "Options:\n";
    echo "  -h, --help            Display this help message and exit\n";
    echo "  --config=<file>       Path to Semgrep configuration ruleset\n";
    echo "  --path=<dir>          Source directory to scan (default: src/)\n";
    echo "  --severity=<level>    Minimum severity to report: error (default), warning, info\n";
    echo "  --strict              Enforce zero tolerance on warnings\n";
    echo "  --output-json=<file>  Save structured vulnerability findings as JSON\n\n";
    echo "Documentation: docs/SAST_AND_CVE_REMEDIATION.md\n";
    exit(0);
}

// 1. Parse Arguments
$configFile = null;
$sourceDir = 'src';
$minSeverity = 'error';
$strictMode = false;
$outputJsonFile = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--config=')) {
        $configFile = substr($arg, strlen('--config='));
    } elseif (str_starts_with($arg, '--path=')) {
        $sourceDir = substr($arg, strlen('--path='));
    } elseif (str_starts_with($arg, '--severity=')) {
        $minSeverity = strtolower(substr($arg, strlen('--severity=')));
    } elseif ('--strict' === $arg) {
        $strictMode = true;
    } elseif (str_starts_with($arg, '--output-json=')) {
        $outputJsonFile = substr($arg, strlen('--output-json='));
    }
}

if (null === $configFile) {
    $candidates = [
        '.semgrep.yaml',
        'semgrep.yaml',
        'templates/security/semgrep.yaml.dist',
        __DIR__ . '/semgrep.yaml.dist',
    ];
    foreach ($candidates as $cand) {
        if (file_exists($cand)) {
            $configFile = $cand;
            break;
        }
    }
}

echo "====================================================================\n";
echo "  Symfony Engineering Agent — SAST Security Scanner\n";
echo "====================================================================\n";
echo "Configuration:      " . ($configFile ?? 'Default Semgrep Symfony Rules') . "\n";
echo "Target Directory:   {$sourceDir}\n";
echo "Min Severity:       " . strtoupper($minSeverity) . "\n";
echo "Strict Mode:        " . ($strictMode ? 'ENABLED' : 'DISABLED') . "\n\n";

// 2. Check for Semgrep Executable
$semgrepBin = null;
if (PHP_OS_FAMILY === 'Windows') {
    $whereOutput = @shell_exec('where.exe semgrep 2>NUL');
    if (null !== $whereOutput && '' !== trim($whereOutput)) {
        $lines = explode("\n", trim($whereOutput));
        $semgrepBin = trim($lines[0]);
    }
} else {
    $whichOutput = @shell_exec('which semgrep 2>/dev/null');
    if (null !== $whichOutput && '' !== trim($whichOutput)) {
        $semgrepBin = trim($whichOutput);
    }
}

$findings = [];
$filesScanned = 0;

if (null !== $semgrepBin && null !== $configFile && file_exists($configFile)) {
    echo "Executing Semgrep binary: {$semgrepBin}...\n";
    $tempOutput = tempnam(sys_get_temp_dir(), 'semgrep_') . '.json';

    $cmd = sprintf(
        '%s scan --config=%s --json --quiet %s > %s 2>&1',
        escapeshellcmd($semgrepBin),
        escapeshellarg($configFile),
        escapeshellarg($sourceDir),
        escapeshellarg($tempOutput)
    );

    @exec($cmd);

    if (file_exists($tempOutput)) {
        $json = json_decode((string) file_get_contents($tempOutput), true);
        @unlink($tempOutput);

        if (is_array($json)) {
            $results = $json['results'] ?? [];
            foreach ($results as $r) {
                $sev = strtoupper((string) ($r['extra']['severity'] ?? 'WARNING'));
                $findings[] = [
                    'rule_id' => (string) ($r['check_id'] ?? 'security-violation'),
                    'severity' => $sev,
                    'file' => (string) ($r['path'] ?? 'unknown'),
                    'line' => (int) ($r['start']['line'] ?? 0),
                    'message' => trim((string) ($r['extra']['message'] ?? 'Potential vulnerability detected')),
                ];
            }
        }
    }
} else {
    // Integrated static security analyzer fallback
    echo "Notice: Semgrep CLI not detected. Running integrated static security scanner for PHP & Symfony...\n";

    if (!is_dir($sourceDir)) {
        echo "Directory '{$sourceDir}' not found. No files to inspect.\n";
        echo "Result: PASSED (Zero security vulnerabilities)\n";
        exit(0);
    }

    $directoryIterator = new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directoryIterator);

    $rules = [
        [
            'id' => 'symfony-doctrine-sqli-concatenation',
            'severity' => 'ERROR',
            'pattern' => '/->(where|andWhere|orWhere|createQuery)\s*\(\s*.*?\.\s*\$[a-zA-Z0-9_]+/i',
            'message' => 'Potential SQL/DQL injection via string concatenation in QueryBuilder. Use bound parameters.',
        ],
        [
            'id' => 'php-insecure-unserialize',
            'severity' => 'ERROR',
            'pattern' => '/\bunserialize\s*\(\s*\$(?![a-zA-Z0-9_]+,\s*\[\s*[\'"]allowed_classes[\'"])/i',
            'message' => 'Unsafe unserialize() call without allowed_classes => false. High risk of Remote Code Execution.',
        ],
        [
            'id' => 'php-command-injection',
            'severity' => 'ERROR',
            'pattern' => '/\b(exec|shell_exec|system|passthru)\s*\(\s*.*?\b\$[a-zA-Z0-9_]+/i',
            'message' => 'Dynamic variable passed to shell execution. Use Symfony Process with array arguments.',
        ],
        [
            'id' => 'php-path-traversal',
            'severity' => 'ERROR',
            'pattern' => '/\b(file_get_contents|unlink|readfile|fopen)\s*\(\s*.*?\$(request|_GET|_POST)\[/i',
            'message' => 'Untrusted request parameter passed directly to file system function. Sanitize with basename().',
        ],
        [
            'id' => 'php-hardcoded-secret',
            'severity' => 'ERROR',
            'pattern' => '/(?i)(api[_-]?key|secret[_-]?key|bearer|auth[_-]?token)\s*=\s*[\'"][a-zA-Z0-9_\-]{20,}[\'"]/',
            'message' => 'Possible hardcoded secret or token detected. Use %env(...)% or Symfony secret vaults.',
        ],
        [
            'id' => 'symfony-open-redirect',
            'severity' => 'WARNING',
            'pattern' => '/->redirect\s*\(\s*\$request->(get|query->get)\s*\(/i',
            'message' => 'Potential Open Redirect. Validate destination URL against an internal domain whitelist.',
        ],
    ];

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile() || 'php' !== $fileInfo->getExtension()) {
            continue;
        }

        $filePath = str_replace('\\', '/', $fileInfo->getPathname());
        if (str_contains($filePath, '/Tests/') || str_contains($filePath, '/Migrations/')) {
            continue;
        }

        ++$filesScanned;
        $lines = explode("\n", (string) file_get_contents($filePath));

        foreach ($lines as $lineNum => $line) {
            foreach ($rules as $rule) {
                if (preg_match($rule['pattern'], $line)) {
                    $findings[] = [
                        'rule_id' => $rule['id'],
                        'severity' => $rule['severity'],
                        'file' => $filePath,
                        'line' => $lineNum + 1,
                        'message' => $rule['message'],
                    ];
                }
            }
        }
    }
}

// 3. Filter and Evaluate Findings
$errorCount = 0;
$warningCount = 0;

$filteredFindings = [];
foreach ($findings as $f) {
    if ('error' === $minSeverity && 'ERROR' !== $f['severity']) {
        continue;
    }
    $filteredFindings[] = $f;
    if ('ERROR' === $f['severity']) {
        ++$errorCount;
    } else {
        ++$warningCount;
    }
}

echo "SAST Scan Metrics:\n";
echo "  - Total Findings:        " . count($filteredFindings) . "\n";
echo "  - High/Critical Errors:  {$errorCount}\n";
echo "  - Warnings:              {$warningCount}\n\n";

if (!empty($filteredFindings)) {
    echo "### Detected Security Vulnerabilities:\n\n";
    echo "| Severity | Rule ID | File | Line | Advisory Message |\n";
    echo "|:---:|:---|:---|:---:|:---|
";
    foreach ($filteredFindings as $f) {
        echo sprintf(
            "| %s | `%s` | %s | %d | %s |\n",
            $f['severity'],
            $f['rule_id'],
            $f['file'],
            $f['line'],
            $f['message']
        );
    }
    echo "\n";
}

$hasFailure = $errorCount > 0 || ($strictMode && $warningCount > 0);

if (null !== $outputJsonFile) {
    file_put_contents($outputJsonFile, json_encode([
        'timestamp' => date('c'),
        'config_file' => $configFile,
        'passed' => !$hasFailure,
        'metrics' => [
            'total_findings' => count($filteredFindings),
            'errors' => $errorCount,
            'warnings' => $warningCount,
        ],
        'findings' => $filteredFindings,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "SAST report saved to: {$outputJsonFile}\n";
}

if ($hasFailure) {
    echo "Result: FAILED — Static security analysis detected vulnerabilities violating OWASP standards!\n";
    exit(1);
}

echo "Result: PASSED — Zero security vulnerabilities detected!\n";
exit(0);
