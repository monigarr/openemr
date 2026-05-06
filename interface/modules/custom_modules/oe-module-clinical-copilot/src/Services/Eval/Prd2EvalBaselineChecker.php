<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Compare per-rubric agreement counts from Prd2EvalRunner against a committed baseline (PRD Core #6).
 *
 * Usage: Called from eval/run_eval.php when prd2_eval_baseline.json is present.
 *
 * Security/PHI: N/A — counts only.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Eval;

final class Prd2EvalBaselineChecker
{
    /**
     * @param array<string,int> $currentAgreement rubric => count of cases where actual matched expected
     * @param array<string,mixed> $baseline decoded prd2_eval_baseline.json
     * @return array{ok:bool,violations:list<string>}
     */
    public static function check(array $currentAgreement, array $baseline, int $caseCount): array
    {
        $violations = [];
        $v = $baseline['version'] ?? 0;
        if ((int) $v !== 1) {
            return ['ok' => false, 'violations' => ['baseline_unsupported_version']];
        }
        $baseCount = (int) ($baseline['case_count'] ?? 0);
        if ($baseCount !== $caseCount) {
            return ['ok' => false, 'violations' => ['baseline_case_count_mismatch']];
        }
        $stored = $baseline['per_rubric_agreement'] ?? null;
        if (!is_array($stored)) {
            return ['ok' => false, 'violations' => ['baseline_missing_per_rubric_agreement']];
        }
        $passThreshold = (float) ($baseline['pass_threshold_rate'] ?? 0.95);
        $maxRegression = (float) ($baseline['max_regression_rate'] ?? 0.05);
        if ($passThreshold < 0.0 || $passThreshold > 1.0 || $maxRegression < 0.0 || $maxRegression > 1.0) {
            return ['ok' => false, 'violations' => ['baseline_invalid_thresholds']];
        }
        foreach (Prd2EvalRunner::RUBRIC_KEYS as $key) {
            $cur = (int) ($currentAgreement[$key] ?? 0);
            $base = (int) ($stored[$key] ?? 0);
            $curRate = $caseCount > 0 ? $cur / $caseCount : 0.0;
            $baseRate = $caseCount > 0 ? $base / $caseCount : 0.0;
            if ($curRate + 1e-9 < $passThreshold) {
                $violations[] = $key . '_below_pass_threshold(current=' . round($curRate, 4) . ',min=' . $passThreshold . ')';
            }
            if (($baseRate - $curRate) > $maxRegression + 1e-9) {
                $violations[] = $key . '_regression_exceeds_cap(baseline=' . round($baseRate, 4) . ',current=' . round($curRate, 4) . ',max_drop=' . $maxRegression . ')';
            }
        }
        return ['ok' => $violations === [], 'violations' => $violations];
    }
}
