<?php

/**
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
