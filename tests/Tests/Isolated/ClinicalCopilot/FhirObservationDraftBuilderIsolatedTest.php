<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: FHIR R4 Observation draft JSON shape from validated lab rows.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\Document\LabResultLine;
use OpenEMR\Modules\ClinicalCopilot\Services\Fhir\FhirObservationDraftBuilder;
use PHPUnit\Framework\TestCase;

class FhirObservationDraftBuilderIsolatedTest extends TestCase
{
    public function testBuildsDraftWithDerivedFromWhenDocumentIdPresent(): void
    {
        $row = LabResultLine::validated([
            'test_name' => 'Glucose',
            'value' => '99',
            'unit' => 'mg/dL',
            'reference_range' => '60-100',
            'collection_date' => '2026-02-01',
            'abnormal_flag' => false,
            'citation' => [
                'source_type' => 'lab_pdf',
                'source_id' => 's1',
                'page_or_section' => '1',
                'field_or_chunk_id' => 'glucose',
                'quote_or_value' => 'Glucose 99',
            ],
        ]);
        $this->assertIsArray($row);
        $b = new FhirObservationDraftBuilder();
        $drafts = $b->labRowsToObservationDrafts(42, [$row], 1001);
        $this->assertCount(1, $drafts);
        $d = $drafts[0];
        $this->assertSame('Observation', $d['resourceType'] ?? null);
        $this->assertSame('Patient/42', $d['subject']['reference'] ?? null);
        $this->assertArrayHasKey('derivedFrom', $d);
        $this->assertStringContainsString('DocumentReference/1001', (string) json_encode($d['derivedFrom']));
    }
}
