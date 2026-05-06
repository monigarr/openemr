<?php

/**
 * @version 0.1.0
 * @date 2026-05-03
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Isolated PHPUnit tests for `VerificationGate` — citation-backed truth filtering for Clinical
 * CoPilot (no database): keeps statements only when every citation resolves to non-empty tool JSON paths.
 *
 * Usage: Run when modifying verification rules, citation path syntax, or merged-bundle shapes used by the gate.
 *
 * Example:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/VerificationGateIsolatedTest.php
 *
 * Dependencies: `OpenEMR\Modules\ClinicalCopilot\Services\VerificationGate`, PHPUnit `TestCase`.
 *
 * Security/PHI: Fixtures use synthetic names/paths; production gate must never log raw model output with PHI.
 * HIPAA: N/A in tests — production path is audit-relevant when filtering assistant statements tied to chart citations.
 * FHIR: N/A — not interoperability.
 * Accessibility: N/A — non-UI test.
 * Performance: In-memory graph walks only; scales with statement/citation count in one response.
 * Stability: Defines expected `verify()` outputs (`statements`, `stripped`, multi-citation cases, list indices).
 * Legal/compliance: OpenEMR GPLv3; verification is a safety layer, not a substitute for clinician judgment.
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

    public function testKeepsStatementWithMultipleCitationsAllValid(): void
    {
        $tool = [
            'chart_lists' => [
                'allergies' => [['title' => 'Penicillin']],
                'medications' => [['title' => 'Lisinopril']],
            ],
        ];
        $parsed = [
            'statements' => [
                [
                    'text' => 'Allergy and med noted.',
                    'citations' => ['chart_lists.allergies.0.title', 'chart_lists.medications.0.title'],
                ],
            ],
            'uncertainties' => [],
        ];
        $gate = new VerificationGate();
        $v = $gate->verify($tool, $parsed);
        $this->assertCount(1, $v['statements']);
        $this->assertCount(2, $v['statements'][0]['citations']);
    }

    public function testMergedBundleRecentEncountersPath(): void
    {
        $tool = [
            'recent_encounters' => [
                'encounters' => [['date' => '2024-01-02', 'reason' => 'Follow-up', 'visit_category' => 'Office']],
            ],
        ];
        $parsed = [
            'statements' => [
                ['text' => 'Recent visit documented.', 'citations' => ['recent_encounters.encounters.0.reason']],
            ],
            'uncertainties' => [],
        ];
        $gate = new VerificationGate();
        $v = $gate->verify($tool, $parsed);
        $this->assertCount(1, $v['statements']);
    }

    public function testDocumentExtractionsLabPath(): void
    {
        $tool = [
            'document_extractions' => [
                'labs' => [
                    ['test_name' => 'Glucose', 'value' => '100'],
                ],
            ],
        ];
        $parsed = [
            'statements' => [
                ['text' => 'Uploaded lab shows glucose.', 'citations' => ['document_extractions.labs.0.test_name']],
            ],
            'uncertainties' => [],
        ];
        $gate = new VerificationGate();
        $v = $gate->verify($tool, $parsed);
        $this->assertCount(1, $v['statements']);
    }

    public function testGuidelineEvidenceChunkPath(): void
    {
        $tool = [
            'guideline_evidence' => [
                'chunks' => [
                    ['text' => 'Assess glycemic control with A1C in outpatient diabetes care.'],
                ],
            ],
        ];
        $parsed = [
            'statements' => [
                ['text' => 'Guideline supports A1C review.', 'citations' => ['guideline_evidence.chunks.0.text']],
            ],
            'uncertainties' => [],
        ];
        $gate = new VerificationGate();
        $v = $gate->verify($tool, $parsed);
        $this->assertCount(1, $v['statements']);
    }
}
