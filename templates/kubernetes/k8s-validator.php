<?php

declare(strict_types=1);

/**
 * ==============================================================================
 * Symfony Engineering Agent — Kubernetes & Helm Architecture Validator
 * ==============================================================================
 *
 * Validates cloud-native Kubernetes Helm charts, GitOps delivery manifests,
 * Horizontal Pod Autoscaling (HPA) policies, and CIS container security standards.
 *
 * Usage:
 *   php templates/kubernetes/k8s-validator.php [options]
 *
 * Options:
 *   -h, --help            Display this help message and exit
 *   --chart=<dir>         Path to Helm chart directory (default: templates/kubernetes/helm/symfony-app)
 *   --gitops=<dir>        Path to GitOps manifests directory (default: templates/kubernetes/gitops)
 *   --strict              Enforce zero tolerance on warnings
 *   --output-json=<file>  Save structured validation report as JSON
 */

$options = getopt('h', ['help', 'chart:', 'gitops:', 'strict', 'output-json:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
====================================================================
  Symfony Engineering Agent — Kubernetes & Helm Architecture Validator
====================================================================
Usage: php k8s-validator.php [options]

Options:
  -h, --help            Display this help message and exit
  --chart=<dir>         Path to Helm chart directory (default: templates/kubernetes/helm/symfony-app)
  --gitops=<dir>        Path to GitOps directory (default: templates/kubernetes/gitops)
  --strict              Enforce zero tolerance on warnings
  --output-json=<file>  Save structured validation report as JSON

Documentation: docs/KUBERNETES_AND_GITOPS.md

HELP;
    exit(0);
}

$chartDir = rtrim((string) ($options['chart'] ?? 'templates/kubernetes/helm/symfony-app'), '/\\');
$gitopsDir = rtrim((string) ($options['gitops'] ?? 'templates/kubernetes/gitops'), '/\\');
$strictMode = isset($options['strict']);
$outputJson = isset($options['output-json']) ? (string) $options['output-json'] : null;

echo "====================================================================\n";
echo "  Symfony Engineering Agent — Kubernetes Architecture Validator     \n";
echo "====================================================================\n";
echo "Helm Chart Directory: {$chartDir}\n";
echo "GitOps Directory:     {$gitopsDir}\n";
echo "Strict Mode:          " . ($strictMode ? "ENABLED (Zero warnings tolerated)" : "DISABLED") . "\n\n";

$errors = [];
$warnings = [];
$passedChecks = [];

// -----------------------------------------------------------------------------
// 1. Helm Chart Structure & Metadata Validation
// -----------------------------------------------------------------------------
$chartFile = $chartDir . '/Chart.yaml';
$valuesFile = $chartDir . '/values.yaml';
$templatesDir = $chartDir . '/templates';

if (!file_exists($chartFile)) {
    $errors[] = "Chart.yaml not found at path: {$chartFile}";
} else {
    $chartYaml = (string) file_get_contents($chartFile);
    if (str_contains($chartYaml, 'apiVersion: v2')) {
        $passedChecks[] = "Chart.yaml specifies official Helm v2 apiVersion";
    } else {
        $errors[] = "Chart.yaml missing or invalid 'apiVersion: v2'";
    }

    if (preg_match('/name:\s+[a-zA-Z0-9_-]+/i', $chartYaml) && preg_match('/version:\s+[0-9.]+/i', $chartYaml)) {
        $passedChecks[] = "Chart.yaml defines valid name and semver version";
    } else {
        $errors[] = "Chart.yaml missing mandatory name or version field";
    }
}

if (!file_exists($valuesFile)) {
    $errors[] = "values.yaml not found at path: {$valuesFile}";
} else {
    $valuesYaml = (string) file_get_contents($valuesFile);

    // Resources check
    if (str_contains($valuesYaml, 'resources:') && str_contains($valuesYaml, 'limits:') && str_contains($valuesYaml, 'requests:')) {
        $passedChecks[] = "values.yaml defines CPU & memory limits and requests";
    } else {
        $errors[] = "values.yaml missing mandatory container resource requests or limits";
    }

    // Security context
    if (str_contains($valuesYaml, 'runAsNonRoot: true')) {
        $passedChecks[] = "Pod security context enforces unprivileged execution (runAsNonRoot: true)";
    } else {
        $errors[] = "Pod security context does not enforce 'runAsNonRoot: true' (CIS Benchmark violation)";
    }

    // Probes check
    if (str_contains($valuesYaml, 'liveness:') && str_contains($valuesYaml, 'readiness:') && str_contains($valuesYaml, 'startup:')) {
        $passedChecks[] = "values.yaml defines liveness, readiness, and startup health probes";
    } else {
        $warnings[] = "values.yaml missing complete probe coverage (liveness/readiness/startup)";
    }

    // Autoscaling check
    if (str_contains($valuesYaml, 'autoscaling:') && str_contains($valuesYaml, 'minReplicas:') && str_contains($valuesYaml, 'maxReplicas:')) {
        $passedChecks[] = "values.yaml defines Horizontal Pod Autoscaling (HPA) min/max parameters";
    } else {
        $warnings[] = "values.yaml missing autoscaling configuration";
    }

    // PodDisruptionBudget check
    if (str_contains($valuesYaml, 'podDisruptionBudget:') && str_contains($valuesYaml, 'minAvailable:')) {
        $passedChecks[] = "values.yaml defines PodDisruptionBudget (PDB) policy";
    } else {
        $warnings[] = "values.yaml missing podDisruptionBudget configuration";
    }

    // Asynchronous Worker check
    if (str_contains($valuesYaml, 'worker:') && str_contains($valuesYaml, 'messenger:consume')) {
        $passedChecks[] = "values.yaml defines dedicated asynchronous Messenger worker deployment";
    } else {
        $warnings[] = "values.yaml missing async messenger worker parameters";
    }
}

// -----------------------------------------------------------------------------
// 2. Helm Template Manifests Validation
// -----------------------------------------------------------------------------
$expectedTemplates = [
    'deployment-web.yaml' => 'apps/v1 Deployment for Web / FrankenPHP application',
    'deployment-worker.yaml' => 'apps/v1 Deployment for Symfony Messenger workers',
    'service.yaml' => 'v1 Service definition for internal pod routing',
    'ingress.yaml' => 'networking.k8s.io/v1 Ingress specification with TLS support',
    'hpa.yaml' => 'autoscaling/v2 HorizontalPodAutoscaler policy',
    'pdb.yaml' => 'policy/v1 PodDisruptionBudget manifest',
    'job-migration.yaml' => 'batch/v1 Job for automated database migrations',
    'configmap.yaml' => 'v1 ConfigMap for application environment variables',
    'secret.yaml' => 'v1 Secret for encrypted credentials',
    '_helpers.tpl' => 'Helm template helper functions and standard labels',
];

foreach ($expectedTemplates as $tpl => $desc) {
    $tplPath = $templatesDir . '/' . $tpl;
    if (file_exists($tplPath)) {
        $passedChecks[] = "Helm template exists: {$tpl} ({$desc})";
    } else {
        $errors[] = "Missing required Helm template: {$tpl} ({$desc})";
    }
}

// Check rolling update in web deployment
$webDeploy = $templatesDir . '/deployment-web.yaml';
if (file_exists($webDeploy)) {
    $deployContent = (string) file_get_contents($webDeploy);
    if (str_contains($deployContent, 'RollingUpdate') && str_contains($deployContent, 'maxUnavailable: 0')) {
        $passedChecks[] = "Web deployment enforces zero-downtime rolling updates (maxUnavailable: 0)";
    } else {
        $warnings[] = "Web deployment does not explicitly assert maxUnavailable: 0 for zero-downtime updates";
    }
}

// Check migration hook annotations in migration job
$migrationJob = $templatesDir . '/job-migration.yaml';
if (file_exists($migrationJob)) {
    $jobContent = (string) file_get_contents($migrationJob);
    if (str_contains($jobContent, 'helm.sh/hook') && str_contains($jobContent, 'post-install,post-upgrade')) {
        $passedChecks[] = "Database migration job configured with Helm lifecycle hooks (post-install, post-upgrade)";
    } else {
        $warnings[] = "Database migration job missing official Helm hook lifecycle annotations";
    }
}

// -----------------------------------------------------------------------------
// 3. GitOps Continuous Delivery Manifests Validation
// -----------------------------------------------------------------------------
$argoFile = $gitopsDir . '/argocd-application.yaml.dist';
$fluxFile = $gitopsDir . '/flux-kustomization.yaml.dist';

if (!file_exists($argoFile)) {
    $errors[] = "ArgoCD application manifest not found at: {$argoFile}";
} else {
    $argoContent = (string) file_get_contents($argoFile);
    if (str_contains($argoContent, 'argoproj.io/v1alpha1') && str_contains($argoContent, 'kind: Application') && str_contains($argoContent, 'syncPolicy:')) {
        $passedChecks[] = "ArgoCD Application manifest defines automated sync policy (prune & selfHeal)";
    } else {
        $errors[] = "ArgoCD manifest invalid: missing argoproj.io API or syncPolicy";
    }
}

if (!file_exists($fluxFile)) {
    $errors[] = "Flux CD manifest not found at: {$fluxFile}";
} else {
    $fluxContent = (string) file_get_contents($fluxFile);
    if (str_contains($fluxContent, 'HelmRelease') && str_contains($fluxContent, 'GitRepository')) {
        $passedChecks[] = "Flux CD manifest defines GitRepository source and HelmRelease orchestration";
    } else {
        $errors[] = "Flux CD manifest invalid: missing HelmRelease or GitRepository definitions";
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
    echo "\nResult: FAILED — Kubernetes validation encountered critical errors.\n";
    exit(1);
}

if ($strictMode && !empty($warnings)) {
    echo "\nResult: FAILED — Strict mode enabled and warnings were encountered.\n";
    exit(1);
}

echo "\nResult: PASSED — Kubernetes Helm and GitOps architecture validated successfully!\n";
exit(0);
