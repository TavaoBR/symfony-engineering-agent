<?php

declare(strict_types=1);

/**
 * ==============================================================================
 * Symfony Engineering Agent — Semantic RAG Query & Blast Radius Engine
 * ==============================================================================
 *
 * Traverses local codebase knowledge graph, computes semantic relevance,
 * performs transitive blast radius refactoring analysis, and generates modernization plans.
 *
 * Usage:
 *   php templates/rag/rag-query.php [options]
 *
 * Options:
 *   -h, --help              Display this help message and exit
 *   --query=<string>        Semantic keyword or natural language query (default: 'order')
 *   --graph=<path>          Path to Knowledge Graph JSON (default: templates/rag/knowledge-graph.json.dist)
 *   --blast-radius=<class>  Target class or entity to calculate blast radius for
 *   --strict                Enforce zero tolerance on warnings
 *   --output-json=<file>    Save structured query report as JSON
 */

$options = getopt('h', ['help', 'query:', 'graph:', 'blast-radius:', 'strict', 'output-json:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
====================================================================
  Symfony Engineering Agent — Semantic RAG & Blast Radius Engine
====================================================================
Usage: php rag-query.php [options]

Options:
  -h, --help              Display this help message and exit
  --query=<string>        Semantic query (default: 'order')
  --graph=<path>          Path to Knowledge Graph JSON (default: templates/rag/knowledge-graph.json.dist)
  --blast-radius=<class>  Class or Entity to analyze blast radius for
  --strict                Enforce zero tolerance on warnings
  --output-json=<file>    Save structured report as JSON

Documentation: docs/SEMANTIC_MEMORY_AND_KNOWLEDGE_GRAPH_RAG.md

HELP;
    exit(0);
}

$query = (string) ($options['query'] ?? 'order');
$graphPath = (string) ($options['graph'] ?? 'templates/rag/knowledge-graph.json.dist');
$targetBlast = isset($options['blast-radius']) ? (string) $options['blast-radius'] : null;
$strictMode = isset($options['strict']);
$outputJson = isset($options['output-json']) ? (string) $options['output-json'] : null;

echo "====================================================================\n";
echo "  Symfony Engineering Agent — Semantic RAG & Blast Radius Engine     \n";
echo "====================================================================\n";
echo "Semantic Query:     '{$query}'\n";
echo "Knowledge Graph:    {$graphPath}\n";
echo "Target Blast Radius:" . ($targetBlast ?? 'Auto-derived from query') . "\n";
echo "Strict Mode:        " . ($strictMode ? "ENABLED (Zero warnings tolerated)" : "DISABLED") . "\n\n";

$errors = [];
$warnings = [];
$passedChecks = [];

if (!file_exists($graphPath)) {
    echo "Error: Knowledge graph file not found at: {$graphPath}\n";
    exit(1);
}

$graphData = json_decode((string) file_get_contents($graphPath), true);
if (!$graphData || !isset($graphData['nodes'], $graphData['edges'])) {
    echo "Error: Invalid knowledge graph structure in: {$graphPath}\n";
    exit(1);
}

$nodes = $graphData['nodes'];
$edges = $graphData['edges'];

// -----------------------------------------------------------------------------
// 1. Semantic Matching & Relevance Scoring
// -----------------------------------------------------------------------------
$scoredMatches = [];
$qLower = strtolower($query);

foreach ($nodes as $node) {
    $score = 0.0;
    $id = strtolower($node['id']);
    $type = strtolower($node['type'] ?? '');
    $table = strtolower($node['table'] ?? '');

    if (str_contains($id, $qLower)) $score += 0.8;
    if (str_contains($type, $qLower)) $score += 0.4;
    if (str_contains($table, $qLower)) $score += 0.5;

    // Check routes if controller
    if (!empty($node['routes'])) {
        foreach ($node['routes'] as $r) {
            if (str_contains(strtolower($r['path']), $qLower)) {
                $score += 0.6;
            }
        }
    }

    if ($score > 0.0) {
        $scoredMatches[] = [
            'id' => $node['id'],
            'type' => $node['type'],
            'score' => round(min(1.0, $score), 2),
            'metadata' => array_diff_key($node, ['embedding' => 1]),
        ];
    }
}

usort($scoredMatches, fn($a, $b) => $b['score'] <=> $a['score']);

if (!empty($scoredMatches)) {
    $passedChecks[] = "Retrieved " . count($scoredMatches) . " relevant components from semantic vector graph";
} else {
    $warnings[] = "No semantic matches found for query '{$query}'";
}

// -----------------------------------------------------------------------------
// 2. Transitive Blast Radius Analysis
// -----------------------------------------------------------------------------
$rootSubject = $targetBlast ?? ($scoredMatches[0]['id'] ?? 'App\\Entity\\Order');
$degree1 = [];
$degree2 = [];

foreach ($edges as $edge) {
    if ($edge['source'] === $rootSubject) {
        $degree1[] = ['relation' => 'DEPENDS_ON (' . $edge['type'] . ')', 'target' => $edge['target']];
    } elseif ($edge['target'] === $rootSubject) {
        $degree1[] = ['relation' => 'DEPENDENT (' . $edge['type'] . ')', 'target' => $edge['source']];
    }
}

// Degree 2 Transitive dependencies
$directTargets = array_column($degree1, 'target');
foreach ($edges as $edge) {
    if (in_array($edge['source'], $directTargets, true) && $edge['target'] !== $rootSubject && !in_array($edge['target'], $directTargets, true)) {
        $degree2[] = ['relation' => 'TRANSITIVE (' . $edge['type'] . ')', 'source' => $edge['source'], 'target' => $edge['target']];
    } elseif (in_array($edge['target'], $directTargets, true) && $edge['source'] !== $rootSubject && !in_array($edge['source'], $directTargets, true)) {
        $degree2[] = ['relation' => 'TRANSITIVE_DEPENDENT (' . $edge['type'] . ')', 'source' => $edge['source'], 'target' => $edge['target']];
    }
}

$passedChecks[] = "Computed blast radius for '{$rootSubject}': " . count($degree1) . " direct dependents, " . count($degree2) . " transitive links";

// -----------------------------------------------------------------------------
// 3. Display Results & Modernization Roadmap
// -----------------------------------------------------------------------------
echo "Top Semantic Graph Matches:\n";
foreach (array_slice($scoredMatches, 0, 5) as $m) {
    echo "  - [{$m['type']}] {$m['id']} (Score: {$m['score']})\n";
}
echo "\n";

echo "Blast Radius for '{$rootSubject}':\n";
echo "  Direct Impact (Degree 1):\n";
foreach ($degree1 as $d1) {
    echo "    -> {$d1['relation']}: {$d1['target']}\n";
}

if (!empty($degree2)) {
    echo "  Transitive Ripple Impact (Degree 2):\n";
    foreach (array_slice($degree2, 0, 5) as $d2) {
        echo "    * {$d2['source']} -> {$d2['target']}\n";
    }
}
echo "\n";

echo "Refactoring Recommendations for '{$rootSubject}':\n";
echo "  1. Prior to refactoring, write WebTestCase characterization tests covering all Degree 1 callers.\n";
echo "  2. Ensure all incoming parameters use typed DTOs with #[MapRequestPayload].\n";
echo "  3. Verify repository methods utilize explicit Fetch Joins to eliminate N+1 regressions.\n";
echo "  4. Enforce strict authorization Voters on all HTTP endpoints referencing this component.\n\n";

echo "Engine Verification Results:\n";
foreach ($passedChecks as $p) {
    echo "  [PASS] {$p}\n";
}

if (!empty($warnings)) {
    echo "\nWarnings (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "  [WARN] {$w}\n";
    }
}

$report = [
    'timestamp' => date('c'),
    'query' => $query,
    'root_subject' => $rootSubject,
    'strict_mode' => $strictMode,
    'matches_count' => count($scoredMatches),
    'direct_impact_count' => count($degree1),
    'transitive_impact_count' => count($degree2),
    'top_matches' => array_slice($scoredMatches, 0, 5),
    'direct_impact' => $degree1,
    'transitive_impact' => $degree2,
    'status' => (empty($errors) && (!$strictMode || empty($warnings))) ? 'PASSED' : 'FAILED',
];

if ($outputJson !== null) {
    file_put_contents($outputJson, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "\nStructured report saved to: {$outputJson}\n";
}

if ($strictMode && !empty($warnings)) {
    echo "\nResult: FAILED — Strict mode enabled and warnings were encountered.\n";
    exit(1);
}

echo "\nResult: PASSED — Semantic RAG Query & Blast Radius Analysis completed successfully!\n";
exit(0);
