<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Build FHIR R4 Observation **draft** JSON from validated lab extraction rows (no server POST; audit / interoperability prep).
 *
 * Usage: Called after successful `attach_and_extract` for lab_pdf; institution may persist via FHIR API separately.
 *
 * FHIR: R4 Observation; US Core profiling left to integrator.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Fhir;

final class FhirObservationDraftBuilder
{
    /**
     * @param list<array<string,mixed>> $validatedLabRows Output of `LabResultLine::validated` rows
     * @return list<array<string,mixed>>
     */
    public function labRowsToObservationDrafts(int $patientPid, array $validatedLabRows, ?int $sourceDocumentId): array
    {
        if ($patientPid < 1) {
            return [];
        }
        $out = [];
        foreach ($validatedLabRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = isset($row['test_name']) && is_string($row['test_name']) ? $row['test_name'] : '';
            $value = isset($row['value']) && is_string($row['value']) ? $row['value'] : '';
            if ($name === '' || $value === '') {
                continue;
            }
            $unit = isset($row['unit']) && is_string($row['unit']) ? $row['unit'] : '';
            $ref = isset($row['reference_range']) && is_string($row['reference_range']) ? $row['reference_range'] : '';
            $when = isset($row['collection_date']) && is_string($row['collection_date']) ? $row['collection_date'] : '';
            $cite = $row['citation'] ?? null;
            $sourceId = '';
            if (is_array($cite) && isset($cite['source_id']) && is_string($cite['source_id'])) {
                $sourceId = $cite['source_id'];
            }
            $id = 'copilot-draft-' . hash('sha256', $patientPid . '|' . $name . '|' . $value . '|' . $sourceId);
            $draft = [
                'resourceType' => 'Observation',
                'id' => $id,
                'meta' => [
                    'tag' => [[
                        'system' => 'https://openemr.org/clinical-copilot',
                        'code' => 'agentforge-draft',
                        'display' => 'Unverified extraction draft — clinician validation required',
                    ]],
                ],
                'status' => 'preliminary',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'laboratory',
                        'display' => 'Laboratory',
                    ]],
                ]],
                'code' => [
                    'text' => $name,
                ],
                'subject' => [
                    'reference' => 'Patient/' . $patientPid,
                ],
                'note' => [[
                    'text' => 'Derived from Clinical Co-Pilot document extraction. Source citation: ' . $sourceId,
                ]],
            ];
            if ($when !== '') {
                $draft['effectiveDateTime'] = $when;
            }
            if (is_numeric($value)) {
                $draft['valueQuantity'] = [
                    'value' => (float) $value,
                    'unit' => $unit,
                ];
            } else {
                $draft['valueString'] = $value;
            }
            if ($ref !== '') {
                $draft['referenceRange'] = [['text' => $ref]];
            }
            if ($sourceDocumentId !== null && $sourceDocumentId > 0) {
                $draft['derivedFrom'] = [[
                    'reference' => 'DocumentReference/' . $sourceDocumentId,
                    'display' => 'OpenEMR documents.id',
                ]];
            }
            $out[] = $draft;
        }
        return $out;
    }
}
