<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Session- or test-backed store for Week 2 pending uploads and validated extraction rows.
 *
 * Usage: Injected into `DocumentExtractionsTool` and `AttachAndExtractTool`; production uses session implementation.
 *
 * Security/PHI: Holds references to temp files and extracted fields — do not log store contents to third parties.
 * HIPAA: Minimum necessary; demo-oriented session scope.
 * FHIR: N/A — persistence mapping is pipeline-specific.
 * Accessibility: N/A — non-UI.
 * Performance: In-memory/session map per request.
 * Stability: Clear pending after successful extract or explicit clear.
 * Legal/compliance: GPLv3; demo corpus only unless institution configures real storage.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

interface DocumentExtractionsStoreInterface
{
    /**
     * Payload merged under `document_extractions` for verification dot-paths.
     *
     * @return array{labs:list<array<string,mixed>>,intakes:list<array<string,mixed>>,pending_status?:string,provenance?:array{chart_document_id?:int|null}}
     */
    public function getMergedPayload(int $pid): array;

    /**
     * @param array{doc_type:string,path:string,original_filename:string} $pending
     */
    public function setPending(int $pid, array $pending): void;

    public function clearPending(int $pid): void;

    /**
     * @return ?array{doc_type:string,path:string,original_filename:string}
     */
    public function getPending(int $pid): ?array;

    /**
     * @param list<array<string,mixed>> $rows
     */
    public function appendLabs(int $pid, array $rows): void;

    /**
     * @param array<string,mixed> $intake
     */
    public function appendIntake(int $pid, array $intake): void;

    /**
     * OpenEMR `documents` id for the last copilot PDF upload when persistence is enabled (optional provenance).
     */
    public function setLastChartDocumentId(int $pid, int $documentId): void;

    public function getLastChartDocumentId(int $pid): ?int;
}
