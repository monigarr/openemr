<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Validates Week 2 extraction row shapes (citations, labs, intake).
 *
 * Usage:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/DocumentSchemaIsolatedTest.php
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\Document\IntakeFormRecord;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\LabResultLine;
use PHPUnit\Framework\TestCase;

class DocumentSchemaIsolatedTest extends TestCase
{
    public function testLabResultLineRequiresCitation(): void
    {
        $bad = LabResultLine::validated([
            'test_name' => 'Glucose',
            'value' => '100',
            'unit' => 'mg/dL',
            'reference_range' => '70-99',
            'collection_date' => '2026-01-01',
            'abnormal_flag' => false,
        ]);
        $this->assertNull($bad);
    }

    public function testLabResultLineValid(): void
    {
        $ok = LabResultLine::validated([
            'test_name' => 'Glucose',
            'value' => '100',
            'unit' => 'mg/dL',
            'reference_range' => '70-99',
            'collection_date' => '2026-01-01',
            'abnormal_flag' => false,
            'citation' => [
                'source_type' => 'lab_pdf',
                'source_id' => 'doc1',
                'page_or_section' => '1',
                'field_or_chunk_id' => 'glucose',
                'quote_or_value' => 'Glucose 100',
            ],
        ]);
        $this->assertIsArray($ok);
        $this->assertSame('Glucose', $ok['test_name'] ?? null);
    }

    public function testIntakeFormValid(): void
    {
        $ok = IntakeFormRecord::validated([
            'demographics' => ['age_bracket' => '40-55'],
            'chief_concern' => 'Blood pressure check',
            'current_medications' => ['Med A'],
            'allergies' => ['NKDA'],
            'family_history' => [],
            'citation' => [
                'source_type' => 'intake_form',
                'source_id' => 'int1',
                'page_or_section' => 'p1',
                'field_or_chunk_id' => 'summary',
                'quote_or_value' => 'concern text',
            ],
        ]);
        $this->assertIsArray($ok);
        $this->assertSame('Blood pressure check', $ok['chief_concern'] ?? null);
    }
}
