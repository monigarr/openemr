<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Strict intake form shape for document extraction (Week 2).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

final class IntakeFormRecord
{
    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>|null
     */
    public static function validated(array $row): ?array
    {
        $demo = $row['demographics'] ?? null;
        if (!is_array($demo)) {
            return null;
        }
        $chief = isset($row['chief_concern']) && is_string($row['chief_concern']) ? trim($row['chief_concern']) : '';
        $meds = $row['current_medications'] ?? [];
        $allergies = $row['allergies'] ?? [];
        $fh = $row['family_history'] ?? [];
        if (!is_array($meds) || !is_array($allergies) || !is_array($fh)) {
            return null;
        }
        $cite = ClinicalCitation::fromRow($row);
        if ($cite === null) {
            return null;
        }
        if ($chief === '') {
            return null;
        }
        return [
            'demographics' => self::stringMap($demo),
            'chief_concern' => $chief,
            'current_medications' => self::stringList($meds),
            'allergies' => self::stringList($allergies),
            'family_history' => self::stringList($fh),
            'citation' => $cite,
        ];
    }

    /**
     * @param array<mixed,mixed> $in
     * @return array<string,string>
     */
    private static function stringMap(array $in): array
    {
        $out = [];
        foreach ($in as $k => $v) {
            if (is_string($k) && is_string($v) && $k !== '' && $v !== '') {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    /**
     * @param array<mixed,mixed> $in
     * @return list<string>
     */
    private static function stringList(array $in): array
    {
        $out = [];
        foreach ($in as $v) {
            if (is_string($v) && $v !== '') {
                $out[] = $v;
            }
        }
        return $out;
    }
}
