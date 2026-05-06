<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Strict lab row shape for document extraction (Week 2).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

final class LabResultLine
{
    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>|null
     */
    public static function validated(array $row): ?array
    {
        $name = isset($row['test_name']) && is_string($row['test_name']) ? trim($row['test_name']) : '';
        $value = isset($row['value']) && is_string($row['value']) ? trim($row['value']) : '';
        $unit = isset($row['unit']) && is_string($row['unit']) ? trim($row['unit']) : '';
        $ref = isset($row['reference_range']) && is_string($row['reference_range']) ? trim($row['reference_range']) : '';
        $date = isset($row['collection_date']) && is_string($row['collection_date']) ? trim($row['collection_date']) : '';
        $abnormal = $row['abnormal_flag'] ?? false;
        $abnormalFlag = is_bool($abnormal) ? $abnormal : false;
        if ($name === '' || $value === '') {
            return null;
        }
        $cite = ClinicalCitation::fromRow($row);
        if ($cite === null) {
            return null;
        }
        return [
            'test_name' => $name,
            'value' => $value,
            'unit' => $unit,
            'reference_range' => $ref,
            'collection_date' => $date,
            'abnormal_flag' => $abnormalFlag,
            'citation' => $cite,
        ];
    }
}
