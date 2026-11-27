#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Automated OpenAPI 3.0 / 3.1 Contract Validator & Route Coverage Verifier
 *
 * Validates OpenAPI specifications against syntax, structural integrity, broken $ref pointers,
 * RFC 7807 Problem Details conformance, and optional Symfony route coverage.
 */

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo "====================================================================\n";
    echo "  Symfony Engineering Agent — OpenAPI Contract Validator\n";
    echo "====================================================================\n";
    echo "Usage: php openapi-validator.php [spec-file.yaml|json] [options]\n\n";
    echo "Arguments:\n";
    echo "  spec-file           Path to OpenAPI specification (default: auto-detected in config/, docs/, or templates/)\n\n";
    echo "Options:\n";
    echo "  -h, --help          Display this help message and exit\n";
    echo "  --strict            Enforce additionalProperties: false and RFC 7807 on 4xx/5xx responses\n";
    echo "  --check-routes      Compare OpenAPI paths against Symfony router (php bin/console debug:router)\n";
    echo "  --output-json=<file> Save validation report as JSON\n\n";
    echo "Documentation: docs/OPENAPI_CONTRACT_TESTING.md\n";
    exit(0);
}

// 1. Locate Specification File
$specFile = null;
$strictMode = false;
$checkRoutes = false;
$outputJsonFile = null;

foreach (array_slice($argv, 1) as $arg) {
    if ('--strict' === $arg) {
        $strictMode = true;
    } elseif ('--check-routes' === $arg) {
        $checkRoutes = true;
    } elseif (str_starts_with($arg, '--output-json=')) {
        $outputJsonFile = substr($arg, strlen('--output-json='));
    } elseif (!str_starts_with($arg, '--')) {
        $specFile = $arg;
    }
}

if (null === $specFile) {
    $candidates = [
        'config/openapi.yaml',
        'config/openapi.json',
        'docs/openapi.yaml',
        'docs/openapi.json',
        'openapi.yaml',
        'openapi.json',
        'templates/contracts/openapi.yaml.dist',
        'templates/contracts/openapi.json.dist',
        __DIR__ . '/openapi.yaml.dist',
        __DIR__ . '/openapi.json.dist',
    ];

    foreach ($candidates as $cand) {
        if (file_exists($cand)) {
            $specFile = $cand;
            break;
        }
    }
}

if (null === $specFile || !file_exists($specFile)) {
    fwrite(STDERR, "Error: OpenAPI specification file not found.\n");
    fwrite(STDERR, "Usage: php openapi-validator.php [spec-file.yaml|json]\n");
    exit(1);
}

// 2. Parse Specification (JSON or YAML)
$raw = (string) file_get_contents($specFile);
$spec = null;

if (str_ends_with($specFile, '.json') || str_starts_with(trim($raw), '{')) {
    try {
        $spec = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        fwrite(STDERR, "JSON Parse Error in {$specFile}: " . $e->getMessage() . "\n");
        exit(1);
    }
} else {
    // Try Symfony Yaml if available, then yaml_parse
    if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
        $spec = \Symfony\Component\Yaml\Yaml::parse($raw);
    } elseif (function_exists('yaml_parse')) {
        $spec = yaml_parse($raw);
    } else {
        // Fallback simple YAML check
        fwrite(STDERR, "Notice: Parsing YAML requires Symfony Yaml component or ext-yaml. Trying JSON fallback...\n");
        try {
            $spec = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            fwrite(STDERR, "Error: Unable to parse YAML. Please install symfony/yaml or provide openapi.json.\n");
            exit(1);
        }
    }
}

if (!is_array($spec)) {
    fwrite(STDERR, "Error: Specification root must be a valid mapping / object.\n");
    exit(1);
}

echo "====================================================================\n";
echo "  Symfony Engineering Agent — OpenAPI Contract Validator\n";
echo "====================================================================\n";
echo "Spec File:    {$specFile}\n";
echo "Strict Mode:  " . ($strictMode ? 'ENABLED' : 'DISABLED') . "\n";
echo "Route Check:  " . ($checkRoutes ? 'ENABLED' : 'DISABLED') . "\n\n";

$errors = [];
$warnings = [];

// 3. Structural Validation
$openapiVersion = $spec['openapi'] ?? null;
if (null === $openapiVersion || (!str_starts_with((string) $openapiVersion, '3.0') && !str_starts_with((string) $openapiVersion, '3.1'))) {
    $errors[] = "Unsupported or missing OpenAPI version: '{$openapiVersion}'. Expected 3.0.x or 3.1.x.";
}

$info = $spec['info'] ?? [];
if (empty($info['title'])) {
    $errors[] = "Missing 'info.title' in OpenAPI specification.";
}
if (empty($info['version'])) {
    $errors[] = "Missing 'info.version' in OpenAPI specification.";
}

$paths = $spec['paths'] ?? [];
if (empty($paths) || !is_array($paths)) {
    $errors[] = "OpenAPI specification must define at least one path in 'paths'.";
}

// 4. Validate Paths and Operations
$operationCount = 0;
$pathCount = count($paths);
$httpMethods = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head'];

foreach ($paths as $pathUri => $pathItem) {
    if (!str_starts_with($pathUri, '/')) {
        $errors[] = "Path '{$pathUri}' must start with a leading slash '/'.";
    }

    if (!is_array($pathItem)) {
        $errors[] = "Path item for '{$pathUri}' must be an object.";
        continue;
    }

    foreach ($httpMethods as $method) {
        if (!isset($pathItem[$method])) {
            continue;
        }

        ++$operationCount;
        $op = $pathItem[$method];
        $methodUpper = strtoupper($method);

        if (!is_array($op)) {
            $errors[] = "Operation [{$methodUpper} {$pathUri}] must be an object.";
            continue;
        }

        $responses = $op['responses'] ?? [];
        if (empty($responses)) {
            $errors[] = "Operation [{$methodUpper} {$pathUri}] must define at least one response in 'responses'.";
            continue;
        }

        // Validate response status codes
        foreach ($responses as $statusCode => $responseObj) {
            if ('default' !== $statusCode && !preg_match('/^[1-5][0-9]{2}$/', (string) $statusCode)) {
                $warnings[] = "Operation [{$methodUpper} {$pathUri}] has non-standard response code: '{$statusCode}'.";
            }

            // Check RFC 7807 compliance on 4xx / 5xx error responses
            if ($strictMode && preg_match('/^[45][0-9]{2}$/', (string) $statusCode)) {
                $resolvedResponse = $responseObj;
                if (isset($responseObj['$ref']) && str_starts_with((string) $responseObj['$ref'], '#/')) {
                    $parts = explode('/', substr((string) $responseObj['$ref'], 2));
                    $curr = $spec;
                    foreach ($parts as $p) {
                        $curr = $curr[$p] ?? null;
                    }
                    if (is_array($curr)) {
                        $resolvedResponse = $curr;
                    }
                }

                $content = $resolvedResponse['content'] ?? [];
                if (!isset($content['application/problem+json']) && !isset($content['application/json'])) {
                    $warnings[] = "RFC 7807 Warning: Error response {$statusCode} on [{$methodUpper} {$pathUri}] does not define 'application/problem+json'.";
                }
            }
        }
    }
}

// 5. Validate $ref Resolution and Schemas
$components = $spec['components'] ?? [];
$schemas = $components['schemas'] ?? [];
$schemaCount = count($schemas);

function checkReferences(mixed $node, array $spec, array &$errors, string $context): void
{
    if (!is_array($node)) {
        return;
    }

    if (isset($node['$ref'])) {
        $ref = (string) $node['$ref'];
        if (str_starts_with($ref, '#/')) {
            $parts = explode('/', substr($ref, 2));
            $current = $spec;
            $found = true;
            foreach ($parts as $p) {
                if (!isset($current[$p])) {
                    $found = false;
                    break;
                }
                $current = $current[$p];
            }
            if (!$found) {
                $errors[] = "Broken \$ref pointer '{$ref}' found in {$context}.";
            }
        }
    }

    foreach ($node as $k => $child) {
        checkReferences($child, $spec, $errors, "{$context}->{$k}");
    }
}

checkReferences($spec, $spec, $errors, 'spec');

// 6. Strict Mode: Check additionalProperties: false
if ($strictMode) {
    foreach ($schemas as $schemaName => $schemaObj) {
        if (is_array($schemaObj) && 'object' === ($schemaObj['type'] ?? null)) {
            if (!isset($schemaObj['additionalProperties']) || false !== $schemaObj['additionalProperties']) {
                $warnings[] = "Strict Contract Rule: Schema '#/components/schemas/{$schemaName}' should explicitly set 'additionalProperties: false'.";
            }
        }
    }
}

// 7. Optional Route Coverage Check against Symfony Router
$uncoveredRoutes = [];
if ($checkRoutes && file_exists('bin/console')) {
    echo "Checking route coverage against Symfony router (bin/console debug:router)...\n";
    $cmd = 'php bin/console debug:router --format=json 2>&1';
    $output = @shell_exec($cmd);

    if (null !== $output && ($routerData = json_decode($output, true)) && is_array($routerData)) {
        $specPathKeys = array_keys($paths);

        foreach ($routerData as $routeName => $routeInfo) {
            $routePath = $routeInfo['path'] ?? '';
            // Only inspect API routes
            if (str_starts_with($routePath, '/api')) {
                $normalizedPath = preg_replace('/\{[a-zA-Z0-9_]+\}/', '{id}', $routePath);
                $isDocumented = false;

                foreach ($specPathKeys as $sk) {
                    $normalizedSpec = preg_replace('/\{[a-zA-Z0-9_]+\}/', '{id}', $sk);
                    if ($normalizedPath === $normalizedSpec) {
                        $isDocumented = true;
                        break;
                    }
                }

                if (!$isDocumented) {
                    $uncoveredRoutes[] = "{$routePath} ({$routeName})";
                }
            }
        }

        if (!empty($uncoveredRoutes)) {
            $warnings[] = "Undocumented Symfony API routes found:\n    - " . implode("\n    - ", $uncoveredRoutes);
        }
    }
}

// 8. Output Results
echo "Validation Metrics:\n";
echo "  - Paths Documented:      {$pathCount}\n";
echo "  - Operations Defined:    {$operationCount}\n";
echo "  - Component Schemas:     {$schemaCount}\n";
echo "  - Errors Detected:       " . count($errors) . "\n";
echo "  - Warnings:              " . count($warnings) . "\n\n";

if (!empty($warnings)) {
    echo "Warnings:\n";
    foreach ($warnings as $w) {
        echo "  [WARN] {$w}\n";
    }
    echo "\n";
}

$passed = empty($errors);

if (null !== $outputJsonFile) {
    file_put_contents($outputJsonFile, json_encode([
        'spec_file' => $specFile,
        'passed' => $passed,
        'metrics' => [
            'paths' => $pathCount,
            'operations' => $operationCount,
            'schemas' => $schemaCount,
        ],
        'errors' => $errors,
        'warnings' => $warnings,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Report saved to: {$outputJsonFile}\n";
}

if (!$passed) {
    echo "Errors Encountered:\n";
    foreach ($errors as $e) {
        echo "  [ERROR] {$e}\n";
    }
    echo "\nResult: FAILED (Contract violations detected)\n";
    exit(1);
}

echo "Result: PASSED (OpenAPI contract specification is valid!)\n";
exit(0);
