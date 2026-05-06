<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Process pending clinician upload into validated extraction rows (server-side only).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

use OpenEMR\Modules\ClinicalCopilot\Services\Document\DocumentExtractionPipelineInterface;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\DocumentExtractionsStoreInterface;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\IntakeFormRecord;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\LabResultLine;
use OpenEMR\Modules\ClinicalCopilot\Services\Fhir\FhirDocumentReferenceDraftBuilder;
use OpenEMR\Modules\ClinicalCopilot\Services\Fhir\FhirObservationDraftBuilder;

final class AttachAndExtractTool implements ToolInterface
{
    public function __construct(
        private readonly DocumentExtractionsStoreInterface $store,
        private readonly DocumentExtractionPipelineInterface $pipeline,
        private readonly FhirObservationDraftBuilder $fhirObservationDraftBuilder,
        private readonly FhirDocumentReferenceDraftBuilder $fhirDocumentReferenceDraftBuilder,
    ) {
    }

    public function name(): string
    {
        return 'attach_and_extract';
    }

    public function openAiToolDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Process the clinician-uploaded document for the active patient session. Requires a prior UI upload matching doc_type. Idempotent per pending upload.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'doc_type' => [
                            'type' => 'string',
                            'enum' => ['lab_pdf', 'intake_form'],
                        ],
                    ],
                    'required' => ['doc_type'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function execute(int $pid, ?string $argumentsJson = null): array
    {
        if ($pid < 1) {
            return ['status' => 'error', 'error' => 'invalid_pid'];
        }
        $docType = '';
        if (is_string($argumentsJson) && $argumentsJson !== '') {
            $decoded = json_decode($argumentsJson, true);
            if (is_array($decoded) && isset($decoded['doc_type']) && is_string($decoded['doc_type'])) {
                $docType = trim($decoded['doc_type']);
            }
        }
        if ($docType !== 'lab_pdf' && $docType !== 'intake_form') {
            return ['status' => 'error', 'error' => 'missing_or_invalid_doc_type'];
        }
        $pending = $this->store->getPending($pid);
        if ($pending === null) {
            return ['status' => 'error', 'error' => 'no_pending_upload', 'hint' => 'upload_via_ui_first'];
        }
        if (($pending['doc_type'] ?? '') !== $docType) {
            return ['status' => 'error', 'error' => 'doc_type_mismatch'];
        }
        $result = $this->pipeline->process($pid, $pending);
        $path = $pending['path'] ?? '';
        if (is_string($path) && $path !== '' && is_file($path)) {
            @unlink($path);
        }
        $this->store->clearPending($pid);
        if (!($result['ok'] ?? false)) {
            return [
                'status' => 'error',
                'error' => isset($result['error']) && is_string($result['error']) ? $result['error'] : 'extract_failed',
            ];
        }
        if ($docType === 'lab_pdf') {
            $rows = $result['lab_rows'] ?? [];
            if (!is_array($rows)) {
                return ['status' => 'error', 'error' => 'invalid_lab_payload'];
            }
            $validated = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $v = LabResultLine::validated($row);
                if ($v !== null) {
                    $validated[] = $v;
                }
            }
            if ($validated === []) {
                return ['status' => 'error', 'error' => 'schema_validation_failed'];
            }
            $this->store->appendLabs($pid, $validated);
            $chartDocId = $this->store->getLastChartDocumentId($pid);
            return [
                'status' => 'ok',
                'doc_type' => $docType,
                'rows_appended' => count($validated),
                'chart_document_id' => $chartDocId,
                'fhir_observation_drafts' => $this->fhirObservationDraftBuilder->labRowsToObservationDrafts($pid, $validated, $chartDocId),
            ];
        }
        $intake = $result['intake'] ?? null;
        if (!is_array($intake)) {
            return ['status' => 'error', 'error' => 'invalid_intake_payload'];
        }
        $v = IntakeFormRecord::validated($intake);
        if ($v === null) {
            return ['status' => 'error', 'error' => 'schema_validation_failed'];
        }
        $this->store->appendIntake($pid, $v);
        $chartDocId = $this->store->getLastChartDocumentId($pid);
        return [
            'status' => 'ok',
            'doc_type' => $docType,
            'rows_appended' => 1,
            'chart_document_id' => $chartDocId,
            'fhir_document_reference_draft' => $this->fhirDocumentReferenceDraftBuilder->fromIntakeContext(
                $pid,
                $v,
                $chartDocId,
            ),
        ];
    }
}
