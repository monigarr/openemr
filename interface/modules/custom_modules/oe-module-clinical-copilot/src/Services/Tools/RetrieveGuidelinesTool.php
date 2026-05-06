<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Parametric hybrid retrieval + rerank over bundled guideline corpus (Week 2).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\HybridGuidelineRetriever;

final class RetrieveGuidelinesTool implements ToolInterface
{
    public function __construct(private readonly HybridGuidelineRetriever $retriever)
    {
    }

    public function name(): string
    {
        return 'retrieve_guidelines';
    }

    public function openAiToolDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Retrieve top clinical guideline excerpts relevant to the clinician question (hybrid: lexical sparse + deterministic dense vectors, then reranked). Response includes chunks citeable as guideline_evidence.chunks.N.text.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Focused clinical question for guideline search (no patient identifiers).',
                        ],
                    ],
                    'required' => ['query'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function execute(int $pid, ?string $argumentsJson = null): array
    {
        if ($pid < 1) {
            return ['chunks' => [], 'query' => '', 'note' => 'invalid_pid'];
        }
        $query = '';
        if (is_string($argumentsJson) && $argumentsJson !== '') {
            $decoded = json_decode($argumentsJson, true);
            if (is_array($decoded) && isset($decoded['query']) && is_string($decoded['query'])) {
                $query = trim($decoded['query']);
            }
        }
        if ($query === '') {
            return ['chunks' => [], 'query' => '', 'note' => 'missing_query'];
        }
        return $this->retriever->retrieve($query);
    }
}
