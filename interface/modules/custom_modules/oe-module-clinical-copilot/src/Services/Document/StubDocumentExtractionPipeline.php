<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Demo extraction pipeline — validates temp file exists and emits schema-valid synthetic rows (replace with VLM).
 *
 * Security/PHI: Operates on clinician-uploaded temp paths; delete temp files after processing.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

final class StubDocumentExtractionPipeline implements DocumentExtractionPipelineInterface
{
    /**
     * @param array{doc_type:string,path:string,original_filename:string} $pending
     * @return array{ok:bool,error?:string,lab_rows?:list<array<string,mixed>>,intake?:array<string,mixed>}
     */
    public function process(int $pid, array $pending): array
    {
        if ($pid < 1) {
            return ['ok' => false, 'error' => 'invalid_pid'];
        }
        $path = $pending['path'] ?? '';
        if (!is_string($path) || $path === '' || !is_file($path)) {
            return ['ok' => false, 'error' => 'missing_temp_file'];
        }
        $docType = $pending['doc_type'] ?? '';
        if (!is_string($docType)) {
            return ['ok' => false, 'error' => 'invalid_doc_type'];
        }
        $name = $pending['original_filename'] ?? 'upload.bin';
        $name = is_string($name) ? $name : 'upload.bin';
        $sourceId = 'upload:' . hash('sha256', $name . "\0" . (string) filesize($path));

        return match ($docType) {
            'lab_pdf' => [
                'ok' => true,
                'lab_rows' => [
                    self::demoLabRow('Glucose', '99', 'mg/dL', '65-99', '2026-05-01', false, $sourceId, 'lab_pdf'),
                    self::demoLabRow('HbA1c', '5.6', '%', '<5.7', '2026-05-01', false, $sourceId, 'lab_pdf'),
                ],
            ],
            'intake_form' => [
                'ok' => true,
                'intake' => [
                    'demographics' => [
                        'age_bracket' => '40-55',
                        'sex_at_birth' => 'unspecified',
                    ],
                    'chief_concern' => 'Follow-up for blood pressure and recent lab review.',
                    'current_medications' => ['Lisinopril 10mg daily'],
                    'allergies' => ['NKDA'],
                    'family_history' => ['Type 2 diabetes in parent'],
                    'citation' => [
                        'source_type' => 'intake_form',
                        'source_id' => $sourceId,
                        'page_or_section' => 'page_1',
                        'field_or_chunk_id' => 'intake_summary',
                        'quote_or_value' => 'Structured intake (demo extraction).',
                        'bbox_norm' => ['x' => 0.08, 'y' => 0.12, 'w' => 0.84, 'h' => 0.22],
                    ],
                ],
            ],
            default => ['ok' => false, 'error' => 'unsupported_doc_type'],
        };
    }

    /**
     * @return array<string,mixed>
     */
    private static function demoLabRow(
        string $testName,
        string $value,
        string $unit,
        string $ref,
        string $collected,
        bool $abnormal,
        string $sourceId,
        string $sourceType,
    ): array {
        return [
            'test_name' => $testName,
            'value' => $value,
            'unit' => $unit,
            'reference_range' => $ref,
            'collection_date' => $collected,
            'abnormal_flag' => $abnormal,
            'citation' => [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'page_or_section' => 'page_1',
                'field_or_chunk_id' => strtolower(str_replace(' ', '_', $testName)),
                'quote_or_value' => $testName . ' ' . $value . ' ' . $unit,
                'bbox_norm' => ['x' => 0.1, 'y' => 0.2, 'w' => 0.35, 'h' => 0.04],
            ],
        ];
    }
}
