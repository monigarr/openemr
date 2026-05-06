<?php

/**
 * @version 0.1.0
 * @date 2026-05-06
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Build a FHIR R4 DocumentReference draft from validated intake extraction (metadata + optional relatesTo; no Binary POST).
 *
 * Usage: Inject `FhirDocumentReferenceDraftBuilder` into `AttachAndExtractTool`; call `fromIntakeContext($pid, $validatedIntake, $chartDocumentId)` after successful intake schema validation.
 *
 * Example:
 *   $draft = (new FhirDocumentReferenceDraftBuilder())->fromIntakeContext(7, $validated, 555);
 *   // Returns array suitable for JSON encode; not persisted until approved.
 *
 * Dependencies: `IntakeFormRecord::validated` shape (chief_concern, citation.source_id).
 *
 * Security/PHI: Draft may echo chief concern text; do not log full draft in client-visible channels without review.
 * HIPAA: Minimum necessary; draft only until clinician approves persistence.
 * FHIR: R4 DocumentReference draft; interoperability N/A until posted with valid references.
 * Accessibility: N/A — non-UI.
 * Performance: O(1); no I/O.
 * Stability: Returns empty array if pid invalid; idempotent hash from inputs.
 * Legal/compliance: Third-party FHIR server posting requires BAA/consent as applicable; N/A for in-memory draft.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Fhir;

final class FhirDocumentReferenceDraftBuilder
{
    /**
     * @param array<string,mixed> $validatedIntake Output of `IntakeFormRecord::validated`
     * @return array<string,mixed>
     */
    public function fromIntakeContext(int $patientPid, array $validatedIntake, ?int $sourceDocumentId): array
    {
        if ($patientPid < 1) {
            return [];
        }
        $chief = isset($validatedIntake['chief_concern']) && is_string($validatedIntake['chief_concern'])
            ? $validatedIntake['chief_concern'] : '';
        $cite = $validatedIntake['citation'] ?? null;
        $sourceId = '';
        if (is_array($cite) && isset($cite['source_id']) && is_string($cite['source_id'])) {
            $sourceId = $cite['source_id'];
        }
        $hash = hash('sha256', $patientPid . '|intake|' . $chief . '|' . $sourceId . '|' . (string) ($sourceDocumentId ?? 0));
        $draft = [
            'resourceType' => 'DocumentReference',
            'id' => 'copilot-docref-' . $hash,
            'meta' => [
                'tag' => [[
                    'system' => 'https://openemr.org/clinical-copilot',
                    'code' => 'agentforge-intake-draft',
                    'display' => 'Intake extraction draft — validate before persisting',
                ]],
            ],
            'status' => 'current',
            'type' => [
                'text' => 'Patient intake form (extracted)',
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientPid,
            ],
            'description' => $chief !== '' ? $chief : 'Intake document extraction',
            'note' => [[
                'text' => 'Draft from Clinical Co-Pilot. Link Binary/content only after clinician approval.',
            ]],
        ];
        if ($sourceDocumentId !== null && $sourceDocumentId > 0) {
            $draft['relatesTo'] = [[
                'code' => 'appends',
                'target' => [
                    'reference' => 'DocumentReference/' . $sourceDocumentId,
                    'display' => 'Prior OpenEMR chart document id when upload persistence enabled',
                ],
            ]];
        }
        return $draft;
    }
}
