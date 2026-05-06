<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Persists Week 2 document extraction state in the OpenEMR session (per pid key).
 *
 * Usage: Construct with active session wrapper from `SessionWrapperFactory`.
 *
 * Security/PHI: Session data must not be exported to client JSON; keep server-side only.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionDocumentExtractionsStore implements DocumentExtractionsStoreInterface
{
    private const SESSION_KEY = 'clinical_copilot_w2_doc_state';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function getMergedPayload(int $pid): array
    {
        $s = $this->bucket()[$pid] ?? ['labs' => [], 'intakes' => [], 'pending' => null];
        $out = [
            'labs' => is_array($s['labs'] ?? null) ? $s['labs'] : [],
            'intakes' => is_array($s['intakes'] ?? null) ? $s['intakes'] : [],
        ];
        if (is_array($s['pending'] ?? null)) {
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
        $all = $this->bucket();
        $cur = $all[$pid] ?? ['labs' => [], 'intakes' => [], 'pending' => null, 'last_chart_document_id' => null];
        if (!is_array($cur['labs'] ?? null)) {
            $cur['labs'] = [];
        }
        if (!is_array($cur['intakes'] ?? null)) {
            $cur['intakes'] = [];
        }
        $cur['pending'] = $pending;
        $all[$pid] = $cur;
        $this->saveBucket($all);
    }

    public function clearPending(int $pid): void
    {
        $all = $this->bucket();
        if (!isset($all[$pid])) {
            return;
        }
        $all[$pid]['pending'] = null;
        $this->saveBucket($all);
    }

    public function getPending(int $pid): ?array
    {
        $p = $this->bucket()[$pid]['pending'] ?? null;
        return is_array($p) ? $p : null;
    }

    public function appendLabs(int $pid, array $rows): void
    {
        $all = $this->bucket();
        $cur = $all[$pid] ?? ['labs' => [], 'intakes' => [], 'pending' => null, 'last_chart_document_id' => null];
        if (!is_array($cur['labs'] ?? null)) {
            $cur['labs'] = [];
        }
        if (!is_array($cur['intakes'] ?? null)) {
            $cur['intakes'] = [];
        }
        foreach ($rows as $row) {
            if (is_array($row)) {
                $cur['labs'][] = $row;
            }
        }
        $all[$pid] = $cur;
        $this->saveBucket($all);
    }

    public function appendIntake(int $pid, array $intake): void
    {
        $all = $this->bucket();
        $cur = $all[$pid] ?? ['labs' => [], 'intakes' => [], 'pending' => null, 'last_chart_document_id' => null];
        if (!is_array($cur['labs'] ?? null)) {
            $cur['labs'] = [];
        }
        if (!is_array($cur['intakes'] ?? null)) {
            $cur['intakes'] = [];
        }
        $cur['intakes'][] = $intake;
        $all[$pid] = $cur;
        $this->saveBucket($all);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bucket(): array
    {
        $raw = $this->session->get(self::SESSION_KEY);
        return is_array($raw) ? $raw : [];
    }

    /**
     * @param array<int, array<string, mixed>> $all
     */
    private function saveBucket(array $all): void
    {
        $this->session->set(self::SESSION_KEY, $all);
    }

    public function setLastChartDocumentId(int $pid, int $documentId): void
    {
        if ($documentId < 1) {
            return;
        }
        $all = $this->bucket();
        $cur = $all[$pid] ?? ['labs' => [], 'intakes' => [], 'pending' => null, 'last_chart_document_id' => null];
        if (!is_array($cur['labs'] ?? null)) {
            $cur['labs'] = [];
        }
        if (!is_array($cur['intakes'] ?? null)) {
            $cur['intakes'] = [];
        }
        $cur['last_chart_document_id'] = $documentId;
        $all[$pid] = $cur;
        $this->saveBucket($all);
    }

    public function getLastChartDocumentId(int $pid): ?int
    {
        $raw = $this->bucket()[$pid]['last_chart_document_id'] ?? null;
        return is_int($raw) && $raw > 0 ? $raw : null;
    }
}
