<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Validates PRD Week 2 machine-readable citation metadata on extracted facts.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

final class ClinicalCitation
{
    /**
     * @param array<string,mixed> $row
     * @return array{source_type:string,source_id:string,page_or_section:string,field_or_chunk_id:string,quote_or_value:string,bbox_norm?:array{x:float,y:float,w:float,h:float}}|null
     */
    public static function fromRow(array $row): ?array
    {
        $c = $row['citation'] ?? null;
        if (!is_array($c)) {
            return null;
        }
        $st = isset($c['source_type']) && is_string($c['source_type']) ? trim($c['source_type']) : '';
        $sid = isset($c['source_id']) && is_string($c['source_id']) ? trim($c['source_id']) : '';
        $page = isset($c['page_or_section']) && is_string($c['page_or_section']) ? trim($c['page_or_section']) : '';
        $field = isset($c['field_or_chunk_id']) && is_string($c['field_or_chunk_id']) ? trim($c['field_or_chunk_id']) : '';
        $quote = isset($c['quote_or_value']) && is_string($c['quote_or_value']) ? trim($c['quote_or_value']) : '';
        if ($st === '' || $sid === '' || $page === '' || $field === '' || $quote === '') {
            return null;
        }
        $out = [
            'source_type' => $st,
            'source_id' => $sid,
            'page_or_section' => $page,
            'field_or_chunk_id' => $field,
            'quote_or_value' => $quote,
        ];
        $bbox = $c['bbox_norm'] ?? null;
        if (is_array($bbox)
            && isset($bbox['x'], $bbox['y'], $bbox['w'], $bbox['h'])
            && is_numeric($bbox['x'])
            && is_numeric($bbox['y'])
            && is_numeric($bbox['w'])
            && is_numeric($bbox['h'])
        ) {
            $out['bbox_norm'] = [
                'x' => (float) $bbox['x'],
                'y' => (float) $bbox['y'],
                'w' => (float) $bbox['w'],
                'h' => (float) $bbox['h'],
            ];
        }
        return $out;
    }
}
