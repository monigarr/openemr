<?php

/**
 * @version 0.1.0
 * @date 2026-05-06
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\Document\IntakeFormRecord;
use OpenEMR\Modules\ClinicalCopilot\Services\Fhir\FhirDocumentReferenceDraftBuilder;
use PHPUnit\Framework\TestCase;

class FhirDocumentReferenceDraftBuilderIsolatedTest extends TestCase
{
    public function testBuildsDocumentReferenceWithRelatesTo(): void
    {
        $intake = IntakeFormRecord::validated([
            'demographics' => ['age_bracket' => '30-40'],
            'chief_concern' => 'Annual physical',
            'current_medications' => [],
            'allergies' => [],
            'family_history' => [],
            'citation' => [
                'source_type' => 'intake_form',
                'source_id' => 'x',
                'page_or_section' => '1',
                'field_or_chunk_id' => 'f',
                'quote_or_value' => 'Annual physical',
            ],
        ]);
        $this->assertIsArray($intake);
        $b = new FhirDocumentReferenceDraftBuilder();
        $d = $b->fromIntakeContext(7, $intake, 555);
        $this->assertSame('DocumentReference', $d['resourceType'] ?? null);
        $this->assertSame('Patient/7', $d['subject']['reference'] ?? null);
        $this->assertArrayHasKey('relatesTo', $d);
    }
}
