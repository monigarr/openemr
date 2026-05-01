<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Human-readable, PII-safe labels for citation paths (no raw chart values in labels).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

final class CitationLabelBuilder
{
    /**
     * @param array<string,mixed> $mergedToolData
     * @return list<array{path:string,label:string}>
     */
    public function labelsForPaths(array $mergedToolData, array $paths): array
    {
        $out = [];
        foreach ($paths as $p) {
            if (!is_string($p) || $p === '') {
                continue;
            }
            $out[] = ['path' => $p, 'label' => $this->labelForPath($mergedToolData, $p)];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $mergedToolData
     */
    public function labelForPath(array $mergedToolData, string $path): string
    {
        $parts = explode('.', $path);
        $root = $parts[0] ?? '';
        if ($root === 'chart_lists') {
            return $this->labelChartLists($parts);
        }
        if ($root === 'recent_encounters') {
            return $this->labelEncounter($mergedToolData, $parts);
        }
        if ($root === 'recent_labs') {
            return $this->labelLab($mergedToolData, $parts);
        }
        return 'Chart reference';
    }

    /**
     * @param list<string> $parts
     */
    private function labelChartLists(array $parts): string
    {
        $seg = $parts[1] ?? '';
        return match ($seg) {
            'allergies' => 'Allergy list',
            'medications' => 'Medication list',
            'problems' => 'Problem list',
            'patient' => 'Demographics (non-identifying)',
            default => 'Chart list',
        };
    }

    /**
     * @param list<string> $parts
     */
    private function labelEncounter(array $mergedToolData, array $parts): string
    {
        $idx = null;
        if (isset($parts[2]) && ctype_digit($parts[2])) {
            $idx = (int) $parts[2];
        }
        $date = '';
        if ($idx !== null) {
            $enc = $mergedToolData['recent_encounters'] ?? null;
            $list = is_array($enc) && isset($enc['encounters']) && is_array($enc['encounters']) ? $enc['encounters'] : [];
            $row = $list[$idx] ?? null;
            if (is_array($row) && isset($row['date']) && is_string($row['date'])) {
                $date = $row['date'];
            }
        }
        if ($date !== '') {
            return 'Recent encounter (' . $this->truncateDateLabel($date) . ')';
        }
        return 'Recent encounter';
    }

    /**
     * @param list<string> $parts
     */
    private function labelLab(array $mergedToolData, array $parts): string
    {
        $idx = null;
        if (isset($parts[2]) && ctype_digit($parts[2])) {
            $idx = (int) $parts[2];
        }
        if ($idx !== null) {
            $labs = $mergedToolData['recent_labs'] ?? null;
            $list = is_array($labs) && isset($labs['labs']) && is_array($labs['labs']) ? $labs['labs'] : [];
            $row = $list[$idx] ?? null;
            if (is_array($row)) {
                $name = isset($row['procedure_name']) && is_string($row['procedure_name']) ? trim($row['procedure_name']) : '';
                if ($name !== '') {
                    return 'Lab / procedure result';
                }
            }
        }
        return 'Lab / procedure result';
    }

    private function truncateDateLabel(string $date): string
    {
        if (strlen($date) > 24) {
            return substr($date, 0, 24) . '…';
        }
        return $date;
    }
}
