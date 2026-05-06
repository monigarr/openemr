<?php

/**
 * CLI: PRD 2 eval gate (50-case golden set, boolean rubrics).
 *
 * Usage (repo root):
 *   php interface/modules/custom_modules/oe-module-clinical-copilot/eval/run_eval.php
 *
 * Export/regenerate cases.json from Prd2EvalRunner::buildDefaultCases():
 *   php .../run_eval.php --export-cases
 *
 * Export prd2_eval_baseline.json after a clean golden run (per-rubric agreement):
 *   php .../run_eval.php --export-baseline
 *
 * Machine-readable summary on stdout (CI artifacts):
 *   php .../run_eval.php --summary-json
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

$root = dirname(__DIR__, 5);
require_once $root . '/vendor/autoload.php';

use OpenEMR\Modules\ClinicalCopilot\Services\Eval\Prd2EvalBaselineChecker;
use OpenEMR\Modules\ClinicalCopilot\Services\Eval\Prd2EvalRunner;

$export = in_array('--export-cases', $argv ?? [], true);
$exportBaseline = in_array('--export-baseline', $argv ?? [], true);
$summaryJson = in_array('--summary-json', $argv ?? [], true);
if ($export) {
    $path = __DIR__ . '/cases.json';
    $cases = Prd2EvalRunner::buildDefaultCases();
    if (count($cases) !== Prd2EvalRunner::EXPECTED_CASE_COUNT) {
        fwrite(STDERR, "Case builder returned " . count($cases) . ", expected " . Prd2EvalRunner::EXPECTED_CASE_COUNT . PHP_EOL);
        exit(1);
    }
    file_put_contents($path, json_encode($cases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    echo "Wrote {$path}\n";
    exit(0);
}

if ($exportBaseline) {
    $runner = new Prd2EvalRunner();
    $result = $runner->run(__DIR__ . '/cases.json');
    if (($result['failed'] ?? 1) > 0) {
        fwrite(STDERR, "Cannot export baseline: golden run has failures.\n");
        exit(1);
    }
    $path = __DIR__ . '/prd2_eval_baseline.json';
    $payload = [
        'version' => 1,
        'case_count' => $result['case_count'],
        'per_rubric_agreement' => $result['per_rubric_agreement'],
        'pass_threshold_rate' => 0.95,
        'max_regression_rate' => 0.05,
        'note' => 'Exported via run_eval.php --export-baseline after a clean golden run.',
    ];
    file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    echo "Wrote {$path}\n";
    exit(0);
}

$runner = new Prd2EvalRunner();
$result = $runner->run(__DIR__ . '/cases.json');
$baselinePath = __DIR__ . '/prd2_eval_baseline.json';
if (is_readable($baselinePath)) {
    $rawBaseline = file_get_contents($baselinePath);
    $baseline = is_string($rawBaseline) ? json_decode($rawBaseline, true) : null;
    if (!is_array($baseline)) {
        fwrite(STDERR, "Invalid prd2_eval_baseline.json\n");
        exit(1);
    }
    /** @var array<string,int> $agree */
    $agree = $result['per_rubric_agreement'] ?? [];
    $chk = Prd2EvalBaselineChecker::check($agree, $baseline, (int) ($result['case_count'] ?? 0));
    $result['baseline_regression_check'] = $chk;
    if (!$chk['ok']) {
        if ($summaryJson) {
            echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        } else {
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }
        exit(1);
    }
}
if ($summaryJson) {
    echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
if ($result['failed'] > 0) {
    if (!$summaryJson) {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
    exit(1);
}
if (!$summaryJson) {
    echo "PRD2 eval OK: {$result['passed']}/{$result['case_count']} cases passed.\n";
}
exit(0);
