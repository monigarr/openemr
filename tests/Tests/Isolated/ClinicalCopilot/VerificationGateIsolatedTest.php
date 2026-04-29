<?php

/**
 * Isolated tests for Clinical Co-Pilot verification (no database).
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\VerificationGate;
use PHPUnit\Framework\TestCase;

class VerificationGateIsolatedTest extends TestCase
{
    public function testKeepsStatementWithValidCitation(): void
    {
        $tool = [
            'patient' => ['fname' => 'Jane', 'lname' => 'Doe'],
            'allergies' => [['title' => 'Penicillin']],
        ];
        $parsed = [
            'statements' => [
                ['text' => 'First name is Jane.', 'citations' => ['patient.fname']],
            ],
            'uncertainties' => [],
        ];
        $gate = new VerificationGate();
        $v = $gate->verify($tool, $parsed);
        $this->assertCount(1, $v['statements']);
        $this->assertSame([], $v['stripped']);
    }

    public function testStripsUncitedStatement(): void
    {
        $tool = ['patient' => ['fname' => 'Jane']];
        $parsed = [
            'statements' => [
                ['text' => 'Unverifiable claim.', 'citations' => []],
            ],
        ];
        $gate = new VerificationGate();
        $v = $gate->verify($tool, $parsed);
        $this->assertCount(0, $v['statements']);
        $this->assertNotEmpty($v['stripped']);
    }

    public function testStripsBadCitationPath(): void
    {
        $tool = ['patient' => ['fname' => 'Jane']];
        $parsed = [
            'statements' => [
                ['text' => 'Wrong path.', 'citations' => ['patient.not_a_field']],
            ],
        ];
        $gate = new VerificationGate();
        $v = $gate->verify($tool, $parsed);
        $this->assertCount(0, $v['statements']);
    }

    public function testListIndexCitation(): void
    {
        $tool = ['allergies' => [['title' => 'Eggs']]];
        $parsed = [
            'statements' => [
                ['text' => 'Allergy noted.', 'citations' => ['allergies.0.title']],
            ],
        ];
        $gate = new VerificationGate();
        $v = $gate->verify($tool, $parsed);
        $this->assertCount(1, $v['statements']);
    }

    public function testCitationResolvesHelper(): void
    {
        $gate = new VerificationGate();
        $this->assertTrue($gate->citationResolves(['a' => ['b' => 'x']], 'a.b'));
        $this->assertFalse($gate->citationResolves(['a' => ['b' => '']], 'a.b'));
    }
}
