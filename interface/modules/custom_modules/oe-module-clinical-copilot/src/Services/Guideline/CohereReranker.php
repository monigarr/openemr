<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Optional Cohere rerank v2 for guideline snippets.
 *
 * Security: API key from environment only; no PHI in query/documents (guideline text only).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Guideline;

final class CohereReranker implements RerankerInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'rerank-english-v3.0',
    ) {
    }

    public function rerank(string $query, array $chunks): array
    {
        if ($chunks === [] || $this->apiKey === '') {
            return $chunks;
        }
        $documents = [];
        foreach ($chunks as $c) {
            $t = isset($c['text']) && is_string($c['text']) ? $c['text'] : '';
            $documents[] = $t;
        }
        $payload = json_encode([
            'model' => $this->model,
            'query' => $query,
            'documents' => $documents,
            'top_n' => min(count($documents), 10),
        ], JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return $chunks;
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$this->apiKey}\r\nContent-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 15,
            ],
        ]);
        $resp = @file_get_contents('https://api.cohere.com/v1/rerank', false, $ctx);
        if (!is_string($resp) || $resp === '') {
            return $chunks;
        }
        $decoded = json_decode($resp, true);
        if (!is_array($decoded) || !isset($decoded['results']) || !is_array($decoded['results'])) {
            return $chunks;
        }
        $ordered = [];
        foreach ($decoded['results'] as $row) {
            if (!is_array($row) || !isset($row['index']) || !is_numeric($row['index'])) {
                continue;
            }
            $idx = (int) $row['index'];
            if (isset($chunks[$idx])) {
                $ordered[] = $chunks[$idx];
            }
        }
        return $ordered !== [] ? $ordered : $chunks;
    }
}
