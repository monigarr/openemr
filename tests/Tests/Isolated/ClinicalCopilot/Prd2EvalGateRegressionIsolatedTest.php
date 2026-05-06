<?php

/**
 * @version 0.1.0
 * @date 2026-05-06
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Proves the PRD 2 eval gate fails on intentional regressions (PRD hard gate / grader injection).
 *
 * Usage:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/Prd2EvalGateRegressionIsolatedTest.php
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\Eval\Prd2EvalRunner;
use PHPUnit\Framework\TestCase;

class Prd2EvalGateRegressionIsolatedTest extends TestCase
{
    private function goldenPath(): string
    {
        $path = dirname(__DIR__, 4) . '/interface/modules/custom_modules/oe-module-clinical-copilot/eval/cases.json';
        $this->assertFileExists($path);

        return $path;
    }

    /**
     * Invalid citation path strips all verified statements while expect still requires citations → rubric failure.
     */
    public function testGateFailsWhenCitationDoesNotResolveAgainstMergedTools(): void
    {
        $raw = file_get_contents($this->goldenPath());
        $this->assertNotFalse($raw);
        $cases = json_decode($raw, true);
        $this->assertIsArray($cases);
        $this->assertCount(Prd2EvalRunner::EXPECTED_CASE_COUNT, $cases);
        $cases[0]['parsed']['statements'][0]['citations'] = ['chart_lists.patient.not_real'];

        $jsonPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prd2eval_reg_' . uniqid('', true) . '.json';
        try {
            file_put_contents($jsonPath, json_encode($cases, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            $r = (new Prd2EvalRunner())->run($jsonPath);
            $this->assertGreaterThan(0, $r['failed'], 'Injected bad citation should fail at least one rubric');
            $this->assertLessThan(Prd2EvalRunner::EXPECTED_CASE_COUNT, $r['passed']);
            $hit = false;
            foreach ($r['failures'] as $f) {
                if (is_array($f) && ($f['rubric'] ?? null) === 'citation_present') {
                    $hit = true;
                    break;
                }
            }
            $this->assertTrue($hit, 'Expected citation_present mismatch in failures: ' . json_encode($r['failures']));
        } finally {
            if (is_file($jsonPath)) {
                @unlink($jsonPath);
            }
        }
    }

    public function testGateFailsWhenCaseCountIsNotFifty(): void
    {
        $raw = file_get_contents($this->goldenPath());
        $this->assertNotFalse($raw);
        $cases = json_decode($raw, true);
        $this->assertIsArray($cases);
        array_pop($cases);
        $this->assertCount(49, $cases);

        $jsonPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prd2eval_cnt_' . uniqid('', true) . '.json';
        try {
            file_put_contents($jsonPath, json_encode($cases, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            $r = (new Prd2EvalRunner())->run($jsonPath);
            $this->assertSame(49, $r['case_count']);
            $this->assertGreaterThan(0, $r['failed']);
            $this->assertStringContainsString('expected_', (string) ($r['failures'][0]['reason'] ?? ''));
        } finally {
            if (is_file($jsonPath)) {
                @unlink($jsonPath);
            }
        }
    }
}
