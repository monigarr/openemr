<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Bounded recent procedure/lab rows for UC2 (honest empty when none).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

use OpenEMR\Common\Acl\AclMain;

final class RecentLabsTool implements ToolInterface
{
    private const MAX_ROWS = 20;

    public function name(): string
    {
        return 'get_recent_labs';
    }

    public function openAiToolDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Load recent laboratory/procedure results (bounded) for the active patient when present in OpenEMR procedure tables.',
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
            return ['labs' => [], 'note' => 'invalid_pid'];
        }
        if (!AclMain::aclCheckCore('patients', 'lab')) {
            return ['labs' => [], 'note' => 'insufficient_acl'];
        }
        try {
            $sql = "SELECT pr.result_text, pr.result, pr.units, pr.range, pr.abnormal, preport.date_report
                FROM procedure_result AS pr
                INNER JOIN procedure_report AS preport ON pr.procedure_report_id = preport.procedure_report_id
                INNER JOIN procedure_order AS po ON preport.procedure_order_id = po.procedure_order_id
                WHERE po.patient_id = ?
                ORDER BY preport.date_report DESC, pr.procedure_result_id DESC
                LIMIT " . escape_limit(self::MAX_ROWS);
            $res = sqlStatement($sql, [$pid]);
        } catch (\Throwable $e) {
            return ['labs' => [], 'note' => 'tool_error'];
        }
        $out = [];
        while ($row = sqlFetchArray($res)) {
            $out[] = [
                'reported_at' => (string) ($row['date_report'] ?? ''),
                'result_text' => trim((string) ($row['result_text'] ?? '')),
                'result' => trim((string) ($row['result'] ?? '')),
                'units' => trim((string) ($row['units'] ?? '')),
                'reference_range' => trim((string) ($row['range'] ?? '')),
                'abnormal_flag' => trim((string) ($row['abnormal'] ?? '')),
            ];
        }
        if ($out === []) {
            return ['labs' => [], 'note' => 'no_rows'];
        }
        return ['labs' => $out];
    }
}
