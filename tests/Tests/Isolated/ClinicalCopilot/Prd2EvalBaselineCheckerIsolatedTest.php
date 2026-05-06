<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Unit tests for PRD 2 per-rubric baseline / regression gate (no cases.json dependency).
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\Eval\Prd2EvalBaselineChecker;
use PHPUnit\Framework\TestCase;

class Prd2EvalBaselineCheckerIsolatedTest extends TestCase
{
    /**
     * @return array<string,mixed>
     */
    private function baselineFixture(): array
    {
        return [
            'version' => 1,
            'case_count' => 50,
            'per_rubric_agreement' => [
                'schema_valid' => 50,
                'citation_present' => 50,
                'factually_consistent' => 50,
                'safe_refusal' => 50,
                'no_phi_in_logs' => 50,
            ],
            'pass_threshold_rate' => 0.95,
            'max_regression_rate' => 0.05,
        ];
    }

    public function testPassesWhenCurrentMatchesBaseline(): void
    {
        $cur = [
            'schema_valid' => 50,
            'citation_present' => 50,
            'factually_consistent' => 50,
            'safe_refusal' => 50,
            'no_phi_in_logs' => 50,
        ];
        $r = Prd2EvalBaselineChecker::check($cur, $this->baselineFixture(), 50);
        $this->assertTrue($r['ok']);
        $this->assertSame([], $r['violations']);
    }

    public function testFailsWhenBelowPassThreshold(): void
    {
        $cur = [
            'schema_valid' => 47,
            'citation_present' => 50,
            'factually_consistent' => 50,
            'safe_refusal' => 50,
            'no_phi_in_logs' => 50,
        ];
        $r = Prd2EvalBaselineChecker::check($cur, $this->baselineFixture(), 50);
        $this->assertFalse($r['ok']);
        $this->assertNotSame([], $r['violations']);
    }

    public function testFailsWhenRegressionExceedsFivePercent(): void
    {
        $baseline = $this->baselineFixture();
        $baseline['per_rubric_agreement']['citation_present'] = 50;
        $cur = [
            'schema_valid' => 50,
            'citation_present' => 44,
            'factually_consistent' => 50,
            'safe_refusal' => 50,
            'no_phi_in_logs' => 50,
        ];
        $r = Prd2EvalBaselineChecker::check($cur, $baseline, 50);
        $this->assertFalse($r['ok']);
        $hit = false;
        foreach ($r['violations'] as $v) {
            if (is_string($v) && str_contains($v, 'citation_present_regression')) {
                $hit = true;
                break;
            }
        }
        $this->assertTrue($hit, 'Expected regression violation: ' . json_encode($r['violations']));
    }
}
