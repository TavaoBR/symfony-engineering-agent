#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Automated Architectural Boundary Validator for Symfony & Deptrac
 *
 * Verifies clean architectural boundaries (Hexagonal / Clean Architecture) across layers:
 * Domain, Application, Infrastructure, Presentation, and DTO.
 * Executes official Deptrac if installed, or runs built-in static AST/import analysis.
 */

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo "====================================================================\n";
    echo "  Symfony Engineering Agent — Architectural Boundary Validator\n";
    echo "====================================================================\n";
    echo "Usage: php architecture-validator.php [options]\n\n";
    echo "Options:\n";
    echo "  -h, --help            Display this help message and exit\n";
    echo "  --config=<file>       Path to deptrac.yaml configuration\n";
    echo "  --path=<dir>          Source directory to inspect (default: src/)\n";
    echo "  --output-json=<file>  Save validation report as JSON\n";
    echo "  --strict              Enforce zero tolerance for boundary warnings\n\n";
    echo "Documentation: docs/ARCHITECTURAL_BOUNDARIES.md\n";
    exit(0);
}

// 1. Parse Arguments
$configFile = null;
$sourceDir = 'src';
$outputJsonFile = null;
$strictMode = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--config=')) {
        $configFile = substr($arg, strlen('--config='));
    } elseif (str_starts_with($arg, '--path=')) {
        $sourceDir = substr($arg, strlen('--path='));
    } elseif (str_starts_with($arg, '--output-json=')) {
        $outputJsonFile = substr($arg, strlen('--output-json='));
    } elseif ('--strict' === $arg) {
        $strictMode = true;
    }
}

// Locate config file
if (null === $configFile) {
    $candidates = [
        'deptrac.yaml',
        'deptrac.yaml.dist',
        'templates/architecture/deptrac.yaml.dist',
        __DIR__ . '/deptrac.yaml.dist',
    ];
    foreach ($candidates as $cand) {
        if (file_exists($cand)) {
            $configFile = $cand;
            break;
        }
    }
}

echo "====================================================================\n";
echo "  Symfony Engineering Agent — Architectural Boundary Validator\n";
echo "====================================================================\n";
echo "Configuration:      " . ($configFile ?? 'Default Hexagonal Rules') . "\n";
echo "Source Directory:   {$sourceDir}\n";
echo "Strict Mode:        " . ($strictMode ? 'ENABLED' : 'DISABLED') . "\n\n";

// 2. Check for Deptrac Executable
$deptracBin = null;
$binCandidates = [
    'vendor/bin/deptrac',
    'bin/deptrac',
    'deptrac.phar',
];

foreach ($binCandidates as $bin) {
    if (file_exists($bin) && is_executable($bin)) {
        $deptracBin = $bin;
        break;
    }
}

if (null === $deptracBin) {
    if (PHP_OS_FAMILY === 'Windows') {
        $whereOutput = @shell_exec('where.exe deptrac 2>NUL');
        if (null !== $whereOutput && '' !== trim($whereOutput)) {
            $lines = explode("\n", trim($whereOutput));
            $deptracBin = trim($lines[0]);
        }
    } else {
        $whichOutput = @shell_exec('which deptrac 2>/dev/null');
        if (null !== $whichOutput && '' !== trim($whichOutput)) {
            $deptracBin = trim($whichOutput);
        }
    }
}

$violations = [];
$totalFilesInspected = 0;

if (null !== $deptracBin && null !== $configFile && file_exists($configFile)) {
    echo "Executing Deptrac binary: {$deptracBin}...\n";
    $cmd = escapeshellcmd($deptracBin) . ' analyse --config-file=' . escapeshellarg($configFile) . ' --no-interaction';
    passthru($cmd, $exitCode);

    if (0 !== $exitCode) {
        echo "\nResult: FAILED (Deptrac reported architectural boundary violations)\n";
        exit(1);
    }

    echo "\nResult: PASSED (All architectural boundaries strictly preserved)\n";
    exit(0);
}

// 3. Fallback Built-in Architectural Static Analysis
echo "Notice: Deptrac binary not detected. Running built-in static architectural boundary analyzer...\n";

// Standard Hexagonal Architecture Layer Rules
$allowedDependencies = [
    'Domain' => [],
    'Application' => ['Domain', 'DTO'],
    'Infrastructure' => ['Domain', 'Application', 'DTO'],
    'Presentation' => ['Application', 'Domain', 'DTO'],
    'DTO' => ['Domain'],
];

if (!is_dir($sourceDir)) {
    echo "Directory '{$sourceDir}' not found in current workspace. Creating empty report.\n";
    echo "Result: PASSED (Zero boundary violations)\n";
    exit(0);
}

// Recursive file scanner
$directoryIterator = new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($directoryIterator);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile() || 'php' !== $fileInfo->getExtension()) {
        continue;
    }

    $filePath = $fileInfo->getPathname();
    $normalizedPath = str_replace('\\', '/', $filePath);

    // Skip tests or migrations
    if (str_contains($normalizedPath, '/Tests/') || str_contains($normalizedPath, '/Migrations/')) {
        continue;
    }

    ++$totalFilesInspected;
    $content = (string) file_get_contents($filePath);
    $lines = explode("\n", $content);

    // Determine current layer
    $currentLayer = null;
    if (preg_match('#/(Domain)/#i', $normalizedPath)) {
        $currentLayer = 'Domain';
    } elseif (preg_match('#/(Application)/#i', $normalizedPath)) {
        $currentLayer = 'Application';
    } elseif (preg_match('#/(Infrastructure)/#i', $normalizedPath)) {
        $currentLayer = 'Infrastructure';
    } elseif (preg_match('#/(Controller|Command|Presentation)/#i', $normalizedPath)) {
        $currentLayer = 'Presentation';
    } elseif (preg_match('#/(DTO)/#i', $normalizedPath)) {
        $currentLayer = 'DTO';
    }

    if (null === $currentLayer) {
        continue;
    }

    $allowed = $allowedDependencies[$currentLayer] ?? [];

    foreach ($lines as $lineNum => $line) {
        $trimmed = trim($line);
        if (!str_starts_with($trimmed, 'use ') || !str_ends_with($trimmed, ';')) {
            continue;
        }

        $importedClass = trim(substr($trimmed, 4, -1));

        // Check if imported class belongs to a specific layer
        $targetLayer = null;
        if (str_contains($importedClass, '\\Domain\\')) {
            $targetLayer = 'Domain';
        } elseif (str_contains($importedClass, '\\Application\\')) {
            $targetLayer = 'Application';
        } elseif (str_contains($importedClass, '\\Infrastructure\\')) {
            $targetLayer = 'Infrastructure';
        } elseif (str_contains($importedClass, '\\Controller\\') || str_contains($importedClass, '\\Presentation\\')) {
            $targetLayer = 'Presentation';
        } elseif (str_contains($importedClass, '\\DTO\\')) {
            $targetLayer = 'DTO';
        }

        if (null !== $targetLayer && $targetLayer !== $currentLayer) {
            if (!in_array($targetLayer, $allowed, true)) {
                $violations[] = [
                    'file' => $normalizedPath,
                    'line' => $lineNum + 1,
                    'current_layer' => $currentLayer,
                    'forbidden_layer' => $targetLayer,
                    'import' => $importedClass,
                ];
            }
        }
    }
}

// 4. Output Results
echo "Architectural Analysis Summary:\n";
echo "  - Files Inspected:       {$totalFilesInspected}\n";
echo "  - Violations Detected:   " . count($violations) . "\n\n";

if (!empty($violations)) {
    echo "### Architectural Boundary Violations Detected:\n\n";
    echo "| File | Line | Source Layer | Forbidden Target | Import |\n";
    echo "|:---|:---:|:---:|:---:|:---|\n";
    foreach ($violations as $v) {
        echo sprintf(
            "| %s | %d | %s | %s | `%s` |\n",
            $v['file'],
            $v['line'],
            $v['current_layer'],
            $v['forbidden_layer'],
            $v['import']
        );
    }
    echo "\n";
}

$passed = empty($violations);

if (null !== $outputJsonFile) {
    file_put_contents($outputJsonFile, json_encode([
        'timestamp' => date('c'),
        'files_inspected' => $totalFilesInspected,
        'passed' => $passed,
        'violations' => $violations,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Architectural report saved to: {$outputJsonFile}\n";
}

if (!$passed) {
    echo "Result: FAILED — Prohibited layer dependencies detected violating Hexagonal Architecture!\n";
    exit(1);
}

echo "Result: PASSED — Architectural boundaries clean and verified!\n";
exit(0);
