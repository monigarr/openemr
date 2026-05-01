<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Allergies, medications, problems — no direct name/DOB in model payload.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

use OpenEMR\Modules\ClinicalCopilot\Services\ChartContextTool;

final class ChartListsTool implements ToolInterface
{
    public function __construct(private readonly ChartContextTool $inner)
    {
    }

    public function name(): string
    {
        return 'get_chart_lists';
    }

    public function openAiToolDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Load active allergy, medication, and problem list entries plus non-identifying demographics for the active patient.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function execute(int $pid): array
    {
        $raw = $this->inner->collectForPatient($pid);
        if (isset($raw['patient']) && is_array($raw['patient'])) {
            $p = $raw['patient'];
            unset($p['pid']);
            $raw['patient'] = array_filter(
                $p,
                static fn ($v) => $v !== null && $v !== ''
            );
        }
        return $raw;
    }
}
