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
        if ($root === 'document_extractions') {
            return $this->labelDocumentExtractions($parts);
        }
        if ($root === 'guideline_evidence') {
            return $this->labelGuidelineEvidence($mergedToolData, $parts);
        }
        return 'Chart reference';
    }

    /**
     * @param list<string> $parts
     */
    private function labelDocumentExtractions(array $parts): string
    {
        $seg = $parts[1] ?? '';
        return match ($seg) {
            'labs' => 'Uploaded lab document (extracted)',
            'intakes' => 'Uploaded intake form (extracted)',
            'provenance' => 'Uploaded document (OpenEMR chart storage id)',
            default => 'Uploaded document extraction',
        };
    }

    /**
     * @param list<string> $parts
     */
    private function labelGuidelineEvidence(array $mergedToolData, array $parts): string
    {
        if (($parts[1] ?? '') === 'chunks' && isset($parts[2]) && ctype_digit($parts[2])) {
            $idx = (int) $parts[2];
            $ge = $mergedToolData['guideline_evidence'] ?? null;
            $chunks = is_array($ge) && isset($ge['chunks']) && is_array($ge['chunks']) ? $ge['chunks'] : [];
            $row = $chunks[$idx] ?? null;
            if (is_array($row)) {
                $title = isset($row['title']) && is_string($row['title']) ? $row['title'] : '';
                if ($title !== '') {
                    return 'Guideline excerpt: ' . $this->truncateDateLabel($title);
                }
            }
        }
        return 'Guideline excerpt';
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
