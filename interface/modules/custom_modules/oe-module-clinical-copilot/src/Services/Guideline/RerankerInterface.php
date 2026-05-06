<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Rerank retrieved guideline chunks (Cohere or pass-through).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Guideline;

interface RerankerInterface
{
    /**
     * @param list<array<string,mixed>> $chunks
     * @return list<array<string,mixed>>
     */
    public function rerank(string $query, array $chunks): array;
}
