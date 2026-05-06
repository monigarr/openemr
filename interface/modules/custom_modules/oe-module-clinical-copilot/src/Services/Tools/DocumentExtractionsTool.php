<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Expose validated document extraction rows for citation paths under `document_extractions.*`.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

use OpenEMR\Modules\ClinicalCopilot\Services\Document\DocumentExtractionsStoreInterface;

final class DocumentExtractionsTool implements ToolInterface
{
    public function __construct(private readonly DocumentExtractionsStoreInterface $store)
    {
    }

    public function name(): string
    {
        return 'get_document_extractions';
    }

    public function openAiToolDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Load structured facts extracted from clinician-uploaded lab PDFs and intake forms for the active patient (session-scoped). Use citations under document_extractions.labs.* and document_extractions.intakes.*.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function execute(int $pid, ?string $argumentsJson = null): array
    {
        if ($pid < 1) {
            return ['labs' => [], 'intakes' => [], 'note' => 'invalid_pid'];
        }
        return $this->store->getMergedPayload($pid);
    }
}
