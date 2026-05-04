<?php

/**
 * @version 0.1.0
 * @date 2026-05-03
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: PHPUnit isolation tests for `ClinicalDomainRules` — post-processing of verified model output
 * (strip dosing/diagnosis language, surface polypharmacy uncertainty) for Clinical CoPilot PRD 1 safety rails.
 *
 * Usage: Run single file or full `ClinicalCopilot` isolated directory from repo root.
 *
 * Example:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/ClinicalDomainRulesTest.php
 *
 * Dependencies: `OpenEMR\Modules\ClinicalCopilot\Services\ClinicalDomainRules`, PHPUnit `TestCase`.
 *
 * Security/PHI: Fixtures are synthetic; rules reduce risky prose, not live chart exposure.
 * HIPAA: N/A — test data only; aligns with conservative clinical wording in production paths.
 * FHIR: N/A — not interoperability.
 * Accessibility: N/A — non-UI test.
 * Performance: Small in-memory structures; loop test bounds medication count explicitly.
 * Stability: Asserts shape of `apply()` return (`statements`, `uncertainties`, `domain_stripped`, etc.).
 * Legal/compliance: OpenEMR GPLv3; behavior documents clinical-copy guardrails, not medical advice.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\ClinicalDomainRules;
use PHPUnit\Framework\TestCase;

class ClinicalDomainRulesTest extends TestCase
{
    public function testStripsDosingLanguage(): void
    {
        $rules = new ClinicalDomainRules();
        $verified = [
            'statements' => [
                ['text' => 'Take 500 mg daily for pain.', 'citations' => ['chart_lists.medications.0.title']],
            ],
            'uncertainties' => [],
            'stripped' => [],
        ];
        $merged = ['chart_lists' => ['medications' => [['title' => 'Aspirin']]]];
        $out = $rules->apply($verified, $merged);
        $this->assertCount(0, $out['statements']);
        $this->assertNotEmpty($out['domain_stripped']);
    }

    public function testStripsDefinitiveNewDiagnosisPhrasing(): void
    {
        $rules = new ClinicalDomainRules();
        $verified = [
            'statements' => [
                ['text' => 'You have diabetes per chart.', 'citations' => ['chart_lists.problems.0.title']],
            ],
            'uncertainties' => [],
            'stripped' => [],
        ];
        $merged = ['chart_lists' => ['problems' => [['title' => 'Diabetes']]]];
        $out = $rules->apply($verified, $merged);
        $this->assertCount(0, $out['statements']);
    }

    public function testPolypharmacyAddsUncertainty(): void
    {
        $rules = new ClinicalDomainRules();
        $meds = [];
        for ($i = 0; $i < 15; $i++) {
            $meds[] = ['title' => 'Med' . $i];
        }
        $verified = [
            'statements' => [
                ['text' => 'Review medication list.', 'citations' => ['chart_lists.medications.0.title']],
            ],
            'uncertainties' => [],
            'stripped' => [],
        ];
        $merged = ['chart_lists' => ['medications' => $meds]];
        $out = $rules->apply($verified, $merged);
        $this->assertTrue(
            count(array_filter($out['uncertainties'], static fn ($u) => str_contains((string) $u, 'Polypharmacy'))) > 0
        );
    }
}
