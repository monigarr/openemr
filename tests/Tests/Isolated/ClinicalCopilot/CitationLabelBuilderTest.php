<?php

/**
 * @version 0.1.0
 * @date 2026-05-03
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: PHPUnit isolation tests for `CitationLabelBuilder` — ensures human-facing citation labels
 * use stable template text and do not echo raw chart values (reduces accidental PHI leakage in UI labels).
 *
 * Usage: Run from repo root with PHPUnit (same suite as other Clinical CoPilot isolated tests).
 *
 * Example:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/CitationLabelBuilderTest.php
 *
 * Dependencies: `OpenEMR\Modules\ClinicalCopilot\Services\CitationLabelBuilder`, PHPUnit `TestCase`.
 *
 * Security/PHI: Tests use synthetic strings only; asserts labels never contain a stand-in “secret” value.
 * HIPAA: N/A — no PHI; demonstrates minimum-necessary display pattern for citation chrome.
 * FHIR: N/A — not interoperability; backs PRD 1 Clinical CoPilot chart-context labeling.
 * Accessibility: N/A — non-UI test.
 * Performance: O(paths); trivial dataset sizes.
 * Stability: Pure unit behavior; no DB or I/O.
 * Legal/compliance: OpenEMR GPLv3; no third-party clinical content in fixtures.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\CitationLabelBuilder;
use PHPUnit\Framework\TestCase;

class CitationLabelBuilderTest extends TestCase
{
    public function testLabelsAvoidEchoingValues(): void
    {
        $b = new CitationLabelBuilder();
        $merged = [
            'chart_lists' => ['allergies' => [['title' => 'SECRET_VALUE']]],
            'recent_encounters' => ['encounters' => [['date' => '2020-01-01', 'reason' => 'X']]],
        ];
        $labels = $b->labelsForPaths($merged, ['chart_lists.allergies.0.title', 'recent_encounters.encounters.0.reason']);
        $this->assertSame('Allergy list', $labels[0]['label']);
        $this->assertStringContainsString('Recent encounter', $labels[1]['label']);
        $this->assertStringNotContainsString('SECRET_VALUE', $labels[0]['label']);
    }
}
