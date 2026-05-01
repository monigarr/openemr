<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Bounded recent encounters for visit framing (UC1) and deltas (UC2).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

use OpenEMR\Services\EncounterService;

final class RecentEncountersTool implements ToolInterface
{
    private const MAX_ROWS = 10;

    public function name(): string
    {
        return 'get_recent_encounters';
    }

    public function openAiToolDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Load recent visit dates, categories, and documented visit reasons for the active patient (bounded list).',
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
        if ($pid < 1) {
            return ['encounters' => [], 'note' => 'invalid_pid'];
        }
        try {
            $es = new EncounterService();
            $rows = $es->getEncountersForPatientByPid($pid);
        } catch (\Throwable $e) {
            return ['encounters' => [], 'note' => 'tool_error'];
        }
        if (!is_array($rows)) {
            return ['encounters' => []];
        }
        usort($rows, static function ($a, $b): int {
            $da = strtotime((string) (is_array($a) ? ($a['date'] ?? '') : '')) ?: 0;
            $db = strtotime((string) (is_array($b) ? ($b['date'] ?? '') : '')) ?: 0;
            return $db <=> $da;
        });
        $rows = array_slice($rows, 0, self::MAX_ROWS);
        $out = [];
        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $out[] = [
                'date' => (string) ($r['date'] ?? ''),
                'reason' => (string) ($r['reason'] ?? ''),
                'visit_category' => (string) ($r['pc_catname'] ?? ''),
            ];
        }
        return ['encounters' => $out];
    }
}
