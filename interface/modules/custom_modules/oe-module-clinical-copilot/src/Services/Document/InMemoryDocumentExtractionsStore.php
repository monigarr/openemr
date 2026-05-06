<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: In-memory document extraction store for isolated tests and local experiments.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

final class InMemoryDocumentExtractionsStore implements DocumentExtractionsStoreInterface
{
    /** @var array<int, array{labs:list<array<string,mixed>>,intakes:list<array<string,mixed>>,pending:?array<string,mixed>,last_chart_document_id:?int}> */
    private array $state = [];

    public function getMergedPayload(int $pid): array
    {
        $s = $this->state[$pid] ?? ['labs' => [], 'intakes' => [], 'pending' => null, 'last_chart_document_id' => null];
        $out = [
            'labs' => $s['labs'],
            'intakes' => $s['intakes'],
        ];
        if ($s['pending'] !== null) {
            $out['pending_status'] = 'upload_ready';
        }
        $docId = $s['last_chart_document_id'] ?? null;
        if (is_int($docId) && $docId > 0) {
            $out['provenance'] = ['chart_document_id' => $docId];
        }
        return $out;
    }

    public function setPending(int $pid, array $pending): void
    {
        $this->ensure($pid);
        $this->state[$pid]['pending'] = $pending;
    }

    public function clearPending(int $pid): void
    {
        if (isset($this->state[$pid])) {
            $this->state[$pid]['pending'] = null;
        }
    }

    public function getPending(int $pid): ?array
    {
        $p = $this->state[$pid]['pending'] ?? null;
        return is_array($p) ? $p : null;
    }

    public function appendLabs(int $pid, array $rows): void
    {
        $this->ensure($pid);
        foreach ($rows as $row) {
            if (is_array($row)) {
                $this->state[$pid]['labs'][] = $row;
            }
        }
    }

    public function appendIntake(int $pid, array $intake): void
    {
        $this->ensure($pid);
        $this->state[$pid]['intakes'][] = $intake;
    }

    public function setLastChartDocumentId(int $pid, int $documentId): void
    {
        if ($documentId < 1) {
            return;
        }
        $this->ensure($pid);
        $this->state[$pid]['last_chart_document_id'] = $documentId;
    }

    public function getLastChartDocumentId(int $pid): ?int
    {
        $raw = $this->state[$pid]['last_chart_document_id'] ?? null;
        return is_int($raw) && $raw > 0 ? $raw : null;
    }

    private function ensure(int $pid): void
    {
        if (!isset($this->state[$pid])) {
            $this->state[$pid] = ['labs' => [], 'intakes' => [], 'pending' => null, 'last_chart_document_id' => null];
        }
    }
}
