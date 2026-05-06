<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Ensures PRD 2 golden eval suite passes against committed cases.json.
 *
 * Usage:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/Prd2EvalRunnerIsolatedTest.php
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\Eval\Prd2EvalRunner;
use PHPUnit\Framework\TestCase;

class Prd2EvalRunnerIsolatedTest extends TestCase
{
    public function testGoldenCasesAllPass(): void
    {
        $path = dirname(__DIR__, 4) . '/interface/modules/custom_modules/oe-module-clinical-copilot/eval/cases.json';
        $this->assertFileExists($path);
        $runner = new Prd2EvalRunner();
        $r = $runner->run($path);
        $this->assertSame(Prd2EvalRunner::EXPECTED_CASE_COUNT, $r['case_count']);
        $this->assertSame(0, $r['failed'], json_encode($r['failures'], JSON_PRETTY_PRINT));
        $this->assertSame(Prd2EvalRunner::EXPECTED_CASE_COUNT, $r['passed']);
        $this->assertArrayHasKey('per_rubric_agreement', $r);
        foreach (Prd2EvalRunner::RUBRIC_KEYS as $k) {
            $this->assertSame(Prd2EvalRunner::EXPECTED_CASE_COUNT, $r['per_rubric_agreement'][$k] ?? -1, $k);
        }
    }

    public function testBuilderProducesFiftyCases(): void
    {
        $cases = Prd2EvalRunner::buildDefaultCases();
        $this->assertCount(Prd2EvalRunner::EXPECTED_CASE_COUNT, $cases);
    }
}
